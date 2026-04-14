<?php
declare(strict_types=1);

use BaserCore\Database\Migration\BcMigration;

/**
 * auth_provider_links テーブルを作成するマイグレーション
 *
 * BcAuthSocial が管理する外部プロバイダ連携テーブルです。
 * baserCMS ユーザーと外部プロバイダアカウントを 1 対多で紐づけます。
 */
class CreateBcAuthProviderLinks extends BcMigration
{
    public function up(): void
    {
        $this->table('bc_auth_provider_links', [
            'collation' => 'utf8mb4_general_ci',
        ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit'   => null,
                'null'    => false,
                'comment' => 'users テーブルの ID',
            ])
            ->addColumn('prefix', 'string', [
                'default' => 'Admin',
                'limit'   => 50,
                'null'    => false,
                'comment' => '利用プレフィックス（Admin / Front）',
            ])
            ->addColumn('provider', 'string', [
                'default' => null,
                'limit'   => 50,
                'null'    => false,
                'comment' => 'プロバイダ識別子（google, x, line, apple など）',
            ])
            ->addColumn('provider_user_id', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => false,
                'comment' => '外部プロバイダ側のユーザー一意識別子',
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => true,
                'comment' => '取得したメールアドレス。X など取得不可の場合は NULL',
            ])
            ->addColumn('email_verified', 'boolean', [
                'default' => false,
                'null'    => false,
                'comment' => 'メール確認済みか',
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => true,
                'comment' => '外部プロバイダからの表示名',
            ])
            ->addColumn('avatar_url', 'string', [
                'default' => null,
                'limit'   => 512,
                'null'    => true,
                'comment' => 'アバター画像 URL',
            ])
            ->addColumn('profile', 'text', [
                'default' => null,
                'null'    => true,
                'comment' => 'プロフィール補助情報（JSON シリアライズ、最小限）',
            ])
            ->addColumn('linked_by', 'string', [
                'default' => 'self',
                'limit'   => 20,
                'null'    => false,
                'comment' => '連携の起点（self / admin / auto）',
            ])
            ->addColumn('last_login', 'datetime', [
                'default' => null,
                'null'    => true,
                'comment' => 'この連携を使った最終ログイン日時',
            ])
            ->addColumn('last_login_ip', 'string', [
                'default' => null,
                'limit'   => 45,
                'null'    => true,
                'comment' => '最終ログイン元 IP（IPv6 対応 45 文字）',
            ])
            ->addColumn('last_login_user_agent', 'string', [
                'default' => null,
                'limit'   => 512,
                'null'    => true,
                'comment' => '最終ログイン端末の User-Agent',
            ])
            ->addColumn('disabled', 'boolean', [
                'default' => false,
                'null'    => false,
                'comment' => '個別連携の無効化フラグ',
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null'    => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null'    => true,
            ])
            ->addIndex(
                ['provider', 'provider_user_id'],
                ['unique' => true, 'name' => 'UNIQUE_provider_link']
            )
            ->addIndex(['user_id'])
            ->addIndex(['prefix'])
            ->create();
    }

    public function down(): void
    {
        $this->table('bc_auth_provider_links')->drop()->save();
    }
}
