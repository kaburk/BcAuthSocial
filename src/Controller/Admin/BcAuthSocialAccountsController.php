<?php
declare(strict_types=1);

namespace BcAuthSocial\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use BaserCore\Utility\BcUtil;
use BcAuthSocial\Service\BcAuthSocialConfigsService;
use BcAuthSocial\Service\BcAuthSocialService;
use Cake\Core\Configure;
use Cake\Http\Response;

class BcAuthSocialAccountsController extends BcAdminAppController
{
    public function index()
    {
        $setupRedirect = $this->guardSetup();
        if ($setupRedirect) {
            return $setupRedirect;
        }

        $loginUser = BcUtil::loginUser();
        $service = new BcAuthSocialService();
        $links = $service->getUserLinks((int) $loginUser->id, 'Admin');
        $registry = Configure::read('BcAuthSocial') ?? [];
        $providers = [];
        foreach ($registry as $provider => $cfg) {
            if (is_array($cfg) && isset($cfg['label'])) {
                $providers[$provider] = [
                    'label' => $cfg['label'],
                    'order' => (int)($cfg['order'] ?? 9999),
                ];
            }
        }
        uasort($providers, fn(array $a, array $b) => $a['order'] <=> $b['order']);
        $providerLabels = [];
        foreach ($providers as $provider => $providerConfig) {
            $providerLabels[$provider] = $providerConfig['label'];
        }
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
        $service = new BcAuthSocialService();

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
        $configService = new BcAuthSocialConfigsService();

        if (!$configService->hasInstalledSchema()) {
            $this->BcMessage->setError(__d('baser_core', 'BcAuthSocial の初期化が完了していません。先に設定画面を確認してください。'));
            return $this->redirect($configService->getSetupUrl());
        }

        return null;
    }
}
