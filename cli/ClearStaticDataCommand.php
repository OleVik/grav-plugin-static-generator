<?php

/**
 * StaticGenerator Plugin, Clear Data
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
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Grav\Plugin\StaticGenerator\Timer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Clear Data
 *
 * Helper-utility for clearing data
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-theme-scholar
 */
class ClearStaticDataCommand extends ConsoleCommand
{
    /**
     * Command definitions
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName("clear")
            ->setDescription("Clears Index and static-generator Page(s)")
            ->setHelp('The <info>clear</info>-command deletes Index and static-generator Page(s).')
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Override target-option or set a custom destination'
            );
    }

    /**
     * Clear Data Index
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOExceptionInterface
     *
     * @return void
     */
    protected function serve()
    {
        $timer = new Timer();
        $config = Grav::instance()['config']->get('plugins.static-generator');
        $locator = Grav::instance()['locator'];
        $target = $this->input->getArgument('target');
        if ($target === null) {
            $target = $config['index'];
        }
        $this->output->writeln('<info>Clearing data</info>');
        try {
            $Filesystem = new Filesystem();
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
            $Filesystem->remove($location);
            $this->output->writeln('<white>Deleted ' . $location . '</white>');
        } catch (IOExceptionInterface $e) {
            throw new IOExceptionInterface($e);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
