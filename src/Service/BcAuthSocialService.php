<?php
declare(strict_types=1);

namespace BcAuthSocial\Service;

use BcAuthSocial\Adapter\ProviderAdapterInterface;
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Adapter\ProviderUserProfile;
use BcAuthSocial\Model\Entity\BcAuthProviderLink;
use BcAuthSocial\Model\Table\BcAuthProviderLinksTable;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\Exception\BadRequestException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use RuntimeException;

class BcAuthSocialService
{
    private const AUTH_SESSION_PREFIX = 'BcAuthSocial.';
    private const PENDING_LINK_SESSION_PREFIX = 'BcAuthSocial.PendingLink.';

    private BcAuthProviderLinksTable $links;
    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        $this->links = TableRegistry::getTableLocator()->get('BcAuthSocial.BcAuthProviderLinks');
        $this->httpClient = $httpClient ?? new Client();
    }

    public function buildAuthorizationUrl(string $provider, string $prefix, ?string $redirect = null): string
    {
        $adapter = ProviderAdapterRegistry::getInstance()->get($provider);
        $config = $this->getProviderConfig($provider);
        $state = $this->generateState();
        $nonce = $adapter->usesIdToken() ? $this->generateNonce() : null;

        $sessionData = [
            'provider' => $provider,
            'prefix' => $prefix,
            'redirect' => $redirect,
            'state' => $state,
            'nonce' => $nonce,
            'requested_at' => time(),
        ];

        if ($adapter->usesPkce()) {
            $codeVerifier = $this->generateCodeVerifier();
            $sessionData['code_verifier'] = $codeVerifier;
        }

        Router::getRequest()->getSession()->write($this->getAuthSessionKey($provider, $prefix), $sessionData);

        $params = array_merge([
            'client_id' => $config['clientId'],
            'redirect_uri' => $this->buildCallbackUrl($provider, $prefix),
            'response_type' => 'code',
            'scope' => implode(' ', $adapter->getScopes()),
            'state' => $state,
        ], $adapter->getAdditionalAuthParams());

        if ($nonce) {
            $params['nonce'] = $nonce;
        }

        if ($adapter->usesPkce()) {
            $codeChallenge = rtrim(
                strtr(base64_encode(hash('sha256', $sessionData['code_verifier'], true)), '+/', '-_'),
                '='
            );
            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        return $adapter->getAuthorizationEndpoint() . '?' . http_build_query($params);
    }

    public function handleCallback(string $provider, string $prefix, string $code, string $state): ProviderUserProfile
    {
        $session = Router::getRequest()->getSession();
        $stored = $session->read($this->getAuthSessionKey($provider, $prefix));
        $session->delete($this->getAuthSessionKey($provider, $prefix));

        if (empty($stored['state']) || !hash_equals((string)$stored['state'], $state)) {
            throw new RuntimeException('state が一致しません。CSRF の可能性があります。');
        }

        $adapter = ProviderAdapterRegistry::getInstance()->get($provider);
        $config = $this->getProviderConfig($provider);

        $tokenResponse = $this->requestAccessToken($adapter, $config, $prefix, $code, $stored);
        $idTokenClaims = $this->extractIdTokenClaims($adapter, $config, $tokenResponse, $stored);
        $userInfoResponse = $this->requestUserInfo($adapter, $tokenResponse, $idTokenClaims);

        return $adapter->normalizeUser($tokenResponse, $userInfoResponse);
    }

    public function getStoredRedirect(string $provider, string $prefix): ?string
    {
        return Router::getRequest()->getSession()->read($this->getAuthSessionKey($provider, $prefix) . '.redirect');
    }

    public function isProviderAvailable(string $provider): bool
    {
        if (!ProviderAdapterRegistry::getInstance()->has($provider)) {
            return false;
        }

        $config = Configure::read('BcAuthSocial.' . $provider) ?? [];

        return !empty($config['enabled']) && !empty($config['clientId']);
    }

    public function resolveUserId(ProviderUserProfile $profile, string $prefix = 'Admin'): int
    {
        $link = $this->links->findByProviderUserId($profile->provider, $profile->providerUserId);
        if ($link) {
            return (int)$link->user_id;
        }

        return 0;
    }

    public function resolveLinkCandidate(ProviderUserProfile $profile)
    {
        if (!$profile->email || !$profile->emailVerified) {
            return null;
        }
        if (!$this->canSuggestLinkCandidate($profile->provider)) {
            return null;
        }

        $users = TableRegistry::getTableLocator()->get('BaserCore.Users');
        $query = $users->find()->where(['Users.email' => $profile->email]);
        $query = $users->findAvailable($query);

        if ($query->count() !== 1) {
            return null;
        }

        return $query->first();
    }

    public function storePendingLinkCandidate(string $provider, string $prefix, ProviderUserProfile $profile, int $userId, ?string $redirect = null): void
    {
        Router::getRequest()->getSession()->write($this->getPendingLinkSessionKey($provider, $prefix), [
            'provider' => $provider,
            'prefix' => $prefix,
            'redirect' => $redirect,
            'user_id' => $userId,
            'profile' => [
                'providerUserId' => $profile->providerUserId,
                'provider' => $profile->provider,
                'email' => $profile->email,
                'emailVerified' => $profile->emailVerified,
                'name' => $profile->name,
                'avatarUrl' => $profile->avatarUrl,
            ],
        ]);
    }

    public function getPendingLinkCandidate(string $provider, string $prefix): ?array
    {
        $stored = Router::getRequest()->getSession()->read($this->getPendingLinkSessionKey($provider, $prefix));
        if (!$stored || empty($stored['profile']) || empty($stored['user_id'])) {
            return null;
        }

        $users = TableRegistry::getTableLocator()->get('BaserCore.Users');
        $candidateUser = $users->get((int)$stored['user_id']);

        return [
            'provider' => $provider,
            'prefix' => $prefix,
            'redirect' => $stored['redirect'] ?? null,
            'candidateUser' => $candidateUser,
            'profile' => $this->hydrateProfile($stored['profile']),
        ];
    }

    public function clearPendingLinkCandidate(string $provider, string $prefix): void
    {
        Router::getRequest()->getSession()->delete($this->getPendingLinkSessionKey($provider, $prefix));
    }

    public function confirmPendingLinkCandidate(string $provider, string $prefix): array
    {
        $stored = $this->getPendingLinkCandidate($provider, $prefix);
        if (!$stored) {
            throw new RuntimeException('連携候補情報が見つかりません。');
        }

        $link = $this->links->findByProviderUserId($provider, $stored['profile']->providerUserId);
        if (!$link) {
            $this->linkUser((int)$stored['candidateUser']->id, $stored['profile'], $prefix, 'auto');
        }

        $this->clearPendingLinkCandidate($provider, $prefix);

        return [
            'user_id' => (int)$stored['candidateUser']->id,
            'redirect' => $stored['redirect'],
            'profile' => $stored['profile'],
        ];
    }

    public function linkUser(
        int $userId,
        ProviderUserProfile $profile,
        string $prefix,
        string $linkedBy = 'self'
    ): BcAuthProviderLink {
        // disabled 含む既存レコードを探す（解除→再連携時の UNIQUE 違反を防ぐ）
        $existing = $this->links->find()
            ->where([
                'provider' => $profile->provider,
                'provider_user_id' => $profile->providerUserId,
            ])
            ->first();

        if ($existing) {
            $entity = $this->links->patchEntity($existing, [
                'user_id' => $userId,
                'prefix' => $prefix,
                'email' => $profile->email,
                'email_verified' => $profile->emailVerified,
                'name' => $profile->name,
                'avatar_url' => $profile->avatarUrl,
                'linked_by' => $linkedBy,
                'disabled' => false,
            ]);
        } else {
            $entity = $this->links->newEntity([
                'user_id' => $userId,
                'prefix' => $prefix,
                'provider' => $profile->provider,
                'provider_user_id' => $profile->providerUserId,
                'email' => $profile->email,
                'email_verified' => $profile->emailVerified,
                'name' => $profile->name,
                'avatar_url' => $profile->avatarUrl,
                'linked_by' => $linkedBy,
            ]);
        }

        $saved = $this->links->save($entity);
        if (!$saved) {
            throw new RuntimeException('プロバイダ連携の保存に失敗しました。');
        }

        return $saved;
    }

    public function getUserLinks(int $userId, string $prefix = 'Admin'): array
    {
        return $this->links->findByUser($userId, $prefix)->all()->toList();
    }

    public function unlinkUserLink(int $userId, int $linkId, string $prefix = 'Admin'): void
    {
        $link = $this->links->find()
            ->where([
                'id' => $linkId,
                'user_id' => $userId,
                'prefix' => $prefix,
                'disabled' => false,
            ])
            ->first();

        if (!$link) {
            throw new RuntimeException('連携済みアカウントが見つかりません。');
        }

        $link = $this->links->patchEntity($link, ['disabled' => true]);
        if (!$this->links->save($link)) {
            throw new RuntimeException('連携解除に失敗しました。');
        }
    }

    public function updateLastLogin(ProviderUserProfile $profile): void
    {
        $link = $this->links->findByProviderUserId($profile->provider, $profile->providerUserId);
        if (!$link) {
            return;
        }

        $request = Router::getRequest();
        $link = $this->links->patchEntity($link, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_login_ip' => $request?->clientIp(),
            'last_login_user_agent' => $request?->getHeaderLine('User-Agent'),
        ]);
        $this->links->save($link);
    }

    private function requestAccessToken(ProviderAdapterInterface $adapter, array $config, string $prefix, string $code, array $stored): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->buildCallbackUrl($adapter->getProvider(), $prefix),
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];

        if ($adapter->usesBasicAuthForToken()) {
            // 機密クライアント（X など）: Basic 認証ヘッダーで client 認証
            $headers['Authorization'] = 'Basic ' . base64_encode($config['clientId'] . ':' . ($config['clientSecret'] ?? ''));
        } else {
            // 通常クライアント: ボディパラメータで client 認証
            $params['client_id'] = $config['clientId'];
            if (!empty($config['clientSecret'])) {
                $params['client_secret'] = $config['clientSecret'];
            }
        }

        if ($adapter->usesPkce()) {
            $params['code_verifier'] = $stored['code_verifier'] ?? '';
        }

        $response = $this->httpClient->post(
            $adapter->getTokenEndpoint(),
            http_build_query($params),
            ['headers' => $headers]
        );

        return $this->decodeJsonResponse($response, 'アクセストークンの取得に失敗しました。');
    }

    private function requestUserInfo(ProviderAdapterInterface $adapter, array $tokenResponse, array $fallbackClaims = []): array
    {
        $endpoint = $adapter->getUserInfoEndpoint();
        if (!$endpoint) {
            return $fallbackClaims;
        }
        if (empty($tokenResponse['access_token'])) {
            throw new RuntimeException('アクセストークンがありません。');
        }

        $response = $this->httpClient->get(
            $endpoint,
            $adapter->getAdditionalUserInfoParams(),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenResponse['access_token'],
                    'Accept' => 'application/json',
                ],
            ]
        );

        return $this->decodeJsonResponse($response, 'ユーザー情報の取得に失敗しました。');
    }

    private function decodeJsonResponse(Response $response, string $message): array
    {
        $json = $response->getJson();
        if (!$response->isOk() || !is_array($json)) {
            $status = $response->getStatusCode();
            $body = (string)$response->getBody();
            \Cake\Log\Log::error(
                sprintf('[BcAuthSocial] %s | HTTP %d | body: %s', $message, $status, mb_substr($body, 0, 1000))
            );
            throw new RuntimeException($message);
        }

        return $json;
    }

    private function getProviderConfig(string $provider): array
    {
        $config = Configure::read('BcAuthSocial.' . $provider) ?? [];
        if (empty($config['enabled'])) {
            throw new RuntimeException('このプロバイダは現在無効です。');
        }
        if (empty($config['clientId'])) {
            throw new RuntimeException('clientId が設定されていません。');
        }

        return $config;
    }

    private function buildCallbackUrl(string $provider, string $prefix): string
    {
        $config = Configure::read('BcAuthSocial.' . $provider) ?? [];
        if (!empty($config['redirectUri'])) {
            return (string)$config['redirectUri'];
        }

        return Router::url([
            'plugin' => 'BcAuthSocial',
            'prefix' => $prefix === 'Front' ? false : $prefix,
            'controller' => 'BcAuth',
            'action' => 'callback',
            $provider,
        ], true);
    }

    private function canSuggestLinkCandidate(string $provider): bool
    {
        return (bool)(Configure::read('BcAuthSocial.' . $provider . '.allowLinkCandidate') ?? false);
    }

    private function hydrateProfile(array $profile): ProviderUserProfile
    {
        return new ProviderUserProfile(
            providerUserId: (string)$profile['providerUserId'],
            provider: (string)$profile['provider'],
            email: $profile['email'] ?? null,
            emailVerified: (bool)($profile['emailVerified'] ?? false),
            name: $profile['name'] ?? null,
            avatarUrl: $profile['avatarUrl'] ?? null,
        );
    }

    private function getAuthSessionKey(string $provider, string $prefix): string
    {
        return self::AUTH_SESSION_PREFIX . $provider . '.' . $prefix;
    }

    private function getPendingLinkSessionKey(string $provider, string $prefix): string
    {
        return self::PENDING_LINK_SESSION_PREFIX . $provider . '.' . $prefix;
    }

    private function generateState(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function generateNonce(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function extractIdTokenClaims(
        ProviderAdapterInterface $adapter,
        array $config,
        array $tokenResponse,
        array $stored
    ): array {
        if ($adapter->getProvider() !== 'yahoojp') {
            return [];
        }

        $idToken = $tokenResponse['id_token'] ?? null;
        if (!is_string($idToken) || $idToken === '') {
            throw new RuntimeException('Yahoo! JAPAN の ID Token が返却されませんでした。');
        }

        $metadata = $this->fetchJsonDocument(
            'https://auth.login.yahoo.co.jp/yconnect/v2/.well-known/openid-configuration',
            'Yahoo! JAPAN の OpenID Connect 設定取得に失敗しました。'
        );
        $jwksUri = $metadata['jwks_uri'] ?? null;
        $issuer = $metadata['issuer'] ?? null;
        if (!is_string($jwksUri) || $jwksUri === '' || !is_string($issuer) || $issuer === '') {
            throw new RuntimeException('Yahoo! JAPAN の OpenID Connect 設定が不正です。');
        }

        $jwks = $this->fetchJsonDocument($jwksUri, 'Yahoo! JAPAN の公開鍵取得に失敗しました。');

        try {
            $keys = JWK::parseKeySet($jwks, 'RS256');
            $claims = (array)JWT::decode($idToken, $keys);
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('[BcAuthSocial] Yahoo! JAPAN ID Token validation failed: ' . $e->getMessage());
            throw new RuntimeException('Yahoo! JAPAN の ID Token 検証に失敗しました。');
        }

        $this->assertYahooIdTokenClaims($claims, $issuer, (string)$config['clientId'], $stored, $tokenResponse);

        return $claims;
    }

    private function fetchJsonDocument(string $url, string $message): array
    {
        $response = $this->httpClient->get($url, [], [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return $this->decodeJsonResponse($response, $message);
    }

    private function assertYahooIdTokenClaims(
        array $claims,
        string $issuer,
        string $clientId,
        array $stored,
        array $tokenResponse
    ): void {
        if (($claims['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('Yahoo! JAPAN の ID Token issuer が不正です。');
        }

        $audience = $claims['aud'] ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];
        if (!in_array($clientId, $audiences, true)) {
            throw new RuntimeException('Yahoo! JAPAN の ID Token audience が一致しません。');
        }

        $nonce = $claims['nonce'] ?? null;
        $expectedNonce = $stored['nonce'] ?? null;
        if (!is_string($nonce) || !is_string($expectedNonce) || !hash_equals($expectedNonce, $nonce)) {
            throw new RuntimeException('Yahoo! JAPAN の ID Token nonce が一致しません。');
        }

        $subject = $claims['sub'] ?? null;
        if (!is_string($subject) || $subject === '') {
            throw new RuntimeException('Yahoo! JAPAN の ID Token に sub が含まれていません。');
        }

        if (isset($claims['at_hash']) && !empty($tokenResponse['access_token'])) {
            $expectedAtHash = $this->generateAtHash((string)$tokenResponse['access_token']);
            if (!hash_equals((string)$claims['at_hash'], $expectedAtHash)) {
                throw new RuntimeException('Yahoo! JAPAN の ID Token at_hash が一致しません。');
            }
        }
    }

    private function generateAtHash(string $accessToken): string
    {
        $hash = hash('sha256', $accessToken, true);
        $halfHash = substr($hash, 0, (int)(strlen($hash) / 2));

        return rtrim(strtr(base64_encode($halfHash), '+/', '-_'), '=');
    }
}
