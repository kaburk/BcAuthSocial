<?php
declare(strict_types=1);

namespace BcSocialAuth\Model\Entity;

use Cake\ORM\Entity;

/**
 * SocialAuthConfig Entity
 *
 * ソーシャル認証プロバイダ設定を表すバーチャルエンティティです。
 * DB テーブルは持たず、設定は .env に保存されます。
 * FormHelper の EntityContext が要求するテーブルクラスは
 * SocialAuthConfigsTable がバーチャルスキーマを提供します。
 */
class SocialAuthConfig extends Entity
{
    protected array $_accessible = ['*' => true];
}
