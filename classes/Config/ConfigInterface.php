<?php

/**
 * Static Generator Plugin, Config Interface
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

use Grav\Common\Config\Config;

/**
 * Config Interface
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Config\ConfigInterface
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
interface ConfigInterface
{
    public function __construct(Config $config, string $path, string $name);
}
