<?php declare(strict_types=1);

namespace PHPStanMagento1\Type;

use PHPStanMagento1\Config\MageCoreConfig;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function class_exists;
use function count;
use function is_callable;

final class MageTypeExtension implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{
    /** @var class-string */
    private string $className;
    private MageCoreConfig $mageCoreConfig;

    /**
     * @param class-string $className
     */
    public function __construct(string $className, MageCoreConfig $mageCoreConfig)
    {
        $this->className = $className;
        $this->mageCoreConfig = $mageCoreConfig;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $fn = $this->mageCoreConfig->getClassNameConverterFunction(
            $methodReflection->getDeclaringClass()->getName(),
            $methodReflection->getName()
        );

        return is_callable($fn);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, CallLike $methodCall, Scope $scope): ?Type
    {
        if (count($methodCall->getArgs()) === 0) {
            return null;
        }

        $fn = $this->mageCoreConfig->getClassNameConverterFunction(
            $methodReflection->getDeclaringClass()->getName(),
            $methodReflection->getName()
        );

        if (!is_callable($fn)) {
            throw new ShouldNotHappenException();
        }

        $aliases = $scope->getType($methodCall->getArgs()[0]->value)->getConstantStrings();

        $returnTypes = [];

        foreach ($aliases as $alias) {

            $className = $fn($alias->getValue());

            if ($className === false || class_exists($className) === false) {
                $returnTypes[] = new ConstantBooleanType(false);
            } else {
                $returnTypes[] = new ObjectType($className);
            }
        }

        if (count($returnTypes) === 0) {
            $returnTypes[] = $methodReflection->getVariants()[0]->getReturnType();
        }

        return TypeCombinator::union(...$returnTypes);
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodSupported($methodReflection);
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, CallLike $methodCall, Scope $scope): ?Type
    {
        return $this->getTypeFromMethodCall($methodReflection, $methodCall, $scope);
    }
}
