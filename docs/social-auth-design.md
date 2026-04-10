# BcSocialAuth 設計書

## 概要

BcSocialAuth は、baserCMS 5 に OAuth 2.0 / OpenID Connect ベースの外部認証を追加するためのプラグイン構想です。

初期対象は管理画面ログインとし、将来的に Front プレフィックスのログインにも展開できる構成を目指します。

このプラグインは、パスワード認証やパスキー認証を置き換えるものではなく、ログイン画面に追加の認証入口を提供し、認証成功後は baserCMS の既存ログイン状態へ接続する役割を持ちます。

## 背景

baserCMS 4 系向けに作成された BcGoogleLogin は、管理画面ログインに Google ログインボタンを追加し、Google アカウントのメールアドレスと baserCMS ユーザーのメールアドレスを一致条件としてログインさせる構成でした。

baserCMS 5 系では、次の点を改善した設計を目指します。

- Google 以外の外部プロバイダにも拡張しやすい構成にする
- メールアドレス一致だけに依存しないユーザーひも付けを採用する
- BcPasskeyAuth と同時に導入しても UI やログイン完了処理が競合しにくい構成にする
- Admin と Front の両方へ展開可能な責務分離にする

## 目的

- 管理画面ログインに Google、Apple、GitHub、LINE などの外部認証を追加できるようにする
- 将来的に OpenID Connect 対応プロバイダを横展開しやすい構成にする
- BcPasskeyAuth と同時利用できるようにする
- 認証後の baserCMS ログイン確立は共通責務として切り出しやすい構成にする

## 非目的

- すべての外部プロバイダを最初から同時に実装すること
- 初期段階で Admin API 認証まで対象に含めること
- 認証後の権限管理や会員属性同期をフル機能で実装すること

## 初期スコープ

### フェーズ 1（実装済み）

- ✅ Google ログイン対応
- ✅ X ログイン対応
- ✅ 管理画面（Admin prefix）ログイン対応
- ✅ 既存 baserCMS ユーザーとのひも付け（`auth_provider_links`）
- ✅ ログイン画面への Google / X ログインボタン追加
- ✅ Google を中心とした token exchange / UserInfo 取得
- ✅ メール一致候補に対する確認付き連携導線
- ✅ プロバイダ設定画面（`SocialAuthConfigsController`）
- ✅ 連携済みアカウント管理画面（`SocialAuthAccountsController`）

### フェーズ 2（進行中・予定）

- ⬜ Front プレフィックス対応（AuthController + routes + ログインボタン統合）
- ⬜ X provider 実運用検証
- ⬜ Docker e2e 動作確認
- ⬜ Apple や GitHub など他プロバイダの追加（将来）

## 対応プロバイダ方針

### アーキテクチャの決定

**BcSocialAuth（基盤）＋ 外部アドオン登録方式** を採用します。

- **BcSocialAuth** に Google と X を同梱し、単体で動作する
- **`ProviderAdapterInterface`** と **`ProviderAdapterRegistry`** を公開 API として外部に開放する
- LINE / Apple / GitHub など追加プロバイダは **独立したアドオンプラグイン** として提供する
- アドオン側は `ProviderAdapterRegistry::getInstance()->register()` を呼ぶだけで組み込める

この方式により、コアを小さく保ちながら、サードパーティ開発者がプロバイダを自由に追加できます。

### BcSocialAuth に同梱するプロバイダ（初期）

| プロバイダ | 理由 |
| --- | --- |
| Google | BcGoogleLogin の知見継承。OIDC で設計が安定している |
| X | Google とは異なる PKCE 必須・メールなし制約を初期から扱うことで ProviderAdapter 設計を現実的にできる |

### 外部アドオンプラグインとして提供するプロバイダ（将来）

| プロバイダ | プラグイン名（案） | 認証方式 |
| --- | --- | --- |
| LINE | BcLineAuth | OAuth 2.0（LINE Login v2.1） |
| Apple | BcAppleAuth | OIDC（一部独自制約あり） |
| GitHub | BcGitHubAuth | OAuth 2.0 |
| Microsoft | BcMicrosoftAuth | OIDC（Azure AD） |
| 汎用 OIDC | BcOidcAuth | 設定値で任意プロバイダ対応 |

各アドオンは `BcSocialAuth` に依存するが、`BcSocialAuth` 側はアドオンを知らない（依存逆転）。

## Google と X を初期対象にする際の注意点

Google と X はどちらも OAuth 2.0 系ですが、実装上の性質はかなり異なります。

### Google の特徴

- OpenID Connect に寄せやすい
- ID Token や UserInfo が比較的扱いやすい
- メールアドレスや email_verified の取得前提を組みやすい

### X の特徴

- API や認証ポリシーの変更影響を受けやすい
- OpenID Connect より OAuth 2.0 ベースの provider 実装として見る方が安全
- メールアドレス取得を前提にしない設計が必要
- user id を主体にした provider_user_id 連携が特に重要

そのため、BcSocialAuth は OIDC 専用設計ではなく、OAuth 2.0 / OIDC の両方を扱える ProviderAdapter 前提で進める必要があります。

## BcGoogleLogin 5 系移行方針

BcGoogleLogin をそのまま 5 系へ移植するのではなく、次のどちらかで整理する想定です。

1. BcSocialAuth の Google Provider として統合する
2. BcGoogleLogin5 を独立プラグインとして作り、その内部で BcAuthCommon の共通サービスを利用する

推奨は 1 です。

理由は次の通りです。

- Google だけ特別な UI や設定構造を持たせる理由が薄い
- 将来 Apple や LINE を足すときに重複実装が増える
- callback や provider link の考え方を共通化しやすい

ただし、今回の初期対象を Google と X にする場合でも、最初の実装順は Google を先行させるのが妥当です。

推奨順は次の通りです。

1. Google Provider を先に実装する
2. ProviderAdapter の境界を固める
3. その上で X Provider を追加する

## BcPasskeyAuth との同時利用

BcSocialAuth は、BcPasskeyAuth と同時に有効化されることを前提とします。

ログイン画面では、次のような認証入口が同時に表示される状態を許容します。

- メールアドレスとパスワードでログイン
- パスキーでログイン
- Google でログイン
- X でログイン
- 他の外部認証でログイン

このため、次の設計が必要です。

- ログイン画面に認証ボタンを差し込む領域を共通化する
- ログイン成功後のセッション確立とリダイレクト判定を共通責務として整理する
- プラグインごとの UI 追加順序や重複描画を制御できるようにする

## 認証フロー

### 管理画面ログインフロー

1. ユーザーが管理画面ログインページを開く
2. Google または X でログイン ボタンをクリックする
3. 認可リクエストを外部プロバイダへ送る
4. ユーザーがプロバイダ上で認証する
5. callback URL に認可コードが返る
6. サーバーがトークン交換を行う
7. ID Token または UserInfo からプロバイダユーザー情報を取得する
8. provider_user_id を用いて baserCMS ユーザーひも付けを探索する
9. ひも付け済みユーザーがいればログインを確立する
10. 未ひも付けで許可条件を満たす場合は関連付けを行う
11. 管理画面トップまたは元の遷移先へリダイレクトする

## ユーザーひも付け方針

### 基本方針

外部認証の主体は provider_user_id で管理します。

メールアドレスは補助情報として扱い、同一性判定の主キーにはしません。

特に X はメールアドレス前提の運用にしない前提で設計します。

### 初回連携時の候補ルール

1. 既に provider_user_id のひも付けがある場合はそのユーザーにログインする
2. ひも付けがない場合、メールアドレス一致が 1 件だけなら確認付きで連携候補とする
3. 管理者または本人確認済みユーザーによる明示的な連携を優先する
4. あいまいな一致がある場合は自動ログインしない

この方針により、旧 BcGoogleLogin のメールアドレス一致だけに依存する方式より安全にできます。

### X 連携時の補足

X ではメールアドレスを安定して利用できない前提で考えるべきため、初回連携は次のどちらかを基本とします。

1. 事前にログイン済みユーザーがアカウント連携する
2. 管理者が許可した限定的な自動連携ルールを使う

少なくとも初期段階で、X のみを使った曖昧な自動ユーザー特定は避けるべきです。

## フェーズ 1 の仕様決定

初期実装で曖昧さを減らすため、次の点を先に固定します。

### 対象範囲

- 対応 prefix は Admin を最優先とする
- Google と X の両方を ProviderAdapter として実装する
- ログイン済みユーザー向けの連携 UI はフェーズ 2 以降でもよいが、データモデルはフェーズ 1 から対応可能にする
- Admin API ログインへの展開は初期対象外とする

### ユーザー作成ポリシー

- フェーズ 1 では外部認証成功時の新規ユーザー自動作成は行わない
- ログイン対象は既存の baserCMS ユーザーのみとする
- 初回ログインは provider_user_id による既存連携があるか、明示的な連携確認を通過した場合のみ許可する

### 自動連携ポリシー

初期既定値では 自動連携は無効 とします。

ただし、Google に限り将来的な設定拡張余地として、次の条件をすべて満たす場合のみ 連携候補提示 を許容します。

- `email_verified = true`
- 有効ユーザーとの完全一致が 1 件のみ
- provider_user_id の既存衝突がない
- 管理者がその動作を明示的に有効化している

この場合も、いきなり確定連携するのではなく、確認画面または確認メッセージ付きの連携確定を推奨します。

X はメールアドレスを前提にできないため、フェーズ 1 では次のどちらかだけを許可します。

- 既存の provider_user_id 連携によるログイン
- ログイン済みユーザー本人による事前連携

### 二段階認証との関係

Admin で二段階認証が有効な場合、外部認証成功後もそのままログイン完了にはしません。

パスワードログイン時と同じ安全基準を維持するため、外部プロバイダで本人確認が成功しても、初期仕様では既存の login_code フローへ受け渡します。

将来的に 特定 provider は二段階認証免除対象にする という拡張余地はありますが、フェーズ 1 では採用しません。

## 設定方針

provider ごとの設定値は `BcSocialAuth.providers.{provider}` 配下で扱います。
初期実装では [plugins/BcSocialAuth/config/setting.php](plugins/BcSocialAuth/config/setting.php) から env を読み込みます。

主な設定項目は次の通りです。

- `enabled`
- `clientId`
- `clientSecret`
- `redirectUri`
- `allowLinkCandidate`

Google は `allowLinkCandidate=true` を既定とし、`email_verified=true` かつ候補ユーザーが一意の場合のみ確認画面へ進みます。
X はメールアドレスを前提にしないため、既定では `allowLinkCandidate=false` とします。

## 連携判定マトリクス

| 状況 | Google | X |
| --- | --- | --- |
| provider_user_id の既存連携あり | ログイン可 | ログイン可 |
| 既存連携なし、ログイン済み本人が連携 | 連携可 | 連携可 |
| 既存連携なし、メール一致 1 件、email_verified=true | 既定では不可。設定有効時のみ確認付き候補 | 不可 |
| 既存連携なし、メール不一致または複数一致 | 不可 | 不可 |
| 新規ユーザー自動作成 | 不可 | 不可 |

## データモデル

### テーブル名案

auth_provider_links

### 主なカラム

| カラム | 型 | 用途 |
| --- | --- | --- |
| id | integer | 主キー |
| user_id | integer | baserCMS ユーザー |
| prefix | string | Admin / Front など |
| provider | string | google, apple, github, line など |
| provider_user_id | string | 外部プロバイダ側の一意識別子 |
| email | string nullable | 取得したメールアドレス |
| email_verified | boolean | メール確認済みか |
| name | string nullable | 表示名 |
| avatar_url | string nullable | アバター URL |
| profile | text nullable | 必要最小限のプロフィール情報 |
| last_login | datetime nullable | 最終ログイン日時 |
| created | datetime | 作成日時 |
| modified | datetime | 更新日時 |

### インデックス方針

- provider + provider_user_id に一意インデックス
- user_id に通常インデックス
- prefix に通常インデックス

### 追加したい運用列

フェーズ 1 では必須ではないものの、将来の運用を見越して次の列を拡張候補とします。

| カラム | 用途 |
| --- | --- |
| linked_by | `self`, `admin`, `auto` などの連携経路 |
| last_login_ip | 最終ログイン元 IP |
| last_login_user_agent | 最終ログイン端末 |
| disabled | 個別連携の無効化フラグ |

## ルーティング案

### Admin

- GET /baser/admin/bc-social-auth/auth/{provider}/login
- GET /baser/admin/bc-social-auth/auth/{provider}/callback
- POST /baser/admin/bc-social-auth/auth/{provider}/disconnect
- GET /baser/admin/bc-social-auth/provider_links/index

### Front

- GET /bc-social-auth/auth/{provider}/login
- GET /bc-social-auth/auth/{provider}/callback

## サービス設計

### SocialAuthService の責務

- provider ごとの認可 URL を生成する
- state や nonce を発行し検証する
- 認可コードをトークンへ交換する
- ID Token や UserInfo を検証、正規化する
- provider_user_id をキーにユーザーひも付けを解決する
- 認証開始時の state / nonce / code_verifier / redirect を短命セッションへ保存する
- callback 後に共通ログイン層へ `auth_source` 付きで処理委譲する

### ProviderAdapter の責務と設計

- プロバイダ固有のエンドポイント定義
- スコープ定義
- UserInfo の正規化
- provider_user_id と email_verified の取り出し
- メールアドレスが取得できないプロバイダでも成立する正規化

#### インターフェース定義案

```php
<?php
namespace BcSocialAuth\Adapter;

/**
 * 各プロバイダが実装すべき共通契約
 */
interface ProviderAdapterInterface
{
    /**
     * プロバイダ識別子を返す
     * 例: 'google', 'x'
     */
    public function getProvider(): string;

    /**
     * UI に表示するラベルを返す
     * 例: 'Google でログイン', 'X でログイン'
     */
    public function getLabel(): string;

    /**
     * 認可エンドポイント URL を返す
     */
    public function getAuthorizationEndpoint(): string;

    /**
     * トークンエンドポイント URL を返す
     */
    public function getTokenEndpoint(): string;

    /**
     * UserInfo エンドポイント URL を返す（OIDC で不要な場合は null）
     */
    public function getUserInfoEndpoint(): ?string;

    /**
     * 認可リクエストに使うスコープを返す
     * 例: ['openid', 'email', 'profile']
     */
    public function getScopes(): array;

    /**
     * PKCE を使うかどうかを返す
     */
    public function usesPkce(): bool;

    /**
     * ID Token を使うかどうかを返す（OIDC 前提かどうか）
     */
    public function usesIdToken(): bool;

    /**
     * トークンレスポンスとプロフィールデータから正規化されたユーザー情報を返す
     *
     * @param array $tokenResponse  トークンエンドポイントのレスポンス
     * @param array $userInfoResponse ユーザー情報エンドポイントのレスポンス（取得できた場合）
     * @return ProviderUserProfile
     */
    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile;

    /**
     * 認可リクエストに追加するプロバイダ固有パラメータを返す
     * 例: prompt, access_type, hosted_domain など
     */
    public function getAdditionalAuthParams(): array;
}
```

#### ProviderUserProfile（正規化済みユーザー情報）

```php
<?php
namespace BcSocialAuth\Adapter;

/**
 * プロバイダから取得したユーザー情報の正規化済みVO
 */
class ProviderUserProfile
{
    public function __construct(
        public readonly string  $providerUserId,  // 必須：プロバイダ内一意ID
        public readonly string  $provider,         // 必須：'google', 'x' など
        public readonly ?string $email,            // nullable（X は取得できないことがある）
        public readonly bool    $emailVerified,    // メール確認済みか
        public readonly ?string $name,             // 表示名（nullable）
        public readonly ?string $avatarUrl,        // アバター URL（nullable）
    ) {}
}
```

#### GoogleProviderAdapter の実装案

```php
<?php
namespace BcSocialAuth\Adapter;

class GoogleProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'google'; }
    public function getLabel(): string { return 'Google でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        return 'https://www.googleapis.com/oauth2/v3/userinfo';
    }

    public function getScopes(): array
    {
        return ['openid', 'email', 'profile'];
    }

    public function usesPkce(): bool { return false; }
    public function usesIdToken(): bool { return true; }

    public function getAdditionalAuthParams(): array
    {
        // hosted_domain や prompt は設定値から注入する想定
        return [];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        // Google は userinfo から sub, email, email_verified, name, picture を取得できる
        return new ProviderUserProfile(
            providerUserId: $userInfoResponse['sub'],
            provider:       'google',
            email:          $userInfoResponse['email'] ?? null,
            emailVerified:  (bool)($userInfoResponse['email_verified'] ?? false),
            name:           $userInfoResponse['name'] ?? null,
            avatarUrl:      $userInfoResponse['picture'] ?? null,
        );
    }
}
```

#### XProviderAdapter の実装案

```php
<?php
namespace BcSocialAuth\Adapter;

class XProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'x'; }
    public function getLabel(): string { return 'X でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        // OAuth 2.0 PKCE ベース（v2）
        return 'https://twitter.com/i/oauth2/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        // ユーザー情報は /2/users/me を別途叩く
        return 'https://api.twitter.com/2/users/me';
    }

    public function getScopes(): array
    {
        // tweet.read users.read offline.access
        // メールアドレス取得には特別なアクセス申請が必要なため含めない
        return ['tweet.read', 'users.read', 'offline.access'];
    }

    public function usesPkce(): bool { return true; } // X v2 は PKCE 必須
    public function usesIdToken(): bool { return false; } // OIDC ではない

    public function getAdditionalAuthParams(): array
    {
        return [];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        // X の /2/users/me レスポンス例:
        // { "data": { "id": "...", "name": "...", "username": "..." } }
        $data = $userInfoResponse['data'] ?? $userInfoResponse;

        return new ProviderUserProfile(
            providerUserId: (string)$data['id'],
            provider:       'x',
            email:          null,       // X は標準スコープでメール取得不可
            emailVerified:  false,
            name:           $data['name'] ?? null,
            avatarUrl:      null,       // profile_image_url は fields 指定で別途取得
        );
    }
}
```

#### Google と X の比較

| 項目 | Google | X |
| --- | --- | --- |
| プロトコル | OIDC / OAuth 2.0 | OAuth 2.0 のみ |
| PKCE | 任意 | 必須 |
| ID Token | あり | なし |
| UserInfo エンドポイント | あり | /2/users/me を個別呼び出し |
| メールアドレス取得 | 標準スコープで可 | 特別な申請が必要 |
| provider_user_id の位置 | userinfo.sub | data.id |
| email_verified | userinfo.email_verified | 取れない（常に false） |
| 画像 URL | userinfo.picture | fields=profile_image_url で別途 |
| ユーザー識別のリスク | 低（OIDC sub は安定） | 低（数値 ID は安定） |

この比較から、`emailVerified: false` のプロバイダ（X）からの自動ユーザーひも付けは避け、明示的な連携操作を前提にする方針が有効です。

#### AdapterRegistry（登録と取得）

外部アドオンから参照できるよう、シングルトンとして実装します。

```php
<?php
namespace BcSocialAuth\Adapter;

class ProviderAdapterRegistry
{
    private static ?self $instance = null;

    /** @var ProviderAdapterInterface[] */
    private array $adapters = [];

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function register(ProviderAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getProvider()] = $adapter;
    }

    public function get(string $provider): ProviderAdapterInterface
    {
        if (!isset($this->adapters[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: {$provider}");
        }
        return $this->adapters[$provider];
    }

    public function has(string $provider): bool
    {
        return isset($this->adapters[$provider]);
    }

    /** @return ProviderAdapterInterface[] */
    public function all(): array
    {
        return $this->adapters;
    }
}
```

BcSocialAuth の `config/bootstrap.php` で Google と X を登録します：

```php
// plugins/BcSocialAuth/config/bootstrap.php
use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcSocialAuth\Adapter\GoogleProviderAdapter;
use BcSocialAuth\Adapter\XProviderAdapter;

$registry = ProviderAdapterRegistry::getInstance();
$registry->register(new GoogleProviderAdapter());
$registry->register(new XProviderAdapter());
```

外部アドオンプラグイン（例: BcLineAuth）は自分の bootstrap.php で追加登録するだけです：

```php
// plugins/BcLineAuth/config/bootstrap.php
use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcLineAuth\Adapter\LineProviderAdapter;

ProviderAdapterRegistry::getInstance()->register(new LineProviderAdapter());
```

BcSocialAuth 側は BcLineAuth を知らず、BcLineAuth 側が BcSocialAuth に依存する依存逆転の構成になります。

#### PKCE フローの扱い

X は PKCE が必須のため、`SocialAuthService` 内で `usesPkce()` の戻り値を見て分岐します。

```
if ($adapter->usesPkce()) {
    $codeVerifier  = bin2hex(random_bytes(32));
    $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    // セッションに code_verifier を保存
    // 認可リクエストに code_challenge, code_challenge_method=S256 を追加
}
```

### ProviderLinkService の責務

- auth_provider_links の作成、更新、削除
- ログイン済みユーザーへの明示的連携
- 自動連携可否の判定
- 連携解除時に 代替ログイン手段が残るか を確認する

## 認証状態の管理

OAuth / OIDC の途中状態は短命セッションで管理します。

保存項目の最小構成は次の通りです。

- provider
- prefix
- redirect
- state
- nonce
- code_verifier
- requested_at

セッションキー例:

- `BcSocialAuth.google.Admin`
- `BcSocialAuth.x.Admin`

これにより、複数 provider を並行利用した場合でも状態が衝突しにくくなります。

## BcAuthCommon との境界

BcSocialAuth 自体は OAuth / OIDC に集中し、次の責務は共通側へ寄せる想定です。

- 認証成功後の baserCMS ログイン確立
- ログイン後リダイレクトの正規化
- ログイン画面への認証ボタン差し込み領域の管理
- 監査ログや失敗ログの共通化

## 設定項目案

プロバイダごとに次の設定を持ちます。

- client_id
- client_secret
- redirect_uri
- scope
- enabled

Google では追加で次の点を扱う可能性があります。

- hosted domain 制限
- prompt パラメータ
- access_type

X では追加で次の点を扱う可能性があります。

- PKCE の扱い
- 取得可能なスコープの整理
- API プランや提供条件の差異への対応

## セキュリティ方針

- state を必須とする
- OIDC 利用時は nonce を検証する
- provider_user_id を同一性の主判定に使う
- email_verified が false の場合は自動連携しない
- callback 失敗時は詳細を出しすぎず、安全に通常ログインへ戻す
- PKCE 必須 provider では code_verifier を必ずセッション照合する
- redirect は共通リダイレクト層で内部 URL のみに正規化する
- provider 側エラー内容は監査ログへ残しつつ、UI には抽象化した失敗理由を表示する

## UI 方針

- ログイン画面の認証ボタン群の中に Google でログイン と X でログイン を表示する
- BcPasskeyAuth と同時導入時も UI が破綻しないことを優先する
- 設定画面では provider ごとに有効、無効を切り替えられるようにする
- 将来的にはログイン済みユーザー向けの連携一覧画面を提供する

## ログイン画面への組み込み方式

ログイン画面への認証ボタン追加方法としては、主に次の 2 案があります。

### 1. テンプレート override 方式

- Admin は bc-admin-third のログインテンプレートを override する
- Front は bc-front のログインテンプレートを override する

利点:

- 差し込み位置を正確に制御しやすい
- Google と X の複数ボタン配置を安定して実装しやすい
- 初期段階で完成形の UI を作りやすい

欠点:

- コアやテーマ側テンプレート変更の影響を受けやすい
- 複数認証プラグインがそれぞれ override すると競合しやすい

### 2. Event / 共通入口方式

- ログイン画面側に共通の認証入口領域を用意する
- 各プラグインはボタン定義を登録する

利点:

- BcPasskeyAuth と BcSocialAuth の共存に向く
- 将来的なプロバイダ追加がしやすい

欠点:

- 現状のログインテンプレートには差し込みポイントが不足している
- 初回からこれを完成させようとすると共通基盤の作業量が増える

### 推奨方針

初期段階では、テンプレート override 方式を採用するのが現実的です。

ただし、override するテンプレート内では認証ボタン群を部分テンプレートや配列定義経由で描画し、将来は Event / 共通入口方式へ寄せられるようにしておくのがよいです。

つまり、結論は次の通りです。

- 最初の実装は template override 推奨
- 将来の完成形は共通入口方式を目指す
- 両方を最初から完全対応するのは可能だが、初手としては重い

## ファイル構成

### BcSocialAuth（基盤プラグイン）

```
plugins/BcSocialAuth/
├── src/
│   ├── BcSocialAuthPlugin.php
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── AuthController.php          # 認可開始 / コールバック (Admin)
│   │   │   └── ProviderLinksController.php  # 連携一覧・解除 (Admin)
│   │   └── AuthController.php              # 認可開始 / コールバック (Front)
│   ├── Service/
│   │   ├── SocialAuthService.php           # state 管理, token 交換, ユーザーひも付け解決
│   │   └── ProviderLinkService.php         # auth_provider_links CRUD
│   ├── Adapter/                            # 公開 API（アドオンがここに依存）
│   │   ├── ProviderAdapterInterface.php    ← 外部アドオンが実装すべき契約
│   │   ├── ProviderAdapterRegistry.php     ← シングルトン、外部から register() 可能
│   │   ├── ProviderUserProfile.php         # 正規化済みユーザーVO
│   │   ├── GoogleProviderAdapter.php       # 同梱アダプタ
│   │   └── XProviderAdapter.php            # 同梱アダプタ
│   └── Model/
│       ├── Table/
│       │   └── AuthProviderLinksTable.php
│       └── Entity/
│           └── AuthProviderLink.php
├── config/
│   ├── bootstrap.php                       # Google + X を Registry に登録
│   ├── routes.php
│   └── Migrations/
│       └── YYYYMMDDHHIISS_CreateAuthProviderLinks.php
├── templates/
│   ├── Admin/
│   │   ├── Auth/
│   │   │   └── callback_error.php
│   │   ├── ProviderLinks/
│   │   │   └── index.php
│   │   └── Users/
│   │       └── login.php                   # bc-admin-third の override
│   └── Users/
│       └── login.php                       # bc-front の override
├── tests/
│   └── TestCase/
│       ├── Service/
│       │   └── SocialAuthServiceTest.php
│       └── Adapter/
│           ├── GoogleProviderAdapterTest.php
│           └── XProviderAdapterTest.php
├── webroot/
│   └── css/
│       └── social_auth.css
├── composer.json
└── README.md
```

### BcLineAuth（外部アドオンプラグインの例）

BcSocialAuth を require し、bootstrap.php で登録するだけの最小構成です。

```
plugins/BcLineAuth/
├── src/
│   ├── BcLineAuthPlugin.php
│   └── Adapter/
│       └── LineProviderAdapter.php         # ProviderAdapterInterface を実装
├── config/
│   └── bootstrap.php                       # ProviderAdapterRegistry::getInstance()->register()
├── composer.json                           # "require": { "baserproject/bc-social-auth": "^1.0" }
└── README.md
```

Apple / GitHub も同様の構成で独立プラグインとして提供します。

## 未確定事項

- Google と X を同時着手するか、Google 先行で X を後追いにするか
- Admin と Front を同時に実装するか、Admin 先行にするか
- 自動連携ルールをどこまで許容するか
- 管理者による手動ひも付け UI を初期段階で入れるか
- BcLineAuth / BcAppleAuth / BcGitHubAuth それぞれの着手優先順位

## まとめ

BcSocialAuth は **基盤プラグイン＋外部アドオン登録方式** を採用します。

- Google と X を同梱し、単体で動作する
- `ProviderAdapterInterface` と `ProviderAdapterRegistry` を公開 API として外部に開放する
- LINE / Apple / GitHub など追加プロバイダは独立したアドオンプラグイン（BcLineAuth 等）として提供する
- アドオン側は `ProviderAdapterRegistry::getInstance()->register()` を呼ぶだけで組み込める

BcPasskeyAuth と同時利用される前提で、初期段階では template override を用いつつ、将来的にはログイン画面の共通入口とログイン完了処理の共通化へ寄せる設計を採用します。
