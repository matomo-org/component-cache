<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Cache;

use Piwik\Cache\Backend\ArrayCache;
use Piwik\Cache\Eager;
use Piwik\Cache\Backend;

/**
 * @covers \Piwik\Cache\Eager
 */
class EagerTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
    {
        $this->backend = new ArrayCache();
        $this->backend->doSave($this->storageId, array($this->cacheId => $this->cacheValue));

        $this->cache = $this->createEagerCache();
        $this->cache->populateCache($this->backend, $this->storageId);
    }

    public function test_isPopulated_shouldNotBePopulatedByDefault()
    {
        $cache = $this->createEagerCache();

        $this->assertFalse($cache->isPopulated());
    }

    public function test_isPopulated_shouldBePopulated_IfWasPopulateBefore()
    {
        $this->assertTrue($this->cache->isPopulated());
    }

    public function test_has_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->has('randomid'));
    }

    public function test_has_shouldReturnTrue_IfSuchCacheIdExists()
    {
        $this->assertTrue($this->cache->has($this->cacheId));
    }

    public function test_get_shouldReturnTheCachedValue_IfCacheIdExists()
    {
        $this->assertEquals($this->cacheValue, $this->cache->get($this->cacheId));
    }

    public function test_set_shouldOverwriteAnyValue_IfCacheIdAlreadyExists()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $value = 'anyotherValuE';
        $this->cache->set($this->cacheId, $value);

        $this->assertSame($value, $this->cache->get($this->cacheId));
    }

    public function test_set_shouldBeAbleToSetArrays()
    {
        $value = array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->set($this->cacheId, $value);

        $this->assertSame($value, $this->cache->get($this->cacheId));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage cannot use this cache to cache an object
     */
    public function test_set_shouldFail_IfTryingToSetAnObject()
    {
        $value = (object) array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->set($this->cacheId, $value);

        $this->assertSame($value, $this->cache->get($this->cacheId));
    }

    public function test_set_shouldBeAbleToSetNumbers()
    {
        $value = 5.4;
        $this->cache->set($this->cacheId, $value);

        $this->assertSame($value, $this->cache->get($this->cacheId));
    }

    public function test_delete_shouldReturnTrue_OnSuccess()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
    }

    public function test_delete_shouldReturnFalse_IfCacheIdDoesNotExist()
    {
        $this->assertFalse($this->cache->delete('IdoNotExisT'));
    }

    public function test_delete_shouldActuallyDeleteCacheId()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasNotCacheEntry($this->cacheId);
    }

    public function test_delete_shouldNotDeleteAnyOtherCacheIds()
    {
        $this->cache->set('anyother', 'myvalue');
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasCacheEntry('anyother');
    }

    public function test_flush_shouldRemoveAllCacheIds()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->set('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
    }

    public function test_persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingSet()
    {
        $this->cache->set('mykey', 'myvalue');

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $expected['mykey'] = 'myvalue';

        $this->assertEquals($expected, $this->getContentOfStorage());
    }

    public function test_persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingDelete()
    {
        $this->cache->delete($this->cacheId);

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertEquals(array(), $this->getContentOfStorage());
    }

    public function test_persistCacheIfNeeded_shouldNotSaveAnyValuesInBackend_IfThereWasNoChange()
    {
        $this->backend->doDelete($this->storageId);
        $this->assertFalse($this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertFalse($this->getContentOfStorage()); // should not have set the content of cache ($cacheId => $cacheValue)
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cache was not populated
     */
    public function test_persistCacheIfNeeded_shouldFail_IfNeverPopulated()
    {
        $this->createEagerCache()->persistCacheIfNeeded(400);
    }

    private function getContentOfStorage()
    {
        return $this->backend->doFetch($this->storageId);
    }

    private function createEagerCache()
    {
        return new Eager();
    }

    private function assertHasCacheEntry($cacheId)
    {
        $this->assertTrue($this->cache->has($cacheId));
    }

    private function assertHasNotCacheEntry($cacheId)
    {
        $this->assertFalse($this->cache->has($cacheId));
    }

}