<?php

namespace Nevestul4o\NetworkController\Transformers\Interface;

interface NestedIncludesTransformer
{
    public function setNestedIncludes(array $nestedIncludes): void;
    public function getNestedIncludes(): array;
    public function getNestedInclude(string $name): array;
}