<?php declare(strict_types=1);

namespace PHPStanMagento1\Reflection;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function array_map;
use function array_slice;

final class MagicMethodReflection implements MethodReflection
{
    private MethodReflection $originalMethod;

    public function __construct(MethodReflection $originalMethod)
    {
        $this->originalMethod = $originalMethod;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->originalMethod->getDeclaringClass();
    }

    public function isStatic(): bool
    {
        return $this->originalMethod->isStatic();
    }

    public function isPrivate(): bool
    {
        return $this->originalMethod->isPrivate();
    }

    public function isPublic(): bool
    {
        return $this->originalMethod->isPublic();
    }

    public function getDocComment(): ?string
    {
        return $this->originalMethod->getDocComment();
    }

    public function getName(): string
    {
        return $this->originalMethod->getName();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this->originalMethod->getPrototype();
    }

    /**
     * @return list<FunctionVariant>
     */
    public function getVariants(): array
    {
        $variant = $this->originalMethod->getVariants()[0];
        return [
            new FunctionVariant(
                $variant->getTemplateTypeMap(),
                $variant->getResolvedTemplateTypeMap(),
                array_slice($variant->getParameters(), 1),
                $variant->isVariadic(),
                $variant->getReturnType(),
            )
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return $this->originalMethod->isDeprecated();
    }

    public function getDeprecatedDescription(): ?string
    {
        return $this->originalMethod->getDeprecatedDescription();
    }

    public function isFinal(): TrinaryLogic
    {
        return $this->originalMethod->isFinal();
    }

    public function isInternal(): TrinaryLogic
    {
        return $this->originalMethod->isInternal();
    }

    public function getThrowType(): ?Type
    {
        return $this->originalMethod->getThrowType();
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return $this->originalMethod->hasSideEffects();
    }
}
