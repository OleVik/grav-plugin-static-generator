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
class Source implements SourceInterface
{
    /**
     * Instantiate class
     *
     * @param Page  $page  Page-instance
     * @param Pages $pages Pages-instance
     */
    public function __construct(Page $page, Pages $pages)
    {
        $this->page = $page;
        $this->pages = $pages;
    }

    /**
     * Determine origin of image
     *
     * @param string $source Image src-attribute
     * @param string $prefix Optional prefix to Page location
     *
     * @return array Image source, filename, and optionally Page
     */
    public function render(string $source, string $prefix = '')
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return [
                'src' => $source
            ];
        }
        $source = urldecode($source);
        $page = $media = $src = null;
        if (Utils::contains($source, '/')) {
            if (Utils::startsWith($source, '..')) {
                // chdir($this->page->path());
                // $folder = str_replace('\\', '/', realpath($source));
                // $page = $this->pages->get(dirname($folder));
            } elseif (Utils::startsWith($source, '/')) {
                // $page = $this->pages->find($prefix . dirname($source));
                $src = $source . '/index.html';
            } else {
                // $page = $this->pages->find('/' . dirname($source));
            }
        } else {
            // $page = $this->page;
        }
        // if ($page !== null) {
        //     $media = new Media($page->path());
        //     if ($media->get(basename($source))) {
        //         $src = $media->get(basename($source))->url();
        //     } else {
        //         $src = $source;
        //     }
        // }
        return [
            'src' => $src,
            // 'filename' => basename($source) ?? null,
            // 'page' => $page
        ];
    }

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

    /**
     * Manipulate content and build list from headings
     *
     * @param string $content HTML-content
     * @param bool   $itemize Assign indices to tags
     *
     * @return object [content, headings]
     */
    public static function pageNavigation(string $content, bool $itemize = false): object
    {
        if (empty($content)) {
            return (object) ['content' => '', 'headings' => ''];
        }
        $anchors = array();
        $targetElements = ['a'];
        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXpath($doc);
        libxml_clear_errors();
        $level = $index = 1;
        $elements = $xpath->query('//' . implode(' | //', $targetElements));
        foreach ($elements as $element) {
            $id = Inflector::hyphenize($element->nodeValue);
            if (in_array($element->nodeName, $targetHeadings)) {
                $id = Inflector::hyphenize($element->nodeValue);
                $level = (int) str_replace('h', '', $element->nodeName);
                $headings[$element->nodeValue] = ['href' => $id, 'level' => $level];
                $fragment = $doc->createDocumentFragment();
                $fragment->appendXML(
                    '<a name="' . $id . '"><' . $element->nodeName
                    . '>' . $element->textContent
                    . '</' . $element->nodeName . '></a>'
                );
                $element->parentNode->replaceChild($fragment, $element);
            } else {
                if ($itemize) {
                    $element->setAttribute('aria-label', '1.' . $index);
                    $index++;
                }
            }
        }
        return (object) [
            'content' => self::getInnerHTML(
                $doc->getElementsByTagName('body')[0],
                false
            ),
            'headings' => self::buildList($headings)
        ];
    }
}