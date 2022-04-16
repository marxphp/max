<?php

namespace Max\Swagger;

class Parameter implements \JsonSerializable
{
    public function __construct(
        protected string $name,
        protected string $type,
        protected bool   $required = false,
        protected string $in = 'query',
        protected string $description = ''
    )
    {
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'type'        => $this->type,
            'name'        => $this->name,
            'required'    => $this->required,
            'description' => $this->description,
            'in'          => $this->in,
        ];
    }

}