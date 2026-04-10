# BcSocialAuth plugin for baserCMS

BcSocialAuth は、baserCMS 5 に Google や X などの外部認証を追加するためのプラグインです。

旧 BcGoogleLogin の用途を baserCMS 5 向けに再整理しつつ、**基盤プラグイン＋外部アドオン登録方式** により将来のプロバイダ拡張に対応します。

## 目的

- 管理画面ログインに Google や X などの外部認証を追加する
- `ProviderAdapterInterface` を公開し、サードパーティが任意のプロバイダを追加できる構成にする
- BcPasskeyAuth と同時利用できるようにする

## 初期スコープ（同梱プロバイダ）

- Google（OIDC）
- X（OAuth 2.0 / PKCE）
- 管理画面ログイン
- 既存ユーザーとの安全なひも付け

## アドオンによる拡張

BcSocialAuth をインストールすると `ProviderAdapterRegistry` がシングルトンとして利用可能になります。
追加プロバイダは独立したプラグインの `bootstrap.php` で登録するだけで組み込めます。

```php
// plugins/BcLineAuth/config/bootstrap.php
use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcLineAuth\Adapter\LineProviderAdapter;

ProviderAdapterRegistry::getInstance()->register(new LineProviderAdapter());
```

### 将来のアドオンプラグイン候補

| プラグイン名 | プロバイダ |
| --- | --- |
| BcLineAuth | LINE Login |
| BcAppleAuth | Sign in with Apple |
| BcGitHubAuth | GitHub |
| BcMicrosoftAuth | Microsoft / Azure AD |

## 方針

- メールアドレス一致だけに依存しない（`provider_user_id` を主体）
- X のようにメールが取れないプロバイダでも成立する設計
- ログイン完了処理は BcAuthCommon へ切り出しやすくする
- 初期の画面組み込みは template override を基本とする

## ドキュメント

詳細設計は [docs/social-auth-design.md](docs/social-auth-design.md) を参照してください。

横断整理は ../BcAuthCommon/docs/auth-plugin-spec-summary.md を参照してください。

## 設定

設定は [plugins/BcSocialAuth/config/setting.php](plugins/BcSocialAuth/config/setting.php) の env 読み出しで行います。

初回導入時は、プラグインの install により migration を自動実行する前提とし、その後に provider 設定を行います。

管理画面から `.env` に設定を書き込める provider 設定画面を実装済みです。
`.env` が書き込み不可の環境では、画面上で必要なキー名と callback URL を案内し、手作業設定へフォールバックします。

主な環境変数:

- `BC_SOCIAL_AUTH_GOOGLE_ENABLED`
- `BC_SOCIAL_AUTH_GOOGLE_CLIENT_ID`
- `BC_SOCIAL_AUTH_GOOGLE_CLIENT_SECRET`
- `BC_SOCIAL_AUTH_GOOGLE_REDIRECT_URI`
- `BC_SOCIAL_AUTH_X_ENABLED`
- `BC_SOCIAL_AUTH_X_CLIENT_ID`
- `BC_SOCIAL_AUTH_X_CLIENT_SECRET`
- `BC_SOCIAL_AUTH_X_REDIRECT_URI`

`*_REDIRECT_URI` を未設定の場合は、ルーティングから callback URL を自動生成します。

## 実装ステータス

フェーズ 1 の一部まで実装済みです。

実装済み:

- `ProviderAdapterInterface` / `ProviderAdapterRegistry` による provider 拡張基盤
- Google / X の adapter 実装
- `auth_provider_links` マイグレーション、Entity、Table
- Admin 向け `AuthController` とルーティング
- Admin 向け provider 設定画面
- install 完了後の設定画面への遷移
- DB 未初期化 / provider 未設定時のガード
- ログイン済み Admin ユーザー向けの連携済みアカウント一覧 / 連携解除 UI
- 連携済みアカウント画面からの追加連携導線
- Google / X の認可 URL 生成
- Google を中心とした token exchange / UserInfo 取得
- Admin ログイン画面へのソーシャルログインボタン表示
- `BcAuthCommon` の `AuthLoginService` への接続
- 未連携時の連携候補確認画面

未完了:

- Front プレフィックス対応
- X の実運用検証
