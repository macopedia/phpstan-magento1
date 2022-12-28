<?php

declare(strict_types=1);

namespace PHPStanMagento1\Type\Mage;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStanMagento1\Config\MagentoCore;

abstract class MethodReturnTypeDetector
{
    /**
     * @var MagentoCore|\Mage_Core_Model_Config|null
     */
    protected static $config;

    abstract protected static function getMethodName(): string;
    abstract protected function getMagentoClassName(string $identifier): string;

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === $this::getMethodName();
    }

    /**
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromExpr($methodReflection, $methodCall, $scope);
    }

    /**
     * @param MethodCall|\PhpParser\Node\Expr\StaticCall $methodCall
     */
    protected function getTypeFromExpr(MethodReflection $methodReflection, $methodCall, Scope $scope): Type
    {
        $argument = $methodCall->getArgs()[0] ?? null;
        if ($argument === null  || ! $argument->value instanceof String_) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $modelName = $argument->value->value;
        $modelClassName = $this->getMagentoClassName($modelName);

        return new ObjectType($modelClassName);
    }

    /**
     * Load Magento XML configuration
     *
     * @return MagentoCore|\Mage_Core_Model_Config
     */
    protected function getMagentoConfig()
    {
        if (self::$config) {
            return self::$config;
        }

        //change this to DI of staticReflection config
        if (\defined('staticReflection')) {
            $config = new MagentoCore();
            $config->loadBase();
            $config->loadModules();
        } else {
            $config = \Mage::app()->getConfig();
        }
        self::$config = $config;
        return self::$config;
    }
}
