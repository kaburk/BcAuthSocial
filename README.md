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

フェーズ 1 実装済み（Admin prefix 対応）。Front prefix は未対応。

### 作成済みファイル

| ファイル | 概要 |
|---|---|
| `config.php` | adminLink / installMessage 設定 |
| `config/routes.php` | Admin ルート定義 |
| `config/setting.php` | env ベースの provider 設定（providerLabels / envKeys / callbackUrls） |
| `config/Migrations/20260409000001_CreateAuthProviderLinks.php` | マイグレーション |
| `src/BcSocialAuthPlugin.php` | プラグインクラス |
| `src/Adapter/ProviderAdapterInterface.php` | アダプタインターフェース（外部アドオン向け公開 API） |
| `src/Adapter/ProviderAdapterRegistry.php` | シングルトン registry |
| `src/Adapter/ProviderUserProfile.php` | プロフィール DTO |
| `src/Adapter/GoogleProviderAdapter.php` | Google OIDC アダプタ |
| `src/Adapter/XProviderAdapter.php` | X（OAuth 2.0 / PKCE）アダプタ |
| `src/Model/Entity/AuthProviderLink.php` | エンティティ |
| `src/Model/Table/AuthProviderLinksTable.php` | テーブルクラス |
| `src/Model/Entity/SocialAuthConfig.php` | バーチャルエンティティ（env 読み書き） |
| `src/Model/Table/SocialAuthConfigsTable.php` | バーチャルテーブル |
| `src/Service/SocialAuthService.php` | 認可 URL 生成・callback 処理・ユーザーひも付け・連携候補フロー |
| `src/Service/SocialAuthConfigsService.php` | provider 設定の読み書き（.env / 画面表示） |
| `src/Service/SocialAuthConfigsServiceInterface.php` | インターフェース |
| `src/ServiceProvider/BcSocialAuthServiceProvider.php` | DI 登録 |
| `src/Controller/Admin/AuthController.php` | login / callback / link_candidate / confirm_link / cancel_link |
| `src/Controller/Admin/SocialAuthAccountsController.php` | 連携済みアカウント管理（一覧・解除・追加連携導線） |
| `src/Controller/Admin/SocialAuthConfigsController.php` | provider 設定画面 |
| `templates/Admin/Auth/link_candidate.php` | 連携候補確認画面 |
| `templates/Admin/SocialAuthAccounts/index.php` | 連携済みアカウント一覧 |
| `templates/Admin/SocialAuthConfigs/index.php` | provider 設定画面 |
| `templates/element/social_login_buttons.php` | ログイン画面埋め込み用ソーシャルボタン element |

### ログイン画面への統合状況

Admin ログイン画面へのソーシャルログインボタン表示は、**BcPasskeyAuth の template override 経由**で実現しています。

- BcPasskeyAuth が有効な場合：BcPasskeyAuth の login.php override が `BcSocialAuth.social_login_buttons` element を条件付きでインクルードする
- **BcPasskeyAuth なしで BcSocialAuth のみインストールした場合：ログイン画面にソーシャルボタンが表示されない**（残タスク #1 参照）

### 残タスク

1. **単体動作対応**（優先度: 高）: BcPasskeyAuth がなくても Admin ログイン画面にソーシャルボタンが表示されるよう、BcSocialAuth 独自の `templates/plugin/BcAdminThird/Admin/Users/login.php` override を追加する
2. **Front prefix 対応**（優先度: 高）: Front 向けの `AuthController`（login / callback）と routes を追加する
3. **Front ログイン画面統合**（優先度: 高・2の後）: Front ログイン画面へのソーシャルボタン差し込みを実装する
4. **X provider 実運用検証**（優先度: 中）: X PKCE フローの end-to-end 確認
5. **Docker e2e 動作確認**（優先度: 高）: 管理画面からインストール → Google/X 認証フローの一気通貫確認
- X の実運用検証
