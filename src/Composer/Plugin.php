<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerRepoPath\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Sweetchuck\ComposerRepoPath\Handler;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

    protected Event $event;

    protected Composer $composer;

    protected IOInterface $io;

    protected Handler $handler;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::COMMAND => 'onCommandEvent',
        ];
    }

    public function __construct(?Handler $handler = null)
    {
        $this->handler = $handler ?: new Handler();
    }

    /**
     * {@inheritDoc}
     */
    public function getCapabilities()
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    public function onCommandEvent(CommandEvent $event): bool
    {
        switch ($event->getCommandName()) {
            case 'install':
            case 'update':
                return $this->onCommandEventInstall($event);
        }

        return true;
    }

    protected function onCommandEventInstall(CommandEvent $event): bool
    {
        $repositories = $this->handler->getRepositories(ComposerFactory::getComposerFile());
        $this->handler->setLogger($this->io);
        $this->handler->setOutput($event->getOutput());
        $this->handler->downloadRepositories($repositories);

        return true;
    }
}
