<?php
declare(strict_types=1);

namespace BcSocialAuth\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AuthProviderLinksTable
 *
 * baserCMS ユーザーと外部プロバイダアカウントの連携情報を管理します。
 */
class AuthProviderLinksTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('auth_provider_links');
        $this->setPrimaryKey('id');
        $this->setDisplayField('provider');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className'  => 'BaserCore.Users',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('prefix')
            ->maxLength('prefix', 50)
            ->requirePresence('prefix', 'create')
            ->notEmptyString('prefix');

        $validator
            ->scalar('provider')
            ->maxLength('provider', 50)
            ->requirePresence('provider', 'create')
            ->notEmptyString('provider');

        $validator
            ->scalar('provider_user_id')
            ->maxLength('provider_user_id', 255)
            ->requirePresence('provider_user_id', 'create')
            ->notEmptyString('provider_user_id');

        $validator
            ->scalar('email')
            ->maxLength('email', 255)
            ->allowEmptyString('email');

        $validator
            ->boolean('email_verified')
            ->notEmptyString('email_verified');

        $validator
            ->scalar('linked_by')
            ->maxLength('linked_by', 20)
            ->inList('linked_by', ['self', 'admin', 'auto'])
            ->notEmptyString('linked_by');

        return $validator;
    }

    /**
     * provider と provider_user_id でレコードを取得する
     *
     * @param string $provider
     * @param string $providerUserId
     * @return \BcSocialAuth\Model\Entity\AuthProviderLink|null
     */
    public function findByProviderUserId(string $provider, string $providerUserId): ?object
    {
        return $this->find()
            ->where([
                'provider'         => $provider,
                'provider_user_id' => $providerUserId,
                'disabled'         => false,
            ])
            ->first();
    }

    /**
     * ユーザー単位の連携一覧を返す
     *
     * @param int    $userId
     * @param string $prefix
     * @return \Cake\ORM\Query
     */
    public function findByUser(int $userId, string $prefix = 'Admin'): object
    {
        return $this->find()
            ->where([
                'user_id' => $userId,
                'prefix'  => $prefix,
                'disabled' => false,
            ])
            ->orderByAsc('provider');
    }
}
