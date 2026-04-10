<?php
declare(strict_types=1);

namespace BcSocialAuth\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use BaserCore\Utility\BcUtil;
use BcAuthCommon\Service\AuthLoginService;
use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcSocialAuth\Service\SocialAuthConfigsService;
use BcSocialAuth\Service\SocialAuthService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * AuthController (Admin)
 *
 * Admin プレフィックスにおける外部プロバイダ認証のエンドポイントを提供します。
 */
class AuthController extends BcAdminAppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated([
            'login',
            'callback',
            'link_candidate',
            'confirm_link',
            'cancel_link',
        ]);
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->FormProtection->setConfig('unlockedActions', [
            'confirm_link',
            'cancel_link',
        ]);
    }

    public function login(string $provider): Response
    {
        $this->request->allowMethod('get');

        $setupRedirect = $this->guardSetup();
        if ($setupRedirect) {
            return $setupRedirect;
        }

        $service = new SocialAuthService();
        if (!ProviderAdapterRegistry::getInstance()->has($provider) || !$service->isProviderAvailable($provider)) {
            $this->BcMessage->setError(__d('baser_core', '未対応または未設定のプロバイダです。'));
            return $this->redirect([
                'prefix' => 'Admin',
                'plugin' => false,
                'controller' => 'Users',
                'action' => 'login',
            ]);
        }

        try {
            $authUrl = $service->buildAuthorizationUrl($provider, 'Admin', $this->request->getQuery('redirect'));
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            return $this->redirect([
                'prefix' => 'Admin',
                'plugin' => false,
                'controller' => 'Users',
                'action' => 'login',
            ]);
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
            return $this->redirect($this->getLoginUrl());
        }

        $service = new SocialAuthService();
        $redirect = $service->getStoredRedirect($provider, 'Admin');

        try {
            $profile = $service->handleCallback($provider, 'Admin', $code, $state);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
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
            return $this->completeLogin($userId, $provider, $redirect);
        }

        $candidateUser = $service->resolveLinkCandidate($profile);
        if ($candidateUser) {
            $service->storePendingLinkCandidate($provider, 'Admin', $profile, (int)$candidateUser->id, $redirect);
            return $this->redirect([
                'prefix' => 'Admin',
                'plugin' => 'BcSocialAuth',
                'controller' => 'Auth',
                'action' => 'link_candidate',
                $provider,
            ]);
        }

        $this->BcMessage->setError(
            __d('baser_core', 'このアカウントは連携されていません。管理者に連絡してください。')
        );
        return $this->redirect($this->getLoginUrl());
    }

    public function link_candidate(string $provider)
    {
        $this->request->allowMethod('get');

        $service = new SocialAuthService();
        $candidate = $service->getPendingLinkCandidate($provider, 'Admin');
        if (!$candidate) {
            $this->BcMessage->setError(__d('baser_core', '連携候補情報が見つかりません。'));
            return $this->redirect($this->getLoginUrl());
        }

        $candidateUser = $candidate['candidateUser'];
        $profile = $candidate['profile'];
        $this->set(compact('provider', 'candidateUser', 'profile'));
    }

    public function confirm_link(string $provider): Response
    {
        $this->request->allowMethod('post');

        $service = new SocialAuthService();
        try {
            $result = $service->confirmPendingLinkCandidate($provider, 'Admin');
            $service->updateLastLogin($result['profile']);
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
            return $this->redirect($this->getLoginUrl());
        }

        return $this->completeLogin((int)$result['user_id'], $provider, $result['redirect'] ?? null);
    }

    public function cancel_link(string $provider): Response
    {
        $this->request->allowMethod('post');

        $service = new SocialAuthService();
        $service->clearPendingLinkCandidate($provider, 'Admin');
        $this->BcMessage->setInfo(__d('baser_core', '外部アカウント連携をキャンセルしました。'));

        return $this->redirect($this->getLoginUrl());
    }

    private function completeLogin(int $userId, string $provider, ?string $redirect): Response
    {
        $loginService = new AuthLoginService();

        try {
            $loginResult = $loginService->login([
                'user_id' => $userId,
                'prefix' => 'Admin',
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
            'prefix' => 'Admin',
            'plugin' => false,
            'controller' => 'Users',
            'action' => 'login',
        ];
    }

    private function getAccountsUrl(): array
    {
        return [
            'prefix' => 'Admin',
            'plugin' => 'BcSocialAuth',
            'controller' => 'SocialAuthAccounts',
            'action' => 'index',
        ];
    }

    private function guardSetup(): ?Response
    {
        $configService = new SocialAuthConfigsService();

        if (!$configService->hasInstalledSchema()) {
            $this->BcMessage->setError(__d('baser_core', 'BcSocialAuth の初期化が完了していません。先に設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        if (!$configService->hasAnyAvailableProvider()) {
            $this->BcMessage->setError(__d('baser_core', '利用可能なソーシャルログイン provider が設定されていません。設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        return null;
    }
}

