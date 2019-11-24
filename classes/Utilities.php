<?php
/**
 * Static Generator Plugin, Utilities
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
 * Utilities
 *
 * @category Extensions
 * @package  Grav\Plugin\StaticGenerator\Utilities
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-static-generator
 */
class Utilities
{
    /**
     * Search for a file in multiple locations
     *
     * @param string $file      Filename.
     * @param array  $locations List of locations.
     *
     * @return string File location.
     */
    public static function fileFinder(string $file, array $locations): string
    {
        $return = '';
        foreach ($locations as $location) {
            if (file_exists($location . '/' . $file)) {
                $return = $location . '/' . $file;
                break;
            }
        }
        return $return;
    }

    /**
     * Search for a folder in multiple locations
     *
     * @param string $folder    Folder name..
     * @param array  $locations List of locations.
     *
     * @return string Folder location.
     */
    public static function folderFinder(string $folder, array $locations): string
    {
        $return = '';
        foreach ($locations as $location) {
            if (is_dir($location . '/' . $folder)) {
                $return = $location . '/' . $folder;
                break;
            }
        }
        return $return;
    }

    /**
     * Search for files in multiple locations
     *
     * @param string $directory Folder-name.
     * @param array  $types     File extensions.
     *
     * @return array List of file locations.
     */
    public static function filesFinder(string $directory, array $types): array
    {
        $files = [];
        if (!is_dir($directory)) {
            return $files;
        }
        $iterator = new \FilesystemIterator(
            $directory,
            \FilesystemIterator::SKIP_DOTS
        );
        $files = [];
        foreach ($iterator as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $types)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * Search for a folders in multiple locations
     *
     * @param array $locations List of locations.
     *
     * @return array List of folder locations.
     */
    public static function foldersFinder(array $locations): array
    {
        $return = array();
        foreach ($locations as $location) {
            $folders = new \DirectoryIterator($location);
            foreach ($folders as $folder) {
                if ($folder->isDir() && !$folder->isDot()) {
                    $return[] = $folder->getFilename();
                }
            }
        }
        return $return;
    }
}
