<?php

use RapidRest\Database\Migration\Migration;

class Migration_20231228_create_users_table extends Migration
{
    public function up(): void
    {
        $this->schema->createTable('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropTable('users');
    }
}
