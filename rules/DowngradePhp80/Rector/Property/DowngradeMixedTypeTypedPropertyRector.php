<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\MixedType;
use Rector\CodeQuality\NodeFactory\PropertyTypeDecorator;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\DowngradePhp80\Rector\Property\DowngradeMixedTypeTypedPropertyRector\DowngradeMixedTypeTypedPropertyRectorTest
 */
final class DowngradeMixedTypeTypedPropertyRector extends AbstractRector
{
    public function __construct(
        private readonly PropertyTypeDecorator $propertyTypeDecorator
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes mixed type property type definition, adding `@var` annotations instead.', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    private mixed $property;
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var mixed
     */
    private $property;
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->type === null) {
            return null;
        }

        if ($this->shouldSkip($node)) {
            return null;
        }

        $this->propertyTypeDecorator->decoratePropertyWithDocBlock($node, $node->type);
        $node->type = null;

        return $node;
    }

    private function shouldSkip(Property $property): bool
    {
        if ($property->type === null) {
            return true;
        }

        $type = $this->staticTypeMapper->mapPhpParserNodePHPStanType($property->type);
        if (! $type instanceof MixedType) {
            return true;
        }

        return ! $type->isExplicitMixed();
    }
}
