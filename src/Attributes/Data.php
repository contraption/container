<?php

namespace Contraption\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD || Attribute::TARGET_PARAMETER)]
class Data
{
    private string  $key;
    private ?string $parameter;

    public function __construct(string $key, ?string $parameter = null)
    {
        $this->key       = $key;
        $this->parameter = $parameter;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTarget(): ?string
    {
        return $this->parameter;
    }
}