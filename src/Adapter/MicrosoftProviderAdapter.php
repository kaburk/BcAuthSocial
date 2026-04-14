<?php
declare(strict_types=1);

namespace BcAuthSocial\Adapter;

/**
 * MicrosoftProviderAdapter
 *
 * Microsoft アカウント（Azure AD v2 / Microsoft Entra ID）向けの ProviderAdapter 実装です。
 *
 * `common` エンドポイントを使用するため、個人アカウント・組織アカウントの両方に対応します。
 * 特定テナントのみに制限したい場合は、エンドポイントの `common` をテナント ID に変更してください。
 *
 * メールアドレスは Microsoft Graph の `mail` フィールドから取得します。
 * `mail` が null の場合（一部の組織アカウント）は `userPrincipalName` にフォールバックします。
 *
 * Azure Portal: https://portal.azure.com/
 * Microsoft Entra: https://entra.microsoft.com/
 */
class MicrosoftProviderAdapter implements ProviderAdapterInterface
{
    public function getProvider(): string { return 'microsoft'; }

    public function getLabel(): string { return 'Microsoft でログイン'; }

    public function getAuthorizationEndpoint(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    public function getUserInfoEndpoint(): ?string
    {
        // Microsoft Graph API でユーザー情報を取得
        return 'https://graph.microsoft.com/v1.0/me';
    }

    public function getScopes(): array
    {
        // openid / profile / email: OIDC 標準スコープ
        // User.Read: Microsoft Graph /me エンドポイントアクセス
        return ['openid', 'profile', 'email', 'User.Read'];
    }

    /** Microsoft v2.0 は PKCE 対応だが必須ではない（Web アプリ） */
    public function usesPkce(): bool { return false; }

    /** Microsoft は OIDC（id_token あり）だが、Graph API でプロフィールを取得 */
    public function usesIdToken(): bool { return true; }

    /** Microsoft はボディパラメータで client 認証するため Basic 認証ヘッダーは不要 */
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
        // Microsoft Graph /v1.0/me レスポンス例:
        // { "id": "abc123...", "displayName": "...", "mail": "user@example.com",
        //   "userPrincipalName": "user@tenant.onmicrosoft.com", ... }
        // mail は個人アカウントや一部の組織で null になる場合があるため userPrincipalName で補完

        $email = null;
        if (!empty($userInfoResponse['mail'])) {
            $email = (string)$userInfoResponse['mail'];
        } elseif (!empty($userInfoResponse['userPrincipalName'])) {
            // userPrincipalName が ext.xxx 形式（federation）でないことを確認
            $upn = (string)$userInfoResponse['userPrincipalName'];
            if (filter_var($upn, FILTER_VALIDATE_EMAIL)) {
                $email = $upn;
            }
        }

        return new ProviderUserProfile(
            providerUserId: (string)$userInfoResponse['id'],
            provider: 'microsoft',
            email: $email,
            emailVerified: $email !== null,
            name: $userInfoResponse['displayName'] ?? null,
            // Graph の /photo エンドポイントは別途アクセストークンが必要なため null
            avatarUrl: null,
        );
    }
}
