<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * @codeCoverageIgnore
 */
final class ComposerPlugin implements EventSubscriberInterface, PluginInterface
{

    // phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    // phpcs:disable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction

    public function activate(Composer $composer, IOInterface $io): void
    {
        // Required by PluginInterface
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Required by PluginInterface
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Required by PluginInterface
    }

    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    // phpcs:enable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction

    public function runInstaller(): void
    {
        Installer::run(['cursor-rules', 'install', '--force']);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'runInstaller',
            ScriptEvents::POST_UPDATE_CMD => 'runInstaller',
        ];
    }

}
