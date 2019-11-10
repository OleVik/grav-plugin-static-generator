<?php
/**
 * Static Generator Plugin, Data Builder
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
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Common\Page\Header;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Data Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGeneratorPlugin\Data
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Data
{
    public $data;

    /**
     * Initialize class
     *
     * @param boolean $content   Whether to include content.
     * @param int     $maxLength Maximum character-length of content.
     * @param \Output $handle    Instance of Symfony\Component\Console\Output.
     * @param string  $orderBy   Property to order by.
     * @param string  $orderDir  Direction to order.
     */
    public function __construct($content = false, $maxLength = false, $handle = false, $orderBy = 'date', $orderDir = 'desc')
    {
        $this->data = array();
        $this->content = $content;
        $this->maxLength = $maxLength;
        $this->handle = $handle;
        $this->orderBy = $orderBy;
        $this->orderDir = $orderDir;
    }

    /**
     * Initialize progress-counter
     *
     * @param string $route Route to page.
     *
     * @return void
     */
    public function setup($route)
    {
        $this->progressBar = new ProgressBar($this->handle, $this->count($route));
    }

    /**
     * Finish progress-counter
     *
     * @return void
     */
    public function teardown()
    {
        $this->progressBar->finish();
    }

    /**
     * Count items
     *
     * @param string $route Route to page.
     *
     * @return int
     */
    public function count($route): int
    {
        $grav = Grav::instance();
        $grav['pages']->init();
        $pages = $grav['page']->evaluate(['@page.descendants' => $route]);
        return count($pages) + 1;
    }

    /**
     * Create data-structure recursively
     *
     * @param string  $route Route to page.
     * @param string  $mode  Placeholder for operation-mode, private.
     * @param integer $depth Placeholder for recursion depth, private.
     *
     * @return array Index of Pages with FrontMatter
     */
    public function buildIndex($route, $mode = false, $depth = 0)
    {
        $grav = Grav::instance();
        $grav['pages']->init();
        $grav['twig']->init();
        $depth++;
        $mode = '@page.self';
        if ($route == '/') {
            $mode = '@root.children';
        }
        if ($depth > 1) {
            $mode = '@page.children';
        }
        $pages = $grav['page']->evaluate([$mode => $route]);
        $pages = $pages->published()->order($this->orderBy, $this->orderDir);
        foreach ($pages as $page) {
            $route = $page->rawRoute();
            $item = array(
                'title' => $page->title(),
                'date' => \DateTime::createFromFormat('U', $page->date())->format('c'),
                'url' => $page->url(true, true, true),
                'taxonomy' => array(
                    'categories' => array(),
                    'tags' => array()
                )
            );
            if (isset($page->taxonomy()['category'])) {
                $item['taxonomy']['categories'] = array_merge(
                    $item['taxonomy']['categories'],
                    $page->taxonomy()['category']
                );
            }
            if (isset($page->taxonomy()['categories'])) {
                $item['taxonomy']['categories'] = array_merge(
                    $item['taxonomy']['categories'],
                    $page->taxonomy()['categories']
                );
            }
            if (isset($page->taxonomy()['tags'])) {
                $item['taxonomy']['tags'] = array_merge(
                    $item['taxonomy']['tags'],
                    $page->taxonomy()['tags']
                );
            }
            if (!empty($page->media()->all())) {
                $item['media'] = array_keys($page->media()->all());
            }
            if (!$this->content) {
                $item['taxonomy']['categories'] = implode(' ', $item['taxonomy']['categories']);
                $item['taxonomy']['tags'] = implode(' ', $item['taxonomy']['tags']);
                $item['media'] = implode(' ', $item['media']);
            }
            if ($this->content && !empty($page->content()) && strlen($page->content()) <= $this->maxLength) {
                $item['content'] = $page->content();
            }
            if (count($page->children()) > 0) {
                $this->buildIndex($route, $mode, $depth);
            }
            $this->data[] = (object) $item;
            $this->progressBar->advance();
        }
    }
}
