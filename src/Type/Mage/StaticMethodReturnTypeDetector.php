<?php

declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

abstract class StaticMethodReturnTypeDetector extends MethodReturnTypeDetector implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Mage::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodSupported($methodReflection);
    }

    /**
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromExpr($methodReflection, $methodCall, $scope);
    }
}
