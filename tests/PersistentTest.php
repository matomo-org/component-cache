<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Cache;

use Piwik\Cache\Backend\ArrayCache;
use Piwik\Cache\Persistent;

/**
 * @covers \Piwik\Cache\Persistent
 */
class PersistentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Persistent
     */
    private $cache;

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    public function setUp()
    {
        $backend = new ArrayCache();
        $this->cache = new Persistent($backend);
        $this->cache->set($this->cacheId, $this->cacheValue);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Empty cache id
     */
    public function test_get_shouldFail_IfCacheIdIsEmpty()
    {
        $this->cache->get('');
    }

    /**
     * @dataProvider getInvalidCacheIds
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid cache id
     */
    public function test_shouldFail_IfCacheIdIsInvalid($method, $id)
    {
        $this->executeMethodOnCache($method, $id);
    }

    /**
     * @dataProvider getValidCacheIds
     */
    public function test_shouldNotFail_IfCacheIdIsValid($method, $id)
    {
        $this->executeMethodOnCache($method, $id);
        $this->assertTrue(true);
    }

    private function executeMethodOnCache($method, $id)
    {
        if ('set' === $method) {
            $this->cache->$method($id, 'val');
        } else {
            $this->cache->$method($id);
        }
    }

    public function test_get_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->get('randomid'));
    }

    public function test_get_shouldReturnTheCachedValue_IfCacheIdExists()
    {
        $this->assertEquals($this->cacheValue, $this->cache->get($this->cacheId));
    }

    public function test_has_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->has('randomid'));
    }

    public function test_has_shouldReturnTrue_IfCacheIdExists()
    {
        $this->assertTrue($this->cache->has($this->cacheId));
    }

    public function test_delete_shouldReturnTrue_OnSuccess()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
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

    public function test_flush_shouldRemoveAllCacheIds()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->set('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
    }

    private function assertHasCacheEntry($cacheId)
    {
        $this->assertTrue($this->cache->has($cacheId));
    }

    private function assertHasNotCacheEntry($cacheId)
    {
        $this->assertFalse($this->cache->has($cacheId));
    }

    public function getInvalidCacheIds()
    {
        $ids = array();
        $methods = array('get', 'set', 'has', 'delete');

        foreach ($methods as $method) {
            $ids[] = array($method, 'eteer#');
            $ids[] = array($method, '-test');
            $ids[] = array($method, '_test');
            $ids[] = array($method, '.test');
            $ids[] = array($method, 'test/test');
            $ids[] = array($method, '../test/');
            $ids[] = array($method, 'test0*');
            $ids[] = array($method, 'test\\test');
        }

        return $ids;
    }

    public function getValidCacheIds()
    {
        $ids = array();
        $methods = array('get', 'set', 'has', 'delete');

        foreach ($methods as $method) {
            $ids[] = array($method, '012test');
            $ids[] = array($method, 'test012test');
            $ids[] = array($method, 't.est.012test');
            $ids[] = array($method, 't-est-test');
            $ids[] = array($method, 't_est_tes4t');
            $ids[] = array($method, 't_est.te-s2t');
            $ids[] = array($method, 't_est...te-s2t');
        }

        return $ids;
    }
}