<?php

namespace Selective\Database;

/**
 * Class Operator.
 *
 * https://dev.mysql.com/doc/refman/5.7/en/non-typed-operators.html
 */
final class Operator
{
    public const EQ = '=';
    public const NOT_EQ = '<>';
    public const NOT_EQ_NULL_SAFE = '<=>';
    public const LT = '<';
    public const GT = '>';
    public const GTE = '>=';
    public const LTE = '<=';
    public const IS = 'is';
    public const IS_NOT = 'is not';
    public const LIKE = 'like';
    public const NOT_LIKE = 'not like';
    public const SOUNDS_LIKE = 'sounds like';
    public const IN = 'in';
    public const NOT_IN = 'not in';
    public const EXISTS = 'exists';
    public const NOT_EXISTS = 'not exists';
    public const BETWEEN = 'between';
    public const NOT_BETWEEN = 'not between';
    public const REGEXP = 'regexp';
    public const NOT_REGEXP = 'not regexp';
    public const BINARY = 'binary';
    public const CASE = 'case';
    public const PLUS = '+';
    public const MINUS = '-';
    public const MULTIPLY = '*';
    public const DIVIDE = '/';
    public const DIV = 'div';
    public const RIGHT_SHIFT = '>>';
    public const LEFT_SHIFT = '<<';
    public const MOD = 'mod';
    public const AND = 'and';
    public const OR = 'or';
    public const XOR = 'xor';
}
