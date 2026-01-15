<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Запуск демо-сидера при вызове db:seed
        $this->call(DemoSeeder::class);
    }
}
