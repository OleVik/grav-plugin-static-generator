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
use Grav\Common\Utils;
use Grav\Common\Page\Interfaces\PageInterface as Page;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
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
    public function __construct(
        $handle,
        string $collection,
        string $route = '',
        string $location = '',
        bool $force = false
    ) {
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
    public function setup(bool $offline): void
    {
        $this->grav = Grav::instance();
        $this->Filesystem = new Filesystem();
        $this->Timer = new Timer();
        $this->Assets = new Assets($this->Filesystem, $this->Timer, $offline);
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
            try {
                $this->store($Page);
            } catch (\Exception $error) {
                throw new \Exception($error);
            }
            $this->store($Page);
            $this->progressBar->advance();
        }
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

    public function mirrorImages(): void
    {
        $this->handle->writeln('<white>Processing Images</white>');
        $this->Filesystem->mirror(
            GRAV_ROOT . '/images',
            $this->location . '/images',
            null,
            [
                'override' => true,
                'copy_on_windows' => true,
                'delete' => true
            ]
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
            $content = $this->Assets->rewriteURL($content, $this->rootPrefix);
            $content = $this->Assets->rewriteMediaURL(
                $content,
                Utils::url($Page->getMediaUri()),
                $this->rootPrefix . $route
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
