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
// use Windwalker\Http\HttpClient;
use GuzzleHttp\Client;
// use GuzzleHttp\Psr7\Request;
use Grav\Framework\Psr7\Request;

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
        // dump($asset);
        if (Utils::startsWith($asset, '/user')) {
            // dump('user');
        } elseif (Utils::startsWith($asset, '/system')) {
            // dump('system');
        } else {
            dump($asset);
            // dump(basename($asset));
            // dump(dirname($asset));
            // dump(parse_url($asset));
            // dump($location);
            $url = parse_url($asset);
            $target = $location . DS . 'assets' . DS . $url['host'] . $url['path'];
            dump($target);
            // file_put_contents($target, fopen($asset, 'r'));
            $this->Filesystem->copy($asset, $target);
            // new Request(
            //     'GET',
            //     $asset,
            //     [
            //         'connect_timeout' => 2,
            //         'sink' => $target,
            //         'timeout' => 3,
            //     ]
            // );
            /* $Request = new Request('GET', $asset);
            dump($Request);
            dump($Request->getBody());
            dump($Request->getBody()->getContents());
            // dump($Request->getBody()->read(0));
            dump($Request->getBody()->__toString());
            dump($Request->getBody()->message);
            dump($Request->getBody()->message->getBody()); */
            
            // include __DIR__ . '/../vendor/autoload.php';
            // $file_path = fopen($target, 'w');
            // dump($file_path);
            // $client = new Client();
            // $response = $client->get($asset, ['sink' => $file_path]);
            // dump($response);

            // $fileHandle = fopen($target, "wb");
            // dump($fileHandle);
            // try {
            //     $client = new Client();
            //     $response = $client->get($asset, [
            //         'sink' => $fileHandle
            //     ]);
            // } catch (RequestException $e) {
            //     throw new ReportFileDownloadException(
            //         "Can't download report file $asset"
            //     );
            // } finally {
            //     @fclose($fileHandle);
            // }

            // dump('other');
            // $http = new HttpClient;
            // $dest = '/path/to/local/file.zip';
            // $response = $http->download('http://example.com/file.zip', $dest);
        }
        return [];
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
