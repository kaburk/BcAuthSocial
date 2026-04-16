<?php
declare(strict_types=1);

namespace BcAuthSocial\Adapter;

/**
 * YahooJpProviderAdapter
 *
 * Yahoo! JAPAN ID連携 v2（Authorization Code フロー）向けの ProviderAdapter 実装です。
 *
 * - 認可エンドポイント: auth.login.yahoo.co.jp
 * - トークンエンドポイント: auth.login.yahoo.co.jp
 * - Token 発行は Basic 認証ヘッダーを利用
 * - ID Token を検証し、`sub` をユーザー識別子として利用
 *
 * UserInfo API には依存しないため、個人開発環境でも Yahoo! JAPAN ID 連携を利用できます。
 * email / profile の claim は ID Token に含まれる場合のみ利用します。
 */
class YahooJpProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'yahoojp'; }

    public function getLabel(): string { return 'Yahoo! JAPAN でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://auth.login.yahoo.co.jp/yconnect/v2/authorization';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://auth.login.yahoo.co.jp/yconnect/v2/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        return null;
    }

    public function getScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }

    public function usesPkce(): bool { return false; }

    public function usesIdToken(): bool { return true; }

    public function usesBasicAuthForToken(): bool { return true; }

    public function getAdditionalAuthParams(): array
    {
        return [
            'bail' => '1',
        ];
    }

    public function getAdditionalUserInfoParams(): array
    {
        return [];
    }

    public function normalizeUser(array $tokenResponse, array $userInfoResponse): ProviderUserProfile
    {
        $email = isset($userInfoResponse['email']) && $userInfoResponse['email'] !== ''
            ? (string)$userInfoResponse['email']
            : null;

        $emailVerified = false;
        if (isset($userInfoResponse['email_verified'])) {
            $raw = $userInfoResponse['email_verified'];
            $emailVerified = $raw === true || $raw === 'true' || $raw === '1' || $raw === 1;
        }

        return new ProviderUserProfile(
            providerUserId: (string)$userInfoResponse['sub'],
            provider: 'yahoojp',
            email: $email,
            emailVerified: $email !== null && $emailVerified,
            name: $userInfoResponse['name'] ?? $userInfoResponse['nickname'] ?? null,
            avatarUrl: $userInfoResponse['picture'] ?? null,
        );
    }
}
