<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

/** @var RouteBuilder $routes */
$routes->plugin(
    'BcSocialAuth',
    ['path' => '/bc-social-auth'],
    function (RouteBuilder $routes) {
        $routes->prefix(
            'Admin',
            ['path' => '/baser/admin'],
            function (RouteBuilder $routes) {
                $routes->connect(
                    '/social_auth_configs',
                    ['controller' => 'SocialAuthConfigs', 'action' => 'index']
                );

                $routes->connect(
                    '/social_auth_accounts',
                    ['controller' => 'SocialAuthAccounts', 'action' => 'index']
                );

                $routes->post(
                    '/social_auth_accounts/unlink/:id',
                    ['controller' => 'SocialAuthAccounts', 'action' => 'unlink']
                )
                    ->setPatterns(['id' => '[0-9]+']);

                $routes->get(
                    '/auth/login/:provider',
                    ['controller' => 'Auth', 'action' => 'login'],
                    'bc_social_auth_admin_login'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->get(
                    '/auth/callback/:provider',
                    ['controller' => 'Auth', 'action' => 'callback'],
                    'bc_social_auth_admin_callback'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->get(
                    '/auth/link-candidate/:provider',
                    ['controller' => 'Auth', 'action' => 'linkCandidate'],
                    'bc_social_auth_admin_link_candidate'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->post(
                    '/auth/confirm-link/:provider',
                    ['controller' => 'Auth', 'action' => 'confirmLink'],
                    'bc_social_auth_admin_confirm_link'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->post(
                    '/auth/cancel-link/:provider',
                    ['controller' => 'Auth', 'action' => 'cancelLink'],
                    'bc_social_auth_admin_cancel_link'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
            }
        );

        $routes->get(
            '/auth/login/:provider',
            ['controller' => 'Auth', 'action' => 'login'],
            'bc_social_auth_front_login'
        )
            ->setPatterns(['provider' => '[a-z0-9_-]+']);

        $routes->get(
            '/auth/callback/:provider',
            ['controller' => 'Auth', 'action' => 'callback'],
            'bc_social_auth_front_callback'
        )
            ->setPatterns(['provider' => '[a-z0-9_-]+']);

        $routes->get(
            '/auth/link-candidate/:provider',
            ['controller' => 'Auth', 'action' => 'linkCandidate'],
            'bc_social_auth_front_link_candidate'
        )
            ->setPatterns(['provider' => '[a-z0-9_-]+']);

        $routes->post(
            '/auth/confirm-link/:provider',
            ['controller' => 'Auth', 'action' => 'confirmLink'],
            'bc_social_auth_front_confirm_link'
        )
            ->setPatterns(['provider' => '[a-z0-9_-]+']);

        $routes->post(
            '/auth/cancel-link/:provider',
            ['controller' => 'Auth', 'action' => 'cancelLink'],
            'bc_social_auth_front_cancel_link'
        )
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
    }
);
