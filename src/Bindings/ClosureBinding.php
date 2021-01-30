<?php

namespace Contraption\Container\Bindings;

use Closure;
use Contraption\Container\Contracts\Container;

class ClosureBinding extends BaseBinding
{
    private Closure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object
    {
        if (! $new && $this->isResolved() && $this->isShared()) {
            return $this->getResolved();
        }

        $resolved = $this->runClosure($container, $arguments);

        if ($this->isShared()) {
            $this->setResolved($resolved);
        }

        return $resolved;
    }

    private function runClosure(Container $container, array $arguments)
    {
        return call_user_func_array($this->closure, array_merge([$container], $arguments));
    }
}