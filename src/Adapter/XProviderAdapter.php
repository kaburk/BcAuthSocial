<?php
declare(strict_types=1);

namespace BcSocialAuth\Adapter;

/**
 * XProviderAdapter
 *
 * X（旧 Twitter）OAuth 2.0 / PKCE 向けの ProviderAdapter 実装です。
 *
 * X はメールアドレスを標準スコープで取得できないため、
 * email と emailVerified は常に null / false になります。
 */
class XProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'x'; }

    public function getLabel(): string { return 'X でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://twitter.com/i/oauth2/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        return 'https://api.twitter.com/2/users/me';
    }

    public function getScopes(): array
    {
        return ['tweet.read', 'users.read', 'offline.access'];
    }

    /** X v2 は PKCE 必須 */
    public function usesPkce(): bool { return true; }

    /** OIDC ではない */
    public function usesIdToken(): bool { return false; }

    public function getAdditionalAuthParams(): array
    {
        return [];
    }

    public function getAdditionalUserInfoParams(): array
    {
        return [
            'user.fields' => 'profile_image_url',
        ];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        $data = $userInfoResponse['data'] ?? $userInfoResponse;

        return new ProviderUserProfile(
            providerUserId: (string)$data['id'],
            provider: 'x',
            email: null,
            emailVerified: false,
            name: $data['name'] ?? ($data['username'] ?? null),
            avatarUrl: $data['profile_image_url'] ?? null,
        );
    }
}
