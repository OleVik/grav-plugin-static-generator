<?php

/**
 * StaticGenerator Plugin, Create Preset
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-theme-scholar
 */

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Inflector;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\CommandLineConfig;
use Grav\Plugin\StaticGenerator\Config\Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Create Preset
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-theme-scholar
 */
class CreatePresetCommand extends ConsoleCommand
{
    /**
     * Command definitions
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('preset')
            ->setDescription('Creates a Preset from the current Config')
            ->setHelp('The <info>preset</info>-command creates a Preset from the current Config.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Set Preset-name'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Override target-option or set a custom destination'
            )
            ->addOption(
                'parameters',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Parameters to pass into Twig'
            )
            ->addOption(
                'create',
                'c',
                InputOption::VALUE_NONE,
                'Create Preset in config://plugins/static-generator.yaml'
            )
            ->addOption(
                'mirror',
                'm',
                InputOption::VALUE_NONE,
                'Mirror config:// to target destination'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forcefully save data'
            );
    }

    /**
     * Clear Data Index
     *
     * @return void
     */
    protected function serve()
    {
        $timer = new Timer();
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $locator = Grav::instance()['locator'];
        $name = $this->input->getArgument('name');
        $target = $this->input->getArgument('target');
        $create = $this->input->getOption('create');
        $mirror = $this->input->getOption('mirror');
        $force = $this->input->getOption('force');
        if ($target === null) {
            $target = $config['index'];
        }
        $params = $this->input->getOption('parameters');
        $parameters = array();
        if (\is_array($params) && !empty($params)) {
            foreach ($params as $param) {
                $param = explode(':', $param);
                $parameters[$param[0]] = $param[1];
            }
        }
        $this->output->writeln('<info>Preset: </info>' . $name);
        try {
            if (Utils::contains($target, '://')) {
                $scheme = parse_url($target, PHP_URL_SCHEME);
                $location = $locator->findResource($scheme . '://') . str_replace($scheme . '://', '/', $target);
            } else {
                $this->output->writeln('<error>Target must be a valid stream resource, prefixing one of:</error>');
                foreach ($locator->getSchemes() as $scheme) {
                    $this->output->writeln($scheme . '://');
                }
                return;
            }
            $name = Inflector::hyphenize($name);
            $location = $location . '/presets/' . $name;
            $source = $locator->findResource('config://');
            if ($create) {
                $preset = Config::addPreset($name, $parameters, $force);
                if ($preset === 1) {
                    $this->output->writeln(
                        '<cyan>Added "' . $name . '" to ' . $source .
                            '/plugins/static-generator.yaml</cyan>'
                    );
                } elseif ($preset === 2) {
                    $this->output->writeln(
                        '<red>In ' . $source . '/plugins/static-generator.yaml,
                        "presets" is not an array or is not set</red>'
                    );
                } elseif ($preset === 3) {
                    $this->output->writeln(
                        '<cyan>Updated "' . $name . '" in ' . $source .
                            '/plugins/static-generator.yaml</cyan>'
                    );
                } elseif ($preset === 4) {
                    $this->output->writeln(
                        '<red>"' . $name . '" is already set, in ' . $source .
                            '/plugins/static-generator.yaml</red>'
                    );
                } else {
                    $this->output->writeln(
                        '<red>Failed adding "' . $name . '" to ' . $source .
                            '/plugins/static-generator.yaml</red>'
                    );
                }
            }
            if ($mirror) {
                $mirroring = Config::mirror($location, $source, $force);
                if ($mirroring) {
                    $this->output->writeln('<cyan>Mirrored ' . $source . ' to ' . $location . '</cyan>');
                } else {
                    $this->output->writeln('<red>Could not mirror ' . $source . ' to ' . $location . '</red>');
                }
            }
            $this->output->writeln('Finished in <magenta>' . Timer::format($timer->getTime()) . '</magenta>');
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
