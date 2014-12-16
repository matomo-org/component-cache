<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Cache\Backend;

use Piwik\Cache\Backend\ArrayCache;
use Piwik\Cache\Backend\Chained;
use Piwik\Cache\Backend\NullCache;

/**
 * @covers \Piwik\Cache\Backend\Chained
 */
class ChainedTest extends \PHPUnit_Framework_TestCase
{

    public function test_constructor_getbackends_shouldMakeSureToHaveProperIndex()
    {
        $arrayCache = new ArrayCache();
        $nullCache  = new NullCache();

        $backends = array(0 => $arrayCache, 2 => $nullCache);
        $cache = $this->createChainedCache($backends);

        $result = $cache->getBackends();
        $this->assertEquals(array($arrayCache, $nullCache), $result);
    }

    public function test_doFetch_shouldPopulateOtherCaches()
    {
        $cacheId = 'myid';
        $cacheValue = 'mytest';

        $arrayCache1 = new ArrayCache();
        $arrayCache2 = new ArrayCache();
        $arrayCache2->doSave($cacheId, $cacheValue);
        $arrayCache3 = new ArrayCache();

        $cache = $this->createChainedCache(array($arrayCache1, $arrayCache2, $arrayCache3));
        $this->assertEquals($cacheValue, $cache->doFetch($cacheId)); // should find the value from second cache

        // should populate previous cache
        $this->assertEquals($cacheValue, $arrayCache1->doFetch($cacheId));

        // should not populate slower cache
        $this->assertFalse($arrayCache3->doContains('myid'));
    }

    private function createChainedCache($backends)
    {
        return new Chained($backends);
    }

}