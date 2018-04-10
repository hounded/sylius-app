<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Validator;

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use Symplify\EasyCodingStandard\Exception\Validator\CheckerIsNotSupportedException;

final class CheckerTypeValidator
{
    /**
     * @var string[]
     */
    private $allowedCheckerTypes = [Sniff::class, FixerInterface::class];

    /**
     * @param string[] $checkers
     */
    public function validate(array $checkers, string $location): void
    {
        foreach ($checkers as $checker) {
            if ($this->isCheckerSupported($checker)) {
                continue;
            }

            throw new CheckerIsNotSupportedException(sprintf(
                'Checker "%s" was not found or is not supported. Use class that implements any of %s. '
                    . 'Invalid checker defined in %s',
                $checker,
                implode(' or ', $this->allowedCheckerTypes),
                $location
            ));
        }
    }

    private function isCheckerSupported(string $checker): bool
    {
        foreach ($this->allowedCheckerTypes as $allowedCheckerType) {
            if (is_a($checker, $allowedCheckerType, true)) {
                return true;
            }
        }

        return false;
    }
}
