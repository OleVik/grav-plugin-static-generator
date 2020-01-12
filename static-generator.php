<?php

/**
 * StaticGenerator Plugin
 *
 * PHP version 7
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage StaticGeneratorPlugin
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
use Grav\Common\Page\Page;
use Grav\Framework\File\YamlFile;
use Grav\Framework\File\Formatter\YamlFormatter;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\StaticGenerator\Data;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Utilities;
use Grav\Plugin\StaticGenerator\Data\SSEData;
use Grav\Plugin\StaticGenerator\Config\SSEConfig;

/**
 * Persist Data and Pages from Grav
 *
 * PHP version 7
 *
 * @category Extensions
 * @package  Grav\Plugin\StaticGeneratorPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class StaticGeneratorPlugin extends Plugin
{
    /**
     * Path for Presets Page
     *
     * @var string
     */
    protected $route = 'presets';

    /**
     * Register intial event and libraries
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0]
        ];
    }

    /**
     * Initialize the plugin and events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin() && $this->config->get('plugins.static-generator.admin')) {
            $this->enable(
                [
                    'onPageInitialized' => ['onPageInitialized', 0],
                    // 'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
                    // 'onGetPageTemplates' => ['onGetPageTemplates', 0],
                    'onTwigTemplatePaths' => ['onTwigAdminTemplatePaths', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                    'onAdminMenu' => ['onAdminMenu', 0],
                    'onAdminTaskExecute' => ['onAdminTaskExecute', 0]
                ]
            );
        }
    }

    public function onPageInitialized()
    {
        // if (strpos($this->grav['uri']->path(), $this->config->get('plugins.admin.route') . '/' . $this->route) === false) {
        //     return;
        // }
        // if ($this->grav['uri']->path() === $this->config->get('plugins.admin.route') . '/' . $this->route) {
        //     $page = $this->grav['pages']->dispatch($this->route);
        //     if (!$page) {
        //         $file = $this->grav['locator']->findResource(
        //             'plugin://' . $this->name . '/admin/pages/presets.md',
        //             true,
        //             true
        //         );
        //         $page = new Page();
        //         $page->init(
        //             new \SplFileInfo($file)
        //         );
        //     }
        //     $this->grav['page'] = $page;
        // }
    }

    public function onTwigAdminTemplatePaths()
    {
        // $this->grav['twig']->twig_paths[] = $this->grav['locator']->findResource(
        //     'plugin://' . $this->name . '/admin/templates'
        // );
        // dump('plugin://' . $this->name . '/blueprints');
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Register blueprints
     *
     * @param Event $event Instance of RocketTheme\Toolbox\Event\Event.
     *
     * @return void
     */
    public function onGetPageBlueprints(Event $event)
    {
        $types = $event->types;
        $types->scanBlueprints('plugins://' . $this->name . '/blueprints');
        // dump($event->types);
    }

    /**
     * Register templates
     *
     * @param Event $event Instance of RocketTheme\Toolbox\Event\Event.
     *
     * @return void
     */
    public function onGetPageTemplates(Event $event)
    {
    //     dump('plugin://' . $this->name . '/blueprints');
    //     dump('plugin://' . $this->name . '/admin/templates');
    //     $event->types->scanTemplates('plugin://' . $this->name . '/admin/templates');
        // $event->types->scanBlueprints('plugin://' . $this->name . '/blueprints');
    //     // $event->types->register('admin/pages/presets');
        
        // $types = $event->types;
        // $locator = Grav::instance()['locator'];
        // $types->scanBlueprints($locator->findResource('plugin://' . $this->name . '/blueprints'));
        // $types->scanTemplates($locator->findResource('plugin://' . $this->name . '/templates'));
        // dump($event->types);

        // $event->types->scanTemplates(
        //     Grav::instance()['locator']->findResource(
        //         'plugin://' . $this->name . '/admin/templates'
        //     )
        // );
        // $event->types->scanBlueprints(
        //     Grav::instance()['locator']->findResource(
        //         'plugin://' . $this->name . '/blueprints'
        //     )
        // );
    }

    /**
     * Register button in Admin Quick Tray
     *
     * @return void
     */
    public function onAdminMenu()
    {
        if ($this->config->get('plugins.static-generator.presets_page')) {
            $options = [
                'authorize' => 'taskIndexSearch',
                'hint' => $this->grav['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.INDEX.HINT']
                ),
                'class' => 'static-generator-search-index',
                'icon' => 'fa-bolt'
            ];
            $this->grav['twig']->plugins_quick_tray[
                $this->grav['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.SEARCH']
                )
            ] = $options;
        }
        if ($this->config->get('plugins.static-generator.presets_page')) {
            $this->grav['twig']->plugins_hooked_nav[
                $this->grav['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.PRESETS']
                )
            ] = [
                'route' => $this->route,
                'icon' => 'fa-th-list',
                'authorize' => 'admin.configuration'
            ];
        }
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
            if (!$event['controller']->authorizeTask('indexSearch', ['admin.maintenance', 'admin.super'])) {
                header('HTTP/1.0 403 Forbidden');
                echo '403 Forbidden';
                exit;
            }
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
            self::storeIndex(
                urldecode($mode),
                '/' . urldecode($route)
            );
        } elseif ($event['method'] == 'taskCopyPreset') {
            if (!$event['controller']->authorizeTask('copyPreset', ['admin.maintenance', 'admin.super'])) {
                header('HTTP/1.0 403 Forbidden');
                echo '403 Forbidden';
                exit;
            }
            $preset = filter_input(
                INPUT_GET,
                'preset',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS
            );
            self::copyPreset(
                $preset,
                $this->grav['locator']->findResource(
                    $this->grav['config']->get('plugins.static-generator.content')
                    . '/presets/' . $preset
                ),
                $this->grav['locator']->findResource('config://')
            );
        }
    }

    /**
     * Mirror Config
     *
     * @param string  $name     Preset name.
     * @param string  $location Location to store Config in.
     * @param string  $source   Source to copy from.
     * @param boolean $force    Forcefully save.
     *
     * @return void
     */
    public static function copyPreset(
        string $name,
        string $location,
        string $source,
        bool $force = true
    ): void {
        include __DIR__ . '/vendor/autoload.php';
        try {
            SSEConfig::headers();
            $Timer = new Timer();
            $Data = new SSEConfig();
            $Data->mirror(
                $name,
                $location,
                $source,
                $Timer,
                $force
            );
            SSEConfig::finish();
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Create and store Data Index
     *
     * @param string $mode  Mode of operation.
     * @param string $route Route to Page.
     *
     * @return void
     */
    public static function storeIndex(string $mode, string $route)
    {
        include __DIR__ . '/vendor/autoload.php';
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $location = $config[$mode];
        try {
            SSEData::headers();
            $Timer = new Timer();
            $Data = new SSEData(true, $config['content_max_length']);
            $Data->setup();
            $route = $Data->verify($route);
            $Data->index($route);
            $slug = Inflector::hyphenize($route);
            $Data->teardown($location, $slug, $Data->data, $Timer);
            SSEData::finish();
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
        $files = Utilities::filesFinder($target, ['js']);
        $searchFiles = array('' => 'None');
        foreach ($files as $file) {
            $name = $file->getFilename();
            $searchFiles[$target . '/' . $name] = $name;
        }
        return $searchFiles;
    }


    /**
     * Get Blueprint fields
     *
     * @param string $path   Path to blueprint
     * @param string $prefix Optional key-prefix
     *
     * @return array Associative, nested array of settings
     */
    public static function getBlueprintFields(string $path, string $prefix = ''): array
    {
        $config = Grav::instance()['config'];
        $locator = Grav::instance()['locator'];
        $formatter = new YamlFormatter;
        $file = new YamlFile($locator->findResource($path, true, true), $formatter);
        $return = array();
        foreach ($file->load() as $name => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $property) {
                    if (Utils::contains($key, '@')) {
                        $key = str_replace(['data-', '@'], '', $key);
                        if (is_string($property)) {
                            $data[$key] = call_user_func_array(
                                $property,
                                []
                            );
                        } elseif (is_array($property)) {
                            $data[$key] = call_user_func_array(
                                $property[0],
                                array_slice($property, 1, count($property)-1, true)
                            );
                        }
                    }
                }
            }
            if (!isset($data['name'])) {
                $data['name'] = $prefix . $name;
            }
            if (!isset($data['default']) && $config->get('theme.' . $name)) {
                $data['default'] = $config->get('theme.' . $name);
            }
            $return [$prefix . $name] = $data;
        }
        return $return;
    }
}
