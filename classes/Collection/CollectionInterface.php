<?php

/**
 * Static Generator Plugin, Collection Interface
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

use Grav\Common\Page\Page;

/**
 * Collection Interface
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection\CollectionInterface
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
interface CollectionInterface
{
    /**
     * Initialize class
     *
     * @param string  $collection Collection to evaluate.
     * @param string  $route      Route to page, optional.
     * @param string  $location   Where to store output.
     * @param boolean $force      Forcefully save data.
     * @param array   $filters    Methods to filter Collection by.
     */
    public function __construct(string $collection, string $route = '', string $location = '', bool $force = false, array $filters = []);

    /**
     * Bootstrap data, events, and helpers
     *
     * @param string $preset Name of Config Preset to load.
     *
     * @return void
     */
    public function setup(string $preset): void;

    /**
     * Build Page(s)
     *
     * @return void
     */
    public function buildCollection(): void;

    /**
     * Build assets
     *
     * @return void
     */
    public function buildAssets(): void;

    /**
     * Mirror images
     *
     * @return void
     */
    public function mirrorImages(): void;

    /**
     * Store Page
     *
     * @param Page $Page Grav Page instance.
     *
     * @return void
     */
    public function store(Page $Page): void;
}
