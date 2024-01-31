<?php

namespace PhpStats;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\FindingVisitor;

class Visitor extends FindingVisitor
{
    public array $stats = [];
    private array $filters = [];

    public function __construct(array $filters = [])
    {
        $filters = array_merge($filters, $this->defaultFilters());
        
        $keys = array_map(fn(FeatureFilter $f) => $f->name(), $filters);

        $this->filters = array_combine($keys, $filters);
    }

    public function enterNode(Node $node)
    {
        foreach ($this->filters as $filter) {
            /** @var $filter FeatureFilter */
            if ($filter->isThis($node)) {
                $this->stats[$filter->name()] = 1;
                unset($this->filters[$filter->name()]);
                $this->foundNodes[] = $node;

                if ($filter->stopOnFind) {
                    return null;
                }
            }
        }

        if (empty($this->filters)) {
            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    private function defaultFilters()
    {
        return [
            new FeatureFilter(FeatureName::ATTRIBUTES, fn(Node $n) => $n instanceof Node\Attribute, false),
            new FeatureFilter(FeatureName::ENUMS, fn(Node $n) => $n instanceof Node\Stmt\Enum_),
            new FeatureFilter(FeatureName::READONLY_ANY, function (Node $n) {
                return ($n instanceof Node\Stmt\Class_ || $n instanceof Node\Stmt\Property) && $n->isReadonly();
            }),
            new FeatureFilter(FeatureName::RETURN_NEVER, function (Node $n) {
                return
                    ($n instanceof Node\Stmt\Function_ || $n instanceof Node\Stmt\ClassMethod)
                    && $n->getReturnType() instanceof Node\Identifier
                    && $n->getReturnType()->name === 'never';
            }),
            new FeatureFilter(FeatureName::NULLSAFE_OPERATOR, fn(Node $n) => $n instanceof Node\Expr\NullsafeMethodCall || $n instanceof Node\Expr\NullsafePropertyFetch),
            new FeatureFilter(FeatureName::MATCH_EXPRESSION, fn(Node $n) => $n instanceof Node\Expr\Match_),
            new FeatureFilter(FeatureName::PROPERTY_PROMOTION, fn(Node $n) => $n instanceof Node\Param && $n->isPromoted()),
            new FeatureFilter(FeatureName::FIBERS, function (Node $n) {
                return
                    $n instanceof Node\Expr\New_
                    && $n->class instanceof Node\Name
                    && $n->class->name === 'Fiber'; // @TODO Check for namespace
            }),
            new FeatureFilter(FeatureName::ALLOW_DYNAMIC_PROPERTIES, function (Node $n) {
                return ($n instanceof Node\Attribute && $n->name->name === 'AllowDynamicProperties');
            }),
            new FeatureFilter(FeatureName::RETURN_TYPE_WILL_CHANGE, function (Node $n) {
                return ($n instanceof Node\Attribute && $n->name->name === 'ReturnTypeWillChange');
            }),
            new FeatureFilter(FeatureName::GENERICS, function (Node $n) {
                $comment = $n->getDocComment();
                if (!$comment instanceof Comment) return false;
                $m = preg_match('/\*\s+@(psalm-|phpstan-)?template\s+\w/', $comment->getText());
                return $m === 1;
            }),
        ];
    }
}
