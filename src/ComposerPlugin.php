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

    private ?Composer $composer = null;

    // phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }

    // phpcs:disable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction

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
        if (!$this->isAutoInstallEnabled()) {
            return;
        }

        $editor = $this->resolveEditorFromConfig();
        Installer::run(['cursor-rules', 'install', '--force', '--editor=' . $editor]);
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

    private function isAutoInstallEnabled(): bool
    {
        if ($this->composer === null) {
            return false;
        }

        $extra = $this->composer->getPackage()->getExtra();

        return ($extra['cursor-rules']['auto-install'] ?? false) === true;
    }

    private function resolveEditorFromConfig(): string
    {
        if ($this->composer === null) {
            return InstallerPath::EDITOR_CURSOR;
        }

        $extra = $this->composer->getPackage()->getExtra();
        $editor = $extra['cursor-rules']['editor'] ?? InstallerPath::EDITOR_CURSOR;

        return in_array($editor, InstallerPath::getAllowedEditors(), true)
            ? $editor
            : InstallerPath::EDITOR_CURSOR;
    }

}
