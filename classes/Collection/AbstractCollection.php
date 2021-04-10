<?php

/**
 * Static Generator Plugin, Abstract Collection Builder
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Collection
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */

namespace Grav\Plugin\StaticGenerator\Collection;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Page\Interfaces\PageInterface as Page;
use Grav\Common\Page\Collection;
use Grav\Plugin\StaticGenerator\Collection\CollectionInterface;
use Grav\Plugin\StaticGenerator\Config\Config;
use Grav\Plugin\StaticGenerator\Assets;
use Grav\Plugin\StaticGenerator\Source\Source;
use Grav\Plugin\StaticGenerator\Timer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Abstract Collection Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection\AbstractCollection
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
abstract class AbstractCollection implements CollectionInterface
{
    public $assets;
    public $routes;

    /**
     * Initialize class
     *
     * @param string  $collection Collection to evaluate.
     * @param string  $route      Route to page, optional.
     * @param string  $location   Where to store output.
     * @param boolean $force      Forcefully save data.
     * @param string  $rootPrefix Root prefix.
     * @param array   $filters    Methods to filter Collection by.
     * @param array   $parameters Parameters to pass to Config or Twig.
     */
    public function __construct(
        string $collection,
        string $route = '',
        string $location = '',
        bool $force = false,
        string $rootPrefix = '',
        array $filters = [],
        array $parameters = []
    ) {
        $this->assets = array();
        $this->route = $route;
        $this->collection = $collection;
        $this->location = $location;
        $this->force = $force;
        $this->rootPrefix = $rootPrefix;
        $this->filters = $filters;
        $this->parameters = $parameters;
    }

    /**
     * Bootstrap data, events, and helpers
     *
     * @param string $preset  Name of Config Preset to load.
     * @param bool   $offline Force offline-mode.
     *
     * @return void
     */
    public function setup(string $preset, $offline): void
    {
        $this->Filesystem = new Filesystem();
        $this->Timer = new Timer();
        $this->Assets = new Assets($this->Filesystem, $this->Timer, $offline);
        $this->Filesystem->mkdir($this->location);
        $this->grav = Grav::instance();
        if (!empty($preset)) {
            $presetLocation = $this->grav['locator']->findResource(
                'user-data://persist/presets/' . $preset,
                true,
                true
            );
            if ($this->Filesystem->exists($presetLocation)) {
                $Config = new Config(
                    $this->grav['config'],
                    'user-data://persist/presets',
                    $preset
                );
                $this->grav['config']->merge($Config->config->toArray());
            }
            $this->parameters = Config::getPresetParameters(
                $this->grav['config'],
                $preset
            );
        }
        Config::applyParameters(
            $this->grav['config'],
            $this->grav['twig'],
            $this->parameters
        );
        if ($this->route == '/') {
            $this->collection = '@root.descendants';
        }
        if (in_array('all', $this->filters)) {
            $this->pages = $this->grav['pages']->all();
        } else {
            $this->pages = $this->grav['page']->evaluate(
                [$this->collection => $this->route]
            );
        }
        $this->pages = $this->filterCollection($this->pages, $this->filters);
        unset($this->grav['page']);
        $this->grav['page'] = $this->grav['pages']->dispatch($this->route);
        $this->count = $this->count();
    }

    /**
     * Filter Collection
     *
     * @param Collection $Collection Pages to filter
     * @param array      $filters    Methods to filter Collection by.
     *
     * @return Collection Filtered Pages
     */
    public function filterCollection(
        Collection $Collection,
        array $filters
    ): Collection {
        foreach ($filters as $filter) {
            if (method_exists($Collection, $filter)) {
                $Collection->$filter();
            }
        }
        return $Collection;
    }

    /**
     * Store routes and Build Page(s)
     *
     * @return void
     */
    public function buildCollection(): void
    {
        foreach ($this->pages as $Page) {
            $this->routes[] = $Page->route();
        }
        foreach ($this->pages as $Page) {
            try {
                $this->store($Page);
            } catch (\Exception $error) {
                throw new \Exception($error);
            }
        }
    }

    /**
     * Build Assets
     *
     * @return void
     */
    public function buildAssets(): void
    {
        foreach ($this->grav['assets']['assets_css'] as $key => $Asset) {
            if (!in_array($Asset['asset'], $this->assets) && get_class($Asset) == 'Grav\Common\Assets\Css') {
                $this->assets[] = $Asset['asset'];
            }
        }
        foreach ($this->grav['assets']['assets_js'] as $key => $Asset) {
            if (!in_array($Asset['asset'], $this->assets) && get_class($Asset) == 'Grav\Common\Assets\Js') {
                $this->assets[] = $Asset['asset'];
            }
        }
        foreach ($this->assets as $asset) {
            $this->reporter(
                $this->Assets->copy(
                    $asset,
                    $this->location,
                    $this->force
                ),
                'white'
            );
        }
    }

    /**
     * Mirror Static Assets
     *
     * @param array $folders    Folders to mirror below /user.
     * @param array $extensions File-extensions to mirror.
     *
     * @return void
     */
    public function mirrorStaticAssets(
        array $folders = ['/plugins', '/themes'],
        array $extensions = ['ttf', 'eot', 'otf', 'woff', 'woff2']
    ): void {
        foreach ($folders as $folder) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(GRAV_ROOT . '/user' . $folder)
            );
            $fileIterator = new \RegexIterator($iterator, '/\.' . implode('|', $extensions) . '$/imu');
            try {
                $this->Filesystem->mirror(
                    GRAV_ROOT . '/user',
                    $this->location . '/assets/user',
                    $fileIterator,
                    [
                        'override' => true,
                        'copy_on_windows' => true,
                        'delete' => false,
                    ]
                );
            } catch (\Exception $e) {
                throw new \Exception($e);
            }
        }
    }

    /**
     * Mirror Images
     *
     * @param boolean $force Forcefully save data.
     *
     * @return void
     */
    public function mirrorImages(bool $force): void
    {
        try {
            $this->Filesystem->mirror(
                GRAV_ROOT . '/images',
                $this->location . '/images',
                null,
                [
                    'override' => true,
                    'copy_on_windows' => true,
                    'delete' => false,
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Store Page
     *
     * @param Page $Page Grav Page instance.
     *
     * @return void
     */
    public function store(Page $Page): void
    {
        $route = $Page->route() == '/' ? '' : $Page->route();
        $template = $Page->template() . '.' . $Page->templateFormat() . '.twig';
        if (!$this->grav['twig']->loader()->exists($template)) {
            $message = 'Template not loaded or found.';
            $this->reporter(
                [
                    'item' => $Page->title() . ' (' . $template . ')',
                    'location' => $message,
                    'time' => Timer::format($this->Timer->getTime()),
                ],
                'red'
            );
            Grav::instance()['log']->info($message);
            return;
        }
        try {
            $content = $this->grav['twig']->processTemplate(
                $template,
                ['page' => $Page]
            );
            $content = Source::rewriteRoutes(
                $content,
                $this->routes
            );
            $content = Source::rewriteAssetURLs($content, $this->rootPrefix);
            $content = Source::rewritePath(
                $content,
                Utils::url($Page->getMediaUri()),
                $this->rootPrefix . $route
            );
            $content = Source::rewritePath(
                $content,
                '/user/pages',
                $this->rootPrefix . ''
            );
            $content = Source::rewritePath(
                $content,
                '/images/',
                $this->rootPrefix . 'images/'
            );
            $content = Source::rewriteMediaRoutes($content);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
        try {
            $file = 'index.' . $Page->templateFormat();
            if ($this->force) {
                $this->Filesystem->remove($this->location . $route . DS . $file);
            }
            $this->Filesystem->dumpFile($this->location . $route . DS . $file, $content);
            $this->reporter(
                [
                    'item' => $Page->title() . ' (' . $template . ')',
                    'location' => $this->location . $route . '/' . $file,
                    'time' => Timer::format($this->Timer->getTime()),
                ]
            );
            foreach ($Page->media()->all() as $filename => $data) {
                $this->reporter(
                    $this->Assets->copyMedia(
                        $filename,
                        $data->path,
                        $this->location . $Page->route(),
                        $this->force
                    ),
                    'yellow'
                );
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count items
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * Increase counter
     *
     * @return void
     */
    public function progress(): void
    {
        $this->progress++;
    }

    /**
     * Report results
     *
     * @param array $items Items to report
     *
     * @return void
     */
    public function reporter(array $items): void
    {
        foreach ($items as $item) {
            echo $item . "\n";
        }
    }
}
