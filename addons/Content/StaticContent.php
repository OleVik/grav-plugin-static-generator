<?php
/**
 * Scholar Theme, Content
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Theme\Scholar
 * @subpackage Grav\Theme\Scholar\Content
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-scholar
 */
namespace Grav\Theme\Scholar\Content;

use Grav\Common\Grav;
use Grav\Common\Inflector;
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Theme\Scholar\Content\ContentInterface;

/**
 * Content
 *
 * @category Extensions
 * @package  Grav\Theme\Scholar\Content\StaticContent
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-scholar
 */
class StaticContent implements ContentInterface
{
    /**
     * Create menu-structure recursively
     *
     * @param string  $route Route to page
     * @param string  $mode  Reserved collection-mode for handling child-pages
     * @param integer $depth Reserved placeholder for recursion depth
     *
     * @return array Page-structure with children
     */
    public static function buildMenu(string $route, $mode = false, $depth = 0)
    {
        $page = Grav::instance()['page'];
        $depth++;
        $mode = '@page.self';
        if ($depth > 1) {
            $mode = '@page.children';
        }
        $pages = $page->evaluate([$mode => $route]);
        $pages = $pages->published()->order('date', 'desc');
        $paths = array();
        foreach ($pages as $page) {
            $route = $page->rawRoute();
            $paths[$route]['depth'] = $depth;
            $paths[$route]['title'] = $page->title();
            $paths[$route]['route'] = $route;
            $paths[$route]['template'] = $page->template();
            if (!empty($paths[$route])) {
                $children = self::buildMenu($route, $mode, $depth);
                if (!empty($children)) {
                    $paths[$route]['children'] = $children;
                }
            }
            $media = new Media($page->path());
            foreach ($media->all() as $filename => $file) {
                $paths[$route]['media'][$filename] = $file->items()['type'];
            }
        }
        if (!empty($paths)) {
            return $paths;
        } else {
            return null;
        }
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
        $headings = array();
        $targetHeadings = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $targetElements = array_merge($targetHeadings, ['p', 'figure', 'img']);
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

    /**
     * Remove given HTML tags
     *
     * @param string $content HTML-content
     * @param mixed  $tags    Tags to strip, comma-separated
     *
     * @return string Manipulated HTML, UTF-8 encoded
     */
    public static function stripHTML(string $content, $tags)
    {
        if (strlen($content) < 1) {
            return;
        }
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        foreach ($tags as $element) {
            $elements = $doc->getElementsByTagName($element);
            for ($i = $elements->length; --$i >= 0;) {
                $target = $elements->item($i);
                $target->parentNode->removeChild($target);
            }
        }
        $node = $doc->getElementsByTagName('body')[0];
        return self::getInnerHTML($node, false);
    }

    /**
     * Wrap HTML tags
     *
     * @param string $content    HTML-content
     * @param string $wrapperTag Tag to wrap around matches
     * @param array  $targetTags HTML tags to wrap
     *
     * @see https://stackoverflow.com/a/10683463
     *
     * @return string Manipulated HTML
     */
    public static function wrapHTML(string $content, string $wrapperTag, array $targetTags): string
    {
        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXpath($doc);
        libxml_clear_errors();
        $segments = array();
        $wrapper = null;
        $elements = $xpath->query('//' . implode(' | //', $targetTags));
        foreach ($elements as $element) {
            $nodes = array($element);
            for ($next = $element->nextSibling; $next && $next->nodeName != $element->nodeName; $next = $next->nextSibling) {
                $nodes[] = $next;
            }
            $wrapper = $doc->createElement($wrapperTag);
            $element->parentNode->replaceChild($wrapper, $element);
            foreach ($nodes as $node) {
                $wrapper->appendChild($node);
            }
            $segments[] = $element->nodeValue;
        }
        $node = $doc->getElementsByTagName('body')[0];
        return self::getInnerHTML($node, false);
    }
    
    /**
     * Extract headings from HTML
     *
     * @param array $data [title => [href, level]]
     *
     * @return string HTML ordered list
     */
    public static function buildList($data): string
    {
        if (empty($data)) {
            return '';
        }
        $output = '<ol>';
        $keys = array_keys($data);
        foreach (array_keys($keys) as $index) {
            $title = current($keys);
            $properties = $data[$title];
            $href = $data[$title]['href'];
            $level = $data[$title]['level'];
            $nextLevel = $data[next($keys)]['level'] ?? null;
        
            if ($nextLevel > $level) {
                $output .= '<li><a href="#' . $href . '">' . $title . '</a><ol>';
            } else {
                $output .= '<li><a href="#' . $href . '">' . $title . '</a></li>';
            }
            if ($nextLevel < $level) {
                $output .= '</ol></li>';
            }
        }
        $output .= '</ol>';
        return $output;
    }

    /**
     * Get inner HTML of a DOM node
     *
     * @param \DOMNode $node DOMDocument node
     * @param boolean  $wrap Include target tag
     *
     * @see https://stackoverflow.com/a/53740544
     *
     * @return string Inner DOM node
     */
    public static function getInnerHTML(\DOMNode $node, $wrap = true): string
    {
        $doc = new \DOMDocument();
        $doc->appendChild($doc->importNode($node, true));
        $html = trim($doc->saveHTML());
        if ($wrap) {
            return $html;
        }
        return preg_replace('@^<' . $node->nodeName . '[^>]*>|</' . $node->nodeName . '>$@', '', $html);
    }

    /**
     * Truncates a string up to a number of characters
     * while preserving whole words and HTML tags
     *
     * @param string  $text         String to truncate.
     * @param integer $length       Length of returned string, including ellipsis.
     * @param string  $ending       Ending to be appended to the trimmed string.
     * @param boolean $exact        If false, $text will not be cut mid-word
     * @param boolean $considerHtml If true, HTML tags would be handled correctly
     *
     * @return string Truncated string.
     *
     * @see https://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
     */
    public static function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true): string
    {
        if ($considerHtml) {
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                if (!empty($line_matchings[1])) {
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                    } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                    } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    $truncate .= $line_matchings[1];
                }
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    $left = $length - $total_length;
                    $entities_length = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }
                    $truncate .= mb_substr($line_matchings[2], 0, $left + $entities_length);
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = mb_substr($text, 0, $length - strlen($ending));
            }
        }
        if (!$exact) {
            if ($considerHtml) {
                preg_match('/^((<.*?>)*)(.*)/', $truncate, $matches);
                $truncate = $matches[3];
            }
            $spacepos = strrpos($truncate, ' ');
            if ($spacepos > 0) {
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
            if ($considerHtml) {
                $truncate = $matches[1] . $truncate;
            }
        }
        if ($considerHtml) {
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        $truncate .= $ending;
        return $truncate;
    }
}
