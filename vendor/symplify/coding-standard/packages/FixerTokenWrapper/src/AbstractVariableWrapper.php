<?php declare(strict_types=1);

namespace Symplify\CodingStandard\FixerTokenWrapper;

use Nette\Utils\Strings;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\FixerTokenWrapper\Naming\ClassFqnResolver;

abstract class AbstractVariableWrapper
{
    /**
     * @var Tokens
     */
    protected $tokens;

    /**
     * @var int
     */
    protected $index;

    protected function __construct(Tokens $tokens, int $index)
    {
        $this->tokens = $tokens;
        $this->index = $index;
    }

    public function getName(): string
    {
        $nameToken = $this->tokens[$this->getNamePosition()];

        return ltrim((string) $nameToken->getContent(), '$');
    }

    public function isClassType(): bool
    {
        $type = $this->getType();

        if (in_array($type, ['string', 'int', 'bool', 'null', 'array'], true)) {
            return false;
        }

        if (Strings::contains($type, '[]')) {
            return false;
        }

        return true;
    }

    public function getType(): ?string
    {
        $previousTokenPosition = $this->tokens->getPrevMeaningfulToken($this->index);
        $previousToken = $this->tokens[$previousTokenPosition];
        if (! $previousToken->isGivenKind(T_STRING)) {
            return null;
        }

        // probably not a class type
        return $previousToken->getContent();
    }

    public function getFqnType(): ?string
    {
        $previousTokenPosition = $this->tokens->getPrevMeaningfulToken($this->index);

        return ClassFqnResolver::resolveForNamePosition($this->tokens, $previousTokenPosition);
    }

    protected function changeNameWithTokenType(string $newName, int $tokenType): void
    {
        if ($tokenType === T_VARIABLE) {
            $newName = Strings::startsWith($newName, '$') ?: '$' . $newName;
        }

        $this->tokens[$this->getNamePosition()] = new Token([$tokenType, $newName]);
    }

    abstract protected function getNamePosition(): ?int;
}
