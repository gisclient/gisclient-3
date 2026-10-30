<?php

namespace GisClient\Author\Persistence;

interface PrimaryKeyAllocator
{
    public function allocate(string $schema, string $table, string $primaryKey): int;
}
