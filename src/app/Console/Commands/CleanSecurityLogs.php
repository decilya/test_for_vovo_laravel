<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanSecurityLogs extends Command
{

    protected $signature = 'security:logs:clean {--days=90 :  Удалить логи старше N дней}';

    protected $description = 'Команда для очистки логов';

    /**
     * @return void
     */
    public function handle(): void
    {
        $days = $this->option('days');
        $path = storage_path('logs/security.log');

        if (File::exists($path)) {
            $this->cleanLogFile($path, $days);
            $this->info("Логи старше {$days} дней были очищены.");

        }
    }

    /**
     * @param string $filePath
     * @param int $daysToKeep
     * @return array
     *
     */
    protected function cleanLogFile(string $filePath, int $daysToKeep): array
    {
        $result = [
            'deleted' => 0,
            'compressed' => 0,
            'skipped' => 0,
            'errors' => [],
            'freed_space' => 0, // в байтах
        ];

        $cutoffDate = now()->subDays($daysToKeep);

        try {
            if (!File::exists($filePath)) {
                $this->warn("Путь не найден: {$filePath}");
                return $result;
            }

            // Обработка директории
            if (File::isDirectory($filePath)) {
                return $this->cleanLogDirectory($filePath, $daysToKeep, $result);
            }

            // Обработка одиночного файла
            return $this->cleanSingleLogFile($filePath, $daysToKeep, $result);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $this->error("Ошибка: " . $e->getMessage());
            return $result;
        }
    }

    /**
     * @param string $directory
     * @param int $daysToKeep
     * @param array $result
     * @return array
     */
    protected function cleanLogDirectory(string $directory, int $daysToKeep, array $result): array
    {
        $cutoffDate = now()->subDays($daysToKeep);

        // Получаем все файлы, включая вложенные
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $fileName = $file->getFilename();

            // Пропускаем системные файлы
            if (in_array($fileName, ['.gitignore', '.htaccess'])) {
                $result['skipped']++;
                continue;
            }

            // Определяем тип файла
            $fileType = $this->getLogFileType($fileName);

            // Обрабатываем в зависимости от типа
            switch ($fileType) {
                case 'daily':
                    $result = $this->processDailyLog($filePath, $fileName, $cutoffDate, $result);
                    break;

                case 'single':
                    $result = $this->processSingleLog($filePath, $cutoffDate, $result);
                    break;

                case 'compressed':
                    $result = $this->processCompressedLog($filePath, $cutoffDate, $result);
                    break;

                default:
                    $result['skipped']++;
                    $this->line("Пропущен неизвестный формат: {$fileName}");
            }
        }

        // Очищаем пустые директории
        $this->removeEmptyDirectories($directory);

        return $result;
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function getLogFileType(string $fileName): string
    {
        if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log/', $fileName) ||
            preg_match('/security-(\d{4}-\d{2}-\d{2})\.log/', $fileName)) {
            return 'daily';
        }

        if (str_ends_with($fileName, '.gz') || str_ends_with($fileName, '.zip')) {
            return 'compressed';
        }

        if (str_ends_with($fileName, '.log')) {
            return 'single';
        }

        return 'unknown';
    }

    protected function processDailyLog(string $filePath, string $fileName, Carbon $cutoffDate, array $result): array
    {
        // Извлекаем дату из имени файла
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $fileName, $matches)) {
            $fileDate = Carbon::parse($matches[1]);

            if ($fileDate->lt($cutoffDate)) {
                $fileSize = File::size($filePath);

                if ($this->option('compress')) {
                    // Архивируем вместо удаления
                    if ($this->compressLogFile($filePath)) {
                        $result['compressed']++;
                        $result['freed_space'] += $fileSize * 0.7; // примерная экономия места
                    }
                } else {
                    // Удаляем файл
                    if (File::delete($filePath)) {
                        $result['deleted']++;
                        $result['freed_space'] += $fileSize;
                        $this->line("Удален: {$fileName}");
                    }
                }
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * @param string $filePath
     * @param Carbon $cutoffDate
     * @param array $result
     * @return array
     */
    protected function processSingleLog(string $filePath, Carbon $cutoffDate, array $result): array
    {
        $lastModified = File::lastModified($filePath);
        $fileName = basename($filePath);

        if ($lastModified < $cutoffDate->timestamp) {
            // Проверяем размер файла
            $fileSize = File::size($filePath);

            if ($this->option('backup') && $fileSize > 1024 * 1024) { // Если больше 1MB
                $this->backupLogFile($filePath);
            }

            if (File::delete($filePath)) {
                $result['deleted']++;
                $result['freed_space'] += $fileSize;
                $this->line("Удален: {$fileName}");
            }
        } else {
            $result['skipped']++;
        }

        return $result;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    protected function compressLogFile(string $filePath): bool
    {
        try {
            $compressedPath = $filePath . '.gz';

            // Открываем исходный файл
            $data = File::get($filePath);

            // Сжимаем
            $compressed = gzencode($data, 9);

            // Сохраняем сжатый файл
            File::put($compressedPath, $compressed);

            // Удаляем исходный файл
            File::delete($filePath);

            $this->line("Сжат: " . basename($filePath) . " → " . basename($compressedPath));

            return true;
        } catch (\Exception $e) {
            $this->error("Ошибка сжатия: " . $e->getMessage());
            return false;
        }
    }

    protected function backupLogFile(string $filePath): void
    {
        $backupDir = storage_path('backups/logs');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backupPath = $backupDir . '/' . basename($filePath) . '.' . now()->format('Y-m-d');

        File::copy($filePath, $backupPath);
        $this->line("Создана резервная копия: " . basename($backupPath));
    }

    protected function removeEmptyDirectories(string $directory): void
    {
        $directories = File::directories($directory);

        foreach ($directories as $dir) {
            if (count(File::allFiles($dir)) === 0) {
                File::deleteDirectory($dir);
                $this->line("Удалена пустая директория: " . basename($dir));
            }
        }
    }

}
