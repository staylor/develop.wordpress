<?php
/**
 * WordPress Custom Autoloader Generator.
 *
 * @package   WordPress\ComposerAutoload
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://www.alainschlesser.com/
 * @copyright 2016 Alain Schlesser
 *
 * Partially based on xrstf/composer-php52 by Christoph Mewes.
 * @see       https://github.com/composer-php52/composer-php52
 */

namespace WordPress\ComposerAutoload;

use Composer\Script\Event;

/**
 * Class Generator.
 *
 * Listen to the PostInstallCmd Event to dump an additional WordPress-specific autoloader.
 *
 * @since   1.0.0
 *
 * @package WordPress\ComposerAutoload
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class Generator
{

    public static function dump(Event $event)
    {
        $composer            = $event->getComposer();
        $installationManager = $composer->getInstallationManager();
        $repoManager         = $composer->getRepositoryManager();
        $localRepo           = $repoManager->getLocalRepository();
        $package             = $composer->getPackage();
        $config              = $composer->getConfig();

        // We can't gain access to the Command's input object, so we have to look
        // for -o / --optimize-autoloader ourselves. Sadly, neither getopt() works
        // (always returns an empty array), nor does Symfony's Console Input, as
        // it expects a full definition of the current command line and we can't
        // provide that.

        $args     = $_SERVER['argv'];
        $optimize = in_array('-o', $args) || in_array('--optimize-autoloader', $args) || in_array('--optimize', $args);

        $suffix = $config->get('autoloader-suffix');

        $extra         = $event->getComposer()->getPackage()->getExtra();
        $classRoot     = isset($extra['wordpress-autoloader']['class-root'])
            ? $extra['wordpress-autoloader']['class-root']
            : "ABSPATH";
        $caseSensitive = isset($extra['wordpress-autoloader']['case-sensitive'])
            ? (bool)$extra['wordpress-autoloader']['case-sensitive']
            : true;

        $generator = new AutoloadGenerator($event->getIO(), $classRoot, $caseSensitive);
        $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $optimize, $suffix);
    }
}
