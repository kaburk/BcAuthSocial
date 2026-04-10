<?php

use BcSocialAuth\Adapter\ProviderAdapterRegistry;
use BcSocialAuth\Service\SocialAuthService;

/**
 * ログイン画面でインクルードするソーシャルログインボタン一覧
 *
 * 利用方法（ログインテンプレートに追加）:
 *   <?= $this->element('BcSocialAuth.social_login_buttons', ['prefix' => 'Admin']) ?>
 *
 * @var \App\View\AppView $this
 * @var string $prefix
 */

$registry = ProviderAdapterRegistry::getInstance();
$adapters = $registry->all();
$service = new SocialAuthService();
$redirect = $this->request->getQuery('redirect');

if (empty($adapters)) {
    return;
}
?>

<div class="social-login-buttons">
<?php foreach ($adapters as $provider => $adapter): ?>
    <?php if (!$service->isProviderAvailable($provider)) continue; ?>
    <a href="<?= $this->Url->build([
        'plugin' => 'BcSocialAuth',
        'prefix' => $prefix ?? 'Admin',
        'controller' => 'Auth',
        'action' => 'login',
        $provider,
        '?' => array_filter(['redirect' => $redirect]),
    ]) ?>" class="btn btn-social btn-<?= h($provider) ?>">
        <?= h($adapter->getLabel()) ?>
    </a>
<?php endforeach; ?>
</div>
