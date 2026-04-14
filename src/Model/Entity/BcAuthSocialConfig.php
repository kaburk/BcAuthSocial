<?php
declare(strict_types=1);

namespace BcAuthSocial\Model\Entity;

use Cake\ORM\Entity;

/**
 * BcAuthSocialConfig Entity
 *
 * ソーシャル認証プロバイダ設定を表すバーチャルエンティティです。
 * DB テーブルは持たず、設定は .env に保存されます。
 * FormHelper の EntityContext が要求するテーブルクラスは
 * BcAuthSocialConfigsTable がバーチャルスキーマを提供します。
 */
class BcAuthSocialConfig extends Entity
{
    protected array $_accessible = ['*' => true];
}
