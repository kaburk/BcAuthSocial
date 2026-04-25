<?php
declare(strict_types=1);

namespace BcAuthSocial\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use BaserCore\Service\UsersService;
use BaserCore\Utility\BcUtil;
use BcAuthCommon\Service\AuthLoginService;
use BcAuthCommon\Service\AuthLoginLogService;
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Service\BcAuthSocialConfigsService;
use BcAuthSocial\Service\BcAuthSocialService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * BcAuthController (Admin)
 *
 * Admin プレフィックスにおける外部プロバイダ認証のエンドポイントを提供します。
 */
class BcAuthController extends BcAdminAppController
{
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated([
                'login',
                'callback',
                'linkCandidate',
                'confirmLink',
                'cancelLink',
            ]);
        }
    }

    public function beforeFilter(EventInterface $event): void
    {
        $this->FormProtection->setConfig('unlockedActions', [
            'confirmLink',
            'cancelLink',
        ]);

        if (in_array($this->request->getParam('action'), ['login', 'callback', 'linkCandidate', 'confirmLink', 'cancelLink'], true)) {
            return;
        }

        parent::beforeFilter($event);
    }

    public function login(string $provider): Response
    {
        $this->request->allowMethod('get');

        $setupRedirect = $this->guardSetup();
        if ($setupRedirect) {
            return $setupRedirect;
        }

        $service = new BcAuthSocialService();
        if (!ProviderAdapterRegistry::getInstance()->has($provider) || !$service->isProviderAvailable($provider)) {
            $this->BcMessage->setError(__d('baser_core', '未対応または未設定のプロバイダです。'));
            return $this->redirect($this->getLoginUrl());
        }

        try {
            $authUrl = $service->buildAuthorizationUrl($provider, 'Admin', $this->request->getQuery('redirect'));
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            return $this->redirect($this->getLoginUrl());
        }

        return $this->redirect($authUrl);
    }

    public function callback(string $provider): Response
    {
        $this->request->allowMethod('get');

        $setupRedirect = $this->guardSetup();
        if ($setupRedirect) {
            return $setupRedirect;
        }

        $code = (string)$this->request->getQuery('code');
        $state = (string)$this->request->getQuery('state');
        $error = $this->request->getQuery('error');

        if ($error || !$code) {
            $this->BcMessage->setError(__d('baser_core', 'ログインがキャンセルされました。'));
            AuthLoginLogService::writeWithContext(
                event: 'link_cancel',
                prefix: 'Admin',
                authSource: 'social:' . $provider,
                request: $this->request,
                context: [
                    'request_path' => (string) $this->request->getRequestTarget(),
                    'referer' => (string) $this->request->getHeaderLine('Referer'),
                    'payload' => ['error' => (string) ($error ?? 'no_code')],
                ]
            );
            return $this->redirect($this->getLoginUrl());
        }

        $service = new BcAuthSocialService();
        $redirect = $service->getStoredRedirect($provider, 'Admin');
        $clientIp = $service->getStoredClientIp($provider, 'Admin');

        try {
            $profile = $service->handleCallback($provider, 'Admin', $code, $state);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            AuthLoginLogService::writeWithContext(
                event: 'login_failure',
                prefix: 'Admin',
                authSource: 'social:' . $provider,
                request: $this->request,
                context: [
                    'request_path' => (string) $this->request->getRequestTarget(),
                    'referer' => (string) $this->request->getHeaderLine('Referer'),
                    'payload' => ['error' => $e->getMessage()],
                ]
            );
            return $this->redirect($this->getLoginUrl());
        }

        $userId = $service->resolveUserId($profile, 'Admin');
        $currentUser = BcUtil::loginUser();
        if ($currentUser) {
            $currentUserId = (int) $currentUser->id;

            if ($userId > 0 && $userId !== $currentUserId) {
                $this->BcMessage->setError(__d('baser_core', 'この外部アカウントは別のユーザーに連携されています。'));
                return $this->redirect($redirect ?: $this->getAccountsUrl());
            }

            if ($userId === $currentUserId) {
                $service->updateLastLogin($profile);
                $this->BcMessage->setInfo(__d('baser_core', 'この外部アカウントは既に連携済みです。'));
                return $this->redirect($redirect ?: $this->getAccountsUrl());
            }

            try {
                $service->linkUser($currentUserId, $profile, 'Admin', 'self');
                $service->updateLastLogin($profile);
            } catch (\RuntimeException $e) {
                $this->BcMessage->setError($e->getMessage());
                return $this->redirect($redirect ?: $this->getAccountsUrl());
            }

            $this->BcMessage->setSuccess(__d('baser_core', '{0} アカウントを現在のユーザーに連携しました。', ucfirst($provider)));

            return $this->redirect($redirect ?: $this->getAccountsUrl());
        }

        if ($userId > 0) {
            $service->updateLastLogin($profile);
            return $this->completeLogin($userId, $provider, $redirect, $clientIp);
        }

        $candidateUser = $service->resolveLinkCandidate($profile);
        if ($candidateUser) {
            $service->storePendingLinkCandidate($provider, 'Admin', $profile, (int)$candidateUser->id, $redirect, $clientIp);
            return $this->redirect([
                'prefix' => 'Admin',
                'plugin' => 'BcAuthSocial',
                'controller' => 'BcAuth',
                'action' => 'linkCandidate',
                $provider,
            ]);
        }

        $this->BcMessage->setError(
            __d('baser_core', 'このアカウントは連携されていません。管理者に連絡してください。')
        );
        AuthLoginLogService::writeWithContext(
            event: 'login_failure',
            prefix: 'Admin',
            authSource: 'social:' . $provider,
            request: $this->request,
            context: [
                'request_path' => (string) $this->request->getRequestTarget(),
                'referer' => (string) $this->request->getHeaderLine('Referer'),
                'payload' => ['error' => 'no linked user'],
            ]
        );
        return $this->redirect($this->getLoginUrl());
    }

    public function linkCandidate(string $provider)
    {
        $this->request->allowMethod('get');

        $service = new BcAuthSocialService();
        $candidate = $service->getPendingLinkCandidate($provider, 'Admin');
        if (!$candidate) {
            $this->BcMessage->setError(__d('baser_core', '連携候補情報が見つかりません。'));
            return $this->redirect($this->getLoginUrl());
        }

        $candidateUser = $candidate['candidateUser'];
        $profile = $candidate['profile'];
        $this->set(compact('provider', 'candidateUser', 'profile'));
    }

    public function confirmLink(string $provider): Response
    {
        $this->request->allowMethod('post');

        $service = new BcAuthSocialService();
        try {
            $result = $service->confirmPendingLinkCandidate($provider, 'Admin');
            $service->updateLastLogin($result['profile']);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            return $this->redirect($this->getLoginUrl());
        }

        return $this->completeLogin((int)$result['user_id'], $provider, $result['redirect'] ?? null, (string) ($result['client_ip'] ?? ''));
    }

    public function cancelLink(string $provider): Response
    {
        $this->request->allowMethod('post');

        $service = new BcAuthSocialService();
        $service->clearPendingLinkCandidate($provider, 'Admin');
        $this->BcMessage->setInfo(__d('baser_core', '外部アカウント連携をキャンセルしました。'));
        AuthLoginLogService::writeWithContext(
            event: 'link_cancel',
            prefix: 'Admin',
            authSource: 'social:' . $provider,
            request: $this->request,
            context: [
                'request_path' => (string) $this->request->getRequestTarget(),
                'referer' => (string) $this->request->getHeaderLine('Referer'),
            ]
        );

        return $this->redirect($this->getLoginUrl());
    }

    private function completeLogin(int $userId, string $provider, ?string $redirect, ?string $clientIp = null): Response
    {
        $loginService = new AuthLoginService();

        try {
            $loginResult = $loginService->login([
                'user_id' => $userId,
                'prefix' => 'Admin',
                'auth_source' => 'social:' . $provider,
                'redirect' => $redirect,
                'saved' => false,
                'client_ip' => $clientIp,
            ], $this->request, $this->response);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 403) {
                $this->BcMessage->setError($e->getMessage());
                return $this->redirect($this->getLoginUrl());
            }
            AuthLoginLogService::writeWithContext(
                event: 'login_failure',
                userId: $userId,
                prefix: 'Admin',
                authSource: 'social:' . $provider,
                request: $this->request,
                context: [
                    'request_path' => (string) $this->request->getRequestTarget(),
                    'referer' => (string) $this->request->getHeaderLine('Referer'),
                    'payload' => ['error' => $e->getMessage()],
                ]
            );
            $this->BcMessage->setError(__d('baser_core', 'ログイン状態の確立に失敗しました。'));
            return $this->redirect($this->getLoginUrl());
        }

        $this->request = $loginResult->request;
        $this->response = $loginResult->response;

        if ($loginResult->status === 'completed') {
            $this->setLoginSuccessMessage($userId);
        }

        return $this->redirect($loginResult->redirect_url);
    }

    private function getLoginUrl(): array
    {
        return [
            'prefix' => 'Admin',
            'plugin' => 'BaserCore',
            'controller' => 'Users',
            'action' => 'login',
        ];
    }

    private function getAccountsUrl(): array
    {
        return [
            'prefix' => 'Admin',
            'plugin' => 'BcAuthSocial',
            'controller' => 'BcAuthSocialAccounts',
            'action' => 'index',
        ];
    }

    private function guardSetup(): ?Response
    {
        $configService = new BcAuthSocialConfigsService();

        if (!$configService->hasInstalledSchema()) {
            $this->BcMessage->setError(__d('baser_core', 'BcAuthSocial の初期化が完了していません。先に設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        if (!$configService->hasAnyAvailableProvider()) {
            $this->BcMessage->setError(__d('baser_core', '利用可能なソーシャルログイン provider が設定されていません。設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        return null;
    }

    private function setLoginSuccessMessage(int $userId): void
    {
        /** @var \BaserCore\Model\Entity\User $user */
        $user = (new UsersService())->get($userId);
        $this->BcMessage->setInfo(__d('baser_core', 'ようこそ、{0}さん。', $user->getDisplayName()));
    }
}

