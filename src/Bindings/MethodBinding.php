<?php

namespace Contraption\Container\Bindings;

use Contraption\Container\Contracts\Binding;
use Contraption\Container\Contracts\Container;

class MethodBinding extends BaseBinding
{
    /**
     * @var \Contraption\Container\Contracts\Binding|null
     */
    private ?Binding $parent;

    private string $method;

    public function __construct(?Binding $parent, string $method)
    {
        $this->parent = $parent;
        $this->method = $method;
    }

    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object
    {
        if (! $new && $this->isResolved() && $this->isShared()) {
            return $this->getResolved();
        }

        $resolved = $this->callMethod($container, $arguments);

        if ($this->isShared()) {
            $this->setResolved($resolved);
        }

        return $resolved;
    }

    private function callMethod(Container $container, array $arguments): ?object
    {
        return call_user_func_array(
            [$this->parent->resolve($container), $this->method],
            $arguments
        );
    }
}