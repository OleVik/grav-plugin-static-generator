<?php
/**
 * Static Generator Plugin, Generic Timer
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

/**
 * Class Generic Timer
 *
 * @category API
 * @package  Grav\Plugin\StaticGenerator\Timer
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */
class Timer
{
    protected $start;
    protected $stop;

    /**
     * Initialize class
     */
    public function __construct()
    {
        $this->start();
    }

    /**
     * Start Timer
     *
     * @return void
     */
    public function start(): void
    {
        $this->start = (int) (microtime(true) * 1000);
    }

    /**
     * Stop Timer
     *
     * @return void
     */
    public function stop(): void
    {
        $this->stop = (int) (microtime(true) * 1000);
    }

    /**
     * Get Time difference
     *
     * @return int
     */
    public function getTime(): int
    {
        $stop = $this->stop;
        if (!$stop) {
            $stop = (int) (microtime(true) * 1000);
        }
        $ms = $stop - $this->start;
        return $ms;
    }

    /**
     * Format milliseconds as closest major time unit
     *
     * @param int $ms Milliseconds
     *
     * @return string
     */
    public static function format($ms): string
    {
        $seconds = round(($ms / 1000), 2);
        $minutes = round(($ms / (1000 * 60)), 2);
        $hours = round(($ms / (1000 * 60 * 60)), 2);
        $days = round(($ms / (1000 * 60 * 60 * 24)), 2);
        if ($seconds <= 0) {
            return $ms . " ms";
        } elseif ($seconds < 60) {
            return $seconds . " sec";
        } elseif ($minutes < 60) {
            return $minutes . " min";
        } elseif ($hours < 24) {
            return $hours . " hrs";
        } else {
            return $days . " days";
        }
    }
}
