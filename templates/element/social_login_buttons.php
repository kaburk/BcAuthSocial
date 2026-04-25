<?php

use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use BcAuthSocial\Service\BcAuthSocialService;
use Cake\Core\Configure;

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

$this->BcBaser->js('BcAuthSocial.bc_auth_social', false, ['defer' => true]);

uasort($adapters, function ($leftAdapter, $rightAdapter) use ($registry) {
    $leftProvider = $leftAdapter->getProvider();
    $rightProvider = $rightAdapter->getProvider();
    $leftOrder = (int)(Configure::read('BcAuthSocial.' . $leftProvider . '.order') ?? 9999);
    $rightOrder = (int)(Configure::read('BcAuthSocial.' . $rightProvider . '.order') ?? 9999);
    if ($leftOrder === $rightOrder) {
        return strcmp($leftProvider, $rightProvider);
    }

    return $leftOrder <=> $rightOrder;
});

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
            <a href="<?= h($loginUrl) ?>" class="bca-btn bca-login-alt-methods__btn bca-login-alt-methods__btn--<?= h($provider) ?>" data-bca-btn-type="login" data-bc-social-login-button="true">
                <span class="bca-login-alt-methods__icon" aria-hidden="true"><?= Configure::read('BcAuthSocial.' . $provider . '.icon') ?? h(strtoupper(substr((string) $provider, 0, 1))) ?></span>
                <span class="bca-login-alt-methods__body">
                    <span class="bca-login-alt-methods__title"><?= h($adapter->getLabel()) ?></span>
                    <span class="bca-login-alt-methods__note"><?= __d('baser_core', 'ソーシャルアカウントを利用') ?></span>
                </span>
            </a>
        </div>
        <?php else: ?>
        <a href="<?= h($loginUrl) ?>" class="btn btn-social btn-<?= h($provider) ?>" data-bc-social-login-button="true">
                <?= h($adapter->getLabel()) ?>
        </a>
        <?php endif; ?>
<?php endforeach; ?>
</div>
