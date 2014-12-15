<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 *
 */
namespace Piwik\Cache;

use Piwik\Cache;
use Piwik\Cache\Backend;

/**
 * This class is used to cache data during one request.
 *
 * Compared to the persistent cache it does not support setting any lifetime. To be a fast cache it does
 * not validate any cache id.
 */
class Transient
{
    /**
     * @var Backend
     */
    private $backend;

    public function __construct()
    {
        $this->backend = new Backend\ArrayCache();
    }

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function get($id)
    {
        return $this->backend->doFetch($id);
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function has($id)
    {
        return $this->backend->doContains($id);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function set($id, $content)
    {
        return $this->backend->doSave($id, $content);
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        return $this->backend->doDelete($id);
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        return $this->backend->doFlush();
    }

}
