<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Команда для индексации товаров
Artisan::command('products:index', function () {
    $this->info('Starting product indexing...');

    $count = \App\Models\Product::count();
    $bar = $this->output->createProgressBar($count);

    \App\Models\Product::chunk(100, function ($products) use ($bar) {
        foreach ($products as $product) {
            // Индексация для поиска
            $product->searchable();
            $bar->advance();
        }
    });

    $bar->finish();
    $this->newLine();
    $this->info("Indexed {$count} products successfully!");
})->purpose('Index products for search');

// Команда для генерации сидов
Artisan::command('seed:demo', function () {
    $this->call('db:seed', ['--class' => 'DemoSeeder']);
    $this->info('Demo data seeded successfully!');
})->purpose('Seed demo products and categories');

// Команда для очистки кэша товаров
Artisan::command('cache:products:clear', function () {
    \Illuminate\Support\Facades\Cache::tags(['products'])->flush();
    $this->info('Products cache cleared!');
})->purpose('Clear products cache');
