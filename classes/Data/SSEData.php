<?php

/**
 * Static Generator Plugin, Server Sent Events Data Builder
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
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Data\AbstractData;

/**
 * Server Sent Events Data Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Data\SSEData
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class SSEData extends AbstractData
{
    /**
     * Declare headers
     *
     * @return void
     */
    public static function headers()
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
     * Check count before progressing
     *
     * @param string $route Route to page.
     *
     * @return string
     */
    public function verify(string $route): string
    {
        $mode = '@page.descendants';
        if ($route == '/') {
            $mode = '@root.descendants';
        }
        $this->pages = $this->grav['page']->evaluate([$mode => $route]);
        if ($this->count() < 1) {
            $route = '/';
            $this->pages = $this->grav['page']->evaluate(['@root.descendants' => '/']);
        }
        if ($this->count() > 0) {
            $this->total = $this->count();
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'total' => $this->count()
                ]
            ) . "\n\n";
        } else {
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'content' => Grav::instance()['language']->translate(
                        ['PLUGIN_STATIC_GENERATOR.ADMIN.EMPTY']
                    ) . '.'
                ]
            ) . "\n\n";
            echo 'event: close' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'content' => 'END-OF-STREAM'
                ]
            ) . "\n\n";
        }
        return $route;
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
        $this->pages = $this->grav['page']->evaluate([$mode => $route]);
        $this->pages = $this->pages->order($this->orderBy, $this->orderDir);
        foreach ($this->pages as $page) {
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
                } catch (\Exception $error) {
                    throw new \Exception($error);
                }
            }
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'progress' => $this->progress,
                    'content' => $page->title()
                ]
            ) . "\n\n";
            $this->progress();
            if (count($page->children()) > 0) {
                $this->index($route, $mode, $depth);
            }
            $this->data[] = (object) $item;
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            if (connection_aborted()) {
                exit;
            }
        }
    }

    /**
     * Cleanup
     *
     * @param string $location Stream to storage-folder.
     * @param string $slug     Hyphenized Page-route.
     * @param array  $data     Data to store.
     * @param Timer  $Timer    Instance of Grav\Plugin\StaticGenerator\Timer.
     *
     * @return void
     */
    public function teardown(string $location, string $slug, array $data, Timer $Timer): void
    {
        if (empty($slug)) {
            $slug = 'index';
        }
        $file = $slug . '.js';
        if ($this->content) {
            $file = $slug . '.full.js';
        }
        $location = (string) (Grav::instance()['locator']->findResource($location) ?:
            Grav::instance()['locator']->findResource($location, true, true));
        $Storage = new FileStorage($location);
        if ($Storage->doHas($file)) {
            $Storage->doDelete($file);
        }
        if ($this->content) {
            $Storage->doSet($file, 'const GravDataIndex = ' . json_encode($data) . ';', 0);
        } else {
            $Storage->doSet($file, 'const GravMetadataIndex = ' . json_encode($data) . ';', 0);
        }
        $message = ucfirst(
            $this->grav['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.STORED']
            )
        ) . ' ' . $this->total . ' ' .
            $this->grav['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.ITEMS']
            ) . ' ' .
            $this->grav['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
            ) . ' ' . $location . '/' . $file . ' ' .
            $this->grav['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
            ) . ' ' . Timer::format($Timer->getTime()) . '.';
        echo 'event: update' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'content' => $message,
                'text' => $file,
                'value' => $location . '/' . $file
            ]
        ) . "\n\n";
        Grav::instance()['log']->info($message);
    }

    /**
     * Finish stream
     *
     * @return void
     */
    public static function finish(): void
    {
        echo 'event: close' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'content' => 'END-OF-STREAM'
            ]
        ) . "\n\n";
        exit;
    }
}
