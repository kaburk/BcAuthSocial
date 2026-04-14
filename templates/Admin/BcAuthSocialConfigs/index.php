<?php
/**
 * @var \BaserCore\View\BcAdminAppView $this
 * @var \Cake\Datasource\EntityInterface $socialAuthConfig
 * @var bool $isWritableEnv
 * @var bool $hasInstalledSchema
 * @var bool $hasAnyAvailableProvider
 * @var array $providerLabels
 * @var array $envKeys
 * @var array $callbackUrls
 */

$this->BcAdmin->setTitle(__d('baser_core', 'ソーシャル認証設定'));
?>

<?php echo $this->BcAdminForm->create($socialAuthConfig, ['url' => ['action' => 'index']]) ?>
<?php echo $this->BcFormTable->dispatchBefore() ?>

<section class="bca-section" data-bca-section-type="form-group">
  <h2 class="bca-main__heading" data-bca-heading-size="lg"><?php echo __d('baser_core', 'セットアップ状態') ?></h2>
  <table class="form-table bca-form-table" data-bca-table-type="type2">
    <tr>
      <th class="col-head bca-form-table__label"><?php echo __d('baser_core', 'DB 初期化') ?></th>
      <td class="col-input bca-form-table__input">
        <?php echo $hasInstalledSchema ? __d('baser_core', '完了') : __d('baser_core', '未完了') ?>
        <?php if (!$hasInstalledSchema): ?>
          <div><small><?php echo __d('baser_core', 'plugin install をやり直すか、migration の適用状態を確認してください。') ?></small></div>
        <?php endif ?>
      </td>
    </tr>
    <tr>
      <th class="col-head bca-form-table__label"><?php echo __d('baser_core', 'provider 設定') ?></th>
      <td class="col-input bca-form-table__input">
        <?php echo $hasAnyAvailableProvider ? __d('baser_core', '利用可能') : __d('baser_core', '未設定') ?>
        <?php if (!$hasAnyAvailableProvider): ?>
          <div><small><?php echo __d('baser_core', 'Google または X の enabled / client ID / client secret を設定してください。') ?></small></div>
        <?php endif ?>
      </td>
    </tr>
  </table>
</section>

<section class="bca-section" data-bca-section-type="form-group">
  <h2 class="bca-main__heading" data-bca-heading-size="lg"><?php echo __d('baser_core', '設定方針') ?></h2>
  <div class="bca-section__content">
    <p><?php echo __d('baser_core', 'Google / X の provider 設定を管理します。redirect URI を空欄にした場合は、下記の自動生成 callback URL を利用します。') ?></p>
    <p><?php echo __d('baser_core', 'install 直後は、この画面で provider 設定を保存してから管理画面ログインで導線を確認します。') ?></p>
    <?php if (!$isWritableEnv): ?>
      <p><?php echo '⚠ ' . __d('baser_core', '.env に書き込みできないため、この画面では保存できません。必要なキーを手作業で設定してください。') ?></p>
    <?php endif ?>
    <?php echo $this->BcAdminForm->error('env') ?>
  </div>
</section>

<?php
$providerGuides = [
    'google' => [
        'console_url' => 'https://console.cloud.google.com/',
        'console_label' => 'Google Cloud Console',
        'steps' => [
            __d('baser_core', '<a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> にアクセスし、プロジェクトを作成（または選択）します。'),
            __d('baser_core', '左メニューの「APIとサービス」→「認証情報」を開きます。'),
            __d('baser_core', '「認証情報を作成」→「OAuth クライアント ID」を選択します。'),
            __d('baser_core', 'アプリケーションの種類で「ウェブ アプリケーション」を選択します。'),
            __d('baser_core', '「承認済みのリダイレクト URI」に下記の Callback URL を追加します。'),
            __d('baser_core', '作成後に表示される「クライアント ID」と「クライアント シークレット」をコピーして入力します。'),
        ],
    ],
    'x' => [
        'console_url' => 'https://developer.twitter.com/en/portal/dashboard',
        'console_label' => 'X Developer Portal',
        'steps' => [
            __d('baser_core', '<a href="https://developer.twitter.com/en/portal/dashboard" target="_blank" rel="noopener noreferrer">X Developer Portal</a> にアクセスし、App を作成（または選択）します。'),
            __d('baser_core', 'App の「Settings」タブ →「User authentication settings」の「Edit」を開きます。'),
            __d('baser_core', '「OAuth 2.0」を On にし、Type of App で「Web App, Automated App or Bot」を選択します。'),
            __d('baser_core', '「Callback URI / Redirect URL」に下記の Callback URL を追加し、保存します。'),
            __d('baser_core', '「Keys and tokens」タブ →「OAuth 2.0 Client ID and Client Secret」の「Generate」または「Regenerate」でキーを取得し、入力します。'),
        ],
    ],
];
?>
<?php foreach ($providerLabels as $provider => $label):
  $collapseId = 'ProviderSection-' . h($provider);
?>
<div class="bca-collapse__action">
  <button type="button"
      class="bca-collapse__btn"
      data-bca-collapse="collapse"
      data-bca-target="#<?php echo $collapseId ?>"
      aria-expanded="false"
      aria-controls="<?php echo $collapseId ?>">
    <?php echo h($label) ?>&nbsp;&nbsp;
    <i class="bca-icon--chevron-down bca-collapse__btn-icon"></i>
  </button>
</div>
<div class="bca-collapse" id="<?php echo $collapseId ?>" data-bca-state="" style="display:none;">
  <section class="bca-section" data-bca-section-type="form-group">
    <table class="form-table bca-form-table" data-bca-table-type="type2">
      <?php if (isset($providerGuides[$provider])): ?>
      <tr>
        <th class="col-head bca-form-table__label"><?php echo __d('baser_core', '取得先・発行手順') ?></th>
        <td class="col-input bca-form-table__input">
          <ol>
            <?php foreach ($providerGuides[$provider]['steps'] as $step): ?>
              <li><small><?php echo $step ?></small></li>
            <?php endforeach ?>
          </ol>
        </td>
      </tr>
      <?php endif ?>
      <tr>
        <th class="col-head bca-form-table__label">
          <?php echo $this->BcAdminForm->label($provider . '_enabled', __d('baser_core', '有効化')) ?>
        </th>
        <td class="col-input bca-form-table__input">
          <?php echo $this->BcAdminForm->control($provider . '_enabled', [
            'type' => 'checkbox',
            'label' => __d('baser_core', '{0} ログインを有効にする', $label),
            'disabled' => !$isWritableEnv,
          ]) ?>
          <?php echo $this->BcAdminForm->error($provider . '_enabled') ?>
        </td>
      </tr>
      <tr>
        <th class="col-head bca-form-table__label">
          <?php echo $this->BcAdminForm->label($provider . '_client_id', __d('baser_core', 'Client ID')) ?>
        </th>
        <td class="col-input bca-form-table__input">
          <div><?php echo $this->BcAdminForm->control($provider . '_client_id', ['type' => 'text', 'size' => 60, 'disabled' => !$isWritableEnv]) ?></div>
          <small><?php echo h($envKeys[$provider]['client_id']) ?></small>
          <?php echo $this->BcAdminForm->error($provider . '_client_id') ?>
        </td>
      </tr>
      <tr>
        <th class="col-head bca-form-table__label">
          <?php echo $this->BcAdminForm->label($provider . '_client_secret', __d('baser_core', 'Client Secret')) ?>
        </th>
        <td class="col-input bca-form-table__input">
          <div><?php echo $this->BcAdminForm->control($provider . '_client_secret', ['type' => 'text', 'size' => 60, 'disabled' => !$isWritableEnv]) ?></div>
          <small><?php echo h($envKeys[$provider]['client_secret']) ?></small>
          <?php echo $this->BcAdminForm->error($provider . '_client_secret') ?>
        </td>
      </tr>
      <tr>
        <th class="col-head bca-form-table__label">
          <?php echo $this->BcAdminForm->label($provider . '_redirect_uri', __d('baser_core', 'Redirect URI')) ?>
        </th>
        <td class="col-input bca-form-table__input">
          <div><?php echo $this->BcAdminForm->control($provider . '_redirect_uri', ['type' => 'text', 'size' => 80, 'placeholder' => $callbackUrls[$provider], 'disabled' => !$isWritableEnv]) ?></div>
          <div><small><?php echo h($envKeys[$provider]['redirect_uri']) ?></small></div>
          <div><small><?php echo __d('baser_core', '未入力時の callback URL: {0}', $callbackUrls[$provider]) ?></small></div>
          <?php echo $this->BcAdminForm->error($provider . '_redirect_uri') ?>
        </td>
      </tr>
      <tr>
        <th class="col-head bca-form-table__label">
          <?php echo __d('baser_core', '手作業時の設定キー') ?>
        </th>
        <td class="col-input bca-form-table__input">
          <div><small><?php echo h($envKeys[$provider]['enabled']) ?></small></div>
          <div><small><?php echo h($envKeys[$provider]['client_id']) ?></small></div>
          <div><small><?php echo h($envKeys[$provider]['client_secret']) ?></small></div>
          <div><small><?php echo h($envKeys[$provider]['redirect_uri']) ?></small></div>
        </td>
      </tr>
    </table>
  </section>
</div>
<?php endforeach ?>

<?php echo $this->BcFormTable->dispatchAfter() ?>

<div class="submit bca-actions">
  <div class="bca-actions__main">
    <?php echo $this->BcAdminForm->submit(__d('baser_core', '保存'), [
      'div' => false,
      'class' => 'bca-btn bca-actions__item',
      'data-bca-btn-type' => 'save',
      'data-bca-btn-size' => 'lg',
      'data-bca-btn-width' => 'lg',
      'disabled' => !$isWritableEnv,
    ]) ?>
  </div>
</div>

<?php echo $this->BcAdminForm->end() ?>
