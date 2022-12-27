<?php

declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

final class GetResourceSingleton extends StaticMethodReturnTypeDetector
{
    public function getMagentoClassName(string $identifier): string
    {
        return $this->getMagentoConfig()->getResourceModelClassName($identifier);
    }

    protected static function getMethodName(): string
    {
        return 'getResourceSingleton';
    }
}
