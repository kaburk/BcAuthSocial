<?php
declare(strict_types=1);

namespace BcSocialAuth\Adapter;

/**
 * GoogleProviderAdapter
 *
 * Google OIDC / OAuth 2.0 向けの ProviderAdapter 実装です。
 */
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
        return [];
    }

    public function getAdditionalUserInfoParams(): array
    {
        return [];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        return new ProviderUserProfile(
            providerUserId: (string)$userInfoResponse['sub'],
            provider: 'google',
            email: $userInfoResponse['email'] ?? null,
            emailVerified: (bool)($userInfoResponse['email_verified'] ?? false),
            name: $userInfoResponse['name'] ?? null,
            avatarUrl: $userInfoResponse['picture'] ?? null,
        );
    }
}
