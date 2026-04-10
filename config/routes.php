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
                    '/auth/:provider/login',
                    ['controller' => 'Auth', 'action' => 'login'],
                    'bc_social_auth_admin_login'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->get(
                    '/auth/:provider/callback',
                    ['controller' => 'Auth', 'action' => 'callback'],
                    'bc_social_auth_admin_callback'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->get(
                    '/auth/:provider/link-candidate',
                    ['controller' => 'Auth', 'action' => 'link_candidate'],
                    'bc_social_auth_admin_link_candidate'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->post(
                    '/auth/:provider/confirm-link',
                    ['controller' => 'Auth', 'action' => 'confirm_link'],
                    'bc_social_auth_admin_confirm_link'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);

                $routes->post(
                    '/auth/:provider/cancel-link',
                    ['controller' => 'Auth', 'action' => 'cancel_link'],
                    'bc_social_auth_admin_cancel_link'
                )
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
            }
        );
    }
);
