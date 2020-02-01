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
                'collection',
                InputArgument::REQUIRED,
                'The Page Collection to store (see https://learn.getgrav.org/16/content/collections#collection-headers)'
            )
            ->addArgument(
                'route',
                InputArgument::OPTIONAL,
                'The route to the page'
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
                'Include assets'
            )
            ->addOption(
                'static-assets',
                's',
                InputOption::VALUE_NONE,
                'Include static assets'
            )
            ->addOption(
                'images',
                'i',
                InputOption::VALUE_NONE,
                'Include Images'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Parameters for filtering'
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
        include __DIR__ . '/../vendor/autoload.php';
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $locator = Grav::instance()['locator'];
        $route = $this->input->getArgument('route') ?? '/';
        $collection = $this->input->getArgument('collection');
        $target = $this->input->getArgument('target');
        if ($target === null) {
            $target = $config['content'];
        }
        $preset = $this->input->getOption('preset') ?? '';
        $assets = $this->input->getOption('assets');
        $mirrorAssets = $this->input->getOption('static-assets');
        $mirrorImages = $this->input->getOption('images');
        $filters = $this->input->getOption('filter');
        $force = $this->input->getOption('force');
        $maxLength = $config['content_max_length'];
        try {
            if (Utils::contains($target, '://')) {
                $scheme = parse_url($target, PHP_URL_SCHEME);
                $location = $locator->findResource($scheme . '://') . str_replace($scheme . '://', '/', $target);
            } else {
                $this->output->error('<error>Target must be a valid stream resource, prefixing one of:</error>');
                foreach ($locator->getSchemes() as $scheme) {
                    $this->output->writeln($scheme . '://');
                }
                return;
            }
            $Collection = new CommandLineCollection($collection, $route, $location, $force, $filters);
            $Collection->handler($this->output);
            $Collection->setup($preset);
            $Collection->collection();
            if ($assets) {
                $Collection->assets();
            }
            if ($mirrorAssets) {
                $Collection->staticAssets();
            }
            if ($mirrorImages) {
                $Collection->images();
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
