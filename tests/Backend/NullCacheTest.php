<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\NullCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\NullCache
 */
class NullCacheTest extends TestCase
{
    /**
     * @var NullCache
     */
    private $cache;

    private $cacheId = 'testid';

    protected function setUp(): void
    {
        $this->cache = new NullCache();
        $this->cache->doSave($this->cacheId, 'anyvalue', 100);
    }

    public function doSave_shouldAlwaysReturnTrueTest()
    {
        $this->assertTrue($this->cache->doSave('randomid', 'anyvalue', 100));
    }

    public function doFetch_shouldAlwaysReturnFalse_EvenIfSomethingWasSetTest()
    {
        $this->assertFalse($this->cache->doFetch($this->cacheId));
    }

    public function doContains_shouldAlwaysReturnFalse_EvenIfSomethingWasSetTest()
    {
        $this->assertFalse($this->cache->doContains($this->cacheId));
    }

    public function doDelete_shouldAlwaysPretendItWorked_EvenIfNoSuchKeyExistsTest()
    {
        $this->assertTrue($this->cache->doDelete('loremipsum'));
    }

    public function doFlush_shouldAlwaysPretendItWorkedTest()
    {
        $this->assertTrue($this->cache->doFlush());
    }

}
