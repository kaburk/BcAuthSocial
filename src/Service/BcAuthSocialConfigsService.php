<?php
declare(strict_types=1);

namespace BcAuthSocial\Service;

use BaserCore\Service\SiteConfigsServiceInterface;
use BaserCore\Utility\BcContainerTrait;
use BcAuthSocial\Model\Entity\BcAuthSocialConfig;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class BcAuthSocialConfigsService implements BcAuthSocialConfigsServiceInterface
{
    use BcContainerTrait;

    private const PROVIDERS = ['google', 'x'];

    private SiteConfigsServiceInterface $siteConfigsService;

    private ?EntityInterface $entity = null;

    public function __construct()
    {
        $this->siteConfigsService = $this->getService(SiteConfigsServiceInterface::class);
    }

    public function get(): EntityInterface
    {
        if ($this->entity) {
            return $this->entity;
        }

        $data = [];
        foreach (self::PROVIDERS as $provider) {
            $upperProvider = strtoupper($provider);
            $data[$provider . '_enabled'] = filter_var(env('BC_SOCIAL_AUTH_' . $upperProvider . '_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
            $data[$provider . '_client_id'] = (string) env('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_ID', '');
            $data[$provider . '_client_secret'] = (string) env('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_SECRET', '');
            $data[$provider . '_redirect_uri'] = (string) env('BC_SOCIAL_AUTH_' . $upperProvider . '_REDIRECT_URI', '');
        }

        $this->entity = new BcAuthSocialConfig($data, ['markClean' => true]);
        $this->entity->setSource('BcAuthSocial.BcAuthSocialConfigs');
        return $this->entity;
    }

    public function update(array $postData): EntityInterface
    {
        $data = [];
        foreach (self::PROVIDERS as $provider) {
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

        foreach (self::PROVIDERS as $provider) {
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

        foreach (self::PROVIDERS as $provider) {
            $upperProvider = strtoupper($provider);
            $this->siteConfigsService->putEnv('BC_SOCIAL_AUTH_' . $upperProvider . '_ENABLED', $data[$provider . '_enabled'] ? 'true' : 'false');
            $this->siteConfigsService->putEnv('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_ID', $data[$provider . '_client_id']);
            $this->siteConfigsService->putEnv('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_SECRET', $data[$provider . '_client_secret']);
            $this->siteConfigsService->putEnv('BC_SOCIAL_AUTH_' . $upperProvider . '_REDIRECT_URI', $data[$provider . '_redirect_uri']);
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
            'providerLabels' => [
                'google' => 'Google',
                'x' => 'X',
            ],
            'envKeys' => [
                'google' => [
                    'enabled' => 'BC_SOCIAL_AUTH_GOOGLE_ENABLED',
                    'client_id' => 'BC_SOCIAL_AUTH_GOOGLE_CLIENT_ID',
                    'client_secret' => 'BC_SOCIAL_AUTH_GOOGLE_CLIENT_SECRET',
                    'redirect_uri' => 'BC_SOCIAL_AUTH_GOOGLE_REDIRECT_URI',
                ],
                'x' => [
                    'enabled' => 'BC_SOCIAL_AUTH_X_ENABLED',
                    'client_id' => 'BC_SOCIAL_AUTH_X_CLIENT_ID',
                    'client_secret' => 'BC_SOCIAL_AUTH_X_CLIENT_SECRET',
                    'redirect_uri' => 'BC_SOCIAL_AUTH_X_REDIRECT_URI',
                ],
            ],
            'callbackUrls' => [
                'google' => $this->buildCallbackUrl('google'),
                'x' => $this->buildCallbackUrl('x'),
            ],
        ];
    }

    public function hasInstalledSchema(): bool
    {
        $connection = TableRegistry::getTableLocator()->get('BaserCore.Plugins')->getConnection();
        $tables = $connection->getSchemaCollection()->listTables();

        return in_array('auth_provider_links', $tables, true);
    }

    public function hasAnyAvailableProvider(): bool
    {
        foreach (self::PROVIDERS as $provider) {
            $upperProvider = strtoupper($provider);
            if (!filter_var(env('BC_SOCIAL_AUTH_' . $upperProvider . '_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            if ((string) env('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_ID', '') === '') {
                continue;
            }
            if ((string) env('BC_SOCIAL_AUTH_' . $upperProvider . '_CLIENT_SECRET', '') === '') {
                continue;
            }

            return true;
        }

        return false;
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
            'prefix' => 'Admin',
            'plugin' => 'BcAuthSocial',
            'controller' => 'Auth',
            'action' => 'callback',
            $provider,
        ], true);
    }
}
