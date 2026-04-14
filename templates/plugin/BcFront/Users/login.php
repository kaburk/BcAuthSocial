<?php
/**
 * baserCMS Front ログイン画面（BcAuthSocial オーバーライド）
 *
 * @var \App\View\AppView $this
 * @var bool $savedEnable
 */

use BcAuthPasskey\Service\PasskeyAuthService;
use BcAuthSocial\Adapter\ProviderAdapterRegistry;
use Cake\Core\Plugin;

$this->BcBaser->setTitle(__d('baser_core', 'ログイン'));
if (Plugin::isLoaded('BcAuthPasskey') && class_exists(PasskeyAuthService::class)) {
    $this->BcBaser->js('BcAuthPasskey.passkey-auth', false, ['defer' => true]);
}
?>

<div id="Login" class="bs-login">
  <div id="LoginInner">
    <?php $this->BcBaser->flash() ?>

    <h1 class="bs-login__title">
      <?php $this->BcBaser->contentsTitle() ?>
    </h1>
    <?= $this->BcAdminForm->create() ?>
    <div class="login-input bs-login-form-item">
      <?php echo $this->BcAdminForm->label('email', __d('baser_core', 'Eメール')) ?>
      <?= $this->BcAdminForm->control('email', [
        'type' => 'text',
        'tabindex' => 1,
        'autofocus' => true,
        'class' => 'bs-textbox__input',
      ]) ?>
    </div>
    <div class="login-input bs-login-form-item">
      <?php echo $this->BcAdminForm->label('password', __d('baser_core', 'パスワード')) ?>
      <?= $this->BcAdminForm->control('password', [
        'type' => 'password',
        'tabindex' => 2,
        'class' => 'bs-textbox__input',
      ]) ?>
    </div>
    <div class="submit bs-login-form-btn-group">
      <?= $this->BcAdminForm->button(__d('baser_core', 'ログイン'), [
        'type' => 'submit',
        'div' => false,
        'class' => 'bs-btn--login bs-btn',
        'data-bs-btn-type' => 'login',
        'id' => 'BtnLogin',
        'tabindex' => 4
      ]); ?>
    </div>
    <div class="clear login-etc bs-login-form-ctrl">
      <?php if ($savedEnable): ?>
        <div class="bs-login-form-checker">
          <?php echo $this->BcAdminForm->control('saved', [
            'type' => 'checkbox',
            'label' => __d('baser_core', 'ログイン状態を保存する'),
            'class' => 'bs-checkbox__input bs-login-form-checkbox',
            'tabindex' => 3
          ]); ?>
        </div>
      <?php endif; ?>
      <div class="bs-login-forgot-pass">
        <?php $this->BcBaser->link(__d('baser_core', 'パスワードを忘れた場合はこちら'), ['controller' => 'password_requests', 'action' => 'entry', $this->request->getParam('prefix') => true]) ?>
      </div>
    </div>
    <?= $this->BcAdminForm->end() ?>

    <div class="bs-login-alt-methods">
      <?php if (Plugin::isLoaded('BcAuthPasskey') && class_exists(PasskeyAuthService::class)): ?>
      <div class="bs-login-passkey">
        <button
          type="button"
          id="BtnPasskeyLogin"
          class="bs-btn bs-btn--passkey"
          data-login-url="<?= $this->Url->build([
            'plugin' => 'BcAuthPasskey',
            'prefix' => false,
            'controller' => 'Passkeys',
            'action' => 'login',
          ]) ?>"
          data-challenge-url="<?= $this->Url->build([
            'plugin' => 'BcAuthPasskey',
            'prefix' => false,
            'controller' => 'Passkeys',
            'action' => 'loginChallenge',
          ]) ?>"
        >
          <?= __d('baser_core', 'パスキーでログイン') ?>
        </button>
      </div>
      <?php endif; ?>

      <?php if (class_exists(ProviderAdapterRegistry::class) && !empty(ProviderAdapterRegistry::getInstance()->all())): ?>
        <div class="bs-login-divider">
          <span><?= __d('baser_core', 'または') ?></span>
        </div>
        <?= $this->element('BcAuthSocial.social_login_buttons', ['prefix' => false]) ?>
      <?php endif; ?>
    </div>

  </div>
</div>
