<?php

namespace Contraption\Container\Bindings;

use Contraption\Container\Contracts\Container;

class ObjectBinding extends BaseBinding
{
    private object $object;

    public function __construct(object $object)
    {
        $this->object = $object;
    }

    public function resolve(Container $container, bool $new = false, array $arguments = []): ?object
    {
        return $this->object;
    }
}