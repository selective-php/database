<?php

namespace Odan\Database;

/**
 * Class Operator.
 *
 * https://dev.mysql.com/doc/refman/5.7/en/non-typed-operators.html
 */
final class Operator
{
    const EQ = '=';
    const NOT_EQ = '<>';
    const NOT_EQ_NULL_SAFE = '<=>';
    const LT = '<';
    const GT = '>';
    const GTE = '>=';
    const LTE = '<=';
    const IS = 'is';
    const IS_NOT = 'is not';
    //const IS_NOT_NULL = 'is not null';
    const LIKE = 'like';
    const NOT_LIKE = 'not like';
    const SOUNDS_LIKE = 'sounds like';
    const IN = 'in';
    const NOT_IN = 'not in';
    const EXISTS = 'exists';
    const NOT_EXISTS = 'not exists';
    const BETWEEN = 'between';
    const NOT_BETWEEN = 'not between';
    const REGEXP = 'regexp';
    const NOT_REGEXP = 'not regexp';
    const BINARY = 'binary';
    const CASE = 'case';
    const PLUS = '+';
    const MINUS = '-';
    const MULTIPLY = '*';
    const DIVIDE = '/';
    const DIV = 'div';
    const RIGHT_SHIFT = '>>';
    const LEFT_SHIFT = '<<';
    const MOD = 'mod';
    const AND = 'and';
    const OR = 'or';
    const XOR = 'xor';
}
