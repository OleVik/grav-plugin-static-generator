<?php
/**
 * Static Generator Plugin, Assets Builder
 *
 * PHP version 7
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
namespace Grav\Plugin\StaticGenerator;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Assets Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Assets
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Assets
{
    public $streams;
    public $schemes;

    /**
     * Initialize class
     *
     * @param Filesystem $Filesystem Instance of Symfony\Component\Filesystem\Filesystem.
     * @param Timer      $Timer      Instance of Grav\Plugin\StaticGenerator\Timer.
     */
    public function __construct(Filesystem $Filesystem, Timer $Timer)
    {
        $this->streams = array();
        $this->schemes = array();
        $this->Filesystem = $Filesystem;
        $this->Timer = $Timer;
        $this->grav = Grav::instance();
        foreach ($this->grav['locator']->getSchemes() as $stream) {
            $this->schemes[$stream] = Utils::url($stream . '://');
        }
        foreach ($this->grav['streams']->getStreams() as $name => $stream) {
            $this->streams[$name] = Utils::url($name . '://');
        }
        // dump($this->schemes);
        // dump($this->streams);
        // exit();
    }

    /**
     * Copy Asset
     *
     * @param string  $asset    Asset to copy.
     * @param string  $location Location to store asset in.
     * @param boolean $force    Forcefully save.
     *
     * @return array Result
     */
    public function copy(string $asset, string $location, bool $force): array
    {
        if (empty($asset)) {
            return [];
        }
        $location = $location . DS . 'assets';
        try {
            if ($force) {
                $this->Filesystem->remove($location . $asset);
            }
            $this->Filesystem->copy(GRAV_ROOT . $asset, $location . $asset);
            return [
                'item' => basename($asset),
                'location' => $location . $asset,
                'time' => Timer::format($this->Timer->getTime())
            ];
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Copy Page Media
     *
     * @param array   $media    List of media to copy.
     * @param string  $location Location to storage media in.
     * @param boolean $force    Forcefully save data.
     *
     * @return array Result
     */
    public function copyMedia(array $media, string $location, bool $force): array
    {
        if (empty($media)) {
            return [];
        }
        $location = rtrim($location, '//') . DS;
        foreach ($media as $filename => $data) {
            try {
                if ($force) {
                    $this->Filesystem->remove($location . $filename);
                }
                $this->Filesystem->copy($data->path(), $location . $filename);
                return [
                    'item' => $filename,
                    'location' => $location . $filename,
                    'time' => Timer::format($this->Timer->getTime())
                ];
            } catch (\Exception $e) {
                throw new \Exception($e);
            }
        }
    }
}
