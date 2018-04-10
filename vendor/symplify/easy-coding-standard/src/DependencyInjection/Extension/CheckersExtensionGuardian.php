<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\DependencyInjection\Extension;

use Nette\Utils\ObjectMixin;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use Symplify\EasyCodingStandard\Exception\DependencyInjection\Extension\FixerIsNotConfigurableException;
use Symplify\EasyCodingStandard\Exception\DependencyInjection\Extension\InvalidSniffPropertyException;

final class CheckersExtensionGuardian
{
    /**
     * @param mixed[] $configuration
     */
    public function ensureFixerIsConfigurable(string $fixerClass, array $configuration): void
    {
        if (is_a($fixerClass, ConfigurationDefinitionFixerInterface::class, true)) {
            return;
        }

        throw new FixerIsNotConfigurableException(sprintf(
            'Fixer "%s" is not configurable with configuration: %s.',
            $fixerClass,
            json_encode($configuration)
        ));
    }

    public function ensurePropertyExists(string $sniffClass, string $property): void
    {
        if (property_exists($sniffClass, $property)) {
            return;
        }

        $suggested = ObjectMixin::getSuggestion(array_keys(get_class_vars($sniffClass)), $property);

        throw new InvalidSniffPropertyException(sprintf(
            'Property "%s" was not found on "%s" sniff class in configuration. %s',
            $property,
            $sniffClass,
            $suggested ? sprintf('Did you mean "%s"?', $suggested) : ''
        ));
    }
}
