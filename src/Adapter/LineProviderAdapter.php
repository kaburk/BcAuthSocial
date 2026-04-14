<?php
declare(strict_types=1);

namespace BcAuthSocial\Adapter;

/**
 * LineProviderAdapter
 *
 * LINE Login（OIDC / OAuth 2.0）向けの ProviderAdapter 実装です。
 *
 * userinfo エンドポイント（/oauth2/v2.1/userinfo）を利用します。
 * メールアドレスは `email` スコープが付与されており、かつ LINE 側でアプリの
 * メール取得が承認されている場合にのみ返却されます。
 * 承認前は email = null として扱います。
 *
 * LINE Developers Console: https://developers.line.biz/
 */
class LineProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'line'; }

    public function getLabel(): string { return 'LINE でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://access.line.me/oauth2/v2.1/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://access.line.me/oauth2/v2.1/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        // openid スコープがある場合、OIDC 準拠の userinfo エンドポイントが利用可能
        return 'https://api.line.me/oauth2/v2.1/userinfo';
    }

    public function getScopes(): array
    {
        // openid: userId/profile 取得。email: メール取得（LINE 側の審査が必要）
        return ['openid', 'profile', 'email'];
    }

    /** LINE Login は PKCE 非必須（Web アプリ） */
    public function usesPkce(): bool { return false; }

    /** LINE は OIDC（id_token あり）だが、userinfo エンドポイントでプロフィールを取得 */
    public function usesIdToken(): bool { return false; }

    /** LINE はボディパラメータで client 認証するため Basic 認証ヘッダーは不要 */
    public function usesBasicAuthForToken(): bool { return false; }

    public function getAdditionalAuthParams(): array
    {
        return [];
    }

    public function getAdditionalUserInfoParams(): array
    {
        return [];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        // LINE OIDC userinfo レスポンス例:
        // { "sub": "U1234567890abcdef", "name": "表示名", "picture": "https://...", "email": "..." }
        // email は LINE 側の審査が通った場合のみ返却

        $email = isset($userInfoResponse['email']) && $userInfoResponse['email'] !== ''
            ? (string)$userInfoResponse['email']
            : null;

        return new ProviderUserProfile(
            providerUserId: (string)$userInfoResponse['sub'],
            provider: 'line',
            email: $email,
            emailVerified: $email !== null,
            name: $userInfoResponse['name'] ?? null,
            avatarUrl: $userInfoResponse['picture'] ?? null,
        );
    }
}
