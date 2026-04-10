<?php
declare(strict_types=1);

namespace BcSocialAuth\Adapter;

/**
 * ProviderUserProfile
 *
 * 各プロバイダから取得したユーザー情報の正規化済み VO です。
 */
class ProviderUserProfile
{
    public function __construct(
        public readonly string  $providerUserId,
        public readonly string  $provider,
        public readonly ?string $email,
        public readonly bool    $emailVerified,
        public readonly ?string $name,
        public readonly ?string $avatarUrl,
    ) {}
}
