<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

/** @var RouteBuilder $routes */

// Admin routes: /baser/admin/bc-auth-social/... （bc-blog 等と同じ prefix 外側パターン）
$routes->prefix(
    'Admin',
    ['path' => '/baser/admin'],
    function (RouteBuilder $routes) {
        $routes->plugin(
            'BcAuthSocial',
            ['path' => '/bc-auth-social'],
            function (RouteBuilder $routes) {
                $routes->connect('/social_auth_configs', ['controller' => 'BcAuthSocialConfigs', 'action' => 'index']);
                $routes->connect('/social_auth_accounts', ['controller' => 'BcAuthSocialAccounts', 'action' => 'index']);
                $routes->post('/social_auth_accounts/unlink/:id', ['controller' => 'BcAuthSocialAccounts', 'action' => 'unlink'])
                    ->setPatterns(['id' => '[0-9]+']);
                $routes->get('/bc_auth/login/:provider', ['controller' => 'BcAuth', 'action' => 'login'], 'bc_auth_social_admin_login')
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
                $routes->get('/bc_auth/callback/:provider', ['controller' => 'BcAuth', 'action' => 'callback'], 'bc_auth_social_admin_callback')
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
                $routes->get('/bc_auth/link-candidate/:provider', ['controller' => 'BcAuth', 'action' => 'linkCandidate'], 'bc_auth_social_admin_link_candidate')
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
                $routes->post('/bc_auth/confirm-link/:provider', ['controller' => 'BcAuth', 'action' => 'confirmLink'], 'bc_auth_social_admin_confirm_link')
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
                $routes->post('/bc_auth/cancel-link/:provider', ['controller' => 'BcAuth', 'action' => 'cancelLink'], 'bc_auth_social_admin_cancel_link')
                    ->setPatterns(['provider' => '[a-z0-9_-]+']);
            }
        );
    }
);

// Front routes: /bc-auth-social/auth/... （prefix なし）
$routes->plugin(
    'BcAuthSocial',
    ['path' => '/bc-auth-social'],
    function (RouteBuilder $routes) {
        $routes->get('/bc_auth/login/:provider', ['controller' => 'BcAuth', 'action' => 'login'], 'bc_auth_social_front_login')
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
        $routes->get('/bc_auth/callback/:provider', ['controller' => 'BcAuth', 'action' => 'callback'], 'bc_auth_social_front_callback')
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
        $routes->get('/bc_auth/link-candidate/:provider', ['controller' => 'BcAuth', 'action' => 'linkCandidate'], 'bc_auth_social_front_link_candidate')
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
        $routes->post('/bc_auth/confirm-link/:provider', ['controller' => 'BcAuth', 'action' => 'confirmLink'], 'bc_auth_social_front_confirm_link')
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
        $routes->post('/bc_auth/cancel-link/:provider', ['controller' => 'BcAuth', 'action' => 'cancelLink'], 'bc_auth_social_front_cancel_link')
            ->setPatterns(['provider' => '[a-z0-9_-]+']);
    }
);
