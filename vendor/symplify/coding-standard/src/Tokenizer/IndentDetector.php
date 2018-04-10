<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Tokenizer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;

final class IndentDetector
{
    /**
     * @var WhitespacesFixerConfig
     */
    private $whitespacesFixerConfig;

    private function __construct(WhitespacesFixerConfig $whitespacesFixerConfig)
    {
        $this->whitespacesFixerConfig = $whitespacesFixerConfig;
    }

    public static function createFromWhitespacesFixerConfig(WhitespacesFixerConfig $whitespacesFixerConfig): self
    {
        return new self($whitespacesFixerConfig);
    }

    public function detectOnPosition(Tokens $tokens, int $arrayStartIndex): int
    {
        for ($i = $arrayStartIndex; $i > 0; --$i) {
            $token = $tokens[$i];

            if ($token->isWhitespace() && $token->getContent() !== ' ') {
                return substr_count($token->getContent(), $this->whitespacesFixerConfig->getIndent());
            }
        }

        return 0;
    }
}
