<?php

declare(strict_types=1);

namespace PHPStanMagento1\Reflection\Varien\Object;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Varien_Object;

final class MagicMethodsReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $magentoMagicMethods = ['get', 'set', 'uns', 'has'];
        return ($classReflection->isSubclassOf(Varien_Object::class) || $classReflection->getName() === Varien_Object::class)
            && \in_array(substr($methodName, 0, 3), $magentoMagicMethods, true);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new MagicMethodReflection($classReflection, $methodName);
    }
}
