<?php
/**
 * Static Generator Plugin, Source Manipulator
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Source
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator\Source;

use Grav\Common\Utils;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Page\Media;

/**
 * Source Manipulator
 *
 * @category Extensions
 * @package  Grav\Plugin\StaticGenerator\Source
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Source
{
    /**
     * Rewrite Page routes
     *
     * @param string $content Page HTML.
     * @param string $old     Original route.
     * @param string $new     New route.
     *
     * @return string Processed HTML
     */
    public static function rewritePageURLs(string $content, string $old, string $new): string
    {
        if ($old !== '/') {
            return str_replace($old, $new, $content);
        }
        return $content;
    }

    /**
     * Rewrite asset-paths
     *
     * @param string $content Page HTML.
     *
     * @return string Processed HTML
     */
    public static function rewriteAssetURLs(string $content): string
    {
        return preg_replace('/(link href|script src)="\//ui', '$1="/assets/', $content);
    }

    /**
     * Rewrite media-paths
     *
     * @param string $content Page HTML.
     * @param string $old     Original path.
     * @param string $new     New path.
     *
     * @return string Processed HTML
     */
    public static function rewriteMediaURLs(string $content, string $old, string $new): string
    {
        return str_replace($old, $new, $content);
    }
}
