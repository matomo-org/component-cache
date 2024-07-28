<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Backend;
use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Backend\Chained;
use Matomo\Cache\Backend\Factory;
use Matomo\Cache\Backend\File;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    private static $backends = array();

    protected function setUp(): void
    {
        foreach (self::$backends as $backend) {
            /** @var Backend[] $backend */
            $backend[0]->doFlush();
            $success = $backend[0]->doSave($this->cacheId, $this->cacheValue);
            $this->assertTrue($success);
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$backends as $backend) {
            /** @var Backend[] $backend */
            $backend[0]->doFlush();
        }

        self::$backends = array();
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doFetch_shouldReturnFalse_IfNoSuchCacheIdExists(Backend $backend)
    {
        $this->assertFalse($backend->doFetch('randomid'));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doFetch_shouldReturnTheCachedValue_IfCacheIdExists(Backend $backend)
    {
        $this->assertEquals($this->cacheValue, $backend->doFetch($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doContains_shouldReturnFalse_IfNoSuchCacheIdExists(Backend $backend)
    {
        $this->assertFalse($backend->doContains('randomid'));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doContains_shouldReturnTrue_IfCacheIdExists(Backend $backend)
    {
        $this->assertTrue($backend->doContains($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doDelete_shouldReturnTrue_OnSuccess(Backend $backend)
    {
        $this->assertTrue($backend->doDelete($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doDelete_shouldActuallyDeleteCacheId(Backend $backend)
    {
        $this->assertHasCacheEntry($backend, $this->cacheId);

        $backend->doDelete($this->cacheId);

        $this->assertHasNotCacheEntry($backend, $this->cacheId);
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doDelete_shouldNotDeleteAnyOtherCacheIds(Backend $backend)
    {
        $backend->doSave('anyother', 'myvalue');
        $this->assertHasCacheEntry($backend, $this->cacheId);

        $backend->doDelete($this->cacheId);

        $this->assertHasCacheEntry($backend, 'anyother');
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doDelete_shouldNotFail_IfCacheEntryDoesNotExist(Backend $backend)
    {
        $success = $backend->doDelete('anYRandoOmId');

        $this->assertTrue($success);
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doSave_shouldOverwriteAnyValue_IfCacheIdAlreadyExists(Backend $backend)
    {
        $this->assertHasCacheEntry($backend, $this->cacheId);

        $value = 'anyotherValuE';
        $backend->doSave($this->cacheId, $value);

        $this->assertSame($value, $backend->doFetch($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doSave_shouldBeAbleToSetArrays(Backend $backend)
    {
        $value = array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $backend->doSave($this->cacheId, $value);

        $this->assertSame($value, $backend->doFetch($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doSave_shouldBeAbleToSetNumbers(Backend $backend)
    {
        $value = 5.4;
        $backend->doSave($this->cacheId, $value);

        $this->assertSame($value, $backend->doFetch($this->cacheId));
    }

    /**
     * @dataProvider getBackends
     */
     #[\PHPUnit\Framework\Attributes\DataProvider('getBackends')]
    public function test_doFlush_shouldRemoveAllCacheIds(Backend $backend)
    {
        $this->assertHasCacheEntry($backend, $this->cacheId);
        $backend->doSave('mykey', 'myvalue');
        $this->assertHasCacheEntry($backend, 'mykey');

        $backend->doFlush();

        $this->assertHasNotCacheEntry($backend, $this->cacheId);
        $this->assertHasNotCacheEntry($backend, 'mykey');
    }

    public static function getBackends()
    {
        if (!empty(self::$backends)) {
            return self::$backends;
        }

        /** @var Backend[] $backends */
        $backends = array();

        $arrayCache = new ArrayCache();
        $fileCache  = new File(self::getPathToCacheDir());

        $chainedFileCache = new File( self::getPathToCacheDir() . '/chain' );
        $chainCache = new Chained( array(new ArrayCache(), $chainedFileCache) );

        $timeoutDecorated = new Backend\DefaultTimeoutDecorated( new ArrayCache(), ['defaultTimeout' => 8866] );
        $prefixDecorated = new Backend\KeyPrefixDecorated( new ArrayCache(), ['keyPrefix' => 'prefix123'] );

        $backends[] = $arrayCache;
        $backends[] = $fileCache;
        $backends[] = $chainCache;
        $backends[] = $timeoutDecorated;
        $backends[] = $prefixDecorated;

        if (extension_loaded('redis')) {
            $factory = new Factory();
            $backends[] = $factory->buildRedisCache(array('host' => '127.0.0.1', 'port' => '6379'));
        }

        foreach ($backends as $backend) {
            self::$backends[] = array($backend);
        }

        return self::$backends;
    }

    private static function getPathToCacheDir()
    {
        return __DIR__ . '/tmp';
    }

    private function assertHasCacheEntry(Backend $backend, $cacheId)
    {
        $this->assertTrue($backend->doContains($cacheId));
    }

    private function assertHasNotCacheEntry(Backend $backend, $cacheId)
    {
        $this->assertFalse($backend->doContains($cacheId));
    }

}
