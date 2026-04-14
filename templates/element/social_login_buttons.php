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
    'github' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.49.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.52 2.34 1.08 2.91.83.09-.65.35-1.08.63-1.33-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.56 9.56 0 0 1 12 6.8c.85 0 1.71.11 2.51.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.69-4.57 4.93.36.31.68.92.68 1.85v2.74c0 .27.18.58.69.48A10.01 10.01 0 0 0 22 12c0-5.52-4.48-10-10-10z"/></svg>',
    'line' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#06C755" d="M12 2C6.48 2 2 5.8 2 10.2c0 3.6 2.37 6.7 5.9 8.22l-.45 2.2a.4.4 0 0 0 .58.43l2.72-1.65c.42.06.85.1 1.25.1 5.52 0 10-3.58 10-8S17.52 2 12 2z"/><path fill="#fff" d="M8 12.1V8.8H9v2.4h1.9v.9H8zm3 0V8.8h1v3.3h-1zm1.6 0V8.8h2.8v.9h-1.8v.5h1.6v.9h-1.6v.5h1.8v.9h-2.8zm3.6 0V8.8h1v3.3h-1z"/></svg>',
    'microsoft' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#F25022" d="M2 2h9.5v9.5H2z"/><path fill="#7FBA00" d="M12.5 2H22v9.5h-9.5z"/><path fill="#00A4EF" d="M2 12.5h9.5V22H2z"/><path fill="#FFB900" d="M12.5 12.5H22V22h-9.5z"/></svg>',
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
        'controller' => 'BcAuth',
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
