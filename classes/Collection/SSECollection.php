<?php
/**
 * Static Generator Plugin, Server Sent Collection Builder
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Collection
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator\Collection;

use Grav\Common\Grav;
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Config\Config;
use Grav\Plugin\StaticGenerator\Collection\AbstractCollection;

/**
 * Server Sent Collection Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection\SSECollection
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class SSECollection extends AbstractCollection
{
    /**
     * Bootstrap data
     *
     * @return void
     */
    public function setup(string $preset): void
    {
        $this->grav = Grav::instance();
        if (isset($this->grav['admin'])) {
            if (method_exists(Grav::instance()['admin'], 'enablePages')) {
                $this->grav['admin']->enablePages();
            }
        }
        $this->grav['streams'];
        $this->grav['config']->init();
        $this->grav['themes']->init();
        $this->grav['twig']->init();
        $this->grav['pages']->init();
        $this->grav['assets']->init();
        $this->grav['config']->set('system.cache.enabled', false);
        $this->pages = $this->grav['page']->evaluate([$this->collection => $this->route]);
        $this->count = $this->count();
        echo 'event: update' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'total' => $this->count
            ]
        ) . "\n\n";
    }

    public function collection()
    {
        foreach ($this->pages as $Page) {
            $content = $Grav['twig']->processTemplate(
                $Page->template() . '.' . $Page->templateFormat('html') . '.twig',
                ['page' => $Page]
            );
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'title' => $Page->title(),
                    'format' => $Page->template() . '.' . $Page->templateFormat('html') . '.twig',
                    'content' => $content
                ]
            ) . "\n\n";
        }
    }

    /**
     * Report results
     *
     * @param array $items Items to report
     *
     * @return void
     */
    public function reporter(array $items): void
    {
        $message = ucfirst(
            Grav::instance()['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.STORED']
            )
        ) . ' "' . $items['item'] . '" ' .
        Grav::instance()['language']->translate(
            ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
        ) . ' ' . $items['location'] . ' ' .
        Grav::instance()['language']->translate(
            ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
        ) . ' ' . $items['time'] . '.';
        echo 'event: update' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'content' => $message,
                'text' => $items['item'],
                'value' => $items['location']
            ]
        ) . "\n\n";
    }
}
