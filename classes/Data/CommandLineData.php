<?php

/**
 * Static Generator Plugin, CLI Data Builder
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
use Symfony\Component\Console\Helper\ProgressBar;
use Grav\Plugin\StaticGenerator\Data\AbstractData;

/**
 * CLI Data Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Data\CommandLineData
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class CommandLineData extends AbstractData
{
    /**
     * Initialize
     *
     * @param string $route  Route to page.
     * @param \Symfony\Component\Console\Output $handle Console-wrapper.
     *
     * @return void
     */
    public function bootstrap($route, $handle)
    {
        parent::setup();
        if ($route == '/') {
            $this->pages = $this->grav['page']->evaluate(['@root.descendants']);
        } else {
            $this->pages = $this->grav['page']->evaluate(['@page.descendants' => $route]);
        }
        $this->count = $this->count();
        $this->progress = new ProgressBar($handle, $this->count);
    }

    /**
     * Increase counter
     *
     * @return void
     */
    public function progress(): void
    {
        $this->progress->advance();
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
            $mode = '@root.children';
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
            if (!$this->content) {
                $item['taxonomy']['categories'] = implode(' ', $item['taxonomy']['categories']);
                $item['taxonomy']['tags'] = implode(' ', $item['taxonomy']['tags']);
                if (isset($item['media']) && is_array($item['media'])) {
                    $item['media'] = implode(' ', $item['media']);
                }
            } else {
                try {
                    $pageContent = $this->content($page);
                    if (!empty($pageContent) && strlen($pageContent) <= $this->maxLength) {
                        $item['content'] = $pageContent;
                    }
                } catch (Exception $error) {
                    throw new Exception($error);
                }
            }
            if (count($page->children()) > 0) {
                $this->index($route, $mode, $depth);
            }
            $this->data[] = (object) $item;
            $this->progress();
        }
    }
}
