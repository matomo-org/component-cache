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
use Piwik\Cache\Backend\Factory;
use Piwik\Cache\Backend\File;
use Piwik\Cache\Backend\NullCache;
use Piwik\Cache\Backend\Redis;

/**
 * @covers \Piwik\Cache\Backend\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory();
    }

    public function test_buildArrayCache_ShouldReturnInstanceOfArray()
    {
        $cache = $this->factory->buildArrayCache();
        $this->assertTrue($cache instanceof ArrayCache);
    }

    public function test_buildNullCache_ShouldReturnInstanceOfNull()
    {
        $cache = $this->factory->buildNullCache();
        $this->assertTrue($cache instanceof NullCache);
    }

    public function test_buildFileCache_ShouldReturnInstanceOfFile()
    {
        $cache = $this->factory->buildFileCache(array('directory' => __DIR__));
        $this->assertTrue($cache instanceof File);
    }

    public function test_buildChainedCache_ShouldReturnInstanceOfChained()
    {
        $cache = $this->factory->buildChainedCache(array('backends' => array()));
        $this->assertTrue($cache instanceof Chained);
    }

    public function test_buildBackend_Chained_ShouldActuallyCreateInstancesOfNestedBackends()
    {
        $options = array(
            'backends' => array('array', 'file'),
            'file'     => array('directory' => __DIR__),
            'array'    => array()
        );

        /** @var Chained $cache */
        $cache = $this->factory->buildBackend('chained', $options);

        $backends = $cache->getBackends();

        $this->assertTrue($backends[0] instanceof ArrayCache);
        $this->assertTrue($backends[1] instanceof File);
    }

    public function test_buildRedisCache_ShouldReturnInstanceOfRedis()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $cache = $this->factory->buildRedisCache(array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0));
        $this->assertTrue($cache instanceof Redis);
    }

    public function test_buildBackend_Redis_ShouldReturnInstanceOfRedis()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0);

        $cache = $this->factory->buildBackend('redis', $options);
        $this->assertTrue($cache instanceof Redis);
    }

    public function test_buildBackend_Redis_ShouldForwardOptionsToRedisInstance()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 4.2, 'database' => 5);

        /** @var Redis $cache */
        $cache = $this->factory->buildBackend('redis', $options);
        $redis = $cache->getRedis();

        $this->assertSame('127.0.0.1', $redis->getHost());
        $this->assertSame(6379, $redis->getPort());
        $this->assertSame(4.2, $redis->getTimeout());
        $this->assertSame(5, $redis->getDBNum());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage RedisCache is not configured
     */
    public function test_buildRedisCache_ShouldFail_IfPortIsMissing()
    {
        $this->factory->buildRedisCache(array('host' => '127.0.0.1'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage RedisCache is not configured
     */
    public function test_buildRedisCache_ShouldFail_IfHostIsMissing()
    {
        $this->factory->buildRedisCache(array('port' => '6379'));
    }

    public function test_buildBackend_ArrayCache_ShouldReturnInstanceOfArray()
    {
        $cache = $this->factory->buildBackend('array', array());
        $this->assertTrue($cache instanceof ArrayCache);
    }

    public function test_buildBackend_NullCache_ShouldReturnInstanceOfNull()
    {
        $cache = $this->factory->buildBackend('null', array());
        $this->assertTrue($cache instanceof NullCache);
    }

    public function test_buildBackend_FileCache_ShouldReturnInstanceOfFile()
    {
        $cache = $this->factory->buildBackend('file', array('directory' => __DIR__));
        $this->assertTrue($cache instanceof File);
    }

    public function test_buildBackend_Chained_ShouldReturnInstanceOfChained()
    {
        $cache = $this->factory->buildBackend('chained', array('backends' => array()));
        $this->assertTrue($cache instanceof Chained);
    }

    /**
     * @expectedException \Piwik\Cache\Backend\Factory\BackendNotFoundException
     */
    public function test_buildBackend_ShouldThrowException_IfInvalidTypeGiven()
    {
        $this->factory->buildBackend('noTValId', array());
    }

    private function skipTestIfRedisIsNotInstalled()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The test ' . __METHOD__ . ' requires the use of redis');
        }
    }

}