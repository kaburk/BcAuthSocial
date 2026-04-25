# BcAuthSocial plugin for baserCMS

BcAuthSocial は、baserCMS 5 に Google や X などの外部認証を追加するためのプラグインです。

**Configure 駆動のプロバイダー管理** により、`config/setting.php` に1ブロック追加するだけで新しいプロバイダーを組み込めます。
`ProviderAdapterInterface` を公開しており、サードパーティが独立したアドオンプラグインとしてプロバイダーを追加することもできます。

## 目的

- 管理画面・フロントログインに外部 OAuth / OIDC 認証を追加する
- プロバイダーを `config/setting.php` の1ブロックで管理し、コード変更なしで追加・削除できるようにする
- BcAuthPasskey と同時利用できるようにする

## 同梱プロバイダー

| プロバイダー | 認証方式 | 備考 |
|---|---|---|
| Google | OIDC |  |
| X | OAuth 2.0 / PKCE |  |
| GitHub | OAuth 2.0 |  |
| LINE | OAuth 2.0 / OIDC |  |
| Yahoo! JAPAN | Authorization Code フロー |  |
| Microsoft | OIDC（common エンドポイント） |  |

## プロバイダーの追加方法

新しいプロバイダーを追加するには以下の手順を実行してください。
詳細は `config/setting.php` の冒頭コメントも参照してください。

1. **`config/setting.php`** に新しいプロバイダーブロックを追加する（`label` / `envPrefix` / `allowLinkCandidate` / `icon` / `guide.steps` を記述）
2. **`src/Adapter/{Name}ProviderAdapter.php`** を作成し `ProviderAdapterInterface` を実装する
3. **`src/BcAuthSocialPlugin.php`** の `bootstrap()` に `$registry->register()` を追加する
4. **`.env`** にクレデンシャルを設定する（管理画面「ソーシャル認証設定」からも設定可）

管理画面の設定フォーム・ログインボタン・連携アカウント一覧はすべて Configure から自動生成されるため、テンプレートの変更は不要です。


## 外部アドオンプラグインによる拡張

`ProviderAdapterRegistry` はシングルトンとして公開されており、独立したプラグインから登録できます。

```php
// plugins/BcAppleAuth/src/BcAppleAuthPlugin.php
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAppleAuth\Adapter\AppleProviderAdapter;

ProviderAdapterRegistry::getInstance()->register('apple', new AppleProviderAdapter());
```

### 将来のアドオンプラグイン候補

| プラグイン名 | プロバイダー | 備考 |
|---|---|---|
| BcAppleAuth | Sign in with Apple | JWT 署名が必要・メール取得は初回のみ |
| BcFacebookAuth | Facebook / Meta | ビジネス・EC サイト向け |
| BcDiscordAuth | Discord | コミュニティサイト向け |
| BcSlackAuth | Slack | 社内向けサイトでの認証基盤として利用可 |
| BcGitLabAuth | GitLab | 開発者ポータル・社内 GitLab 連携向け |

## 設定

設定は `config/setting.php` の env 読み出しで行います。
管理画面「ソーシャル認証設定」から `.env` への書き込みが可能です。
`.env` が書き込み不可の環境では、画面上で必要なキー名と Callback URL を案内します。

各プロバイダーの環境変数は `{envPrefix}_ENABLED` / `_CLIENT_ID` / `_CLIENT_SECRET` / `_REDIRECT_URI` の形式です。

| プロバイダー | envPrefix |
|---|---|
| Google | `BC_SOCIAL_AUTH_GOOGLE` |
| X | `BC_SOCIAL_AUTH_X` |
| GitHub | `BC_SOCIAL_AUTH_GITHUB` |
| LINE | `BC_SOCIAL_AUTH_LINE` |
| Yahoo! JAPAN | `BC_SOCIAL_AUTH_YAHOOJP` |
| Microsoft | `BC_SOCIAL_AUTH_MICROSOFT` |

`*_REDIRECT_URI` を未設定の場合は `/baser/admin/bc-auth-social/bc_auth/callback/{provider}` が自動生成されます。

## 公開プラグインとして配布する場合の注意

BcAuthSocial は、配布元が固定の OAuth クライアント情報を提供する設計ではありません。
公開プラグインとして運用する場合は、**利用者ごとに各自の OAuth アプリを作成し、Client ID / Client Secret を設定してもらう** 前提で案内してください。

- Client ID / Client Secret をプラグインに同梱しない
- `.env` または管理画面「ソーシャル認証設定」で利用者が設定する
- 開発環境・本番環境で OAuth クライアントを分ける

### Google で「組織内でのみ利用可能」と表示される場合

Google Cloud 側の OAuth 同意画面が `Internal` のときに発生します。

- その Google Workspace 組織アカウント以外はログインできません
- 不特定ユーザー向けに公開する場合は `External` を選択してください
- 公開前の検証中はテストユーザー登録が必要です

このエラーは BcAuthSocial の実装不具合ではなく、Google OAuth アプリ設定による制約です。

### Yahoo! JAPAN 登録時の補足

Yahoo! JAPAN の Client ID 登録画面では、少なくとも次を選択してください。

- `ID連携利用有無`: `ID連携を利用する`
- `アプリケーションの種類`: `サーバーサイド（Yahoo! ID連携 v2）`

`ID連携を利用しない` を選ぶと、Authorization Code フローや Callback URL 設定の前提を満たしません。

この実装では Yahoo! JAPAN の UserInfo API には依存せず、Token エンドポイントから返される ID Token を検証して `sub` を取得します。
そのため、Yahoo 側の属性取得 API 申請がない個人開発環境でもログイン自体は利用できます。

一方で、`email` や `profile` の claim が ID Token に含まれない場合があります。
その場合はメールアドレスベースの自動連携候補は表示されず、既存の `provider_user_id` 連携があるユーザーのみログインできます。

また、Client ID 登録直後の入力画面では Callback URL（リダイレクト URI）欄が見つからない場合があります。
その場合は、アプリ作成後の詳細画面・編集画面で ID連携設定を開き、Baser の Callback URL を完全一致で設定してください。

- Admin: `https://localhost/baser/admin/bc-auth-social/bc_auth/callback/yahoojp`
- Front: `https://localhost/bc-auth-social/bc_auth/callback/yahoojp`


## ライセンス

MIT License. 詳細は `LICENSE.txt` を参照してください。
