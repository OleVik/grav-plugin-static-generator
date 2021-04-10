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
     * Rewrite asset-paths
     *
     * @param string $content    Page HTML.
     * @param string $rootPrefix Root prefix.
     *
     * @return string Processed HTML
     */
    public static function rewriteAssetURLs(
        string $content,
        string $rootPrefix
    ): string {
        preg_match_all('/<(?:link href|script src)="(?<url>[^"]*)"/ui', $content, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $asset) {
            if (!isset($asset['url'])) {
                continue;
            }
            if (Utils::startsWith($asset['url'], '/user')) {
                $target = $asset['url'];
            } elseif (Utils::startsWith($asset['url'], '/system')) {
                $target = $asset['url'];
            } else {
                $url = parse_url($asset['url']);
                $target = '/' . $url['host'] . $url['path'];
            }
            $content = str_replace(
                $asset['url'],
                $rootPrefix . 'assets' . $target,
                $content
            );
        }
        return $content;
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
    public static function rewritePath(
        string $content,
        string $old,
        string $new
    ): string {
        return str_replace($old, $new, $content);
    }

    /**
     * Rewrite Page-routes
     *
     * @param string $content Page HTML.
     * @param string $routes  Page routes.
     *
     * @return string Processed HTML
     */
    public static function rewriteRoutes(
        string $content,
        array $routes
    ): string {
        foreach ($routes as $route) {
            if ($route !== '/') {
                $route = \ltrim($route, '/');
                $content = str_replace('//' . $route, '/' . $route, $content);
            }
        }
        return $content;
    }

    /**
     * Rewrite Media-routes, in src-attribute
     *
     * @param string $content
     *
     * @return string Processed HTML
     */
    public static function rewriteMediaRoutes(string $content): string
    {
        return preg_replace('/src="\/\/[0-9]*\.*/mi', 'src="/', $content);
    }
}
