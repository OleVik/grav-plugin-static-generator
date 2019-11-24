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
use Grav\Common\Page\Page;
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
     * @param bool   $content   Whether to include content.
     * @param int    $maxLength Maximum character-length of content.
     * @param string $orderBy   Property to order by.
     * @param string $orderDir  Direction to order.
     */
    public function __construct(bool $content = false, int $maxLength = null, string $orderBy = 'date', string $orderDir = 'desc')
    {
        $this->data = array();
        $this->content = $content;
        $this->maxLength = $maxLength;
        $this->orderBy = $orderBy;
        $this->orderDir = $orderDir;
    }

    /**
     * Declare headers
     *
     * @return void
     */
    public function headers()
    {
        error_reporting(0);
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * Bootstrap data
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
        if ($route == '/') {
            $this->pages = $this->grav['page']->evaluate(['@root.descendants']);
        } else {
            $this->pages = $this->grav['page']->evaluate(['@page.descendants' => $route]);
        }
        $this->progress = 1;
        $this->count = $this->count();
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
     * Parse Page content
     *
     * @param Page $page Instance of Grav\Common\Page\Page.
     *
     * @return string content
     */
    public function content(Page $page): string
    {
        return $page->rawMarkdown() ?? '';
    }

    /**
     * Create data-structure recursively
     *
     * @param string $route Route to page.
     * @param string $mode  Placeholder for operation-mode, private.
     * @param int    $depth Placeholder for recursion depth, private.
     *
     * @return mixed Index of Pages with FrontMatter
     */
    abstract public function index(string $route, string $mode = '', int $depth = 0);
}
