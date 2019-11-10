<?php
/**
 * Static Generator Plugin, Collection Builder
 *
 * PHP version 7
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Common\Page\Header;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Collection Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Collection
{
    public $assets;

    /**
     * Initialize class
     *
     * @param \Output $handle     Instance of Symfony\Component\Console\Output.
     * @param string  $collection Collection to evaluate.
     * @param string  $route      Route to page, optional.
     * @param string  $location   Where to store output.
     * @param boolean $force      Forcefully save data.
     */
    public function __construct($handle, string $collection, string $route = '', string $location = '', bool $force = false)
    {
        include __DIR__ . '/../vendor/autoload.php';
        $this->assets = array();
        $this->handle = $handle;
        $this->collection = $collection;
        $this->route = $route;
        $this->location = $location;
        $this->force = $force;
    }

    /**
     * Bootstrap data, events, and helpers
     *
     * @return void
     */
    public function setup(): void
    {
        $this->grav = Grav::instance();
        $this->grav['twig']->init();
        $this->grav['themes']->init();
        $this->grav['assets']->init();
        $this->grav['pages']->init();
        $this->grav->fireEvent('onAssetsInitialized');
        $this->Filesystem = new Filesystem();
        $this->Timer = new Timer();
        $this->Assets = new Assets($this->handle, $this->Filesystem, $this->Timer);
    }

    /**
     * Finish progress-counter
     *
     * @return void
     */
    public function teardown()
    {
        $this->progressBar->finish();
        $this->handle->writeln('');
        $this->result->render();
    }

    /**
     * Build Page(s)
     *
     * @return void
     */
    public function buildCollection(): void
    {
        $this->result = new Table($this->handle);
        $this->result->setStyle('box');
        $this->result->setHeaders(['Title', 'Destination', 'Time']);
        $this->handle->writeln('<white>Processing Page(s)</white>');
        $pages = $this->grav['page']->evaluate([$this->collection => $this->route]);
        $this->progressBar = new ProgressBar(
            $this->handle,
            count($pages)
        );
        foreach ($pages as $Page) {
            $this->store($Page);
            $this->progressBar->advance();
        }
        foreach ($this->grav['assets']['assets_css'] as $cssAsset) {
            if (!in_array($cssAsset['asset'], $this->assets)) {
                $this->assets[] = $cssAsset['asset'];
            }
        }
        foreach ($this->grav['assets']['assets_js'] as $jsAsset) {
            if (!in_array($jsAsset['asset'], $this->assets)) {
                $this->assets[] = $jsAsset['asset'];
            }
        }
    }

    /**
     * Build assets
     *
     * @return void
     */
    public function buildAssets(): void
    {
        $this->result = new Table($this->handle);
        $this->result->setStyle('box');
        $this->result->setHeaders(['Title', 'Destination', 'Time']);
        $this->handle->writeln('<white>Processing Assets</white>');
        $this->progressBar = new ProgressBar(
            $this->handle,
            count($this->assets)
        );
        $this->Assets->copy(
            $this->assets,
            $this->location,
            $this->result,
            $this->progressBar,
            $this->force
        );
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
        try {
            $content = $this->grav['twig']->processTemplate(
                $Page->template() . '.' . $Page->templateFormat() . '.twig',
                ['page' => $Page]
            );
            $content = $this->Assets->rewriteURL($content);
            $content = $this->Assets->rewriteMediaURL(
                $content,
                Utils::url($Page->getMediaUri()),
                $route
            );
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
        try {
            $file = 'index.' . $Page->templateFormat();
            if ($this->force) {
                $this->Filesystem->remove($this->location . $route . DS . $file);
            }
            $this->Filesystem->dumpFile($this->location . $route . DS . $file, $content);
            $this->result->addRow(
                [
                    '<white>' . $Page->title() . '</white>',
                    '<cyan>' . $this->location . $route . '/' . $file . '</cyan>',
                    '<magenta>' . Timer::format($this->Timer->getTime()) . '</magenta>'
                ]
            );
            $this->Assets->copyMedia(
                $Page->media()->all(),
                $this->location . $Page->route(),
                $this->result,
                $this->force
            );
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
