<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Cache\Backend;

use Piwik\Cache\Backend\File;

/**
 * @covers \Piwik\Cache\Backend\File
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var File
     */
    private $cache;

    private $cacheId = 'testid';

    public function setUp()
    {
        $this->cache = $this->createFileCache();
        $this->cache->doSave($this->cacheId, 'anyvalue', 100);
    }

    public function tearDown()
    {
        $this->cache->flushAll();
    }

    private function createFileCache($namespace = '')
    {
        $path = $this->getPath($namespace);

        return new File($path);
    }

    private function getPath($namespace = '', $id = '')
    {
        $path = __DIR__ . '/../tmp';

        if (!empty($namespace)) {
            $path .= '/' . $namespace;
        }

        if (!empty($id)) {
            $path .= '/' . $id . '.php';
        }

        return $path;
    }

    public function test_doSave_shouldCreateDirectoryWith750Permission_IfWritingIntoNewDirectory()
    {
        $namespace = 'test';

        $file = $this->createFileCache($namespace);
        $file->doSave('myidtest', 'myvalue');

        $this->assertTrue(is_dir($this->getPath($namespace)));
        $file->flushAll();
    }

    public function test_doSave_shouldCreateFile()
    {
        $this->cache->doSave('myidtest', 'myvalue');

        $this->assertFileExists($this->getPath('', 'myidtest'));
    }

    public function test_doSave_shouldSetLifeTime()
    {
        $this->cache->doSave('myidtest', 'myvalue', 500);

        $path =  $this->getPath('', 'myidtest');

        $contents = include $path;

        $this->assertGreaterThan(time() + 450, $contents['lifetime']);
        $this->assertLessThan(time() + 550, $contents['lifetime']);
    }

}