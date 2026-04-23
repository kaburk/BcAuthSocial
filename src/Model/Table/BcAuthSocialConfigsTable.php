<?php
declare(strict_types=1);

namespace BcAuthSocial\Model\Table;

use BcAuthSocial\Model\Entity\BcAuthSocialConfig;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;

/**
 * BcAuthSocialConfigsTable
 *
 * ソーシャル認証設定のバーチャルテーブルクラスです。
 * 設定は .env に保存されるため DB テーブルは存在しませんが、
 * CakePHP の FormHelper が EntityContext を解決するためにこのクラスが必要です。
 * getSchema() でバーチャルスキーマを返し、DB へのアクセスを防ぎます。
 */
class BcAuthSocialConfigsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setEntityClass(BcAuthSocialConfig::class);
    }

    /**
     * バーチャルスキーマを返します。DB へのアクセスは行いません。
     */
    public function getSchema(): TableSchemaInterface
    {
        if ($this->_schema === null) {
            $registry = Configure::read('BcAuthSocial') ?? [];
            $providers = [];
            foreach ($registry as $provider => $cfg) {
                if (!is_array($cfg) || !isset($cfg['label'])) {
                    continue;
                }
                $providers[$provider] = (int)($cfg['order'] ?? 9999);
            }
            asort($providers, SORT_NUMERIC);
            $schema = new TableSchema('social_auth_configs');
            foreach (array_keys($providers) as $provider) {
                $schema->addColumn($provider . '_enabled', ['type' => 'boolean', 'null' => true, 'default' => false]);
                $schema->addColumn($provider . '_client_id', ['type' => 'string', 'null' => true, 'default' => '']);
                $schema->addColumn($provider . '_client_secret', ['type' => 'string', 'null' => true, 'default' => '']);
                $schema->addColumn($provider . '_redirect_uri', ['type' => 'string', 'null' => true, 'default' => '']);
            }
            $this->_schema = $schema;
        }

        return $this->_schema;
    }
}
