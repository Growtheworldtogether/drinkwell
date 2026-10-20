<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Adds a CHECK rule where the database can add one after creation (MySQL 8.0.16+, MariaDB 10.3+). SQLite cannot; rows are scanned by drinkwell:doctor there. */
final class DbCheck
{
    public static function add(string $table, string $name, string $expression): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }
}
