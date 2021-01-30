<?php

namespace Contraption\Container\Contracts;

/**
 * DataStore Contract
 *
 * The data store is a component of the {@see \Contraption\Container\Contracts\Container} and is
 * intended to store primitive values such as strings, integers, floats, booleans and arrays. A good
 * example of this would be containing configuration.
 *
 * All of the data is stored using a {@see \Contraption\Collections\FlatMap} which internally creates an
 * array with a single dimension, using the dot notation to create keys. Retrieving data from the data store
 * will expand it back to its original state if it's an array.
 *
 * The idea is that it moves basic bindings out of the container to its own location.
 *
 * @package Contraption\Container
 */
interface DataStore
{
    /**
     * Get the stored data for the provided key.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Register a custom provider for the provided key.
     *
     * @param string   $key
     * @param callable $resolver
     * @param bool     $shared If set to true this will only be resolved once.
     *
     * @return $this
     */
    public function provide(string $key, callable $resolver, bool $shared = true): static;

    /**
     * Set the value of the provided key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $key, mixed $value): static;
}