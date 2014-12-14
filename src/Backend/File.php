<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

use Doctrine\Common\Cache\PhpFileCache;
use Piwik\Cache\Backend;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache creates one file per id. Every time you try to read the value it will load the cache file again.
 */
class File extends PhpFileCache implements Backend
{
    // for testing purposes since tests run on both CLI/FPM (changes in CLI can't invalidate
    // opcache in FPM, so we have to invalidate before reading)
    public static $invalidateOpCacheBeforeRead = false;

    protected $extension = '.php';

    public function doFetch($id)
    {
        if (self::$invalidateOpCacheBeforeRead) {
            $this->invalidateCacheFile($id);
        }

        return parent::doFetch($id);
    }

    public function doContains($id)
    {
        return parent::doContains($id);
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        if (!is_dir($this->directory)) {
            $this->createDirectory($this->directory);
        }

        if (!is_writable($this->directory)) {
            return false;
        }

        $success = parent::doSave($id, $data, $lifeTime);

        $this->invalidateCacheFile($id);

        return $success;
    }

    public function doDelete($id)
    {
        $this->invalidateCacheFile($id);

        $success = parent::doDelete($id);

        $this->invalidateCacheFile($id); // in case file was cached by another request between invalidate and doDelete()

        return $success;
    }

    public function doFlush()
    {
        foreach ($this->getFileIterator() as $name => $file) {
            $this->opCacheInvalidate($file);
        }

        parent::doFlush();
    }

    private function invalidateCacheFile($id)
    {
        $filename = $this->getFilename($id);
        $this->opCacheInvalidate($filename);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getFilename($id)
    {
        $path = $this->directory . DIRECTORY_SEPARATOR;
        $id   = preg_replace('@[\\\/:"*?<>|]+@', '', $id);

        return $path . DIRECTORY_SEPARATOR . $id . $this->extension;
    }

    private function opCacheInvalidate($filepath)
    {
        if (is_file($filepath)) {
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($filepath, $force = true);
            }
            if (function_exists('apc_delete_file')) {
                @apc_delete_file($filepath);
            }
        }
    }

    /**
     * @return \Iterator
     */
    private function getFileIterator()
    {
        $pattern = '/^.+\\' . $this->extension . '$/i';
        $iterator = new \RecursiveDirectoryIterator($this->directory);
        $iterator = new \RecursiveIteratorIterator($iterator);
        return new \RegexIterator($iterator, $pattern);
    }

    private function createDirectory($path)
    {
        if (!is_dir($path)) {
            // the mode in mkdir is modified by the current umask
            @mkdir($path, 0750, $recursive = true);
        }

        // try to overcome restrictive umask (mis-)configuration
        if (!is_writable($path)) {
            @chmod($path, 0755);
            if (!is_writable($path)) {
                @chmod($path, 0775);
                // enough! we're not going to make the directory world-writeable
            }
        }
    }
}
