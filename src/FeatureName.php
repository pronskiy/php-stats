<?php

namespace PhpStats;

enum FeatureName
{
    case ENUMS;
    case READONLY_ANY;
    case RETURN_NEVER;
    case NULLSAFE_OPERATOR;
    case MATCH_EXPRESSION;
    case PROPERTY_PROMOTION;
    case FIBERS;
    case ATTRIBUTES;
    case ALLOW_DYNAMIC_PROPERTIES;
    case GENERICS;
    case RETURN_TYPE_WILL_CHANGE;
}
