<?php
declare(strict_types=1);

namespace BcAuthSocial;

use BaserCore\BcPlugin;
use BcAuthCommon\Service\AuthEntryService;
use BcAuthSocial\Adapter\GoogleProviderAdapter;
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Adapter\XProviderAdapter;
use BcAuthSocial\Event\BcAuthSocialViewEventListener;
use BcAuthSocial\ServiceProvider\BcAuthSocialServiceProvider;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;

/**
 * plugin for BcAuthSocial
 */
class BcAuthSocialPlugin extends BcPlugin
{
    public function services(ContainerInterface $container): void
    {
        $container->addServiceProvider(new BcAuthSocialServiceProvider());
    }

    /**
     * ProviderAdapterRegistry に Google と X を登録する
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        EventManager::instance()->on(new BcAuthSocialViewEventListener());

        $registry = ProviderAdapterRegistry::getInstance();
        $registry->register(new GoogleProviderAdapter());
        $registry->register(new XProviderAdapter());

        AuthEntryService::getInstance()->register([
            'id'       => 'social',
            'label'    => 'ソーシャルログイン',
            'element'  => 'BcAuthSocial.social_login_buttons',
            'prefixes' => ['Admin', 'Front'],
            'order'    => 20,
        ]);
    }
}
