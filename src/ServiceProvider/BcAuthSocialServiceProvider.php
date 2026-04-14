<?php
declare(strict_types=1);

namespace BcAuthSocial\ServiceProvider;

use BcAuthSocial\Service\BcAuthSocialConfigsService;
use BcAuthSocial\Service\BcAuthSocialConfigsServiceInterface;
use Cake\Core\ServiceProvider;

class BcAuthSocialServiceProvider extends ServiceProvider
{
    protected array $provides = [
        BcAuthSocialConfigsServiceInterface::class,
    ];

    public function services($container): void
    {
        $container->add(BcAuthSocialConfigsServiceInterface::class, BcAuthSocialConfigsService::class);
    }
}
