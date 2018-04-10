<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;

final class TokenBuilder
{
    /**
     * Generates tokens for code like: "public function __construct(type $name)
     * {
     *      $this->name = $name;
     * }".
     *
     * @return Token[]
     */
    public static function createConstructorWithPropertyTokens(string $propertyType, string $propertyName): array
    {
        $constructorTokens = [];

        // public function construct
        $constructorTokens[] = new Token([T_WHITESPACE, PHP_EOL . PHP_EOL . '    ']);
        $constructorTokens[] = new Token([T_PUBLIC, 'public']);
        $constructorTokens[] = new Token([T_WHITESPACE, ' ']);
        $constructorTokens[] = new Token([T_FUNCTION, 'function']);
        $constructorTokens[] = new Token([T_WHITESPACE, ' ']);
        $constructorTokens[] = new Token([T_STRING, '__construct']);

        // (type $name) {
        $constructorTokens[] = new Token('(');
        $constructorTokens[] = new Token([T_STRING, $propertyType]);
        $constructorTokens[] = new Token([T_WHITESPACE, ' ']);
        $constructorTokens[] = new Token([T_VARIABLE, '$' . $propertyName]);

        $constructorTokens[] = new Token(')');
        $constructorTokens[] = new Token([T_WHITESPACE, PHP_EOL . '    ']);
        $constructorTokens[] = new Token('{');

        $constructorTokens = array_merge(
            $constructorTokens,
            self::createPropertyAssignmentTokens($propertyName)
        );

        // }
        $constructorTokens[] = new Token([T_WHITESPACE, PHP_EOL . '    ']);
        $constructorTokens[] = new Token('}');

        return $constructorTokens;
    }

    /**
     * Generates tokens for code like: ", Type $property".
     *
     * @return Token[]
     */
    public static function createLastArgumentTokens(string $propertyType, string $propertyName): array
    {
        return [
            new Token(','),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_STRING, $propertyType]),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_VARIABLE, '$' . $propertyName]),
        ];
    }

    /**
     * Generates tokens for code like: "$this->property = $property;".
     *
     * @return Token[]
     */
    public static function createPropertyAssignmentTokens(string $propertyName): array
    {
        return [
            new Token([T_WHITESPACE, PHP_EOL . '        ']), // 2x indent with spaces
            new Token([T_VARIABLE, '$this']),
            new Token([T_OBJECT_OPERATOR, '->']),
            new Token([T_STRING, $propertyName]),
            new Token([T_WHITESPACE, ' ']),
            new Token('='),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_VARIABLE, '$' . $propertyName]),
            new Token(';'),
        ];
    }

    /**
     * Generates token for code like: " = []".
     *
     * @return Token[]
     */
    public static function createDefaultArrayTokens(): array
    {
        return [
            new Token([T_WHITESPACE, ' ']),
            new Token('='),
            new Token([T_WHITESPACE, ' ']),
            new Token([CT::T_ARRAY_SQUARE_BRACE_OPEN, '[']),
            new Token([CT::T_ARRAY_SQUARE_BRACE_CLOSE, ']']),
        ];
    }
}
