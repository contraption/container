<?php

namespace Contraption\Container;

use Closure;
use Contraption\Collections\Collections;
use Contraption\Collections\Map;
use Contraption\Container\Bindings;
use Contraption\Container\Contracts\Binding;
use Contraption\Container\Exceptions\BindingException;
use Contraption\Container\Exceptions\BindingResolutionException;
use Throwable;

class Container implements Contracts\Container
{
    /**
     * @var \Contraption\Container\Contracts\DataStore
     */
    private Contracts\DataStore $dataStore;

    /**
     * @var \Contraption\Collections\Map<class-string, callable|Binding>
     */
    private Map $bindings;

    /**
     * @var \Contraption\Collections\Map<class-string, class-string>
     */
    private Map $bindingAliases;

    /**
     * @var \Contraption\Collections\Map<class-string, Binding>
     */
    private Map $classBindings;

    /**
     * @var \Contraption\Collections\Map<string, Binding>
     */
    private Map $functionBindings;

    public function __construct(?Contracts\DataStore $dataStore = null)
    {
        $this->dataStore        = $dataStore ?? new DataStore($this);
        $this->bindings         = Collections::map();
        $this->bindingAliases   = Collections::map();
        $this->classBindings    = Collections::map();
        $this->functionBindings = Collections::map();
    }

    /**
     * @inheritDoc
     */
    public function alias(string $class, string ...$aliases): static
    {
        foreach ($aliases as $alias) {
            $this->bindingAliases->put($alias, $class);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function bind(string $abstract, callable $concrete, bool $shared = false): static
    {
        $binding = $this->createBinding($concrete, $shared);

        if ($binding === null) {
            throw new BindingException(sprintf('Invalid binding provided for %s', $abstract));
        }

        $this->addBinding($abstract, $binding);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function call(callable $callable, array $arguments = []): ?object
    {
        return $this->createBinding($callable, false)
            ?->resolve($this, false, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function data(): Contracts\DataStore
    {
        return $this->dataStore;
    }

    /**
     * @inheritDoc
     */
    public function get(string $abstract): ?Binding
    {
        if ($this->bindingAliases->has($abstract)) {
            return $this->get($this->bindingAliases->get($abstract));
        }

        return $this->bindings->get($abstract);
    }

    /**
     * @inheritDoc
     */
    public function make(string $class, array $arguments = [], bool $new = false, bool $shared = false): ?object
    {
        $binding = $this->get($class);

        if ($binding === null) {
            $binding = $this->createClassBinding($class, $shared);
            $this->addBinding($class, $binding);
        }

        try {
            $resolved = $binding->resolve($this, $new, $arguments);
        } catch (Throwable $throwable) {
            throw new BindingResolutionException(
                sprintf('There was an error resolving %s', $class),
                0,
                $throwable
            );
        }

        if (! ($resolved instanceof $class)) {
            throw new BindingResolutionException(sprintf('Binding for %s does not return the correct type', $class));
        }

        return $resolved;
    }

    /**
     * Create a binding for a callable.
     *
     * @param callable $concrete
     * @param bool     $shared
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createBinding(callable $concrete, bool $shared): ?Binding
    {
        if ($concrete instanceof Closure) {
            return $this->createClosureBinding($concrete, $shared);
        }

        if (is_object($concrete)) {
            return $this->createObjectBinding($concrete);
        }

        if (is_string($concrete)) {
            if (class_exists($concrete)) {
                $binding = $this->getClassBinding($concrete);

                if ($binding === null) {
                    $binding = $this->createClassBinding($concrete, $shared);
                    $this->classBindings->put($concrete, $binding);
                }

                return $binding;
            }

            if (function_exists($concrete)) {
                $binding = $this->getFunctionBinding($concrete);

                if ($binding === null) {
                    $binding = $this->createFunctionBinding($concrete, $shared);
                    $this->functionBindings->put($concrete, $binding);
                }

                return $binding;
            }

            return null;
        }

        if (is_array($concrete) && count($concrete) === 2) {
            [$class, $method] = array_values($concrete);

            if (is_string($method) && (is_object($class) || (is_string($class) && class_exists($class)))) {
                return $this->createMethodBinding($class, $method, $shared);
            }

            return null;
        }

        return null;
    }

    /**
     * Create a binding for a closure.
     *
     * @param \Closure $closure
     * @param bool     $shared
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createClosureBinding(Closure $closure, bool $shared): ?Binding
    {
        return (new Bindings\ClosureBinding($closure))->setShared($shared);
    }

    /**
     * Create a binding for an object.
     *
     * @param object $object
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createObjectBinding(object $object): ?Binding
    {
        return (new Bindings\ObjectBinding($object))->setShared(true);
    }

    /**
     * Create a binding for a class.
     *
     * @param string $class
     * @param bool   $shared
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createClassBinding(string $class, bool $shared): ?Binding
    {
        return (new Bindings\ClassBinding($class))->setShared($shared);
    }

    /**
     * Create a binding for a method call on a class or object.
     *
     * @param string|object $class
     * @param string        $method
     * @param bool          $shared
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createMethodBinding(string|object $class, string $method, bool $shared): ?Binding
    {
        return (new Bindings\MethodBinding(
            is_object($class)
                ? $this->createObjectBinding($class)
                : $this->createClassBinding($class, $shared),
            $method
        ))->setShared($shared);
    }

    /**
     * Create a binding for a function.
     *
     * @param string $function
     * @param bool   $shared
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function createFunctionBinding(string $function, bool $shared): ?Binding
    {
        return (new Bindings\FunctionBinding($function))->setShared($shared);
    }

    /**
     * Add a binding to the collection.
     *
     * @param string                                   $abstract
     * @param \Contraption\Container\Contracts\Binding $binding
     */
    private function addBinding(string $abstract, Binding $binding): void
    {
        $this->bindings->put($abstract, $binding);
    }

    /**
     * Get a binding for a class.
     *
     * @param string $class
     *
     * @return \Contraption\Container\Contracts\Binding|null
     */
    private function getClassBinding(string $class): ?Binding
    {
        return $this->classBindings->get($class);
    }

    /**
     * Get a binding for a function.
     *
     * @param string $function
     *
     * @return mixed
     */
    private function getFunctionBinding(string $function)
    {
        return $this->functionBindings->get($function);
    }
}