<?php
return [
    'type' => 'Plugin',
    'title' => __d('baser_core', 'ソーシャル認証'),
    'description' => __d('baser_core', 'Google・X などの外部プロバイダを使って baserCMS 管理画面にログインできるようにするプラグインです。'),
    'author' => 'baserCMS',
    'url' => 'https://basercms.net/',
    'adminLink' => [
        'prefix' => 'Admin',
        'plugin' => 'BcSocialAuth',
        'controller' => 'SocialAuthConfigs',
        'action' => 'index',
    ],
    'installMessage' => __d('baser_core', 'インストール後、メニューの「ソーシャル認証設定」から Google / X の client ID・client secret を設定してください。'),
];
