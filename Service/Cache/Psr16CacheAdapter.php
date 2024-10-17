<?php

namespace TrueLayer\Connect\Service\Cache;

use Psr\SimpleCache\CacheInterface;
use TrueLayer\Connect\Model\Cache\CacheType;
use TrueLayer\Connect\Service\Cache\InvalidArgumentException;

class Psr16CacheAdapter implements CacheInterface
{
    private CacheType $cacheFrontend;

    public function __construct(CacheType $cacheFrontend)
    {
        $this->cacheFrontend = $cacheFrontend;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->cacheFrontend->load($key);

        if ($item === false) {
            return $default;
        }

        return unserialize($item);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $value = serialize($value);
        return $this->cacheFrontend->save(
            $value,
            $key,
            [CacheType::CACHE_TAG],
            $ttl
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
    {
        return $this->cacheFrontend->remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->cacheFrontend->clean();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        }
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * {@inheritdoc}
     * @param iterable<int|string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $stringKeyedValues = [];
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = (string) $key;
            }

            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given', gettype($key)));
            }

            $stringKeyedValues[$key] = $value;
        }
        $success = true;
        foreach ($stringKeyedValues as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        }
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->cacheFrontend->test($key);
    }
}