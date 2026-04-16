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
| Google | OIDC | email_verified 保証あり |
| X | OAuth 2.0 / PKCE | メールアドレス不安定・allowLinkCandidate=false |
| GitHub | OAuth 2.0 | メールは別 API から取得 |
| LINE | OAuth 2.0 / OIDC | メール取得は LINE 側の審査が必要 |
| Microsoft | OIDC（common エンドポイント） | 個人・組織アカウント両対応 |
| Yahoo! JAPAN | Authorization Code フロー | 検証済み ID Token から `sub` を取得。email / profile は claim がある場合のみ利用 |

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
| Microsoft | `BC_SOCIAL_AUTH_MICROSOFT` |
| Yahoo! JAPAN | `BC_SOCIAL_AUTH_YAHOOJP` |

`*_REDIRECT_URI` を未設定の場合は `/baser/admin/bc-auth-social/bc_auth/callback/{provider}` が自動生成されます。

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

## ドキュメント

詳細設計は [docs/social-auth-design.md](docs/social-auth-design.md) を参照してください。

横断整理は [../BcAuthCommon/docs/auth-plugin-spec-summary.md](../BcAuthCommon/docs/auth-plugin-spec-summary.md) を参照してください。

## 実装ステータス

フェーズ 1 実装済み（Admin / Front 両対応）。

### 作成済みファイル

| ファイル | 概要 |
|---|---|
| `config.php` | adminLink / installMessage 設定 |
| `config/routes.php` | Admin / Front ルート定義 |
| `config/setting.php` | プロバイダー単位の設定（label / envPrefix / allowLinkCandidate / icon / guide をプロバイダーごとに集約） |
| `config/Migrations/20260409000001_CreateBcAuthProviderLinks.php` | マイグレーション（テーブル名: `bc_auth_provider_links`） |
| `src/BcAuthSocialPlugin.php` | プラグインクラス（Adapter 登録・AuthEntryService エントリ登録） |
| `src/Adapter/ProviderAdapterInterface.php` | アダプタインターフェース（外部アドオン向け公開 API） |
| `src/Adapter/ProviderAdapterRegistry.php` | シングルトン registry |
| `src/Adapter/ProviderUserProfile.php` | プロフィール DTO |
| `src/Adapter/GoogleProviderAdapter.php` | Google OIDC アダプタ |
| `src/Adapter/XProviderAdapter.php` | X（OAuth 2.0 / PKCE）アダプタ |
| `src/Adapter/GitHubProviderAdapter.php` | GitHub（OAuth 2.0）アダプタ |
| `src/Adapter/LineProviderAdapter.php` | LINE（OAuth 2.0 / OIDC）アダプタ |
| `src/Adapter/MicrosoftProviderAdapter.php` | Microsoft（OAuth 2.0 / OIDC）アダプタ |
| `src/Adapter/YahooJpProviderAdapter.php` | Yahoo! JAPAN（Authorization Code フロー / ID Token 検証）アダプタ |
| `src/Model/Entity/BcAuthProviderLink.php` | エンティティ |
| `src/Model/Table/BcAuthProviderLinksTable.php` | テーブルクラス |
| `src/Model/Entity/BcAuthSocialConfig.php` | バーチャルエンティティ（env 読み書き） |
| `src/Model/Table/BcAuthSocialConfigsTable.php` | バーチャルテーブル（Configure からプロバイダー一覧を動的生成） |
| `src/Service/BcAuthSocialService.php` | 認可 URL 生成・callback 処理・ユーザーひも付け・連携候補フロー |
| `src/Service/BcAuthSocialConfigsService.php` | Configure 駆動の provider 設定読み書き・View 変数生成 |
| `src/Service/BcAuthSocialConfigsServiceInterface.php` | インターフェース |
| `src/ServiceProvider/BcAuthSocialServiceProvider.php` | DI 登録 |
| `src/Event/BcAuthSocialViewEventListener.php` | View イベントリスナー |
| `src/Controller/Admin/BcAuthController.php` | Admin: login / callback / linkCandidate / confirmLink / cancelLink |
| `src/Controller/Admin/BcAuthSocialAccountsController.php` | 連携済みアカウント管理（一覧・解除・追加連携導線） |
| `src/Controller/Admin/BcAuthSocialConfigsController.php` | provider 設定画面 |
| `src/Controller/BcAuthController.php` | Front: login / callback / linkCandidate / confirmLink / cancelLink |
| `templates/Admin/BcAuth/link_candidate.php` | Admin 連携候補確認画面 |
| `templates/Admin/BcAuthSocialAccounts/index.php` | 連携済みアカウント一覧 |
| `templates/Admin/BcAuthSocialConfigs/index.php` | provider 設定画面（Configure から動的フォーム生成） |
| `templates/BcAuth/link_candidate.php` | Front 連携候補確認画面 |
| `templates/element/social_login_buttons.php` | ログイン画面埋め込み用ソーシャルボタン element（icon は Configure から取得） |
| `templates/plugin/BcAdminThird/Admin/Users/login.php` | Admin ログイン画面 override（AuthEntryService でボタン群を描画） |
| `templates/plugin/BcFront/Users/login.php` | Front ログイン画面 override（AuthEntryService でボタン群を描画） |

## DB テーブル

| テーブル | 用途 |
|---|---|
| `bc_auth_provider_links` | baserCMS ユーザーと外部プロバイダーアカウントのひも付け |

## ルーティング

| URL | 用途 |
|---|---|
| `GET /baser/admin/bc-auth-social/bc_auth_social_configs` | provider 設定画面 |
| `GET /baser/admin/bc-auth-social/bc_auth_social_accounts` | 連携済みアカウント管理画面 |
| `GET /baser/admin/bc-auth-social/bc_auth/login/:provider` | Admin ソーシャルログイン開始 |
| `GET /baser/admin/bc-auth-social/bc_auth/callback/:provider` | Admin OAuth コールバック |
| `GET /baser/admin/bc-auth-social/bc_auth/link-candidate/:provider` | Admin 連携候補確認 |
| `POST /baser/admin/bc-auth-social/bc_auth/confirm-link/:provider` | Admin 連携確定 |
| `POST /baser/admin/bc-auth-social/bc_auth/cancel-link/:provider` | Admin 連携キャンセル |
| `GET /bc-auth-social/bc_auth/login/:provider` | Front ソーシャルログイン開始 |
| `GET /bc-auth-social/bc_auth/callback/:provider` | Front OAuth コールバック |
| `GET /bc-auth-social/bc_auth/link-candidate/:provider` | Front 連携候補確認 |
| `POST /bc-auth-social/bc_auth/confirm-link/:provider` | Front 連携確定 |
| `POST /bc-auth-social/bc_auth/cancel-link/:provider` | Front 連携キャンセル |

## 残タスク

- **Front 側デザイン調整**: 実運用テーマ確定後に `templates/plugin/BcFront/Users/login.php` の見た目を調整する（機能自体は実装済み）
| `src/Adapter/LineProviderAdapter.php` | LINE（OAuth 2.0 / OIDC）アダプタ |
| `src/Adapter/MicrosoftProviderAdapter.php` | Microsoft（OAuth 2.0 / OIDC）アダプタ |
| `src/Model/Entity/BcAuthProviderLink.php` | エンティティ |
| `src/Model/Table/BcAuthProviderLinksTable.php` | テーブルクラス |
| `src/Model/Entity/BcAuthSocialConfig.php` | バーチャルエンティティ（env 読み書き） |
| `src/Model/Table/BcAuthSocialConfigsTable.php` | バーチャルテーブル |
| `src/Service/BcAuthSocialService.php` | 認可 URL 生成・callback 処理・ユーザーひも付け・連携候補フロー |
| `src/Service/BcAuthSocialConfigsService.php` | provider 設定の読み書き（.env / 画面表示） |
| `src/Service/BcAuthSocialConfigsServiceInterface.php` | インターフェース |
| `src/ServiceProvider/BcAuthSocialServiceProvider.php` | DI 登録 |
| `src/Event/BcAuthSocialViewEventListener.php` | View イベントリスナー |
| `src/Controller/Admin/BcAuthController.php` | Admin: login / callback / linkCandidate / confirmLink / cancelLink |
| `src/Controller/Admin/BcAuthSocialAccountsController.php` | 連携済みアカウント管理（一覧・解除・追加連携導線） |
| `src/Controller/Admin/BcAuthSocialConfigsController.php` | provider 設定画面 |
| `src/Controller/BcAuthController.php` | Front: login / callback / linkCandidate / confirmLink / cancelLink |
| `templates/Admin/BcAuth/link_candidate.php` | Admin 連携候補確認画面 |
| `templates/Admin/BcAuthSocialAccounts/index.php` | 連携済みアカウント一覧 |
| `templates/Admin/BcAuthSocialConfigs/index.php` | provider 設定画面 |
| `templates/BcAuth/link_candidate.php` | Front 連携候補確認画面 |
| `templates/element/social_login_buttons.php` | ログイン画面埋め込み用ソーシャルボタン element |
| `templates/plugin/BcAdminThird/Admin/Users/login.php` | Admin ログイン画面 override（BcAuthPasskey 単独でも動作、AuthEntryService でボタン群を描画） |
| `templates/plugin/BcFront/Users/login.php` | Front ログイン画面 override（AuthEntryService でボタン群を描画） |

## DB テーブル

| テーブル | 用途 |
|---|---|
| `bc_auth_provider_links` | baserCMS ユーザーと外部プロバイダアカウントのひも付け |

## ルーティング

| URL | 用途 |
|---|---|
| `GET /baser/admin/bc-auth-social/social_auth_configs` | provider 設定画面 |
| `GET /baser/admin/bc-auth-social/social_auth_accounts` | 連携済みアカウント管理画面 |
| `GET /baser/admin/bc-auth-social/bc_auth/login/:provider` | Admin ソーシャルログイン開始 |
| `GET /baser/admin/bc-auth-social/bc_auth/callback/:provider` | Admin OAuth コールバック |
| `GET /baser/admin/bc-auth-social/bc_auth/link-candidate/:provider` | Admin 連携候補確認 |
| `POST /baser/admin/bc-auth-social/bc_auth/confirm-link/:provider` | Admin 連携確定 |
| `POST /baser/admin/bc-auth-social/bc_auth/cancel-link/:provider` | Admin 連携キャンセル |
| `GET /bc-auth-social/bc_auth/login/:provider` | Front ソーシャルログイン開始 |
| `GET /bc-auth-social/bc_auth/callback/:provider` | Front OAuth コールバック |
| `GET /bc-auth-social/bc_auth/link-candidate/:provider` | Front 連携候補確認 |
| `POST /bc-auth-social/bc_auth/confirm-link/:provider` | Front 連携確定 |
| `POST /bc-auth-social/bc_auth/cancel-link/:provider` | Front 連携キャンセル |

## 残タスク

- **Front 側デザイン調整**: 実運用テーマ確定後に `templates/plugin/BcFront/Users/login.php` の見た目を調整する（機能自体は実装済み）
