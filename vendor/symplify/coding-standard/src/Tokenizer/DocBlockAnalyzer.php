<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Tokenizer;

use Nette\Utils\Strings;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Tokenizer\Token;

final class DocBlockAnalyzer
{
    public static function isArrayProperty(Token $token): bool
    {
        $docBlock = new DocBlock($token->getContent());

        if (! $docBlock->getAnnotationsOfType('var')) {
            return false;
        }

        $varAnnotation = $docBlock->getAnnotationsOfType('var')[0];

        return Strings::contains($varAnnotation->getContent(), '[]')
            && ! Strings::contains($varAnnotation->getContent(), '|');
    }

    /**
     * @param string[] $annotations
     */
    public static function hasAnnotations(DocBlock $docBlock, array $annotations): bool
    {
        $foundTypes = 0;
        foreach ($annotations as $annotation) {
            if ($docBlock->getAnnotationsOfType($annotation)) {
                ++$foundTypes;
            }
        }

        return $foundTypes === count($annotations);
    }
}
