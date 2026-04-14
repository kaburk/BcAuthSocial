<?php
/**
 * @var \App\View\AppView $this
 * @var string $provider
 * @var \BaserCore\Model\Entity\User $candidateUser
 * @var \BcAuthSocial\Adapter\ProviderUserProfile $profile
 */

$this->BcBaser->setTitle(__d('baser_core', '外部アカウント連携の確認'));
?>

<div class="bs-contents-body">
  <?php $this->BcBaser->flash() ?>

  <div class="section">
    <p><?= __d('baser_core', '{0} アカウントでのログインに成功しました。', h(ucfirst($provider))) ?></p>
    <p><?= __d('baser_core', '次の baserCMS ユーザーに連携してログインします。') ?></p>
  </div>

  <table class="list-table bs-table-listup">
    <tr>
      <th><?= __d('baser_core', 'プロバイダ') ?></th>
      <td><?= h($provider) ?></td>
    </tr>
    <tr>
      <th><?= __d('baser_core', '外部アカウント名') ?></th>
      <td><?= h($profile->name ?: $profile->providerUserId) ?></td>
    </tr>
    <tr>
      <th><?= __d('baser_core', '外部メールアドレス') ?></th>
      <td><?= h($profile->email ?: '-') ?></td>
    </tr>
    <tr>
      <th><?= __d('baser_core', '連携先ユーザー') ?></th>
      <td><?= h($candidateUser->name ?: $candidateUser->email) ?></td>
    </tr>
    <tr>
      <th><?= __d('baser_core', '連携先メールアドレス') ?></th>
      <td><?= h($candidateUser->email) ?></td>
    </tr>
  </table>

  <div class="submit bs-actions">
    <?= $this->BcAdminForm->create(null, ['url' => ['action' => 'confirmLink', $provider]]) ?>
    <?= $this->BcAdminForm->button(__d('baser_core', '連携してログイン'), [
      'class' => 'bs-btn bs-btn--login',
      'type' => 'submit',
    ]) ?>
    <?= $this->BcAdminForm->end() ?>
  </div>

  <div class="submit bs-actions" style="margin-top: 12px;">
    <?= $this->BcAdminForm->create(null, ['url' => ['action' => 'cancelLink', $provider]]) ?>
    <?= $this->BcAdminForm->button(__d('baser_core', 'キャンセル'), [
      'class' => 'bs-btn',
      'type' => 'submit',
    ]) ?>
    <?= $this->BcAdminForm->end() ?>
  </div>
</div>
