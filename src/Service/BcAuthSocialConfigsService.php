<?php
declare(strict_types=1);

namespace BcAuthSocial\Service;

use BaserCore\Service\SiteConfigsServiceInterface;
use BaserCore\Utility\BcContainerTrait;
use BcAuthSocial\Model\Entity\BcAuthSocialConfig;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class BcAuthSocialConfigsService implements BcAuthSocialConfigsServiceInterface
{
    use BcContainerTrait;

    private SiteConfigsServiceInterface $siteConfigsService;

    private ?EntityInterface $entity = null;

    public function __construct()
    {
        $this->siteConfigsService = $this->getService(SiteConfigsServiceInterface::class);
    }

    /**
     * setting.php の BcAuthSocial キーからプロバイダー識別子の一覧を返す。
     * 各プロバイダーエントリーは 'label' キーを持つ配列として識別する。
     */
    private function getProviderList(): array
    {
        $registry = Configure::read('BcAuthSocial') ?? [];
        return array_keys(array_filter($registry, fn($v) => is_array($v) && isset($v['label'])));
    }

    /**
     * 指定プロバイダーの Configure 設定と envPrefix を返す。
     */
    private function getProviderCfg(string $provider): array
    {
        $cfg = Configure::read('BcAuthSocial.' . $provider) ?? [];
        if (!isset($cfg['envPrefix'])) {
            $cfg['envPrefix'] = 'BC_SOCIAL_AUTH_' . strtoupper($provider);
        }
        return $cfg;
    }

    public function get(): EntityInterface
    {
        if ($this->entity) {
            return $this->entity;
        }

        $data = [];
        foreach ($this->getProviderList() as $provider) {
            $cfg = $this->getProviderCfg($provider);
            $prefix = $cfg['envPrefix'];
            $data[$provider . '_enabled'] = filter_var(env($prefix . '_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
            $data[$provider . '_client_id'] = (string) env($prefix . '_CLIENT_ID', '');
            $data[$provider . '_client_secret'] = (string) env($prefix . '_CLIENT_SECRET', '');
            $data[$provider . '_redirect_uri'] = (string) env($prefix . '_REDIRECT_URI', '');
        }

        $this->entity = new BcAuthSocialConfig($data, ['markClean' => true]);
        $this->entity->setSource('BcAuthSocial.BcAuthSocialConfigs');
        return $this->entity;
    }

    public function update(array $postData): EntityInterface
    {
        $data = [];
        foreach ($this->getProviderList() as $provider) {
            $data[$provider . '_enabled'] = !empty($postData[$provider . '_enabled']);
            $data[$provider . '_client_id'] = trim((string) ($postData[$provider . '_client_id'] ?? ''));
            $data[$provider . '_client_secret'] = trim((string) ($postData[$provider . '_client_secret'] ?? ''));
            $data[$provider . '_redirect_uri'] = trim((string) ($postData[$provider . '_redirect_uri'] ?? ''));
        }

        $entity = new BcAuthSocialConfig($data);
        $entity->setSource('BcAuthSocial.BcAuthSocialConfigs');

        if (!$this->siteConfigsService->isWritableEnv()) {
            $entity->setError('env', [__d('baser_core', '.env に書き込みできないため、この画面から保存できません。')]);
            return $entity;
        }

        foreach ($this->getProviderList() as $provider) {
            if (!$data[$provider . '_enabled']) {
                continue;
            }
            if ($data[$provider . '_client_id'] === '') {
                $entity->setError($provider . '_client_id', [__d('baser_core', '有効化する場合は client ID を入力してください。')]);
            }
            if ($data[$provider . '_client_secret'] === '') {
                $entity->setError($provider . '_client_secret', [__d('baser_core', '有効化する場合は client secret を入力してください。')]);
            }
            if ($data[$provider . '_redirect_uri'] !== '' && !filter_var($data[$provider . '_redirect_uri'], FILTER_VALIDATE_URL)) {
                $entity->setError($provider . '_redirect_uri', [__d('baser_core', '有効な URL を入力してください。')]);
            }
        }

        if ($entity->hasErrors()) {
            return $entity;
        }

        foreach ($this->getProviderList() as $provider) {
            $prefix = $this->getProviderCfg($provider)['envPrefix'];
            $this->siteConfigsService->putEnv($prefix . '_ENABLED', $data[$provider . '_enabled'] ? 'true' : 'false');
            $this->siteConfigsService->putEnv($prefix . '_CLIENT_ID', $data[$provider . '_client_id']);
            $this->siteConfigsService->putEnv($prefix . '_CLIENT_SECRET', $data[$provider . '_client_secret']);
            $this->siteConfigsService->putEnv($prefix . '_REDIRECT_URI', $data[$provider . '_redirect_uri']);
        }

        $this->entity = null;
        return $this->get();
    }

    public function getViewVarsForIndex(EntityInterface $config): array
    {
        return [
            'socialAuthConfig' => $config,
            'isWritableEnv' => $this->siteConfigsService->isWritableEnv(),
            'hasInstalledSchema' => $this->hasInstalledSchema(),
            'hasAnyAvailableProvider' => $this->hasAnyAvailableProvider(),
            'setupUrl' => $this->getSetupUrl(),
            'providerLabels' => $this->buildProviderLabels(),
            'envKeys' => $this->buildEnvKeys(),
            'callbackUrls' => $this->buildCallbackUrls(),
            'providerGuides' => $this->buildProviderGuides(),
        ];
    }

    public function hasInstalledSchema(): bool
    {
        $connection = TableRegistry::getTableLocator()->get('BaserCore.Plugins')->getConnection();
        $tables = $connection->getSchemaCollection()->listTables();

        return in_array('bc_auth_provider_links', $tables, true);
    }

    public function hasAnyAvailableProvider(): bool
    {
        foreach ($this->getProviderList() as $provider) {
            $prefix = $this->getProviderCfg($provider)['envPrefix'];
            if (!filter_var(env($prefix . '_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            if ((string) env($prefix . '_CLIENT_ID', '') === '') {
                continue;
            }
            if ((string) env($prefix . '_CLIENT_SECRET', '') === '') {
                continue;
            }

            return true;
        }

        return false;
    }

    private function buildProviderLabels(): array
    {
        $labels = [];
        foreach ($this->getProviderList() as $provider) {
            $labels[$provider] = $this->getProviderCfg($provider)['label'] ?? $provider;
        }
        return $labels;
    }

    private function buildEnvKeys(): array
    {
        $keys = [];
        foreach ($this->getProviderList() as $provider) {
            $prefix = $this->getProviderCfg($provider)['envPrefix'];
            $keys[$provider] = [
                'enabled'       => $prefix . '_ENABLED',
                'client_id'     => $prefix . '_CLIENT_ID',
                'client_secret' => $prefix . '_CLIENT_SECRET',
                'redirect_uri'  => $prefix . '_REDIRECT_URI',
            ];
        }
        return $keys;
    }

    private function buildCallbackUrls(): array
    {
        $urls = [];
        foreach ($this->getProviderList() as $provider) {
            $urls[$provider] = $this->buildCallbackUrl($provider);
        }
        return $urls;
    }

    private function buildProviderGuides(): array
    {
        $guides = [];
        foreach ($this->getProviderList() as $provider) {
            $guide = $this->getProviderCfg($provider)['guide'] ?? null;
            if ($guide !== null) {
                $guides[$provider] = $guide;
            }
        }
        return $guides;
    }

    public function getSetupUrl(): array
    {
        return [
            'prefix' => 'Admin',
            'plugin' => 'BcAuthSocial',
            'controller' => 'BcAuthSocialConfigs',
            'action' => 'index',
        ];
    }

    private function buildCallbackUrl(string $provider): string
    {
        return Router::url([
            'plugin' => 'BcAuthSocial',
            'prefix' => 'Admin',
            'controller' => 'BcAuth',
            'action' => 'callback',
            $provider,
        ], true);
    }
}
