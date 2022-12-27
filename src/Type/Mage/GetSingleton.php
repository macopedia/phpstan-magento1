<?php

declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

final class GetSingleton extends StaticMethodReturnTypeDetector
{
    public function getMagentoClassName(string $identifier): string
    {
        return $this->getMagentoConfig()->getModelClassName($identifier);
    }

    protected static function getMethodName(): string
    {
        return 'getSingleton';
    }
}
