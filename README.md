# BcAuthSocial plugin for baserCMS

BcAuthSocial は、baserCMS 5 に Google や X などの外部認証を追加するためのプラグインです。

Configure 駆動のプロバイダー管理により、config/setting.php への設定追加でプロバイダーを拡張できます。
ProviderAdapterInterface を公開しているため、外部アドオンプラグインから独自プロバイダーを追加することも可能です。

このプラグイン単体では動作しません。事前に BcAuthCommon の導入が必要です。

## 目的

- 管理画面・フロントログインに外部 OAuth / OIDC 認証を追加する
- プロバイダーを config/setting.php の設定で管理し、追加・削除しやすくする

## 主な機能

- 外部認証ログイン（Admin / Front）
- ソーシャルアカウント連携・解除
- Configure ベースのプロバイダー登録
- 管理画面からの設定支援（.env 反映 / キー案内）

## 同梱プロバイダー（概要）

| プロバイダー | 認証方式 | 備考 |
|---|---|---|
| Google | OIDC |  |
| X | OAuth 2.0 / PKCE |  |
| GitHub | OAuth 2.0 |  |
| LINE | OAuth 2.0 / OIDC |  |
| Yahoo! JAPAN | Authorization Code フロー |  |
| Microsoft | OIDC（common エンドポイント） |  |

## 詳細ドキュメント

- 詳細設計: [docs/social-auth-design.md](docs/social-auth-design.md)
- 認証プラグイン全体整理: [../BcAuthCommon/docs/auth-plugin-spec-summary.md](../BcAuthCommon/docs/auth-plugin-spec-summary.md)

## プロバイダー拡張の要点

新規プロバイダー追加の詳細手順は docs を参照してください。概要は次のとおりです。

1. config/setting.php にプロバイダーブロックを追加
2. src/Adapter/{Name}ProviderAdapter.php を実装
3. src/BcAuthSocialPlugin.php の bootstrap() で registry へ登録
4. .env または管理画面からクレデンシャルを設定

管理画面の設定フォーム・ログインボタン・連携アカウント一覧は Configure から生成されます。

## 外部アドオンプラグイン拡張

ProviderAdapterRegistry はシングルトンとして公開されており、独立プラグインから登録できます。

```php
// plugins/BcAppleAuth/src/BcAppleAuthPlugin.php
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAppleAuth\Adapter\AppleProviderAdapter;

ProviderAdapterRegistry::getInstance()->register('apple', new AppleProviderAdapter());
```

## 設定の要点

- 設定は config/setting.php の env 読み出しで定義
- 管理画面「ソーシャル認証設定」から .env へ書き込み可能
- 環境変数形式は {envPrefix}_ENABLED / _CLIENT_ID / _CLIENT_SECRET / _REDIRECT_URI
- *_REDIRECT_URI 未設定時は callback URL を自動生成

代表的な envPrefix:

| プロバイダー | envPrefix |
|---|---|
| Google | BC_SOCIAL_AUTH_GOOGLE |
| X | BC_SOCIAL_AUTH_X |
| GitHub | BC_SOCIAL_AUTH_GITHUB |
| LINE | BC_SOCIAL_AUTH_LINE |
| Yahoo! JAPAN | BC_SOCIAL_AUTH_YAHOOJP |
| Microsoft | BC_SOCIAL_AUTH_MICROSOFT |

## 公開運用メモ

- OAuth クライアント情報はプラグインへ同梱せず、利用者ごとに設定してもらう
- 開発環境・本番環境で OAuth クライアントを分離する
- Google の Internal / External 設定や Yahoo! JAPAN 登録手順などの詳細は docs を参照

## よく参照する実装ファイル（入口）

- [src/BcAuthSocialPlugin.php](src/BcAuthSocialPlugin.php)
- [config/setting.php](config/setting.php)
- [src/Adapter](src/Adapter)
- [src/Service](src/Service)
- [src/Controller/Admin/BcAuthController.php](src/Controller/Admin/BcAuthController.php)
- [src/Controller/BcAuthController.php](src/Controller/BcAuthController.php)

## 関連プラグイン

- [../BcAuthCommon/README.md](../BcAuthCommon/README.md)
- [../BcAuthPasskey/README.md](../BcAuthPasskey/README.md)
- [../BcAuthSocial/README.md](../BcAuthSocial/README.md)
- [../BcAuthGuard/README.md](../BcAuthGuard/README.md)

## ライセンス

MIT License.

詳細は [LICENSE.md](LICENSE.md) を参照してください。
