<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Backend\Chained;
use Matomo\Cache\Backend\DefaultTimeoutDecorated;
use Matomo\Cache\Backend\Factory;
use Matomo\Cache\Backend\File;
use Matomo\Cache\Backend\KeyPrefixDecorated;
use Matomo\Cache\Backend\NullCache;
use Matomo\Cache\Backend\Redis;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new Factory();
    }

    public function buildArrayCache_ShouldReturnInstanceOfArrayTest()
    {
        $cache = $this->factory->buildArrayCache();
        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function buildNullCache_ShouldReturnInstanceOfNullTest()
    {
        $cache = $this->factory->buildNullCache();
        $this->assertInstanceOf(NullCache::class, $cache);
    }

    public function buildFileCache_ShouldReturnInstanceOfFileTest()
    {
        $cache = $this->factory->buildFileCache(array('directory' => __DIR__));
        $this->assertInstanceOf(File::class, $cache);
    }

    public function buildChainedCache_ShouldReturnInstanceOfChainedTest()
    {
        $cache = $this->factory->buildChainedCache(array('backends' => array()));
        $this->assertInstanceOf(Chained::class, $cache);
    }

    public function buildBackend_Chained_ShouldActuallyCreateInstancesOfNestedBackendsTest()
    {
        $options = array(
            'backends' => array('array', 'file'),
            'file'     => array('directory' => __DIR__),
            'array'    => array()
        );

        /** @var Chained $cache */
        $cache = $this->factory->buildBackend('chained', $options);

        $backends = $cache->getBackends();

        $this->assertInstanceOf(ArrayCache::class, $backends[0]);
        $this->assertInstanceOf(File::class, $backends[1]);
    }

    public function buildRedisCache_ShouldReturnInstanceOfRedisTest()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $cache = $this->factory->buildRedisCache(array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0));
        $this->assertInstanceOf(Redis::class, $cache);
    }

    public function buildBackend_Redis_ShouldReturnInstanceOfRedisTest()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0);

        $cache = $this->factory->buildBackend('redis', $options);
        $this->assertInstanceOf(Redis::class, $cache);
    }

    public function buildBackend_Redis_ShouldForwardOptionsToRedisInstanceTest()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 4.2, 'database' => 5);

        /** @var Redis $cache */
        $cache = $this->factory->buildBackend('redis', $options);
        $redis = $cache->getRedis();

        $this->assertEquals('127.0.0.1', $redis->getHost());
        $this->assertEquals(6379, $redis->getPort());
        $this->assertEquals(4.2, $redis->getTimeout());
        $this->assertEquals(5, $redis->getDBNum());
    }

    public function buildRedisCache_ShouldFail_IfPortIsMissingTest()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('RedisCache is not configured');
        $this->factory->buildRedisCache(array('host' => '127.0.0.1'));
    }

    public function buildRedisCache_ShouldFail_IfHostIsMissingTest()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('RedisCache is not configured');
        $this->factory->buildRedisCache(array('port' => '6379'));
    }

    public function buildBackend_ArrayCache_ShouldReturnInstanceOfArrayTest()
    {
        $cache = $this->factory->buildBackend('array', array());
        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function buildBackend_NullCache_ShouldReturnInstanceOfNullTest()
    {
        $cache = $this->factory->buildBackend('null', array());
        $this->assertInstanceOf(NullCache::class, $cache);
    }

    public function buildBackend_FileCache_ShouldReturnInstanceOfFileTest()
    {
        $cache = $this->factory->buildBackend('file', array('directory' => __DIR__));
        $this->assertInstanceOf(File::class, $cache);
    }

    public function buildBackend_Chained_ShouldReturnInstanceOfChainedTest()
    {
        $cache = $this->factory->buildBackend('chained', array('backends' => array()));
        $this->assertInstanceOf(Chained::class, $cache);
    }

    public function buildBackend_ShouldThrowException_IfInvalidTypeGivenTest()
    {
        self::expectException(\Matomo\Cache\Backend\Factory\BackendNotFoundException::class);
        $this->factory->buildBackend('noTValId', array());
    }

    private function skipTestIfRedisIsNotInstalled()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The test ' . __METHOD__ . ' requires the use of redis');
        }
    }


    public function buildBackend_Chained_ShouldCreateInstancesOfNestedDecoratorsTest()
    {}

    public function buildBackend_Decorated_DefaultTimeoutDecorated_ShouldActuallyCreateInstanceOfNestedBackendTest()
    {
        $options = array(
            'backend' => 'array',
            'array'    => array(),
            'defaultTimeout' => 555
        );

        /** @var DefaultTimeoutDecorated $cache */
        $cache = $this->factory->buildBackend('defaultTimeout', $options);


        $backend = $cache->getBackend();
        $this->assertInstanceOf(ArrayCache::class, $backend);
    }

    public function buildBackend_Decorated_KeyPrefixDecorated_ShouldActuallyCreateInstanceOfNestedBackendTest()
    {
        $options = array(
            'backend' => 'array',
            'array'    => array(),
            'keyPrefix' => '555'
        );

        /** @var KeyPrefixDecorated $cache */
        $cache = $this->factory->buildBackend('keyPrefix', $options);


        $backend = $cache->getBackend();
        $this->assertInstanceOf(ArrayCache::class, $backend);
    }
}
