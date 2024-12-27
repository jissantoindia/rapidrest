<?php

declare(strict_types=1);

namespace RapidRest\Database\Migration;

use RapidRest\Database\Schema\SchemaManager;

abstract class Migration
{
    protected SchemaManager $schema;

    public function __construct(SchemaManager $schema)
    {
        $this->schema = $schema;
    }

    abstract public function up(): void;
    abstract public function down(): void;
}
