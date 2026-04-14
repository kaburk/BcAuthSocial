<?php
declare(strict_types=1);

namespace BcAuthSocial\Service;

use Cake\Datasource\EntityInterface;

interface BcAuthSocialConfigsServiceInterface
{
    public function get(): EntityInterface;

    public function update(array $postData): EntityInterface;

    public function getViewVarsForIndex(EntityInterface $config): array;

    public function hasInstalledSchema(): bool;

    public function hasAnyAvailableProvider(): bool;

    public function getSetupUrl(): array;
}
