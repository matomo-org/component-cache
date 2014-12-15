<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Cache;

use Piwik\Cache\Transient;

/**
 * @covers \Piwik\Cache\Transient
 */
class TransientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Transient
     */
    private $cache;

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    public function setUp()
    {
        $this->cache = new Transient();
        $this->cache->set($this->cacheId, $this->cacheValue);
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

    public function test_set_shouldBeAbleToSetObjects()
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

}