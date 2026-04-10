<?php
declare(strict_types=1);

namespace BcSocialAuth\ServiceProvider;

use BcSocialAuth\Service\SocialAuthConfigsService;
use BcSocialAuth\Service\SocialAuthConfigsServiceInterface;
use Cake\Core\ServiceProvider;

class BcSocialAuthServiceProvider extends ServiceProvider
{
    protected array $provides = [
        SocialAuthConfigsServiceInterface::class,
    ];

    public function services($container): void
    {
        $container->add(SocialAuthConfigsServiceInterface::class, SocialAuthConfigsService::class);
    }
}
