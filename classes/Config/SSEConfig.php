<?php
/**
 * Static Generator Plugin, Server Sent Events Config
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Config
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator\Config;

use Grav\Common\Grav;
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Data\SSEData;
use Grav\Plugin\StaticGenerator\Config\Config;

/**
 * Server Sent Events Config
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Config\SSEConfig
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class SSEConfig extends SSEData
{
    /**
     * Bootstrap data
     *
     * @return void
     */
    public function setup()
    {
        echo 'event: update' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'total' => 1
            ]
        ) . "\n\n";
    }

    /**
     * Mirror Config
     *
     * @param string  $preset Preset name.
     * @param string  $target Location to store Config in.
     * @param string  $source Source to copy from.
     * @param Timer   $Timer  Instance of Grav\Plugin\StaticGenerator\Timer.
     * @param boolean $force  Forcefully save.
     *
     * @return void
     */
    public function mirror(
        string $preset,
        string $target,
        string $source,
        Timer $Timer,
        bool $force = true
    ): void {
        try {
            $target = Grav::instance()['locator']->findResource($target, true, true);
            Config::mirror($target, $source, $force);
            $message = ucfirst(
                Grav::instance()['language']->translate(
                    ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.STORED']
                )
            ) . ' "' . $preset . '" ' .
            Grav::instance()['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
            ) . ' ' . $target . ' ' .
            Grav::instance()['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
            ) . ' ' . Timer::format($Timer->getTime()) . '.';
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'content' => $message,
                    'text' => $preset,
                    'value' => $source
                ]
            ) . "\n\n";
            Grav::instance()['log']->info($message);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
