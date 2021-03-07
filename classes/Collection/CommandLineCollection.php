<?php

/**
 * Static Generator Plugin, CLI Collection Builder
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\Collection
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-static-generator
 */

namespace Grav\Plugin\StaticGenerator\Collection;

use Grav\Common\Grav;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Grav\Plugin\StaticGenerator\Collection\AbstractCollection;

/**
 * CLI Collection Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection\CommandLineCollection
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class CommandLineCollection extends AbstractCollection
{
    /**
     * Bootstrap processor
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $handle Console-wrapper.
     *
     * @return void
     */
    public function handler(\Symfony\Component\Console\Output\OutputInterface $handle)
    {
        $this->handle = $handle;
    }

    /**
     * Bootstrap data, events, and helpers
     *
     * @param string $preset Name of Config Preset to load.
     *
     * @return void
     */
    public function setup(string $preset, $offline): void
    {
        parent::setup($preset, $offline);
        $this->progress = new ProgressBar($this->handle, $this->count);
    }

    /**
     * Increase counter
     *
     * @return void
     */
    public function progress(): void
    {
        $this->progress->advance();
    }

    public function reporter(array $parts, string $itemColor = 'white'): void
    {
        if (!empty($parts)) {
            $message = array();
            if (isset($parts['item'])) {
                $message[] = '<' . $itemColor . '>' . $parts['item'] . '</' . $itemColor . '>';
            }
            if (isset($parts['location'])) {
                $message[] = '<cyan>' . $parts['location'] . '</cyan>';
            }
            if (isset($parts['time'])) {
                $message[] = '<magenta>' . $parts['time'] . '</magenta>';
            }
            $this->handle->writeln(implode("\n", $message));
            $this->handle->writeln('');
        }
    }

    /**
     * Finish progress-counter
     *
     * @param string $message Exit-message
     *
     * @return void
     */
    public function teardown(string $message = '')
    {
        $this->progressBar->finish();
        if (!empty($message)) {
            $this->handle->writeln("\n" . '<white>' . $message . '</white>');
        }
    }

    /**
     * Build and store Page(s)
     *
     * @return void
     */
    public function collection(): void
    {
        $this->handle->writeln('<white>Processing Page(s): ' . $this->count . '</white>');
        $this->progressBar = new ProgressBar(
            $this->handle,
            $this->count
        );
        $this->buildCollection();
        $this->teardown('Finished ' . $this->count . ' Page(s)');
    }

    /**
     * Capture and store Asset(s)
     *
     * @return void
     */
    public function assets(): void
    {
        $count = count($this->grav['assets']['assets_css']) + count($this->grav['assets']['assets_js']);
        $this->handle->writeln('');
        $this->handle->writeln('<white>Processing Asset(s): ' . $count . '</white>');
        $this->progressBar = new ProgressBar(
            $this->handle,
            $count
        );
        $this->buildAssets();
        $this->teardown('Finished ' . $count . ' Asset(s)');
    }

    /**
     * Capture and store Static Asset(s)
     *
     * @return void
     */
    /** */
    public function staticAssets(): void
    {
        $this->handle->writeln('');
        $this->handle->writeln('<white>Mirroring Static Asset(s)</white>');
        $this->mirrorStaticAssets();
        $this->teardown('Finished mirroring Static Asset');
    }

    /**
     * Mirror images
     *
     * @param boolean $force Forcefully save data.
     *
     * @return void
     */
    public function images(bool $force): void
    {
        $this->handle->writeln('');
        $this->handle->writeln('<white>Mirroring generated Images</white>');
        $this->mirrorImages($force);
        $this->teardown('Finished mirroring generated Images');
    }
}
