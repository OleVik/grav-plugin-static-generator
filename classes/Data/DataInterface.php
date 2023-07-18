<?php

/**
 * Static Generator Plugin, Data Builder Interface
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

use Grav\Common\Page\Interfaces\PageInterface as Page;

/**
 * Data Builder Interface
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Data\DataInterface
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
interface DataInterface
{
    /**
     * Instantiate class
     *
     * @param string $url       Custom Base URL.
     * @param bool   $content   Whether to include content.
     * @param int    $maxLength Maximum character-length of content.
     * @param string $orderBy   Property to order by.
     * @param string $orderDir  Direction to order.
     */
    public function __construct(
        string $url = '',
        bool $content = false,
        int $maxLength = null,
        string $orderBy = 'date',
        string $orderDir = 'desc'
    );

    /**
     * Count items
     *
     * @return int
     */
    public function count(): int;

    /**
     * Increase counter
     *
     * @return void
     */
    public function progress(): void;

    /**
     * Create data-structure recursively
     *
     * @param string $route Route to page.
     * @param string $mode  Placeholder for operation-mode, private.
     * @param int    $depth Placeholder for recursion depth, private.
     *
     * @return mixed Index of Pages with FrontMatter
     */
    public function index(string $route, string $mode = '', int $depth = 0);

    /**
     * Parse Page content
     *
     * @param Page $page Instance of Grav\Common\Page\Page.
     *
     * @return string content
     */
    public function content(Page $page): string;
}
