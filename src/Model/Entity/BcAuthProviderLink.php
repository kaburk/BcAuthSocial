<?php
declare(strict_types=1);

namespace BcAuthSocial\Model\Entity;

use Cake\ORM\Entity;

/**
 * BcAuthProviderLink Entity
 *
 * @property int           $id
 * @property int           $user_id
 * @property string        $prefix
 * @property string        $provider
 * @property string        $provider_user_id
 * @property string|null   $email
 * @property bool          $email_verified
 * @property string|null   $name
 * @property string|null   $avatar_url
 * @property string|null   $profile
 * @property string        $linked_by
 * @property \Cake\I18n\FrozenTime|null $last_login
 * @property string|null   $last_login_ip
 * @property string|null   $last_login_user_agent
 * @property bool          $disabled
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class BcAuthProviderLink extends Entity
{
    protected array $_accessible = [
        'user_id'               => true,
        'prefix'                => true,
        'provider'              => true,
        'provider_user_id'      => true,
        'email'                 => true,
        'email_verified'        => true,
        'name'                  => true,
        'avatar_url'            => true,
        'profile'               => true,
        'linked_by'             => true,
        'last_login'            => true,
        'last_login_ip'         => true,
        'last_login_user_agent' => true,
        'disabled'              => true,
        'created'               => true,
        'modified'              => true,
    ];
}
