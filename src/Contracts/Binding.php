<?php

namespace Contraption\Container\Contracts;

/**
 * Binding Contract
 *
 * A binding within the container.
 *
 * @package Contraption\Container
 */
interface Binding
{
    /**
     * Whether or not the binding is shared.
     *
     * @return bool
     */
    public function isShared(): bool;

    /**
     * Set the binding as shared.
     *
     * @param bool $shared
     *
     * @return \Contraption\Container\Contracts\Binding
     */
    public function setShared(bool $shared): static;

    /**
     * Resolve the binding.
     *
     * @param \Contraption\Container\Contracts\Container $container
     * @param bool                                       $new
     * @param array                                      $arguments
     *
     * @return object|null
     */
    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object;
}