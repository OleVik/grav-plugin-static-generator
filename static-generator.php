<?php
/**
 * StaticGenerator Plugin
 *
 * PHP version 7
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage StaticGenerator
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-StaticGenerator
 */
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Persist Data and Pages from Grav
 *
 * PHP version 7
 *
 * @category Extensions
 * @package  Grav\Plugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-StaticGenerator
 */
class StaticGeneratorPlugin extends Plugin
{
    /**
     * Register intial event and libraries
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        include __DIR__ . '/vendor/autoload.php';
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onThemeInitialized' => ['onThemeInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin and events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }
    }
    public function onThemeInitialized()
    {
        // dump(\Grav\Theme\Scholar::getComposerClasses());
        // $classMap = array_keys(require('vendor/composer/autoload_classmap.php'));
        // dump($classMap);
    }
}
