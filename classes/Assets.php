<?php
/**
 * StaticGenerator Plugin, Assets Builder
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-StaticGenerator
 */
namespace Grav\Plugin\StaticGenerator\API;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Common\Page\Header;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Assets Builder
 *
 * @category   API
 * @package    Grav\Plugin\StaticGenerator
 * @subpackage Grav\Plugin\StaticGenerator\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-StaticGenerator
 */
class Assets
{
    public $streams;
    public $schemes;

    /**
     * Initialize class
     *
     * @param ConsoleOutput $handle     Instance of Symfony\Component\Console\ConsoleOutput.
     * @param Filesystem    $Filesystem Symfony\Component\Filesystem\Filesystem.
     * @param Timer         $Timer      Instance of Grav\Plugin\StaticGenerator\API\Timer.
     */
    public function __construct(ConsoleOutput $handle, Filesystem $Filesystem, Timer $Timer)
    {
        include __DIR__ . '/../vendor/autoload.php';
        $this->streams = array();
        $this->schemes = array();
        $this->handle = $handle;
        $this->Filesystem = $Filesystem;
        $this->Timer = $Timer;
        $this->grav = Grav::instance();
        foreach ($this->grav['locator']->getSchemes() as $stream) {
            $this->schemes[$stream] = Utils::url($stream . '://');
        }
        foreach ($this->grav['streams']->getStreams() as $name => $stream) {
            $this->streams[$name] = Utils::url($name . '://');
        }
    }

    /**
     * Rewrite asset-paths
     *
     * @param string $content Page HTML
     *
     * @return string Processed HTML
     */
    public function rewriteURL(string $content): string
    {
        return preg_replace('/(link href|script src)="\//ui', '$1="/assets/', $content);
    }

    /**
     * Rewrite media-paths
     *
     * @param string $content Page HTML
     * @param string $old     Original path
     * @param string $new     New path
     *
     * @return string Processed HTML
     */
    public function rewriteMediaURL(string $content, string $old, string $new): string
    {
        return str_replace($old, $new, $content);
    }

    /**
     * Copy assets
     *
     * @param array       $assets      List of assets to copy.
     * @param string      $location    Location to store assets in.
     * @param Table       $result      Instance of Symfony\Component\Console\Helper\Table.
     * @param ProgressBar $progressBar Instance of Symfony\Component\Console\Helper\ProgressBar.
     * @param boolean     $force       Forcefully save data.
     *
     * @return void
     */
    public function copy(array $assets, string $location, Table $result, ProgressBar $progressBar, bool $force): void
    {
        if (empty($assets)) {
            return;
        }
        $location = $location . DS . 'assets';
        foreach ($assets as $asset) {
            try {
                if ($force) {
                    $this->Filesystem->remove($location . $asset);
                }
                $this->Filesystem->copy(GRAV_ROOT . $asset, $location . $asset);
                $progressBar->advance();
                $result->addRow(
                    [
                        '<yellow>' . basename($asset) . '</yellow>',
                        '<cyan>' . $location . $asset . '</cyan>',
                        '<magenta>' . Timer::format($this->Timer->getTime()) . '</magenta>'
                    ]
                );
            } catch (\Exception $e) {
                throw new \Exception($e);
            }
        }
    }

    /**
     * Copy Page media
     *
     * @param array   $media    List of media to copy.
     * @param string  $location Location to storage media in.
     * @param Table   $result   Instance of Symfony\Component\Console\Helper\Table.
     * @param boolean $force    Forcefully save data.
     *
     * @return void
     */
    public function copyMedia(array $media, string $location, Table $result, bool $force): void
    {
        if (empty($media)) {
            return;
        }
        $location = rtrim($location, '//') . DS;
        foreach ($media as $filename => $data) {
            try {
                if ($force) {
                    $this->Filesystem->remove($location . $filename);
                }
                $this->Filesystem->copy($data->path(), $location . $filename);
                $result->addRow(
                    [
                        '  <yellow>' . $filename . '</yellow>',
                        '<cyan>' . $location . $filename . '</cyan>',
                        '<magenta>' . Timer::format($this->Timer->getTime()) . '</magenta>'
                    ]
                );
            } catch (\Exception $e) {
                throw new \Exception($e);
            }
        }
    }
}
