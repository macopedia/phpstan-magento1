<?php
declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

final class GetResourceModel extends StaticMethodReturnTypeDetector
{
    public function getMagentoClassName(string $identifier): string
    {
        $className = $this->getMagentoConfig()->getResourceModelClassName($identifier);
        if ($className === false) {
            throw new \PHPStan\Broker\ClassNotFoundException($identifier);
        }
        return $className;
    }

    protected static function getMethodName(): string
    {
        return 'getResourceModel';
    }
}
