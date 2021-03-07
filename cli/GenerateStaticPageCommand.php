<?php

/**
 * Static Generator Plugin, Page Builder
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
use Grav\Plugin\StaticGenerator\Collection\CommandLineCollection;
use Grav\Plugin\StaticGenerator\Timer;

/**
 * StaticGenerator Page Builder
 *
 * Command line utility for storing Pages data as HTML
 *
 * @category API
 * @package  Grav\Plugin\Console\GenerateStaticPageCommand
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */
class GenerateStaticPageCommand extends ConsoleCommand
{
    /**
     * Command definitions
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName("page")
            ->setDescription("Generates and stores Page(s) as HTML.")
            ->setHelp('The <info>page</info>-command generates and stores Page(s) as HTML.')
            ->addArgument(
                'route',
                InputArgument::OPTIONAL,
                'The route to the page'
            )
            ->addArgument(
                'collection',
                InputArgument::OPTIONAL,
                'The Page Collection to store (see https://learn.getgrav.org/16/content/collections#collection-headers)'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Override target-option or set a custom destination'
            )
            ->addOption(
                'preset',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Name of Config preset'
            )
            ->addOption(
                'assets',
                'a',
                InputOption::VALUE_NONE,
                'Include Assets'
            )
            ->addOption(
                'root-prefix',
                'r',
                InputArgument::OPTIONAL,
                'Root prefix for assets and images'
            )
            ->addOption(
                'static-assets',
                's',
                InputOption::VALUE_NONE,
                'Include Static Assets'
            )
            ->addOption(
                'images',
                'i',
                InputOption::VALUE_NONE,
                'Include Images'
            )
            ->addOption(
                'offline',
                'o',
                InputOption::VALUE_NONE,
                'Force offline-mode'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Methods for filtering'
            )
            ->addOption(
                'parameters',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Key-value pairs to assign to Twig or Config'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forcefully save data'
            );
    }

    /**
     * Build and save data index
     *
     * @return void
     */
    protected function serve(): void
    {
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $locator = Grav::instance()['locator'];
        $route = $this->input->getArgument('route') ?? '/';
        $collection = $this->input->getArgument('collection') ?? '@page.self';
        $target = $this->input->getArgument('target');
        if ($target === null) {
            $target = $config['content'];
        }
        $preset = $this->input->getOption('preset') ?? '';
        $assets = $this->input->getOption('assets');
        $rootPrefix = $this->input->getOption('root-prefix') ?? '/';
        $mirrorAssets = $this->input->getOption('static-assets');
        $mirrorImages = $this->input->getOption('images');
        $offline = $this->input->getOption('offline');
        $filters = $this->input->getOption('filter');
        $parameters = $this->input->getOption('parameters');
        $force = $this->input->getOption('force');
        $maxLength = $config['content_max_length'];
        try {
            parent::initializePages();
            if (Utils::contains($target, '://')) {
                $scheme = parse_url($target, PHP_URL_SCHEME);
                $location = $locator->findResource($scheme . '://') .
                    str_replace($scheme . '://', '/', $target);
            } else {
                $this->output->writeln(
                    '<error>Target must be a valid stream resource, prefixing one of:</error>'
                );
                foreach ($locator->getSchemes() as $scheme) {
                    $this->output->writeln($scheme . '://');
                }
                return;
            }
            $Collection = new CommandLineCollection(
                $collection,
                $route,
                $location,
                $force,
                $rootPrefix,
                $filters,
                $parameters
            );
            $Collection->handler($this->output);
            $Collection->setup($preset, $offline);
            $Collection->collection();
            if ($assets) {
                $Collection->assets();
            }
            if ($mirrorAssets) {
                $Collection->staticAssets($force);
            }
            if ($mirrorImages) {
                $Collection->images($force);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
