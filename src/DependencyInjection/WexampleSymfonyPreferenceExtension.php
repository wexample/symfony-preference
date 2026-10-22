<?php

namespace Wexample\SymfonyPreference\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyPreference\Enum\PreferenceStorage;
use Wexample\SymfonyPreference\Interface\PreferenceStorageInterface;
use Wexample\SymfonyPreference\Service\Storage\DatabasePreferenceStorage;
use Wexample\SymfonyPreference\Service\Storage\SessionPreferenceStorage;

class WexampleSymfonyPreferenceExtension extends AbstractWexampleSymfonyExtension
{
    final public const string PARAMETER_STORAGE = 'wexample_symfony_preference.storage';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        // Read before the bundle's own default is loaded over the app's choice.
        $storage = PreferenceStorage::from(
            $container->hasParameter(self::PARAMETER_STORAGE)
                ? $container->getParameter(self::PARAMETER_STORAGE)
                : PreferenceStorage::Session->value
        );

        $this->loadConfig(
            __DIR__,
            $container
        );

        $container->setAlias(
            PreferenceStorageInterface::class,
            $storage->isDatabase() ? DatabasePreferenceStorage::class : SessionPreferenceStorage::class
        );
    }
}
