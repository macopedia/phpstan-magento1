<?php declare(strict_types=1);

namespace PHPStanMagento1\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use Varien_Object;
use function in_array;
use function substr;

final class VarienObjectReflectionExtension implements MethodsClassReflectionExtension
{
    private bool $enforceDocBlock;

    public function __construct(bool $enforceDocBlock)
    {
        $this->enforceDocBlock = $enforceDocBlock;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (!in_array(substr($methodName, 0, 3), ['get', 'set', 'uns', 'has'], true)) {
            return false;
        }
        if (!$classReflection->is(Varien_Object::class)) {
            return false;
        }

        if (isset($classReflection->getMethodTags()[$methodName])) {
            return false;
        }

        if ($classReflection->isSubclassOf(Varien_Object::class) && $this->enforceDocBlock) {
            return false;
        }

        return true;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        switch (substr($methodName, 0, 3)) {
        case 'get':
            return new MagicMethodReflection($classReflection->getNativeMethod('getData'));
        case 'set':
            return new MagicMethodReflection($classReflection->getNativeMethod('setData'));
        case 'uns':
            return new MagicMethodReflection($classReflection->getNativeMethod('unsetData'));
        case 'has':
            return new MagicMethodReflection($classReflection->getNativeMethod('hasData'));
        default:
            throw new ShouldNotHappenException();
        }
    }
}
