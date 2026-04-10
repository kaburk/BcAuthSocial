<?php

declare(strict_types=1);

return [
    'BcApp' => [
        'adminNavigation' => [
            'Plugins' => [
                'menus' => [
                    'BcSocialAuthConfigs' => [
                        'title' => __d('baser_core', 'ソーシャル認証設定'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcSocialAuth',
                            'controller' => 'SocialAuthConfigs',
                            'action' => 'index',
                        ],
                    ],
                    'BcSocialAuthAccounts' => [
                        'title' => __d('baser_core', '連携済みアカウント'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcSocialAuth',
                            'controller' => 'SocialAuthAccounts',
                            'action' => 'index',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'BcSocialAuth' => [
        'providers' => [
            'google' => [
                'enabled' => filter_var(env('BC_SOCIAL_AUTH_GOOGLE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                'clientId' => env('BC_SOCIAL_AUTH_GOOGLE_CLIENT_ID', ''),
                'clientSecret' => env('BC_SOCIAL_AUTH_GOOGLE_CLIENT_SECRET', ''),
                'redirectUri' => env('BC_SOCIAL_AUTH_GOOGLE_REDIRECT_URI', ''),
                'allowLinkCandidate' => true,
            ],
            'x' => [
                'enabled' => filter_var(env('BC_SOCIAL_AUTH_X_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                'clientId' => env('BC_SOCIAL_AUTH_X_CLIENT_ID', ''),
                'clientSecret' => env('BC_SOCIAL_AUTH_X_CLIENT_SECRET', ''),
                'redirectUri' => env('BC_SOCIAL_AUTH_X_REDIRECT_URI', ''),
                'allowLinkCandidate' => false,
            ],
        ],
    ],
];
