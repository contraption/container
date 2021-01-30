<?php

namespace Contraption\Container\Contracts;

/**
 * Container Contract
 *
 * The container is responsible for;
 *
 *  - Storing bindings for dependency resolution
 *  - Resolving dependencies of..
 *      - Classes it is creating new instances of
 *      - Methods or other callables it is asked to call
 *  - Registering and booting service providers
 *  - Containing the {@see \Contraption\Container\Contracts\DataStore}
 *
 * @package Contraption\Container
 */
interface Container
{
    /**
     * Bind a concrete for the provided abstract.
     *
     * @param string   $abstract
     * @param callable $concrete
     * @param bool     $shared If set to true the binding will only be resolved once per lifecycle.
     *
     * @return static
     */
    public function bind(string $abstract, callable $concrete, bool $shared = false): static;

    /**
     * Resolve dependencies and call the provided callable, optionally using the
     * provided arguments.
     *
     * @param callable $callable
     * @param array    $arguments
     *
     * @return object|null
     */
    public function call(callable $callable, array $arguments = []): ?object;

    /**
     * Get the current {@see \Contraption\Container\Contracts\DataStore}.
     *
     * @return \Contraption\Container\Contracts\DataStore
     */
    public function data(): DataStore;

    /**
     * Get the {@see \Contraption\Container\Contracts\Binding} for the provided abstract.
     *
     * @psalm-param class-string $abstract
     *
     * @param string             $abstract
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    public function get(string $abstract): ?Binding;

    /**
     * Resolve dependencies and create a new instance of the provided class,
     * optionally using the provided arguments.
     *
     * @psalm-template C
     *
     * @param string $class
     * @param array  $arguments
     * @param bool   $new
     * @param bool   $shared If set to true the resolved instance will be stored,
     *                       if it isn't null.
     *
     * @return C|null
     */
    public function make(string $class, array $arguments = [], bool $new = false, bool $shared = false): ?object;

    /**
     * Register an alias for the provided class, so that when any of the aliases
     * are requested from the container, the provided class is resolved.
     *
     * @param string   $class
     * @param string[] $aliases
     *
     * @return mixed
     */
    public function alias(string $class, string ...$aliases): static;
}