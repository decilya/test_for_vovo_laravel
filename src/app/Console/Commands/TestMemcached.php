<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestMemcached extends Command
{
    protected $signature = 'memcached:test
                            {--detailed : Показать детальную статистику}
                            {--retry=3 : Количество попыток подключения}
                            {--timeout=5 : Таймаут подключения в секундах}';

    protected $description = 'Комплексное тестирование подключения и функциональности Memcached';

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->info('🎯 Комплексное тестирование Memcached');
        $this->line(str_repeat('═', 60));

        // Шаг 1: Проверка расширения PHP
        if (!$this->checkPhpExtension()) {
            return 1;
        }

        // Шаг 2: Проверка конфигурации Laravel
        if (!$this->checkLaravelConfig()) {
            return 1;
        }

        // Шаг 3: Тест подключения к серверу
        if (!$this->testConnection()) {
            return 1;
        }

        // Шаг 4: Функциональное тестирование
        if (!$this->functionalTest()) {
            return 1;
        }

        // Шаг 5: Производительность
        $this->performanceTest();

        // Шаг 6: Детальная статистика (опционально)
        if ($this->option('detailed')) {
            $this->showDetailedStats();
        }

        $this->newLine();
        $this->info('✅ Все тесты пройдены успешно!');
        $this->line('Memcached полностью готов к работе.');

        Log::info('Memcached тестирование пройдено успешно', [
            'driver' => config('cache.default'),
            'host' => config('cache.stores.memcached.servers.0.host'),
            'port' => config('cache.stores.memcached.servers.0.port'),
        ]);

        return 0;
    }

    /**
     * Проверка расширения PHP Memcached
     */
    private function checkPhpExtension(): bool
    {
        $this->info('📦 Шаг 1: Проверка расширения PHP Memcached');

        if (!extension_loaded('memcached')) {
            $this->error('❌ Расширение Memcached не загружено в PHP');
            $this->line('Решение:');
            $this->line('  - Убедитесь, что расширение установлено в Dockerfile');
            $this->line('  - Проверьте командой: php -m | grep memcached');
            $this->line('  - Пересоберите контейнер: docker compose build app');
            return false;
        }

        $version = phpversion('memcached');
        $this->info("✅ Расширение загружено (версия: {$version})");

        if (!class_exists('Memcached')) {
            $this->error('❌ Класс Memcached не существует');
            return false;
        }

        $this->info('✅ Класс Memcached доступен');
        return true;
    }

    /**
     * Проверка конфигурации Laravel
     */
    private function checkLaravelConfig(): bool
    {
        $this->info('⚙️  Шаг 2: Проверка конфигурации Laravel');

        $driver = config('cache.default');
        $this->line("Текущий драйвер кэша: <fg=cyan>{$driver}</>");

        if ($driver !== 'memcached') {
            $this->error("❌ Драйвер кэша должен быть 'memcached', текущий: '{$driver}'");
            $this->line('Решение:');
            $this->line('  - Проверьте .env файл: CACHE_DRIVER=memcached');
            $this->line('  - Очистите кэш конфигурации: php artisan config:clear');
            $this->line('  - Убедитесь, что в .env нет дублирующихся переменных');
            return false;
        }

        $this->info('✅ Драйвер кэша настроен правильно');

        // Проверка серверов
        $servers = config('cache.stores.memcached.servers', []);

        if (empty($servers)) {
            $this->error('❌ Не настроены серверы Memcached');
            return false;
        }

        $this->info('✅ Настроенные серверы:');
        foreach ($servers as $index => $server) {
            $status = isset($server['host'], $server['port']) ? '✓' : '✗';
            $this->line("  {$status} Сервер #" . ($index + 1) . ": {$server['host']}:{$server['port']}");
        }

        // Проверка переменных окружения
        $this->table(
            ['Переменная', 'Значение', 'Статус'],
            [
                ['CACHE_DRIVER', env('CACHE_DRIVER'), env('CACHE_DRIVER') === 'memcached' ? '✅' : '❌'],
                ['SESSION_DRIVER', env('SESSION_DRIVER'), env('SESSION_DRIVER') === 'memcached' ? '✅' : '⚠️'],
                ['MEMCACHED_HOST', env('MEMCACHED_HOST'), env('MEMCACHED_HOST') ? '✅' : '⚠️'],
                ['MEMCACHED_PORT', env('MEMCACHED_PORT'), env('MEMCACHED_PORT') ? '✅' : '⚠️'],
            ]
        );

        return true;
    }

    /**
     * Тест подключения к серверу
     */
    private function testConnection(): bool
    {
        $this->info('🔌 Шаг 3: Тестирование подключения к серверу');

        $servers = config('cache.stores.memcached.servers', []);
        $maxRetries = (int)$this->option('retry');
        $timeout = (int)$this->option('timeout');

        $this->line("Параметры: попыток={$maxRetries}, таймаут={$timeout}с");

        $allConnected = true;
        $memcached = new \Memcached();

        // Настройка параметров подключения
        $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $timeout * 1000);
        $memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, $timeout);
        $memcached->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, $maxRetries);

        foreach ($servers as $index => $server) {
            $host = $server['host'] ?? 'localhost';
            $port = $server['port'] ?? 11211;

            $this->line("Подключение к {$host}:{$port}...");

            $connected = false;
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $memcached->addServer($host, $port);

                    // Проверяем статистику для этого сервера
                    $stats = $memcached->getStats();

                    if (isset($stats["{$host}:{$port}"])) {
                        $connected = true;
                        $serverStats = $stats["{$host}:{$port}"];
                        $uptime = $this->formatUptime($serverStats['uptime'] ?? 0);
                        $version = $serverStats['version'] ?? 'неизвестно';

                        $this->info("✅ Сервер {$host}:{$port} доступен");
                        $this->line("  Версия: {$version}");
                        $this->line("  Uptime: {$uptime}");
                        $this->line("  Использовано памяти: " . $this->formatBytes($serverStats['bytes'] ?? 0));
                        break;
                    }
                } catch (\Exception $e) {
                    $this->line("  Попытка {$attempt}/{$maxRetries} не удалась: " . $e->getMessage());
                }

                if ($attempt < $maxRetries) {
                    sleep(1);
                }
            }

            if (!$connected) {
                $this->error("❌ Не удалось подключиться к серверу {$host}:{$port}");
                $allConnected = false;

                // Диагностика
                $this->line('Диагностика:');
                $this->line("  - Проверьте запущен ли контейнер: docker compose ps memcached");
                $this->line("  - Проверьте логи: docker compose logs memcached");
                $this->line("  - Проверьте сеть: docker compose exec app nc -zv {$host} {$port}");
            }
        }

        return $allConnected;
    }

    /**
     * Функциональное тестирование
     */
    private function functionalTest(): bool
    {
        $this->info('🧪 Шаг 4: Функциональное тестирование');

        $testCases = [
            ['key' => 'laravel_string', 'value' => 'Memcached работает отлично!', 'ttl' => 300],
            ['key' => 'laravel_array', 'value' => ['data' => 'test', 'timestamp' => time()], 'ttl' => 300],
            ['key' => 'laravel_number', 'value' => 12345.67, 'ttl' => 300],
            ['key' => 'laravel_boolean', 'value' => true, 'ttl' => 300],
            ['key' => 'laravel_null', 'value' => null, 'ttl' => 300],
        ];

        $passed = 0;
        $failed = 0;

        $this->withProgressBar($testCases, function ($testCase) use (&$passed, &$failed) {
            $key = $testCase['key'];
            $expectedValue = $testCase['value'];
            $ttl = $testCase['ttl'];

            try {
                // Запись
                $writeResult = Cache::put($key, $expectedValue, $ttl);

                if (!$writeResult) {
                    $failed++;
                    return;
                }

                // Чтение
                $actualValue = Cache::get($key);

                if ($actualValue === $expectedValue) {
                    $passed++;
                } else {
                    $failed++;
                }

                // Удаление
                Cache::forget($key);

            } catch (\Exception $e) {
                $failed++;
            }
        });

        $this->newLine();

        if ($failed > 0) {
            $this->error("❌ Функциональное тестирование: {$passed}/" . count($testCases) . " пройдено");
            $this->line('Проблемы могут быть с:');
            $this->line('  - Сериализацией данных');
            $this->line('  - Размером значений (максимум 1MB)');
            $this->line('  - TTL (время жизни)');
            return false;
        }

        $this->info("✅ Функциональное тестирование: {$passed}/" . count($testCases) . " пройдено");

        // Тест инкремента/декремента
        $this->testIncrementDecrement();

        return true;
    }

    /**
     * Тест инкремента и декремента
     */
    private function testIncrementDecrement(): void
    {
        $counterKey = 'laravel_counter_test_' . time();

        try {
            Cache::put($counterKey, 10, 60);

            // Инкремент
            Cache::increment($counterKey);
            $value = Cache::get($counterKey);

            if ($value === 11) {
                $this->info('✅ Тест инкремента пройден');
            }

            // Декремент
            Cache::decrement($counterKey, 2);
            $value = Cache::get($counterKey);

            if ($value === 9) {
                $this->info('✅ Тест декремента пройден');
            }

            Cache::forget($counterKey);

        } catch (\Exception $e) {
            $this->line("⚠️  Тест инкремента/декремента пропущен: " . $e->getMessage());
        }
    }

    /**
     * Тест производительности
     */
    private function performanceTest(): void
    {
        $this->info('⚡ Шаг 5: Тест производительности');

        $iterations = 100;
        $keyPrefix = 'perf_test_';

        $this->line("Выполняется {$iterations} операций записи/чтения...");

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = $keyPrefix . $i;
            $value = "value_" . $i . "_" . str_repeat('x', 100); // 100 байт

            Cache::put($key, $value, 60);
            Cache::get($key);
            Cache::forget($key);
        }

        $endTime = microtime(true);
        $totalTime = round(($endTime - $startTime) * 1000, 2);
        $opsPerSecond = round($iterations * 3 / ($endTime - $startTime));

        $this->info("✅ Производительность: {$totalTime}мс для {$iterations} операций");
        $this->line("Скорость: {$opsPerSecond} операций/сек");

        // Оценка
        if ($opsPerSecond > 1000) {
            $this->info('🏆 Отличная производительность!');
        } elseif ($opsPerSecond > 500) {
            $this->info('👍 Хорошая производительность');
        } else {
            $this->line('⚠️  Производительность ниже средней, проверьте настройки');
        }
    }

    /**
     * Показать детальную статистику
     */
    private function showDetailedStats(): void
    {
        $this->info('📊 Детальная статистика сервера');

        try {
            $memcached = new \Memcached();
            $servers = config('cache.stores.memcached.servers', []);

            foreach ($servers as $server) {
                $host = $server['host'] ?? 'localhost';
                $port = $server['port'] ?? 11211;

                $memcached->addServer($host, $port);
                $stats = $memcached->getStats();

                if (isset($stats["{$host}:{$port}"])) {
                    $serverStats = $stats["{$host}:{$port}"];

                    $this->table(
                        ['Параметр', 'Значение'],
                        [
                            ['Версия', $serverStats['version'] ?? 'N/A'],
                            ['Uptime', $this->formatUptime($serverStats['uptime'] ?? 0)],
                            ['Текущие подключения', $serverStats['curr_connections'] ?? 'N/A'],
                            ['Всего подключений', $serverStats['total_connections'] ?? 'N/A'],
                            ['Использовано памяти', $this->formatBytes($serverStats['bytes'] ?? 0)],
                            ['Всего элементов', $serverStats['curr_items'] ?? 'N/A'],
                            ['Запросов в секунду', $serverStats['cmd_get'] ?? 'N/A'],
                            ['Попаданий в кэш', round(($serverStats['get_hits'] ?? 0) / max(($serverStats['cmd_get'] ?? 1), 1) * 100, 2) . '%'],
                            ['Заполненность', round(($serverStats['bytes'] ?? 0) / max(($serverStats['limit_maxbytes'] ?? 1), 1) * 100, 2) . '%'],
                        ]
                    );
                }
            }

        } catch (\Exception $e) {
            $this->line("⚠️  Не удалось получить статистику: " . $e->getMessage());
        }
    }

    /**
     * Форматирование времени uptime
     */
    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%dд %02d:%02d:%02d', $days, $hours, $minutes, $secs);
    }

    /**
     * Форматирование байтов
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
