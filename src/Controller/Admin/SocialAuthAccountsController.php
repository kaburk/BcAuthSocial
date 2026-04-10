<?php
declare(strict_types=1);

namespace BcSocialAuth\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use BaserCore\Utility\BcUtil;
use BcSocialAuth\Service\SocialAuthConfigsService;
use BcSocialAuth\Service\SocialAuthService;
use Cake\Http\Response;

class SocialAuthAccountsController extends BcAdminAppController
{
    public function index()
    {
        $setupRedirect = $this->guardSetup();
        if ($setupRedirect) {
            return $setupRedirect;
        }

        $loginUser = BcUtil::loginUser();
        $service = new SocialAuthService();
        $links = $service->getUserLinks((int) $loginUser->id, 'Admin');
        $providerLabels = [
            'google' => 'Google',
            'x' => 'X',
        ];
        $availableProviders = [];
        foreach ($providerLabels as $provider => $label) {
            if ($service->isProviderAvailable($provider)) {
                $availableProviders[$provider] = $label;
            }
        }

        $this->set(compact('links', 'providerLabels', 'availableProviders'));
    }

    public function unlink(int $id): Response
    {
        $this->request->allowMethod(['post']);

        $loginUser = BcUtil::loginUser();
        $service = new SocialAuthService();

        try {
            $service->unlinkUserLink((int) $loginUser->id, $id, 'Admin');
            $this->BcMessage->setSuccess(__d('baser_core', '外部アカウント連携を解除しました。'));
        } catch (\RuntimeException $e) {
            $this->BcMessage->setError($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    private function guardSetup(): ?Response
    {
        $configService = new SocialAuthConfigsService();

        if (!$configService->hasInstalledSchema()) {
            $this->BcMessage->setError(__d('baser_core', 'BcSocialAuth の初期化が完了していません。先に設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        return null;
    }
}
