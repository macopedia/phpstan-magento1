<?php

declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

final class Helper extends StaticMethodReturnTypeDetector
{
    public function getMagentoClassName(string $identifier): string
    {
        return $this->getMagentoConfig()->getHelperClassName($identifier);
    }

    protected static function getMethodName(): string
    {
        return 'helper';
    }
}
