<?php
declare(strict_types=1);

namespace BcAuthSocial\Adapter;

/**
 * ProviderAdapterInterface
 *
 * 各プロバイダが実装すべき共通契約です。
 * 外部アドオンプラグインはこのインターフェースを実装し、
 * ProviderAdapterRegistry::register() で登録します。
 */
interface ProviderAdapterInterface
{
    /** プロバイダ識別子を返す（例: 'google', 'x'） */
    public function getProvider(): string;

    /** UI に表示するラベルを返す（例: 'Google でログイン'） */
    public function getLabel(): string;

    /** 認可エンドポイント URL を返す */
    public function getAuthorizationEndpoint(): string;

    /** トークンエンドポイント URL を返す */
    public function getTokenEndpoint(): string;

    /** UserInfo エンドポイント URL を返す（OIDC 不要なら null） */
    public function getUserInfoEndpoint(): ?string;

    /** 認可リクエストに使うスコープを返す */
    public function getScopes(): array;

    /** PKCE を使うかどうかを返す */
    public function usesPkce(): bool;

    /** ID Token を使うかどうかを返す */
    public function usesIdToken(): bool;

    /**
     * トークンレスポンスとプロフィールデータから正規化済みユーザー情報を返す
     *
     * @param array $tokenResponse    トークンエンドポイントのレスポンス
     * @param array $userInfoResponse UserInfo エンドポイントのレスポンス
     * @return ProviderUserProfile
     */
    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile;

    /** 認可リクエストに追加するプロバイダ固有パラメータを返す */
    public function getAdditionalAuthParams(): array;

    /** UserInfo リクエストに追加するクエリパラメータを返す */
    public function getAdditionalUserInfoParams(): array;

    /**
     * トークンエンドポイントの認証に Basic 認証ヘッダー（client_id:client_secret の Base64）を使うかどうかを返す。
     * X（機密クライアント）のように HTTP Basic Auth が必要なプロバイダで true を返す。
     */
    public function usesBasicAuthForToken(): bool;
}
