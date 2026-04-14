<?php
/**
 * @var \BaserCore\View\BcAdminAppView $this
 * @var array $links
 * @var array $providerLabels
 * @var array $availableProviders
 */

$this->BcAdmin->setTitle(__d('baser_core', '連携済みアカウント'));
?>

<?php $this->BcBaser->flash() ?>

<section class="bca-section" data-bca-section-type="form-group">
  <h2 class="bca-main__heading" data-bca-heading-size="lg"><?php echo __d('baser_core', 'アカウントを追加で連携する') ?></h2>
  <div class="bca-section__content">
    <p><?php echo __d('baser_core', '次のボタンから外部プロバイダ認証を行うと、現在ログイン中のユーザーに連携されます。') ?></p>
    <?php if ($availableProviders): ?>
      <div class="bca-actions">
        <div class="bca-actions__before"></div>
        <div class="bca-actions__main">
          <?php foreach ($availableProviders as $provider => $label): ?>
            <a class="bca-btn bca-actions__item" href="<?php echo $this->Url->build([
              'prefix' => 'Admin',
              'plugin' => 'BcAuthSocial',
              'controller' => 'Auth',
              'action' => 'login',
              $provider,
              '?' => ['redirect' => $this->Url->build(['action' => 'index'])],
            ]) ?>"><?php echo __d('baser_core', '{0} を連携', $label) ?></a>
          <?php endforeach ?>
        </div>
        <div class="bca-actions__sub"></div>
      </div>
    <?php else: ?>
      <p><?php echo __d('baser_core', '利用可能な provider がありません。先にソーシャル認証設定を確認してください。') ?></p>
    <?php endif ?>
  </div>
</section>

<section class="bca-section" data-bca-section-type="form-group">
  <h2 class="bca-main__heading" data-bca-heading-size="lg"><?php echo __d('baser_core', '現在の連携一覧') ?></h2>
  <?php if ($links): ?>
    <table class="list-table bca-table-listup">
      <thead class="bca-table-listup__thead">
        <tr>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', 'プロバイダ') ?></th>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', '外部アカウント') ?></th>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', 'メールアドレス') ?></th>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', '最終利用') ?></th>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', '連携方法') ?></th>
          <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', '操作') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($links as $link): ?>
        <tr>
          <td class="bca-table-listup__tbody-td"><?php echo h($providerLabels[$link->provider] ?? ucfirst((string) $link->provider)) ?></td>
          <td class="bca-table-listup__tbody-td">
            <div><?php echo h($link->name ?: $link->provider_user_id) ?></div>
            <small><?php echo h($link->provider_user_id) ?></small>
          </td>
          <td class="bca-table-listup__tbody-td"><?php echo h($link->email ?: '-') ?></td>
          <td class="bca-table-listup__tbody-td"><?php echo h($link->last_login ? $link->last_login->i18nFormat('yyyy-MM-dd HH:mm') : '-') ?></td>
          <td class="bca-table-listup__tbody-td"><?php echo h($link->linked_by) ?></td>
          <td class="bca-table-listup__tbody-td">
            <?php echo $this->BcAdminForm->postLink(__d('baser_core', '連携解除'), [
              'action' => 'unlink',
              $link->id,
            ], [
              'block' => true,
              'confirm' => __d('baser_core', '{0} との連携を解除します。よろしいですか？', $providerLabels[$link->provider] ?? $link->provider),
              'class' => 'bca-btn bca-actions__item',
              'data-bca-btn-color' => 'danger',
            ]) ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <p><?php echo __d('baser_core', '現在連携されている外部アカウントはありません。') ?></p>
  <?php endif ?>
</section>

<?= $this->fetch('postLink') ?>
