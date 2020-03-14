<?php

/**
 * Static Generator Plugin, Config
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
use Grav\Common\Utils;
use Grav\Framework\File\YamlFile;
use Grav\Framework\File\Formatter\YamlFormatter;
use Grav\Plugin\StaticGenerator\Config\AbstractConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Config
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Config\Config
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Config extends AbstractConfig
{
    /**
     * Mirror Config
     *
     * @param string  $target Location to store Config in.
     * @param string  $source Source to copy from.
     * @param boolean $force  Forcefully save.
     *
     * @return boolean Result
     */
    public static function mirror(
        string $target,
        string $source,
        bool $force = false
    ): bool {
        try {
            $Filesystem = new Filesystem();
            if ($Filesystem->exists($target)) {
                if ($force) {
                    $Filesystem->remove($target);
                } else {
                    return false;
                }
            }
            $Filesystem->mkdir($target);
            $Filesystem->mirror(
                $source,
                $target,
                null,
                [
                    'override' => true,
                    'copy_on_windows' => true,
                    'delete' => true
                ]
            );
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Create and store Preset
     *
     * @param string  $name       Preset name.
     * @param array   $parameters Preset parameters.
     * @param boolean $force      Forcefully save.
     *
     * @return int Result
     */
    public static function addPreset(
        string $name,
        array $parameters,
        bool $force = false
    ): int {
        try {
            $Grav = Grav::instance();
            $formatter = new YamlFormatter;
            $file = new YamlFile(
                $Grav['locator']->findResource(
                    'config://plugins/static-generator.yaml',
                    true,
                    true
                ),
                $formatter
            );
            $file->lock();
            $config = $Grav['config']->get('plugins.static-generator');
            if (!isset($config['presets'])
                && !is_array($config['presets'])
            ) {
                $file->unlock();
                return 2;
            }
            foreach ($config['presets'] as $index => $value) {
                if (isset($value['name']) && $value['name'] == $name) {
                    if ($force) {
                        $config['presets'][$index] = [
                            'name' => $name,
                            'parameters' => $parameters
                        ];
                        $file->save($config);
                        $file->unlock();
                        return 3;
                    } else {
                        $file->unlock();
                        return 4;
                    }
                }
            }
            $config['presets'][] = [
                'name' => $name,
                'parameters' => $parameters
            ];
            $file->save($config);
            $file->unlock();
            return 1;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Apply parameters to Config or Twig
     *
     * @param object $config     Instance of Grav\Common\Config\Config.
     * @param object $twig       Instance of Grav\Common\Twig\Twig.
     * @param array  $parameters Parameters to apply.
     *
     * @return void
     */
    public static function applyParameters(
        object $config,
        object $twig,
        array $parameters
    ): void {
        if (empty($parameters)) {
            return;
        }
        foreach ($parameters as $parameter => $value) {
            if (Utils::startsWith('twig.', $parameter, false)) {
                $twig->twig_vars[end(explode('.', $parameter))] = $value;
            } else {
                $config->set($parameter, $value);
            }
        }
    }

    /**
     * Get Preset-parameters
     *
     * @param object $config Instance of Grav\Common\Config\Config.
     * @param string $preset Name of Preset.
     *
     * @return array Preset-parameters
     */
    public static function getPresetParameters(object $config, string $preset): array
    {
        if ($config->get('plugins.static-generator.presets') !== null
            && !empty($config->get('plugins.static-generator.presets'))
        ) {
            $key = array_search(
                $preset,
                array_column(
                    $config->get('plugins.static-generator.presets'),
                    'name'
                )
            );
            if ($key
                && isset($config->get('plugins.static-generator.presets')[$key]['parameters'])
                && !empty($config->get('plugins.static-generator.presets')[$key]['parameters'])
            ) {
                return $config->get('plugins.static-generator.presets')[$key]['parameters'];
            }
        }
        return [];
    }
}
