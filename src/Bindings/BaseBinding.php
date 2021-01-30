<?php

namespace Contraption\Container\Bindings;

use Contraption\Collections\Sequence;
use Contraption\Container\Attributes\Data;
use Contraption\Container\Contracts\Binding;
use Contraption\Container\Contracts\Container;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

abstract class BaseBinding implements Binding
{
    private bool $shared = false;

    private ?object $resolved;

    /**
     * @inheritDoc
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @inheritDoc
     */
    public function setShared(bool $shared): static
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * @return object|null
     */
    protected function getResolved(): ?object
    {
        return $this->resolved;
    }

    /**
     * @param object|null $resolved
     *
     * @return static
     */
    protected function setResolved(?object $resolved): static
    {
        $this->resolved = $resolved;

        return $this;
    }

    protected function isResolved(): bool
    {
        return $this->resolved !== null;
    }

    protected function resolveParameters(Container $container, ReflectionMethod $method, array $arguments): array
    {
        return (new Sequence($method->getParameters()))
            ->map(function (ReflectionParameter $parameter) use (&$arguments, $container) {
                return $this->resolveParameter($container, $parameter, $arguments);
            })
            ->all();
    }

    protected function resolveParameter(Container $container, ReflectionParameter $parameter, array &$arguments): mixed
    {
        $type     = $parameter?->getType();
        $name     = $parameter->getName();
        $typeName = $type ? $type->getName() : null;
        $provided = null;

        if (isset($arguments[$name])) {
            $provided = $arguments[$name];
            unset($arguments[$name]);
        } else if (isset($arguments[$parameter->getPosition()])) {
            $provided = $arguments[$parameter->getPosition()];
            unset($arguments[$parameter->getPosition()]);
        }

        if ($type instanceof ReflectionNamedType) {
            if ($provided === null && $type->allowsNull()) {
                return null;
            }

            if ((is_object($provided) && ! ($provided instanceof $typeName)) && $typeName !== gettype($provided)) {
                throw new InvalidArgumentException(sprintf(
                    'Argument provided for %s has incorrect type. Expected %s got %s',
                    $name,
                    $typeName,
                    gettype($provided)
                ));
            }

            if ($typeName !== null && ! $type->isBuiltIn()) {
                $argument = $container->make($typeName);

                if ($argument !== null || $type->allowsNull()) {
                    return $argument;
                }
            }
        }

        $dataDeps = $parameter->getAttributes(Data::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        if ($dataDeps !== null) {
            $dataDep = $dataDeps->newInstance();

            return $container->data()->get($dataDep->getKey());
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new InvalidArgumentException(sprintf(
            'Unable to resolve argument %s of type %s',
            $name,
            $typeName
        ));
    }
}