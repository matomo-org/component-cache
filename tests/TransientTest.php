<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Transient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Transient
 */
class TransientTest extends TestCase
{
    /**
     * @var Transient
     */
    private $cache;

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    protected function setUp(): void
    {
        $this->cache = new Transient();
        $this->cache->save($this->cacheId, $this->cacheValue);
    }

    public function fetch_shouldReturnFalse_IfNoSuchCacheIdExistsTest()
    {
        $this->assertFalse($this->cache->fetch('randomid'));
    }

    public function fetch_shouldReturnTheCachedValue_IfCacheIdExistsTest()
    {
        $this->assertEquals($this->cacheValue, $this->cache->fetch($this->cacheId));
    }

    public function contains_shouldReturnFalse_IfNoSuchCacheIdExistsTest()
    {
        $this->assertFalse($this->cache->contains('randomid'));
    }

    public function contains_shouldReturnTrue_IfCacheIdExistsTest()
    {
        $this->assertTrue($this->cache->contains($this->cacheId));
    }

    public function delete_shouldReturnTrue_OnSuccessTest()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
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

    public function save_shouldBeAbleToSetObjectsTest()
    {
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

    public function flush_shouldRemoveAllCacheIdsTest()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->save('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
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
