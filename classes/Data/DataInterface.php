<?php
/**
 * Static Generator Plugin, Data Interface
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

/**
 * Data Interface
 *
 * @category API
 * @package  Grav\Plugin\StaticGeneratorPlugin\Data\DataInterface
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
interface DataInterface
{
    /**
     * Instantiate class
     *
     * @param boolean $content   Whether to include content.
     * @param int     $maxLength Maximum character-length of content.
     * @param string  $orderBy   Property to order by.
     * @param string  $orderDir  Direction to order.
     */
    public function __construct($content = false, $maxLength = false, $orderBy = 'date', $orderDir = 'desc');

    /**
     * Count items
     *
     * @return int
     */
    public function count(): int;

    /**
     * Create data-structure recursively
     *
     * @param string  $route Route to page.
     * @param string  $mode  Placeholder for operation-mode, private.
     * @param integer $depth Placeholder for recursion depth, private.
     *
     * @return mixed Index of Pages with FrontMatter
     */
    public function buildIndex($route, $mode = false, $depth = 0);
}
