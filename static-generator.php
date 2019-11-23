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
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Inflector;
use Grav\Common\Page\Pages;
use Grav\Framework\File\YamlFile;
use Grav\Framework\File\Formatter\YamlFormatter;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\StaticGenerator\Data;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Utilities;
use Grav\Plugin\StaticGenerator\Data\ServerSentEventsData;

/**
 * Persist Data and Pages from Grav
 *
 * PHP version 7
 *
 * @category Extensions
 * @package  Grav\Plugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
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
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin and events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin() && $this->config->get('plugins.static-generator.enabled_admin')) {
            $this->enable(
                [
                    'onGetPageTemplates' => ['onGetPageTemplates', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                    'onAdminMenu' => ['onAdminMenu', 0],
                    'onAdminTaskExecute' => ['onAdminTaskExecute', 0]
                ]
            );
        }
    }

    /**
     * Register Page blueprints
     *
     * @param Event $event Instance of RocketTheme\Toolbox\Event\Event.
     *
     * @return void
     */
    public function onGetPageTemplates(Event $event)
    {
        $event->types->scanBlueprints(
            $this->grav['locator']->findResource('plugin://' . $this->name . '/blueprints')
        );
    }

    /**
     * Register button in Admin Quick Tray
     *
     * @return void
     */
    public function onAdminMenu()
    {
        $options = [
            'authorize' => 'taskIndexSearch',
            'hint' => $this->grav['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.INDEX.HINT']
            ),
            'class' => 'grav-plugin-static-generator-search-index',
            'icon' => 'fa-search-plus'
        ];
        $this->grav['twig']->plugins_quick_tray['Search'] = $options;
    }

    /**
     * Handle Task-call from Admin
     *
     * @param Event $event Instance of RocketTheme\Toolbox\Event\Event.
     *
     * @return void
     */
    public function onAdminTaskExecute(Event $event)
    {
        if ($event['method'] == 'taskIndexSearch') {
            $mode = filter_input(
                INPUT_GET,
                'mode',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS
            );
            $route = filter_input(
                INPUT_GET,
                'route',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS
            );
            $slug = Inflector::hyphenize($route);
            if (empty($slug)) {
                $slug = 'index';
            }
            if (!$event['controller']->authorizeTask('indexSearch', ['admin.maintenance', 'admin.super'])) {
                header('HTTP/1.0 403 Forbidden');
                echo '403 Forbidden';
                exit;
            }
            self::storeIndex(
                urldecode($mode),
                '/' . urldecode($route),
                $slug
            );
        }
    }

    /**
     * Create and store Data Index
     *
     * @param string $mode  Mode of operation
     * @param string $route Route to Page
     * @param string $slug  Slug of Page
     *
     * @return void
     */
    public static function storeIndex(string $mode, string $route, string $slug)
    {
        include __DIR__ . '/vendor/autoload.php';
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $location = $config[$mode];
        try {
            $Timer = new Timer();
            if ($location == 'persist') {
                $location = 'user://data/persist';
            } elseif ($location == 'transient') {
                $location = 'cache://transient';
            } else {
                return;
            }
            $Data = new ServerSentEventsData(true, $config['content_max_length']);
            $Data->setup($route);
            $Data->bootstrap();
            $Data->index($route);
            $Data->teardown($location, $slug, $Data->data, $Timer);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Register assets
     *
     * @return void
     */
    public function onTwigSiteVariables()
    {
        $formatter = new YamlFormatter;
        $file = new YamlFile(
            $this->grav['locator']->findResource(
                'plugin://' . $this->name . '/languages.yaml',
                true,
                true
            ),
            $formatter
        );
        $translation = array();
        foreach (array_keys(Utils::arrayFlattenDotNotation($file->load())) as $key) {
            $key = str_replace('en.', '', $key);
            $translation[$key] = $this->grav['language']->translate([$key]);
        }
        $this->grav['assets']->addInlineJs(
            'const StaticGeneratorTranslation = ' . json_encode(
                Utils::arrayUnflattenDotNotation($translation)['PLUGIN_STATIC_GENERATOR']
            ) . ';'
        );

        if ($this->config->get('plugins.static-generator.js')) {
            $this->grav['assets']->addJs(
                'plugin://static-generator/node_modules/eventsource/example/eventsource-polyfill.js'
            );
            $this->grav['assets']->addJs(
                'plugin://static-generator/js/site-generator.admin.js'
            );
        }
    }

    /**
     * Get search files
     *
     * @param string $mode Mode of operation
     *
     * @return array Associative array of files
     */
    public static function getSearchFiles(string $mode): array
    {
        include __DIR__ . '/vendor/autoload.php';
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $target = $config[$mode];
        if ($target == 'persist') {
            $target = 'user://data/persist';
        } elseif ($target == 'transient') {
            $target = 'cache://transient';
        }
        $files = Utilities::filesFinder($target, ['js']);
        $searchFiles = array('' => 'None');
        foreach ($files as $file) {
            $name = $file->getBasename();
            $searchFiles[$target . '/' . $name] = $name;
        }
        return $searchFiles;
    }
}
