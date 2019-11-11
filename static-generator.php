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
use RocketTheme\Toolbox\Event\Event;
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Data;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Utilities;

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
        if ($this->isAdmin()) {
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
     * @param Event $event RocketTheme\Toolbox\Event\Event
     *
     * @return void
     */
    public function onGetPageTemplates(Event $event)
    {
        $event->types->scanBlueprints(
            $this->grav['locator']->findResource('plugin://' . $this->name . '/blueprints')
        );
    }

    public function onAdminMenu()
    {
        $options = [
            'authorize' => 'taskIndexSearch',
            'hint' => 'Index Search Data',
            'class' => 'grav-plugin-static-generator-search-index',
            'icon' => 'fa-search-plus'
        ];
        $this->grav['twig']->plugins_quick_tray['Search'] = $options;
    }

    public function onAdminTaskExecute(Event $event)
    {
        dump($event);
        // print_r
        if ($event['method'] == 'taskIndexSearch') {
            echo 'taskIndexSearch';
            // header('Content-type: application/json');

            // if (!$controller->authorizeTask('reindexTNTSearch', ['admin.configuration', 'admin.super'])) {
            //     $json_response = [
            //         'status'  => 'error',
            //         'message' => '<i class="fa fa-warning"></i> Index not created',
            //         'details' => 'Insufficient permissions to reindex the search engine database.'
            //     ];
            //     echo json_encode($json_response);
            //     exit;
            // }

            // error_reporting(1);
            // set_time_limit(0);
            // $response = [
            //     'status'  => 'success',
            //     'message' => 'Hello Msg'
            // ];
            // echo json_encode($response);
            exit;
        }
    }

    public function onTwigSiteVariables()
    {
        // $twig = $this->grav['twig'];
        // if ($this->query) {
        //     $twig->twig_vars['query'] = $this->query;
        //     $twig->twig_vars['tntsearch_results'] = $this->results;
        // }
        if ($this->config->get('plugins.static-generator.js')) {
            $this->grav['assets']->addJs('plugin://static-generator/js/site-generator.admin.js');
        }
    }

    public static function storeIndex(string $mode, string $route, string $slug)
    {
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $location = $config[$mode];
        try {
            $timer = new Timer();
            if ($location == 'persist') {
                $location = 'user://data/persist';
            } elseif ($location == 'transient') {
                $location = 'cache://transient';
            } else {
                return;
            }
            $Data = new Data(false, $config['maxLength']);
            $Data->setup($route);
            $Data->buildIndex($route);
            $Data->teardown();
            $Storage = new FileStorage($location);
            $file = $slug . '.js';
            if ($Storage->doHas($file)) {
                $Storage->doDelete($file);
            }
            $Storage->doSet($file, 'const GravMetadataIndex = ' . json_encode($Data->data) . ';', 0);
            return [
                'stored' => $location . '/' . $file,
                'time' => Timer::format($timer->getTime())
            ];
        } catch (\Exception $e) {
            throw new \Exception($e);
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
