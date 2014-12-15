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
 * This cache uses one "cache" item for all keys it contains.
 *
 * This comes handy for things that you need very often, nearly in every request. Instead of having to read eg.
 * a hundred caches from file we only load one file which contains the hundred keys. Should be used only for things
 * that you need very often and only for cache entries that are not too large to keep loading and parsing the single
 * cache entry fast.
 *
 * $cache = new Eager($backend, $storageId = 'eagercache');
 * // $cache->fetch('my'id')
 * // $cache->save('myid', 'test');
 *
 * // ... at some point or at the end of the request
 * $cache->persistCacheIfNeeded(43200);
 */
class Eager
{
    /**
     * @var Backend
     */
    private $storage;
    private $storageId;
    private $content = array();
    private $isDirty = false;

    public function __construct(Backend $storage, $storageId)
    {
        $this->storage = $storage;
        $this->storageId = $storageId;

        $content = $storage->doFetch($storageId);

        if (is_array($content)) {
            $this->content = $content;
        }
    }

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function fetch($id)
    {
        return $this->content[$id];
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function contains($id)
    {
        return array_key_exists($id, $this->content);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function save($id, $content)
    {
        if (is_object($content)) {
            throw new \InvalidArgumentException('You cannot use this cache to cache an object, only arrays, strings and numbers. Have a look at Transient cache.');
            // for performance reasons we do currently not recursively search whether any array contains an object.
        }

        $this->content[$id] = $content;
        $this->isDirty = true;
        return true;
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        if ($this->contains($id)) {
            $this->isDirty = true;
            unset($this->content[$id]);
            return true;
        }

        return false;
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        $this->storage->doDelete($this->storageId);

        $this->content = array();
        $this->isDirty = false;

        return true;
    }

    public function persistCacheIfNeeded($ttl)
    {
        if ($this->isDirty) {
            $this->storage->doSave($this->storageId, $this->content, $ttl);
        }
    }

}
