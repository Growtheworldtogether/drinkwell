<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Adds a CHECK rule where the database can add one after creation (MySQL 8.0.16+, MariaDB 10.3+). SQLite cannot; rows are scanned by drinkwell:doctor there. */
final class DbCheck
{
    public static function add(string $table, string $name, string $expression): void
    {
        // Column names are written in `backticks`: some (day_minute) are reserved words in MySQL and MariaDB.
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "'.$table.'" ADD CONSTRAINT "'.$name.'" CHECK ('.str_replace('`', '"', $expression).')');
        }
    }
}
