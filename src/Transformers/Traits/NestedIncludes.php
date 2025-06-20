<?php

namespace Nevestul4o\NetworkController\Transformers\Traits;

trait NestedIncludes
{
    public array $nestedIncludes = [];

    public function setNestedIncludes(array $nestedIncludes): void
    {
        $this->nestedIncludes = $nestedIncludes;
    }

    public function getNestedIncludes(): array
    {
        return $this->nestedIncludes;
    }

    public function getNestedInclude(string $name): array
    {
        if (empty($this->nestedIncludes[$name])) {
            return [];
        }

        if (!is_array($this->nestedIncludes[$name])) {
            return [$this->nestedIncludes[$name]];
        }

        return $this->nestedIncludes[$name];
    }

    public static function withNestedIncludes(array $nestedIncludes): static
    {
        $instance = new static();
        $instance->setNestedIncludes($nestedIncludes);
        if (method_exists($instance, 'setDefaultIncludes')) {
            $instance->setDefaultIncludes(array_keys($nestedIncludes));
        }
        return $instance;
    }
}

