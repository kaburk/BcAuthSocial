# BcAuthSocial plugin for baserCMS

BcAuthSocial は、baserCMS 5 に Google や X などの外部認証を追加するためのプラグインです。

旧 BcGoogleLogin の用途を baserCMS 5 向けに再整理しつつ、**基盤プラグイン＋外部アドオン登録方式** により将来のプロバイダ拡張に対応します。

## 目的

- 管理画面ログインに Google や X などの外部認証を追加する
- `ProviderAdapterInterface` を公開し、サードパーティが任意のプロバイダを追加できる構成にする
- BcAuthPasskey と同時利用できるようにする

## 初期スコープ（同梱プロバイダ）

- Google（OIDC）
- X（OAuth 2.0 / PKCE）
- GitHub（OAuth 2.0）
- LINE（OAuth 2.0 / OIDC）
- Microsoft（OAuth 2.0 / OIDC）
- 管理画面ログイン
- 既存ユーザーとの安全なひも付け

## アドオンによる拡張

BcAuthSocial をインストールすると `ProviderAdapterRegistry` がシングルトンとして利用可能になります。
追加プロバイダは独立したプラグインの `bootstrap.php` で登録するだけで組み込めます。

```php
// plugins/BcAppleAuth/src/BcAppleAuthPlugin.php
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAppleAuth\Adapter\AppleProviderAdapter;

ProviderAdapterRegistry::getInstance()->register(new AppleProviderAdapter());
```

### 将来のアドオンプラグイン候補

| プラグイン名 | プロバイダ | 備考 |
| --- | --- | --- |
| BcAppleAuth | Sign in with Apple | JWT 署名が必要・メール取得は初回のみ |
| BcYahooJpAuth | Yahoo! JAPAN | 国内ユーザー向け。OpenID Connect 対応 |
| BcFacebookAuth | Facebook / Meta | ビジネス・EC サイト向け |
| BcDiscordAuth | Discord | コミュニティサイト向け |
| BcSlackAuth | Slack | 社内向けサイトでの認証基盤として利用可 |
| BcGitLabAuth | GitLab | 開発者ポータル・社内 GitLab 連携向け |

## 方針

- メールアドレス一致だけに依存しない（`provider_user_id` を主体）
- X のようにメールが取れないプロバイダでも成立する設計
- ログイン完了処理は BcAuthCommon へ切り出しやすくする
- 初期の画面組み込みは template override を基本とする

## ドキュメント

詳細設計は [docs/social-auth-design.md](docs/social-auth-design.md) を参照してください。

横断整理は ../BcAuthCommon/docs/auth-plugin-spec-summary.md を参照してください。

## 設定

設定は [plugins/BcAuthSocial/config/setting.php](plugins/BcAuthSocial/config/setting.php) の env 読み出しで行います。

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
- `BC_SOCIAL_AUTH_GITHUB_ENABLED`
- `BC_SOCIAL_AUTH_GITHUB_CLIENT_ID`
- `BC_SOCIAL_AUTH_GITHUB_CLIENT_SECRET`
- `BC_SOCIAL_AUTH_GITHUB_REDIRECT_URI`
- `BC_SOCIAL_AUTH_LINE_ENABLED`
- `BC_SOCIAL_AUTH_LINE_CLIENT_ID`
- `BC_SOCIAL_AUTH_LINE_CLIENT_SECRET`
- `BC_SOCIAL_AUTH_LINE_REDIRECT_URI`
- `BC_SOCIAL_AUTH_MICROSOFT_ENABLED`
- `BC_SOCIAL_AUTH_MICROSOFT_CLIENT_ID`
- `BC_SOCIAL_AUTH_MICROSOFT_CLIENT_SECRET`
- `BC_SOCIAL_AUTH_MICROSOFT_REDIRECT_URI`

`*_REDIRECT_URI` を未設定の場合は、ルーティングから callback URL を自動生成します。

## 実装ステータス

フェーズ 1 実装済み（Admin / Front 両対応）。

### 作成済みファイル

| ファイル | 概要 |
|---|---|
| `config.php` | adminLink / installMessage 設定 |
| `config/routes.php` | Admin / Front ルート定義 |
| `config/setting.php` | env ベースの provider 設定（providerLabels / envKeys / callbackUrls） |
| `config/Migrations/20260409000001_CreateBcAuthProviderLinks.php` | マイグレーション（テーブル名: `bc_auth_provider_links`） |
| `src/BcAuthSocialPlugin.php` | プラグインクラス（Google / X を ProviderAdapterRegistry に登録、AuthEntryService にエントリ登録） |
| `src/Adapter/ProviderAdapterInterface.php` | アダプタインターフェース（外部アドオン向け公開 API） |
| `src/Adapter/ProviderAdapterRegistry.php` | シングルトン registry |
| `src/Adapter/ProviderUserProfile.php` | プロフィール DTO |
| `src/Adapter/GoogleProviderAdapter.php` | Google OIDC アダプタ |
| `src/Adapter/XProviderAdapter.php` | X（OAuth 2.0 / PKCE）アダプタ |
| `src/Adapter/GitHubProviderAdapter.php` | GitHub（OAuth 2.0）アダプタ |
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
