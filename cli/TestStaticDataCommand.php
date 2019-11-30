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
use Grav\Plugin\StaticGenerator\Data\TestData;
use Grav\Plugin\StaticGenerator\Timer;

/**
 * Data Index Builder
 *
 * Command line utility for storing Pages data as JSON
 *
 * @category API
 * @package  Grav\Plugin\Console\TestStaticDataCommand
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */
class TestStaticDataCommand extends ConsoleCommand
{
    /**
     * Command definitions
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName("test")
            ->setDescription("Tests Page iteration.")
            ->setHelp('The <info>test</info>-command tests Page iteration.')
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
        include __DIR__ . '/../vendor/autoload.php';
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
        $this->output->writeln('<info>Testing data index</info>');
        try {
            $Data = new TestData($content, $maxLength);
            $Data->setup($route, $this->output);
            $this->output->writeln('<info>Count: ' . $Data->count . '</info>');
            $Data->index($route);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
