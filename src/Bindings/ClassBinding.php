<?php

namespace Contraption\Container\Bindings;

use Contraption\Container\Attributes\Data;
use Contraption\Container\Attributes\Inject;
use Contraption\Container\Contracts\Container;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Class ClassBinding
 * @psalm-template C
 * @package        Contraption\Container\Bindings
 */
class ClassBinding extends BaseBinding
{
    /**
     * @psalm-var class-string<C>
     * @var string
     */
    private string $class;

    /**
     * @psalm-param class-string<C> $class
     *
     * @param string                $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @inheritDoc
     *
     * @return C|null
     */
    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object
    {
        if (! $new && $this->isResolved() && $this->isShared()) {
            return $this->getResolved();
        }

        $resolved = $this->resolveClass($container, $arguments);

        if ($this->isShared()) {
            $this->setResolved($resolved);
        }

        return $resolved;
    }

    /**
     * @param \Contraption\Container\Contracts\Container $container
     * @param array                                      $arguments
     *
     * @return C|null
     */
    private function resolveClass(Container $container, array $arguments): ?object
    {
        try {
            $className   = $this->class;
            $reflection  = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            // If there's no constructor or there are no parameters on the constructor that are required, and no
            // arguments have been provided we can just return a new instance.
            if (! $constructor || (! $constructor->getNumberOfRequiredParameters() === 0 && empty($arguments))) {
                // Check properties for dependencies.
                $this->resolvePropertyDependencies($container, $reflection);
                return $reflection->newInstance();
            }

            $this->resolveDataDependencies($container, $constructor, $arguments);
            $class = new $className(...$this->resolveParameters($container, $constructor, $arguments));
            $this->resolvePropertyDependencies($container, $reflection);

            return $class;

        } catch (ReflectionException $e) {
        }

        return null;
    }

    /**
     * Resolve parameter data dependencies.
     *
     * @param \Contraption\Container\Contracts\Container $container
     * @param \ReflectionMethod                          $constructor
     * @param array                                      $arguments
     */
    private function resolveDataDependencies(Container $container, ReflectionMethod $constructor, array &$arguments): void
    {
        $dataDeps = $constructor->getAttributes(Data::class, ReflectionAttribute::IS_INSTANCEOF);

        if (! empty($dataDeps)) {
            foreach ($dataDeps as $dataDep) {
                $attribute = $dataDep->newInstance();

                if ($attribute->getParameter() !== null && ! isset($arguments[$attribute->getParameter()])) {
                    $arguments[$attribute->getParameter()] = $container->data()->get($attribute->getKey());
                }
            }
        }
    }

    /**
     * Resolve lazy property dependencies and property data dependencies.
     *
     * @param \Contraption\Container\Contracts\Container $container
     * @param \ReflectionClass                           $reflection
     */
    private function resolvePropertyDependencies(Container $container, ReflectionClass $reflection): void
    {
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $lazyDeps = $property->getAttributes(Inject::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

            if (($lazyDeps !== null) && $property->hasDefaultValue() && $property->hasType()) {
                $property->setAccessible(true);
                $propertyType = $property->getType();

                if ($propertyType instanceof ReflectionNamedType && ! $propertyType->isBuiltin()) {
                    $property->setValue($container->make($propertyType->getName()));
                }
            }

            $dataDeps = $property->getAttributes(Data::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

            if ($dataDeps !== null) {
                $dataDep = $dataDeps->newInstance();
                $property->setAccessible(true);
                $property->setValue($container->data()->get($dataDep->getKey()));
            }
        }
    }
}