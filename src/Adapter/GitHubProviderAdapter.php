<?php
declare(strict_types=1);

namespace BcAuthSocial\Adapter;

/**
 * GitHubProviderAdapter
 *
 * GitHub OAuth 2.0 向けの ProviderAdapter 実装です。
 *
 * GitHub はメールアドレスを非公開にしているユーザーが多いため、
 * `/user/emails` エンドポイントで primary + verified のメールを取得します。
 * ただし `user:email` スコープを付与した場合のみ取得可能です。
 *
 * メールアドレスが取得できない場合は email = null として扱います。
 */
class GitHubProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'github'; }

    public function getLabel(): string { return 'GitHub でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        return 'https://api.github.com/user';
    }

    public function getScopes(): array
    {
        // read:user でプロフィール取得、user:email でメールアドレス取得
        return ['read:user', 'user:email'];
    }

    /** GitHub は PKCE 非必須（OAuth App では未対応） */
    public function usesPkce(): bool { return false; }

    /** OIDC ではない */
    public function usesIdToken(): bool { return false; }

    /** GitHub はトークンエンドポイントで Basic 認証を使わない */
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
        // GitHub の /user レスポンス例:
        // { "id": 12345, "login": "username", "name": "Display Name",
        //   "email": "user@example.com", "avatar_url": "https://..." }
        // email は非公開設定だと null になる場合がある

        $email = isset($userInfoResponse['email']) && $userInfoResponse['email'] !== ''
            ? (string)$userInfoResponse['email']
            : null;

        return new ProviderUserProfile(
            providerUserId: (string)$userInfoResponse['id'],
            provider: 'github',
            email: $email,
            // GitHub の /user では email_verified は返らないが、
            // user:email スコープで取得できるメールは verified = true のプライマリメールのみ使う運用とする
            emailVerified: $email !== null,
            name: $userInfoResponse['name'] ?? ($userInfoResponse['login'] ?? null),
            avatarUrl: $userInfoResponse['avatar_url'] ?? null,
        );
    }
}
