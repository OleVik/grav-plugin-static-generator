<?php
/**
 * Static Generator Plugin, Server Sent Collection Builder
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
use Grav\Framework\Cache\Adapter\FileStorage;
use Grav\Plugin\StaticGenerator\Timer;
use Grav\Plugin\StaticGenerator\Config\Config;
use Grav\Plugin\StaticGenerator\Collection\AbstractCollection;

/**
 * Server Sent Collection Builder
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Collection\SSECollection
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class SSECollection extends AbstractCollection
{
    /**
     * Bootstrap data
     *
     * @return void
     */
    public function setup(string $preset): void
    {
        /* Grav::resetInstance();
        $this->grav = Grav::instance();
        $this->grav['config']->init();
    
        foreach (array_keys($this->grav['setup']->getStreams()) as $stream) {
            @stream_wrapper_unregister($stream);
        }
        $this->grav['streams'];
        $this->grav['plugins']->init();
        $this->grav['themes'];
        $this->grav['themes']->configure();
        $this->grav['uri']->init();
        $this->grav['debugger']->init();
        $this->grav['assets']->init();
        $this->grav['config']->set('system.cache.enabled', false);
        $this->grav['pages']->init(); */
        Grav::resetInstance();
        $this->grav = Grav::instance();
        $this->grav['config']->init();
    
        foreach (array_keys($this->grav['setup']->getStreams()) as $stream) {
            @stream_wrapper_unregister($stream);
        }

        $this->grav->setup();
        $this->grav->fireEvent('onPluginsInitialized');
        $this->grav->fireEvent('onThemeInitialized');
        $this->grav->fireEvent('onAssetsInitialized');
        $this->grav->fireEvent('onTwigTemplatePaths');
        $this->grav->fireEvent('onTwigInitialized');
        $this->grav->fireEvent('onTwigExtensions');
        $this->grav['streams'];
        $this->grav['uri']->init();
        // $this->grav['plugins']->init();
        // $this->grav['themes']->init();
        $this->grav['assets']->init();
        // dump($this->grav['streams']);
        // $this->grav['twig']->init();
        $this->grav['pages']->init();
        $this->grav['config']->init();
        // if (!empty($preset)) {
        //     $Config = new Config(
        //         $this->grav['config'],
        //         'user-data://persist/presets',
        //         $preset
        //     );
        //     $this->grav['config']->merge($Config->config->toArray());
        // }
        if (isset($this->grav['admin'])) {
            if (method_exists(Grav::instance()['admin'], 'enablePages')) {
                $this->grav['admin']->enablePages();
            }
        }
        dump($this->grav['twig']);
        dump($this->grav['config']);
        // dump($this->grav['config']);
        // unset($this->grav['page']);
        // $this->grav['page'] = $this->grav['pages']->dispatch('/resources');
        // $this->grav['page']->content();
        // dump($this->grav['page']);
        // dump($this->grav['page']->template() . '.' . $this->grav['page']->templateFormat('html') . '.twig');
        // $content = $this->grav['twig']->processTemplate(
        //     $this->grav['page']->template() . '.' . $this->grav['page']->templateFormat('html') . '.twig',
        //     ['page' => $this->grav['page']]
        // );
        // dump($content);
        // dump($this->grav['page']->content());
        exit();
        // parent::setup($preset);
        // $this->pages = $this->grav['page']->evaluate([$this->collection => $this->route]);
        // $this->count = $this->count();
        // echo 'event: update' . "\n\n";
        // echo 'data: ' . json_encode(
        //     [
        //         'datetime' => date(DATE_ISO8601),
        //         'total' => $this->count
        //     ]
        // ) . "\n\n";

        // echo 'event: update' . "\n\n";
        // echo 'data: ' . json_encode(
        //     [
        //         'datetime' => date(DATE_ISO8601),
        //         'content' => $this->collection,
        //         'text' => 'Text',
        //         'value' => 'Value'
        //     ]
        // ) . "\n\n";
    }
    
    public function buildCollection(): void
    {
        foreach ($this->pages as $Page) {
            try {
                // $this->store($Page);
                $content = $this->grav['twig']->processTemplate(
                    $Page->template() . '.html.twig',
                    ['page' => $Page]
                );
                echo 'event: update' . "\n\n";
                echo 'data: ' . json_encode(
                    [
                        'datetime' => date(DATE_ISO8601),
                        'title' => $Page->title(),
                        'format' => $Page->template() . '.' . $Page->templateFormat() . '.twig',
                        'content' => $content
                    ]
                ) . "\n\n";
            } catch (\Exception $error) {
                throw new \Exception($error);
            }
        }
    }

    /**
     * Mirror Config
     *
     * @param string  $preset Preset name.
     * @param string  $target Location to store Config in.
     * @param string  $source Source to copy from.
     * @param Timer   $Timer  Instance of Grav\Plugin\StaticGenerator\Timer.
     * @param boolean $force  Forcefully save.
     *
     * @return void
     */
    public static function collection()
    {
        $Grav = Grav::instance();
        if (isset($Grav['admin'])) {
            if (method_exists(Grav::instance()['admin'], 'enablePages')) {
                $Grav['admin']->enablePages();
            }
        }
        $Grav->fireEvent('onPageInitialized');
        // $Grav->fireEvent('onPagesInitialized');
        $Grav['twig']->init();
        $Grav['pages']->init();
        $Grav['themes']->init();
        $Pages = $Grav['page']->evaluate(['@root.children' => '/']);
        foreach ($Pages as $Page) {
            // $content = $Page->rawMarkdown();
            $content = $Grav['twig']->processTemplate(
                $Page->template() . '.' . $Page->templateFormat('html') . '.twig',
                ['page' => $Page]
            );
            echo 'event: update' . "\n\n";
            echo 'data: ' . json_encode(
                [
                    'datetime' => date(DATE_ISO8601),
                    'title' => $Page->title(),
                    'format' => $Page->template() . '.' . $Page->templateFormat('html') . '.twig',
                    'content' => $content
                ]
            ) . "\n\n";
        }
    }
    /* public function collection(
        string $preset,
        string $target,
        Timer $Timer,
        bool $force = true
    ): void {
        try {
            echo 'event: update' . "\n\n";
            // var_export($preset);
            // var_export($target);
            // var_export($Timer);
            // var_export($force);
            // exit();
            for ($i=0; $i < 5; $i++) {
                echo 'data: ' . json_encode(
                    [
                        'datetime' => date(DATE_ISO8601),
                        'content' => 'Content',
                        'text' => 'Text',
                        'value' => 'Value'
                    ]
                ) . "\n\n";
            }

            // if (Utils::contains($target, '://')) {
            //     $scheme = parse_url($target, PHP_URL_SCHEME);
            //     $location = $locator->findResource($scheme . '://') . str_replace($scheme . '://', '/', $target);
            // } else {
            //     $this->output->error('<error>Target must be a valid stream resource, prefixing one of:</error>');
            //     foreach ($locator->getSchemes() as $scheme) {
            //         $this->output->writeln($scheme . '://');
            //     }
            //     return;
            // }
            // $location = $location . DS . 'static';
            // $Collection = new AbstractCollection('@root', '/', $target);
            // $Collection->setup($preset);
            // $Collection->buildCollection();
            // $Collection->collection();
            // if ($assets) {
            //     $Collection->assets();
            // }
            // if ($mirrorAssets) {
            //     $Collection->staticAssets();
            // }
            // if ($mirrorImages) {
            //     $Collection->images();
            // }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    } */

    /**
     * Report results
     *
     * @param array $items Items to report
     *
     * @return void
     */
    public function reporter(array $items): void
    {
        $message = ucfirst(
            Grav::instance()['language']->translate(
                ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.STORED']
            )
        ) . ' "' . $items['item'] . '" ' .
        Grav::instance()['language']->translate(
            ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
        ) . ' ' . $items['location'] . ' ' .
        Grav::instance()['language']->translate(
            ['PLUGIN_STATIC_GENERATOR.ADMIN.GENERIC.IN']
        ) . ' ' . $items['time'] . '.';
        echo 'event: update' . "\n\n";
        echo 'data: ' . json_encode(
            [
                'datetime' => date(DATE_ISO8601),
                'content' => $message,
                'text' => $items['item'],
                'value' => $items['location']
            ]
        ) . "\n\n";
    }
}
