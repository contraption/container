<?php

namespace Contraption\Container;

use Contraption\Collections\Collections;
use Contraption\Collections\FlatMap;
use Contraption\Collections\Map;
use Contraption\Collections\Set;
use Contraption\Container\Contracts\Container;
use Contraption\Container\Contracts\DataStore as Contract;

/**
 * Class DataStore
 *
 * @package Contraption\Container
 */
class DataStore implements Contract
{
    /**
     * @var \Contraption\Container\Contracts\Container
     */
    private Container $container;

    /**
     * @var \Contraption\Collections\FlatMap
     */
    private FlatMap $data;

    /**
     * @var \Contraption\Collections\Map<string, callable>
     */
    private Map $providers;

    /**
     * @var \Contraption\Collections\Set<string>
     */
    private Set $sharedProviders;

    /**
     * @var \Contraption\Collections\Map<string, string>
     */
    private Map $providerCache;

    /**
     * DataStore constructor.
     *
     * @param \Contraption\Container\Contracts\Container $container
     * @param iterable|array                             $items
     * @param iterable|array                             $providers
     */
    public function __construct(Container $container, iterable $items = [], iterable $providers = [])
    {
        $this->container       = $container;
        $this->data            = Collections::flatMap($items);
        $this->providers       = Collections::map($providers);
        $this->sharedProviders = Collections::set();
        $this->providerCache   = Collections::map();
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->data->get($key, $default);
        }

        $provider = $this->findProvider($key);

        if ($provider !== null) {
            return $this->provideData($provider, $key, $default);
        }

        return $default;
    }

    /**
     * @param string   $key
     * @param callable $resolver
     * @param bool     $shared
     *
     * @return $this
     */
    public function provide(string $key, callable $resolver, bool $shared = true): static
    {
        $this->providers->put($key, $resolver);

        if ($shared) {
            $this->sharedProviders->push($key);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $key, mixed $value): static
    {
        $this->data->put($key, $value);

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return ($this->data->get($key) || $this->findProvider($key)) !== null;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    private function findProvider(string $key): ?string
    {
        if ($this->providerCache->has($key)) {
            return $this->providerCache->get($key);
        }

        $provider   = null;
        $difference = strlen($key);

        $this->providers->each(function (callable $value, string $providerKey) use ($key, &$provider, &$difference) {
            if (str_starts_with($providerKey, $key)) {
                $remaining = strlen(str_replace($key, '', $providerKey));

                if ($remaining === 0 || $remaining <= $difference) {
                    $provider   = $providerKey;
                    $difference = $remaining;
                }
            }
        });

        if ($provider !== null) {
            $this->providerCache->put($key, $provider);
        }

        return $provider;
    }

    /**
     * @param string $providerKey
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function provideData(string $providerKey, string $key, mixed $default): mixed
    {
        $provider = $this->providers->get($providerKey);
        $value    = $provider($key, $default, $this->container) ?? $default;

        if ($this->sharedProviders->contains($key)) {
            $this->set($key, $value);
        }

        return $value;
    }
}