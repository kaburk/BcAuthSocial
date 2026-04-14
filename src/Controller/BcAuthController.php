<?php
declare(strict_types=1);

namespace BcAuthSocial\Controller;

use BaserCore\Controller\BcFrontAppController;
use BaserCore\Utility\BcUtil;
use BcAuthCommon\Service\AuthLoginService;
use BcAuthCommon\Service\AuthLoginLogService;
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Service\BcAuthSocialConfigsService;
use BcAuthSocial\Service\BcAuthSocialService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

class BcAuthController extends BcFrontAppController
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
        parent::beforeFilter($event);
        $this->FormProtection->setConfig('unlockedActions', [
            'confirmLink',
            'cancelLink',
        ]);
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
            $authUrl = $service->buildAuthorizationUrl($provider, 'Front', $this->request->getQuery('redirect'));
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

        $code = (string) $this->request->getQuery('code');
        $state = (string) $this->request->getQuery('state');
        $error = $this->request->getQuery('error');

        if ($error || !$code) {
            $this->BcMessage->setError(__d('baser_core', 'ログインがキャンセルされました。'));
            AuthLoginLogService::write('link_cancel', prefix: 'Front', authSource: 'social:' . $provider, request: $this->request, detail: 'error=' . ($error ?? 'no_code'));
            return $this->redirect($this->getLoginUrl());
        }

        $service = new BcAuthSocialService();
        $redirect = $service->getStoredRedirect($provider, 'Front');

        try {
            $profile = $service->handleCallback($provider, 'Front', $code, $state);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            AuthLoginLogService::write('login_failure', prefix: 'Front', authSource: 'social:' . $provider, request: $this->request, detail: $e->getMessage());
            return $this->redirect($this->getLoginUrl());
        }

        $userId = $service->resolveUserId($profile, 'Front');
        $currentUser = BcUtil::loginUser();
        if ($currentUser) {
            $currentUserId = (int) $currentUser->id;

            if ($userId > 0 && $userId !== $currentUserId) {
                $this->BcMessage->setError(__d('baser_core', 'この外部アカウントは別のユーザーに連携されています。'));
                return $this->redirect($redirect ?: '/');
            }

            if ($userId === $currentUserId) {
                $service->updateLastLogin($profile);
                $this->BcMessage->setInfo(__d('baser_core', 'この外部アカウントは既に連携済みです。'));
                return $this->redirect($redirect ?: '/');
            }

            try {
                $service->linkUser($currentUserId, $profile, 'Front', 'self');
                $service->updateLastLogin($profile);
            } catch (\RuntimeException $e) {
                $this->BcMessage->setError($e->getMessage());
                return $this->redirect($redirect ?: '/');
            }

            $this->BcMessage->setSuccess(__d('baser_core', '{0} アカウントを現在のユーザーに連携しました。', ucfirst($provider)));

            return $this->redirect($redirect ?: '/');
        }

        if ($userId > 0) {
            $service->updateLastLogin($profile);
            return $this->completeLogin($userId, $provider, $redirect);
        }

        $candidateUser = $service->resolveLinkCandidate($profile);
        if ($candidateUser) {
            $service->storePendingLinkCandidate($provider, 'Front', $profile, (int) $candidateUser->id, $redirect);
            return $this->redirect([
                'prefix' => false,
                'plugin' => 'BcAuthSocial',
                'controller' => 'BcAuth',
                'action' => 'linkCandidate',
                $provider,
            ]);
        }

        $this->BcMessage->setError(
            __d('baser_core', 'このアカウントは連携されていません。管理者に連絡してください。')
        );
        AuthLoginLogService::write('login_failure', prefix: 'Front', authSource: 'social:' . $provider, request: $this->request, detail: 'no linked user');
        return $this->redirect($this->getLoginUrl());
    }

    public function linkCandidate(string $provider)
    {
        $this->request->allowMethod('get');

        $service = new BcAuthSocialService();
        $candidate = $service->getPendingLinkCandidate($provider, 'Front');
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
            $result = $service->confirmPendingLinkCandidate($provider, 'Front');
            $service->updateLastLogin($result['profile']);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            return $this->redirect($this->getLoginUrl());
        }

        return $this->completeLogin((int) $result['user_id'], $provider, $result['redirect'] ?? null);
    }

    public function cancelLink(string $provider): Response
    {
        $this->request->allowMethod('post');

        $service = new BcAuthSocialService();
        $service->clearPendingLinkCandidate($provider, 'Front');
        $this->BcMessage->setInfo(__d('baser_core', '外部アカウント連携をキャンセルしました。'));
        AuthLoginLogService::write('link_cancel', prefix: 'Front', authSource: 'social:' . $provider, request: $this->request);

        return $this->redirect($this->getLoginUrl());
    }

    private function completeLogin(int $userId, string $provider, ?string $redirect): Response
    {
        $loginService = new AuthLoginService();

        try {
            $loginResult = $loginService->login([
                'user_id' => $userId,
                'prefix' => 'Front',
                'auth_source' => 'social:' . $provider,
                'redirect' => $redirect,
                'saved' => false,
            ], $this->request, $this->response);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError(__d('baser_core', 'ログイン状態の確立に失敗しました。'));
            return $this->redirect($this->getLoginUrl());
        }

        $this->request = $loginResult->request;
        $this->response = $loginResult->response;

        return $this->redirect($loginResult->redirect_url);
    }

    private function getLoginUrl(): array
    {
        return [
            'prefix' => false,
            'plugin' => false,
            'controller' => 'Users',
            'action' => 'login',
        ];
    }

    private function guardSetup(): ?Response
    {
        $configService = new BcAuthSocialConfigsService();

        if (!$configService->hasInstalledSchema() || !$configService->hasAnyAvailableProvider()) {
            $this->BcMessage->setError(__d('baser_core', 'ソーシャルログインの設定が完了していません。管理者に連絡してください。'));
            return $this->redirect($this->getLoginUrl());
        }

        return null;
    }
}
