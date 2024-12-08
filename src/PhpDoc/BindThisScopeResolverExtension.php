<?php declare(strict_types=1);

namespace PHPStanMagento1\PhpDoc;

use PHPStanMagento1\Reflection\PublicMethodReflection;
use PHPStanMagento1\Reflection\PublicPropertyReflection;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\WrappedExtendedMethodReflection;
use PHPStan\Reflection\WrappedExtendedPropertyReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;
use function is_string;
use function preg_replace;

final class BindThisScopeResolverExtension extends NodeVisitorAbstract implements TypeNodeResolverExtension
{
    private const GENERIC_TYPE = 'bind-this-scope';
    private const PHPDOC_PATTERN = '/@var\s+((\\\?\w+)+)\s+\$this/';
    private const PHPDOC_REPLACE = '@var ' . self::GENERIC_TYPE . '<$1> $this';

    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $this->beforeTraverse($node->stmts);
                break;
            }

            if ($node->getDocComment() === null) {
                continue;
            }

            $comment = preg_replace(self::PHPDOC_PATTERN, self::PHPDOC_REPLACE, $node->getDocComment()->getText(), 1, $match);

            if (is_string($comment) && $match === 1) {
                $node->setDocComment(new Comment\Doc($comment));
                break;
            }
        }
        return null;
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if (!$typeNode instanceof GenericTypeNode) {
            return null;
        }

        $typeName = $typeNode->type;
        if ($typeName->name !== self::GENERIC_TYPE) {
            return null;
        }

        /** @var IdentifierTypeNode[] */
        $arguments = $typeNode->genericTypes;
        if (count($arguments) !== 1) {
            return null;
        }

        $className = $nameScope->resolveStringName($arguments[0]->name);

        return new class($className) extends ObjectType {
            public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
            {
                return new WrappedExtendedMethodReflection(
                    new PublicMethodReflection(parent::getMethod($methodName, $scope))
                );
            }
            public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
            {
                return new WrappedExtendedPropertyReflection(
                    new PublicPropertyReflection(parent::getProperty($propertyName, $scope))
                );
            }
        };
    }
}
