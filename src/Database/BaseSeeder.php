<?php

namespace Nevestul4o\NetworkController\Database;

use Illuminate\Database\Seeder;

abstract class BaseSeeder extends Seeder
{
    protected array $objects = [];

    protected array $insertedObjects = [];

    protected int $lowCount = 0;

    protected int $highCount = 20;

    /**
     * @return array
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * @return array
     */
    public function getInsertedObjects(): array
    {
        return $this->insertedObjects;
    }

    /**
     * Gets a random value between low an high count
     *
     * @return int
     */
    public function getCount(): int
    {
        return rand($this->lowCount, $this->highCount);
    }
}
