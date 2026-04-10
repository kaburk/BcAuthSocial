<?php
declare(strict_types=1);

namespace BcSocialAuth;

use BaserCore\BcPlugin;
use BcSocialAuth\Adapter\GoogleProviderAdapter;
use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcSocialAuth\Adapter\XProviderAdapter;
use BcSocialAuth\ServiceProvider\BcSocialAuthServiceProvider;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;

/**
 * plugin for BcSocialAuth
 */
class BcSocialAuthPlugin extends BcPlugin
{
    public function services(ContainerInterface $container): void
    {
        $container->addServiceProvider(new BcSocialAuthServiceProvider());
    }

    /**
     * ProviderAdapterRegistry に Google と X を登録する
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        $registry = ProviderAdapterRegistry::getInstance();
        $registry->register(new GoogleProviderAdapter());
        $registry->register(new XProviderAdapter());
    }
}
