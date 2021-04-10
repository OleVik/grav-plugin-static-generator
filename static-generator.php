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
use Grav\Framework\File\YamlFile;
use Grav\Framework\File\Formatter\YamlFormatter;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\StaticGenerator\Data;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Utilities;
use Grav\Plugin\StaticGenerator\Data\SSEData;
use Grav\Plugin\StaticGenerator\Config\SSEConfig;
use Grav\Plugin\StaticGenerator\Collection\SSECollection;

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
                    'onTwigTemplatePaths' => ['onTwigAdminTemplatePaths', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                    'onAdminMenu' => ['onAdminMenu', 0],
                    'onAdminTaskExecute' => ['onAdminTaskExecute', 0]
                ]
            );
        }
    }

    public function onTwigAdminTemplatePaths()
    {
        $data = array();
        $data['blueprints://'] = $this->grav['locator']->findResource('blueprints://');
        foreach ($this->grav['locator']->getSchemes() as $stream) {
            $data['schemes'][$stream] = Utils::url($stream . '://');
        }
        foreach ($this->grav['streams']->getStreams() as $name => $stream) {
            $data['streams'][$name] = Utils::url($name . '://');
        }
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
    }

    /**
     * Register button in Admin Quick Tray
     *
     * @return void
     */
    public function onAdminMenu()
    {
        if ($this->config->get('plugins.static-generator.quick_tray')) {
            $index = [
                'authorize' => $this->config->get('plugins.static-generator.quick_tray_permissions'),
                'hint' => $this->grav['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.INDEX.HINT']
                ),
                'class' => 'static-generator-index',
                'icon' => 'fa-bolt'
            ];
            $this->grav['twig']->plugins_quick_tray['static-generator-index'] = $index;
            $content = [
                'authorize' => $this->config->get('plugins.static-generator.quick_tray_permissions'),
                'hint' => $this->grav['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.CONTENT.HINT']
                ),
                'class' => 'static-generator-content',
                'icon' => 'fa-archive'
            ];
            $this->grav['twig']->plugins_quick_tray['static-generator-content'] = $content;
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
        if (
            $event['method'] == 'taskStaticGeneratorIndex' ||
            $event['method'] == 'taskStaticGeneratorContent'
        ) {
            if (!$event['controller']->authorizeTask($event['method'], ['admin.maintenance', 'admin.super'])) {
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
                $this->grav['config']->get('plugins.static-generator.content'),
                $this->grav['locator']->findResource('config://')
            );
        } elseif ($event['method'] == 'taskGenerateFromPreset') {
            if (!$event['controller']->authorizeTask('generateFromPreset', ['admin.maintenance', 'admin.super'])) {
                header('HTTP/1.0 403 Forbidden');
                echo '403 Forbidden';
                exit;
            }
            $preset = filter_input(
                INPUT_GET,
                'preset',
                FILTER_SANITIZE_FULL_SPECIAL_CHARS
            );
            self::generateFromPreset(
                $preset,
                $this->grav['locator']->findResource(
                    $this->grav['config']->get('plugins.static-generator.content')
                        . '/presets/' . $preset
                )
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
        try {
            SSEData::headers();
            $Timer = new Timer();
            $Data = new SSEConfig();
            $Data->mirror(
                $name,
                $location,
                $source,
                $Timer,
                $force
            );
            SSEData::finish();
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Generatic static site from preset
     *
     * @param string  $name     Preset name.
     * @param string  $location Location to store Data in.
     * @param boolean $force    Forcefully save.
     *
     * @return void
     */
    public static function generateFromPreset(
        string $name,
        string $location,
        bool $force = true
    ): void {
        // WIP
        exit;
        try {
            SSEData::headers();
            $Timer = new Timer();
            $Data = new SSECollection('@root', '/', $location);
            $Data->setup($name);
            $Data->collection();
            SSEData::finish();
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
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $location = $config[$mode];
        try {
            SSEData::headers();
            $Timer = new Timer();
            $Data = new SSEData(
                $mode == 'content' ? true : false,
                $config['content_max_length']
            );
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
        if ($this->config->get('plugins.static-generator.css')) {
            $this->grav['assets']->addInlineCss(
                'pre.static-generator-command[data-header] {
                    position: relative;
                    margin: 1rem 1.5rem 0 1.5rem;
                    padding: 1.75rem 0.75rem 0.75rem 0.75rem;
                    white-space: pre-wrap;
                }
                pre.static-generator-command[data-header]:before {
                    content: attr(data-header);
                    display: block;
                    position: absolute;
                    top: 0;
                    right: 0;
                    left: 0;
                    background-color: #666666;
                    padding: 0 0.5rem;
                    font: bold 11px/20px Arial,Sans-Serif;
                    color: #ffffff;
                }'
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
                                array_slice($property, 1, count($property) - 1, true)
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
            $return[$prefix . $name] = $data;
        }
        return $return;
    }

    /**
     * Get available Admin permissions
     *
     * @return array Key-value array of permissions
     */
    public static function getAdminPermissionsBlueprint(): array
    {
        $return = array();
        if (!isset(Grav::instance()['admin'])) {
            return $return;
        }
        if (\method_exists(Grav::instance()['admin'], 'getPermissions')) {
            $permissions = Grav::instance()['admin']->getPermissions();
        } elseif (\method_exists(Grav::instance()['permissions'], 'getInstances')) {
            $permissions = Grav::instance()['permissions']->getInstances();
        }
        if (is_array($permissions) && !empty($permissions)) {
            foreach (array_keys($permissions) as $permission) {
                $return[] = [
                    'text' => $permission,
                    'value' => $permission
                ];
            }
        }
        return $return;
    }

    /**
     * Composer autoload.
     *
     * @return \Composer\Autoload\ClassLoader
     */
    public function autoload(): \Composer\Autoload\ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }
}
