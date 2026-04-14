<?php

use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Service\BcAuthSocialService;

/**
 * ログイン画面でインクルードするソーシャルログインボタン一覧
 *
 * 利用方法（ログインテンプレートに追加）:
 *   <?= $this->element('BcAuthSocial.social_login_buttons', ['prefix' => 'Admin']) ?>
 *
 * @var \App\View\AppView $this
 * @var string $prefix
 */

$registry = ProviderAdapterRegistry::getInstance();
$adapters = $registry->all();
$service = new BcAuthSocialService();
$redirect = $this->request->getQuery('redirect');

$providerIcons = [
    'google' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.2-.9 2.3-1.9 3.1l3 2.3c1.8-1.7 2.8-4.1 2.8-7 0-.7-.1-1.5-.2-2.2H12z"/><path fill="#34A853" d="M12 21c2.6 0 4.8-.9 6.4-2.5l-3-2.3c-.8.6-1.9 1-3.4 1-2.6 0-4.9-1.8-5.7-4.2l-3.1 2.4C4.8 18.8 8.1 21 12 21z"/><path fill="#4A90E2" d="M6.3 13c-.2-.6-.3-1.3-.3-2s.1-1.4.3-2L3.2 6.6C2.4 8.1 2 9.5 2 11s.4 2.9 1.2 4.4L6.3 13z"/><path fill="#FBBC05" d="M12 4.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.8 1.8 14.6 1 12 1 8.1 1 4.8 3.2 3.2 6.6L6.3 9C7.1 6.6 9.4 4.8 12 4.8z"/></svg>',
    'x' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="currentColor" d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 15.7 6.5 22H3.4l7.3-8.3L1 2h6.5l4.5 5.7L18.9 2zm-1.1 18h1.7L6.6 3.9H4.8L17.8 20z"/></svg>',
];

if (empty($adapters)) {
    return;
}

$isAdmin = ($prefix ?? 'Admin') === 'Admin';
?>

<div class="social-login-buttons<?= $isAdmin ? ' social-login-buttons--admin' : '' ?>">
<?php foreach ($adapters as $provider => $adapter): ?>
    <?php if (!$service->isProviderAvailable($provider)) continue; ?>
    <?php
    $loginUrl = $this->Url->build(array_filter([
        'plugin' => 'BcAuthSocial',
        'prefix' => $isAdmin ? 'Admin' : false,
        'controller' => 'Auth',
        'action' => 'login',
        $provider,
        '?' => array_filter(['redirect' => $redirect]),
    ], fn($value) => $value !== null));
    ?>
        <?php if ($isAdmin): ?>
        <div class="submit bca-login-form-btn-group bca-login-form-btn-group--alt">
            <a href="<?= h($loginUrl) ?>" class="bca-btn bca-login-alt-methods__btn bca-login-alt-methods__btn--<?= h($provider) ?>" data-bca-btn-type="login">
                <span class="bca-login-alt-methods__icon" aria-hidden="true"><?= $providerIcons[$provider] ?? strtoupper(substr((string) $provider, 0, 1)) ?></span>
                <span class="bca-login-alt-methods__body">
                    <span class="bca-login-alt-methods__title"><?= h($adapter->getLabel()) ?></span>
                    <span class="bca-login-alt-methods__note"><?= __d('baser_core', 'ソーシャルアカウントを利用') ?></span>
                </span>
            </a>
        </div>
        <?php else: ?>
        <a href="<?= h($loginUrl) ?>" class="btn btn-social btn-<?= h($provider) ?>">
                <?= h($adapter->getLabel()) ?>
        </a>
        <?php endif; ?>
<?php endforeach; ?>
</div>
