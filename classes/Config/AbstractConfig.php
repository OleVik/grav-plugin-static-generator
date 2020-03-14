<?php

/**
 * Static Generator Plugin, Config Builder
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
use Grav\Common\Config\Config;
use Grav\Plugin\StaticGenerator\Config\ConfigInterface;
use Grav\Framework\File\YamlFile;
use Grav\Framework\File\Formatter\YamlFormatter;

/**
 * Config Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Config\AbstractConfig
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
abstract class AbstractConfig implements ConfigInterface
{
    /**
     * Initialize class
     *
     * @param Config $config Instance of Grav\Common\Config\Config.
     * @param string $path   Location of Preset-storage.
     * @param string $name   Name of Preset.
     */
    public function __construct(Config $config, string $path, string $name)
    {
        $this->origin = $config;
        $preset = self::buildPreset($path . DS . $name);
        $this->config = new Config($preset);
        $this->config->environment = 'preset-' . $name;
    }

    /**
     * Structure Preset
     *
     * @param string $folder    Location of Preset-files.
     * @param array  $fileTypes File types to include, defaults to YAML.
     *
     * @return array
     */
    public static function buildPreset(
        string $folder,
        array $fileTypes = ['yaml']
    ): array {
        $preset = array();
        $folder = Grav::instance()['locator']->findResource($folder, true, true);
        $fileIterator = self::fileIterator($folder, $fileTypes);
        $formatter = new YamlFormatter;
        foreach ($fileIterator as $file) {
            $base = $file->getBasename('.' . $file->getExtension());
            $prefix = basename(
                dirname(
                    str_replace(
                        $folder,
                        '',
                        str_replace('\\', '/', $file->getRealPath())
                    )
                )
            );
            $file = new YamlFile(
                $file->getRealPath(),
                $formatter
            );
            if (!$prefix) {
                $preset[$base] = $file->load();
            } else {
                $preset[$prefix][$base] = $file->load();
            }
        }
        return $preset;
    }

    /**
     * Construct Regular Expression File Iterator
     *
     * @param string $folder    Folder to iterate from.
     * @param array  $fileTypes File types to include.
     *
     * @return void
     */
    public static function fileIterator(string $folder, array $fileTypes)
    {
        if (count($fileTypes) > 1) {
            $files = implode('|', $fileTypes);
        } else {
            $files = $fileTypes[0];
        }
        $iterator  = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder)
        );
        return new \RegexIterator($iterator, '/\.' . $files . '$/imu');
    }
}
