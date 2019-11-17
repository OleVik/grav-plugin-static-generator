<?php
/**
 * Static Generator Plugin, Data Builder
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Data
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator\Data;

use Grav\Common\Grav;
// use Grav\Common\Plugin;
// use Grav\Common\Page\Page;
// use Grav\Common\Page\Media;
// use Grav\Common\Page\Header;
// use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\StaticGenerator\Data\DataInterface;

/**
 * Data Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGeneratorPlugin\Data\AbstractData
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
abstract class AbstractData implements DataInterface
{
    public $data;
    public $grav;
    public $pages;
    public $progress;
    public $count;

    /**
     * Instantiate class
     *
     * @param boolean $content   Whether to include content.
     * @param int     $maxLength Maximum character-length of content.
     * @param string  $orderBy   Property to order by.
     * @param string  $orderDir  Direction to order.
     */
    public function __construct($content = false, $maxLength = false, $orderBy = 'date', $orderDir = 'desc')
    {
        $this->data = array();
        $this->content = $content;
        $this->maxLength = $maxLength;
        $this->orderBy = $orderBy;
        $this->orderDir = $orderDir;
    }

    /**
     * Initialize
     *
     * @param string $route Route to page.
     *
     * @return void
     */
    public function setup($route)
    {
        if (isset(Grav::instance()['admin'])) {
            if (method_exists(Grav::instance()['admin'], 'enablePages')) {
                Grav::instance()['admin']->enablePages();
            }
        }
        $this->grav = Grav::instance();
        $this->grav['pages']->init();
        $this->grav['twig']->init();
        $this->pages = $this->grav['page']->evaluate(['@page.descendants' => $route]);
        $this->progress = 0;
        $this->count = $this->count($route);
    }

    /**
     * Count items
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->pages) + 1;
    }

    /**
     * Create data-structure recursively
     *
     * @param string  $route Route to page.
     * @param string  $mode  Placeholder for operation-mode, private.
     * @param integer $depth Placeholder for recursion depth, private.
     *
     * @return mixed Index of Pages with FrontMatter
     */
    abstract public function buildIndex($route, $mode = false, $depth = 0);
}
