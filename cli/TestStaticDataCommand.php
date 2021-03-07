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
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
        $route = $this->input->getArgument('route');
        $maxLength = $config['content_max_length'];
        $this->output->writeln('<info>Testing data index</info>');
        try {
            parent::initializePages();
            $Data = new TestData(true, $maxLength);
            $Data->bootstrap($route);
            $this->output->writeln('<info>Count: ' . $Data->count . '</info>');
            $Data->index($route);
            $this->output->writeln('Finished in <magenta>' . Timer::format($timer->getTime()) . '</magenta>');
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
