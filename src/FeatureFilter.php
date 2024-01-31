<?php

namespace PhpStats;

use PhpParser\Node;

class FeatureFilter
{
    public function __construct(
        private readonly FeatureName $name,
        private readonly \Closure    $filter,
        public readonly bool         $stopOnFind = true
    )
    {
    }

    public function isThis(Node $node)
    {
        return ($this->filter)($node);
    }

    public function name()
    {
        return $this->name->name;
    }
}
