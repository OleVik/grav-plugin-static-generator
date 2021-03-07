<?php

/**
 * Static Generator Plugin, CLI Data Tester
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

use Grav\Plugin\StaticGenerator\Data\AbstractData;

/**
 * CLI Data Tester
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Data\TestData
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class TestData extends AbstractData
{
    /**
     * Initialize
     *
     * @param string $route Route to page.
     *
     * @return void
     */
    public function bootstrap($route)
    {
        if ($route == '/') {
            $this->pages = $this->grav['page']->evaluate(['@root.descendants']);
        } else {
            $this->pages = $this->grav['page']->evaluate(['@page.descendants' => $route]);
        }
        $this->count = $this->count();
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
    public function index(string $route, string $mode = '', int $depth = 0)
    {
        $depth++;
        $mode = '@page.self';
        if ($route == '/') {
            $mode = '@root.descendants';
        }
        if ($depth > 1) {
            $mode = '@page.children';
        }
        $pages = $this->grav['page']->evaluate([$mode => $route]);
        $pages = $pages->order($this->orderBy, $this->orderDir);
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
            try {
                $pageContent = $this->content($page) ?? 0;
            } catch (\Exception $error) {
                throw new \Exception($error);
            }
            echo '[' . $this->progress . '/' . $this->count . '] ' .
                $item['title'] . ' (' . strlen($pageContent) . " characters)\n";
            $this->data[] = (object) $item;
            $this->progress();
        }
    }
}
