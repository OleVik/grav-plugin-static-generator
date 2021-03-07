<?php

/**
 * Static Generator Plugin, Data Index Builder
 *
 * PHP version 7
 *
 * @category API
 * @package  Grav\Plugin\Console
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Data\CommandLineData;
use Grav\Plugin\StaticGenerator\Timer;

/**
 * Data Index Builder
 *
 * Command line utility for storing Pages data as JSON
 *
 * @category API
 * @package  Grav\Plugin\Console\GenerateStaticIndexCommand
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */
class GenerateStaticIndexCommand extends ConsoleCommand
{
    /**
     * Command definitions
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName("index")
            ->setDescription("Generates and stores Pages index.")
            ->setHelp('The <info>index</info>-command generates and stores Pages index.')
            ->addArgument(
                'route',
                InputArgument::REQUIRED,
                'The route to the page'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Override target-option or set a custom destination'
            )
            ->addOption(
                'basename',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Index basename',
                'index'
            )
            ->addOption(
                'content',
                'c',
                InputOption::VALUE_NONE,
                'Include Page content'
            )
            ->addOption(
                'echo',
                'e',
                InputOption::VALUE_NONE,
                'Outputs result directly'
            )
            ->addOption(
                'wrap',
                'w',
                InputOption::VALUE_NONE,
                'Wraps JSON as a JavaScript global'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forcefully save data'
            );
    }

    /**
     * Build and save data index
     *
     * @return void
     */
    protected function serve()
    {
        $timer = new Timer();
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $locator = Grav::instance()['locator'];
        $route = $this->input->getArgument('route');
        $target = $this->input->getArgument('target');
        if ($target === null) {
            $target = $config['index'];
        }
        $basename = $this->input->getOption('basename');
        $content = $this->input->getOption('content');
        $echo = $this->input->getOption('echo');
        $wrap = $this->input->getOption('wrap');
        $force = $this->input->getOption('force');
        $maxLength = $config['content_max_length'];
        $this->output->writeln('<info>Generating data index</info>');
        try {
            parent::initializePages();
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
            $Data = new CommandLineData($content, $maxLength);
            $Data->bootstrap($route, $this->output);
            $Data->index($route);
            if ($echo) {
                echo json_encode($Data->data, JSON_PRETTY_PRINT);
            } else {
                $extension = '.json';
                if ($content) {
                    $basename = $basename . '.full';
                }
                if ($wrap) {
                    $extension = '.js';
                }
                $Storage = new FileStorage($location);
                $file = $basename . $extension;
                if ($force && $Storage->doHas($file)) {
                    $Storage->doDelete($file);
                }
                if ($wrap && !$content) {
                    $Storage->doSet($file, 'const GravMetadataIndex = ' . json_encode($Data->data) . ';', 0);
                } elseif ($wrap && $content) {
                    $Storage->doSet($file, 'const GravDataIndex = ' . json_encode($Data->data) . ';', 0);
                } else {
                    $Storage->doSet($file, json_encode($Data->data), 0);
                }
                $this->output->writeln('');
                $this->output->writeln(
                    '<info>Saved <white>' . count($Data->data)
                        . ' items</white> to <cyan>'
                        . $location . '/' . $file . '</cyan> in <magenta>'
                        . Timer::format($timer->getTime()) . '</magenta>.</info>'
                );
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
