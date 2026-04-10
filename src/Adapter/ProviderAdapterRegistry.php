<?php
declare(strict_types=1);

namespace BcSocialAuth\Adapter;

/**
 * ProviderAdapterRegistry
 *
 * BcSocialAuth に登録されたプロバイダアダプターを管理するシングルトンです。
 *
 * 外部アドオンプラグインは自身の config/bootstrap.php でアダプターを登録します。
 *
 * ```php
 * // plugins/BcLineAuth/config/bootstrap.php
 * use BcSocialAuth\Adapter\ProviderAdapterRegistry;
 * use BcLineAuth\Adapter\LineProviderAdapter;
 *
 * ProviderAdapterRegistry::getInstance()->register(new LineProviderAdapter());
 * ```
 */
class ProviderAdapterRegistry
{
    private static ?self $instance = null;

    /** @var ProviderAdapterInterface[] */
    private array $adapters = [];

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function register(ProviderAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getProvider()] = $adapter;
    }

    /**
     * @throws \InvalidArgumentException 未登録のプロバイダを指定した場合
     */
    public function get(string $provider): ProviderAdapterInterface
    {
        if (!isset($this->adapters[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: {$provider}");
        }
        return $this->adapters[$provider];
    }

    public function has(string $provider): bool
    {
        return isset($this->adapters[$provider]);
    }

    /** @return ProviderAdapterInterface[] */
    public function all(): array
    {
        return $this->adapters;
    }
}
