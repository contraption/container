<?php

namespace Contraption\Container\Bindings;

use Contraption\Container\Contracts\Container;

class FunctionBinding extends BaseBinding
{
    private string $function;

    public function __construct(string $function)
    {
        $this->function = $function;
    }

    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object
    {
        if (! $new && $this->isResolved() && $this->isShared()) {
            return $this->getResolved();
        }

        $resolved = $this->runFunction($container, $arguments);

        if ($this->isShared()) {
            $this->setResolved($resolved);
        }

        return $resolved;
    }

    private function runFunction(Container $container, array $arguments)
    {
        return call_user_func_array($this->function, array_merge([$container], $arguments));
    }
}