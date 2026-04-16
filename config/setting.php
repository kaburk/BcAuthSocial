<?php

declare(strict_types=1);

/**
 * BcAuthSocial 設定ファイル
 *
 * ─── プロバイダーの追加手順 ───────────────────────────────────────────────────
 *
 * 1. このファイル（setting.php）に新しいプロバイダーブロックを追加する。
 *    配列キー（例: 'discord'）がプロバイダー識別子になる。
 *    各キーの説明は下記「設定キー一覧」を参照。
 *
 * 2. Adapter クラスを作成する。
 *    src/Adapter/DiscordProviderAdapter.php を作成し
 *    ProviderAdapterInterface を実装する。
 *    認可 URL / トークン URL / スコープ / PKCE 要否 / ユーザー情報正規化 などを定義する。
 *
 * 3. ProviderAdapterRegistry に登録する。
 *    src/BcAuthSocialPlugin.php の bootstrap() 内で
 *      $registry->register('discord', new DiscordProviderAdapter());
 *    を追加する。
 *
 * 4. .env（または管理画面）でクレデンシャルを設定する。
 *    envPrefix に対応する環境変数を追加する（例: BC_SOCIAL_AUTH_DISCORD_CLIENT_ID）。
 *    管理画面「ソーシャル認証設定」は Configure から自動でフォームを生成するため
 *    テンプレートの変更は不要。
 *
 * ─── 設定キー一覧 ──────────────────────────────────────────────────────────
 *
 * label            (string)  管理画面・ログインボタンに表示するサービス名。
 * envPrefix        (string)  .env キーのプレフィックス。
 *                            {envPrefix}_ENABLED / _CLIENT_ID / _CLIENT_SECRET / _REDIRECT_URI
 *                            の4変数が対応する。
 * allowLinkCandidate (bool)  true にすると、同じメールアドレスの既存ユーザーへの
 *                            自動連携候補提示を有効にする。
 *                            メールアドレスが確実に検証済みと言えないプロバイダーは false にする。
 * enabled          (bool)    .env の {envPrefix}_ENABLED から自動取得。手動変更不要。
 * clientId         (string)  .env の {envPrefix}_CLIENT_ID から自動取得。手動変更不要。
 * clientSecret     (string)  .env の {envPrefix}_CLIENT_SECRET から自動取得。手動変更不要。
 * redirectUri      (string)  .env の {envPrefix}_REDIRECT_URI から自動取得。
 *                            空のときは /baser/admin/bc-auth-social/bc_auth/callback/{provider}
 *                            が自動生成される。
 * icon             (string)  ログインボタンに表示するインライン SVG。
 *                            省略すると識別子の先頭文字（大文字）が代替表示される。
 * guide.steps      (array)   管理画面の設定ガイドに表示する手順の配列（HTML 可）。
 *
 * ────────────────────────────────────────────────────────────────────────────
 */

return [
    'BcApp' => [
        'adminNavigation' => [
            'Plugins' => [
                'menus' => [
                    'BcAuthSocialConfigs' => [
                        'title' => __d('baser_core', 'ソーシャル認証設定'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcAuthSocial',
                            'controller' => 'BcAuthSocialConfigs',
                            'action' => 'index',
                        ],
                    ],
                    'BcAuthSocialAccounts' => [
                        'title' => __d('baser_core', '連携済みアカウント'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcAuthSocial',
                            'controller' => 'BcAuthSocialAccounts',
                            'action' => 'index',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'BcAuthSocial' => [
        'google' => [
            'label' => 'Google',
            'envPrefix' => 'BC_SOCIAL_AUTH_GOOGLE',
            'allowLinkCandidate' => true,
            'enabled' => filter_var(env('BC_SOCIAL_AUTH_GOOGLE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'clientId' => env('BC_SOCIAL_AUTH_GOOGLE_CLIENT_ID', ''),
            'clientSecret' => env('BC_SOCIAL_AUTH_GOOGLE_CLIENT_SECRET', ''),
            'redirectUri' => env('BC_SOCIAL_AUTH_GOOGLE_REDIRECT_URI', ''),
            'icon' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.2-.9 2.3-1.9 3.1l3 2.3c1.8-1.7 2.8-4.1 2.8-7 0-.7-.1-1.5-.2-2.2H12z"/><path fill="#34A853" d="M12 21c2.6 0 4.8-.9 6.4-2.5l-3-2.3c-.8.6-1.9 1-3.4 1-2.6 0-4.9-1.8-5.7-4.2l-3.1 2.4C4.8 18.8 8.1 21 12 21z"/><path fill="#4A90E2" d="M6.3 13c-.2-.6-.3-1.3-.3-2s.1-1.4.3-2L3.2 6.6C2.4 8.1 2 9.5 2 11s.4 2.9 1.2 4.4L6.3 13z"/><path fill="#FBBC05" d="M12 4.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.8 1.8 14.6 1 12 1 8.1 1 4.8 3.2 3.2 6.6L6.3 9C7.1 6.6 9.4 4.8 12 4.8z"/></svg>',
            'guide' => [
                'steps' => [
                    '<a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> にアクセスし、プロジェクトを作成（または選択）します。',
                    '左メニューの「APIとサービス」→「認証情報」を開きます。',
                    '「認証情報を作成」→「OAuth クライアント ID」を選択します。',
                    'アプリケーションの種類で「ウェブ アプリケーション」を選択します。',
                    '「承認済みのリダイレクト URI」に下記の Callback URL を追加します。',
                    '作成後に表示される「クライアント ID」と「クライアント シークレット」をコピーして入力します。',
                ],
            ],
        ],
        'x' => [
            'label' => 'X',
            'envPrefix' => 'BC_SOCIAL_AUTH_X',
            'allowLinkCandidate' => false,
            'enabled' => filter_var(env('BC_SOCIAL_AUTH_X_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'clientId' => env('BC_SOCIAL_AUTH_X_CLIENT_ID', ''),
            'clientSecret' => env('BC_SOCIAL_AUTH_X_CLIENT_SECRET', ''),
            'redirectUri' => env('BC_SOCIAL_AUTH_X_REDIRECT_URI', ''),
            'icon' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="currentColor" d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 15.7 6.5 22H3.4l7.3-8.3L1 2h6.5l4.5 5.7L18.9 2zm-1.1 18h1.7L6.6 3.9H4.8L17.8 20z"/></svg>',
            'guide' => [
                'steps' => [
                    '<a href="https://developer.twitter.com/en/portal/dashboard" target="_blank" rel="noopener noreferrer">X Developer Portal</a> にアクセスし、App を作成（または選択）します。',
                    'App の「Settings」タブ →「User authentication settings」の「Edit」を開きます。',
                    '「OAuth 2.0」を On にし、Type of App で「Web App, Automated App or Bot」を選択します。',
                    '「Callback URI / Redirect URL」に下記の Callback URL を追加し、保存します。',
                    '「Keys and tokens」タブ →「OAuth 2.0 Client ID and Client Secret」の「Generate」または「Regenerate」でキーを取得し、入力します。',
                ],
            ],
        ],
        'github' => [
            'label' => 'GitHub',
            'envPrefix' => 'BC_SOCIAL_AUTH_GITHUB',
            'allowLinkCandidate' => true,
            'enabled' => filter_var(env('BC_SOCIAL_AUTH_GITHUB_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'clientId' => env('BC_SOCIAL_AUTH_GITHUB_CLIENT_ID', ''),
            'clientSecret' => env('BC_SOCIAL_AUTH_GITHUB_CLIENT_SECRET', ''),
            'redirectUri' => env('BC_SOCIAL_AUTH_GITHUB_REDIRECT_URI', ''),
            'icon' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.49.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.52 2.34 1.08 2.91.83.09-.65.35-1.08.63-1.33-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.56 9.56 0 0 1 12 6.8c.85 0 1.71.11 2.51.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.69-4.57 4.93.36.31.68.92.68 1.85v2.74c0 .27.18.58.69.48A10.01 10.01 0 0 0 22 12c0-5.52-4.48-10-10-10z"/></svg>',
            'guide' => [
                'steps' => [
                    '<a href="https://github.com/settings/developers" target="_blank" rel="noopener noreferrer">GitHub Developer Settings</a> にアクセスし、「OAuth Apps」→「New OAuth App」を選択します。',
                    '「Homepage URL」にサイトの URL を入力します。',
                    '「Authorization callback URL」に下記の Callback URL を入力し、「Register application」をクリックします。',
                    'アプリ詳細画面で「Client ID」をコピーし、「Generate a new client secret」でシークレットを生成してコピーします。',
                    'コピーした「Client ID」と「Client Secret」をここに入力します。',
                ],
            ],
        ],
        'line' => [
            'label' => 'LINE',
            'envPrefix' => 'BC_SOCIAL_AUTH_LINE',
            'allowLinkCandidate' => true,
            'enabled' => filter_var(env('BC_SOCIAL_AUTH_LINE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'clientId' => env('BC_SOCIAL_AUTH_LINE_CLIENT_ID', ''),
            'clientSecret' => env('BC_SOCIAL_AUTH_LINE_CLIENT_SECRET', ''),
            'redirectUri' => env('BC_SOCIAL_AUTH_LINE_REDIRECT_URI', ''),
            'icon' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#06C755" d="M12 2C6.48 2 2 5.8 2 10.2c0 3.6 2.37 6.7 5.9 8.22l-.45 2.2a.4.4 0 0 0 .58.43l2.72-1.65c.42.06.85.1 1.25.1 5.52 0 10-3.58 10-8S17.52 2 12 2z"/><path fill="#fff" d="M8 12.1V8.8H9v2.4h1.9v.9H8zm3 0V8.8h1v3.3h-1zm1.6 0V8.8h2.8v.9h-1.8v.5h1.6v.9h-1.6v.5h1.8v.9h-2.8zm3.6 0V8.8h1v3.3h-1z"/></svg>',
            'guide' => [
                'steps' => [
                    '<a href="https://developers.line.biz/" target="_blank" rel="noopener noreferrer">LINE Developers Console</a> にアクセスし、プロバイダーを選択（または作成）します。',
                    '「チャンネル作成」→「LINE ログイン」を選択し、チャンネル情報を入力して作成します。',
                    '「LINE ログイン設定」タブ →「コールバック URL」欄に下記の Callback URL を入力して保存します。',
                    '「チャンネル基本設定」タブの「チャンネル ID」（Client ID）と「チャンネルシークレット」（Client Secret）をコピーして入力します。',
                    '※ メールアドレスの取得には LINE 側の審査が必要です。審査前はメール連携なしでログインのみ可能です。',
                ],
            ],
        ],
        'microsoft' => [
            'label' => 'Microsoft',
            'envPrefix' => 'BC_SOCIAL_AUTH_MICROSOFT',
            'allowLinkCandidate' => true,
            'enabled' => filter_var(env('BC_SOCIAL_AUTH_MICROSOFT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'clientId' => env('BC_SOCIAL_AUTH_MICROSOFT_CLIENT_ID', ''),
            'clientSecret' => env('BC_SOCIAL_AUTH_MICROSOFT_CLIENT_SECRET', ''),
            'redirectUri' => env('BC_SOCIAL_AUTH_MICROSOFT_REDIRECT_URI', ''),
            'icon' => '<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#F25022" d="M2 2h9.5v9.5H2z"/><path fill="#7FBA00" d="M12.5 2H22v9.5h-9.5z"/><path fill="#00A4EF" d="M2 12.5h9.5V22H2z"/><path fill="#FFB900" d="M12.5 12.5H22V22h-9.5z"/></svg>',
            'guide' => [
                'steps' => [
                    '<a href="https://entra.microsoft.com/" target="_blank" rel="noopener noreferrer">Microsoft Entra 管理センター</a>（または <a href="https://portal.azure.com/" target="_blank" rel="noopener noreferrer">Azure Portal</a>）にアクセスします。',
                    '「アプリの登録」→「新規登録」を選択し、アプリ名を入力します。サポートされているアカウントの種類は「任意の組織ディレクトリ内のアカウントと個人の Microsoft アカウント」を選択します。',
                    '「リダイレクト URI」のプラットフォームに「Web」を選択し、下記の Callback URL を入力して「登録」をクリックします。',
                    '「証明書とシークレット」→「新しいクライアントシークレット」を追加し、表示されたシークレット値をコピーします（画面遷移後は再表示されません）。',
                    '「概要」ページの「アプリケーション（クライアント）ID」をコピーし、Client ID に入力します。',
                ],
            ],
        ],
    ],
];
