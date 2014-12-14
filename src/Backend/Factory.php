<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

use Piwik\Cache\Backend;

class Factory
{
    public function buildArrayCache()
    {
        return new ArrayCache();
    }

    public function buildFileCache($options)
    {
        return new File($options['directory']);
    }

    public function buildNullCache()
    {
        return new NullCache();
    }

    /**
     * This cache will persist any set data in the configured backend.
     * @return Backend
     */
    public function buildChainedCache($options)
    {
        $backends = array();

        foreach ($options['backends'] as $backendToBuild) {
            $backends[] = $this->buildBackend($backendToBuild, $options[$backendToBuild]);
        }

        return new Chained($backends);
    }

    public function buildRedisCache($options)
    {
        if (empty($options['host']) || empty($options['port'])) {
            throw new \Exception('RedisCache is not configured. Please provide at least a host and a port');
        }

        $redis = new \Redis();
        $redis->connect($options['host'], $options['port'], $options['timeout']);

        if (!empty($options['password'])) {
            $redis->auth($options['password']);
        }

        if (array_key_exists('database', $options)) {
            $redis->select((int) $options['database']);
        }

        $redisCache = new Redis();
        $redisCache->setRedis($redis);

        return $redisCache;
    }

    /**
     * @param $type
     * @param array $options
     * @return Backend
     * @throws Factory\BackendNotFoundException
     */
    public function buildBackend($type, $options)
    {
        switch ($type) {
            case 'array':

                return $this->buildArrayCache();

            case 'file':

                return $this->buildFileCache($options);

            case 'chained':

                return $this->buildChainedCache($options);

            case 'null':

                return $this->buildNullCache();

            case 'redis':

                return $this->buildRedisCache($options);

            default:

                throw new Factory\BackendNotFoundException("Cache backend $type not valid");
        }
    }
}