<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Eager;
use Matomo\Cache\Backend;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Eager
 */
class EagerTest extends TestCase
{
    /**
     * @var Eager
     */
    private $cache;

    /**
     * @var Backend
     */
    private $backend;

    private $storageId  = 'eagercache';
    private $cacheId    = 'testid';
    private $cacheValue = 'exampleValue';

    protected function setUp(): void
    {
        $this->backend = new ArrayCache();
        $this->backend->doSave($this->storageId, array($this->cacheId => $this->cacheValue));

        $this->cache = new Eager($this->backend, $this->storageId);
    }

    public function contains_shouldReturnFalse_IfNoSuchCacheIdExistsTest()
    {
        $this->assertFalse($this->cache->contains('randomid'));
    }

    public function contains_shouldReturnTrue_IfSuchCacheIdExistsTest()
    {
        $this->assertTrue($this->cache->contains($this->cacheId));
    }

    public function fetch_shouldReturnTheCachedValue_IfCacheIdExistsTest()
    {
        $this->assertEquals($this->cacheValue, $this->cache->fetch($this->cacheId));
    }

    public function save_shouldOverwriteAnyValue_IfCacheIdAlreadyExistsTest()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $value = 'anyotherValuE';
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function save_shouldBeAbleToSetArraysTest()
    {
        $value = array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function save_shouldFail_IfTryingToSetAnObjectTest()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('cannot use this cache to cache an object');
        $value = (object) array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function save_shouldBeAbleToSetNumbersTest()
    {
        $value = 5.4;
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function delete_shouldReturnTrue_OnSuccessTest()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
    }

    public function delete_shouldReturnFalse_IfCacheIdDoesNotExistTest()
    {
        $this->assertFalse($this->cache->delete('IdoNotExisT'));
    }

    public function delete_shouldActuallyDeleteCacheIdTest()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasNotCacheEntry($this->cacheId);
    }

    public function delete_shouldNotDeleteAnyOtherCacheIdsTest()
    {
        $this->cache->save('anyother', 'myvalue');
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasCacheEntry('anyother');
    }

    public function flush_shouldRemoveAllCacheIdsTest()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->save('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');
        $this->assertTrue($this->backend->doContains($this->storageId));

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
        $this->assertFalse($this->backend->doContains($this->storageId)); // should also remove the storage entry
    }

    public function persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingSetTest()
    {
        $this->cache->save('mykey', 'myvalue');

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $expected['mykey'] = 'myvalue';

        $this->assertEquals($expected, $this->getContentOfStorage());
    }

    public function persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingDeleteTest()
    {
        $this->cache->delete($this->cacheId);

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertEquals(array(), $this->getContentOfStorage());
    }

    public function persistCacheIfNeeded_shouldNotSaveAnyValuesInBackend_IfThereWasNoChangeTest()
    {
        $this->backend->doDelete($this->storageId);
        $this->assertFalse($this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertFalse($this->getContentOfStorage()); // should not have set the content of cache ($cacheId => $cacheValue)
    }

    private function getContentOfStorage()
    {
        return $this->backend->doFetch($this->storageId);
    }

    private function assertHasCacheEntry($cacheId)
    {
        $this->assertTrue($this->cache->contains($cacheId));
    }

    private function assertHasNotCacheEntry($cacheId)
    {
        $this->assertFalse($this->cache->contains($cacheId));
    }

}
