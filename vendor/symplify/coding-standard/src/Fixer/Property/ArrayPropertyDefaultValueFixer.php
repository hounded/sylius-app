<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer\Property;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\TokenBuilder;
use Symplify\CodingStandard\Tokenizer\ClassTokensAnalyzer;
use Symplify\CodingStandard\Tokenizer\DocBlockAnalyzer;
use Symplify\CodingStandard\Tokenizer\DocBlockFinder;

final class ArrayPropertyDefaultValueFixer implements DefinedFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Array property should have default value, to prevent undefined array issues.',
            [
                new CodeSample(
                    '<?php
/**
 * @var string[]
 */
public $property;'
                ),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        // analyze only class/trait properties with comments
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds()) &&
            $tokens->isAllTokenKindsFound([T_DOC_COMMENT, T_VARIABLE]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < count($tokens) - 1; ++$index) {
            $token = $tokens[$index];
            if (! $token->isClassy()) {
                continue;
            }

            $classTokensAnalyzer = ClassTokensAnalyzer::createFromTokensArrayStartPosition($tokens, $index);

            $this->fixProperties($tokens, $classTokensAnalyzer->getProperties());
        }
    }

    public function getName(): string
    {
        return self::class;
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    /**
     * @param mixed[]|Token[] $properties
     */
    private function fixProperties(Tokens $tokens, array $properties): void
    {
        foreach ($properties as $index => ['token' => $propertyToken]) {
            $docBlockToken = DocBlockFinder::findPrevious($tokens, $index);
            if ($docBlockToken === null) {
                continue;
            }

            if (! DocBlockAnalyzer::isArrayProperty($docBlockToken)) {
                continue;
            }

            $equalTokenPosition = $tokens->getNextTokenOfKind($index, ['=']);
            $semicolonTokenPosition = (int) $tokens->getNextTokenOfKind($index, [';']);

            if ($this->isDefaultDefinitionSet($equalTokenPosition, $semicolonTokenPosition)) {
                continue;
            }

            $tokens->insertAt($semicolonTokenPosition, TokenBuilder::createDefaultArrayTokens());
        }
    }

    private function isDefaultDefinitionSet(?int $equalTokenPosition, int $semicolonTokenPosition): bool
    {
        return is_numeric($equalTokenPosition) && $equalTokenPosition < $semicolonTokenPosition;
    }
}
