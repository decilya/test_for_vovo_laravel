<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AnalyzeSecurityLogs extends Command
{
    protected $signature = 'security:analyze
                            {--hours=24 : Анализировать логи за последние N часов}
                            {--export : Экспортировать результаты в файл}
                            {--notify : Отправить уведомления}';

    protected $description = 'Анализирует логи безопасности на предмет подозрительной активности';

    public function handle(): int
    {
        $hours = $this->option('hours');
        $logPath = storage_path('logs/security.log');

        if (!File::exists($logPath)) {
            $this->error('Security log file not found!');
            return 1;
        }

        $logs = $this->parseLogs($logPath, $hours);
        $analysis = $this->analyzeLogs($logs);

        $this->displayResults($analysis);

        if ($this->option('export')) {
            $this->exportResults($analysis);
        }

        if ($this->option('notify')) {
            $this->sendNotifications($analysis);
        }

        return 0;
    }

    protected function parseLogs(string $path, int $hours): array
    {
        $since = now()->subHours($hours);
        $logs = [];

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['timestamp'])) {
                $logTime = \Carbon\Carbon::parse($data['timestamp']);
                if ($logTime->gte($since)) {
                    $logs[] = $data;
                }
            }
        }

        return $logs;
    }

    protected function analyzeLogs(array $logs): array
    {
        $analysis = [
            'total_events' => count($logs),
            'failed_logins' => 0,
            'lockouts' => 0,
            'suspicious_ips' => [],
            'top_attack_vectors' => [],
            'timeline' => [],
        ];

        $ipStats = [];

        foreach ($logs as $log) {
            // Подсчет по типам событий
            if (str_contains($log['message'] ?? '', 'Failed login')) {
                $analysis['failed_logins']++;
            }

            if (str_contains($log['message'] ?? '', 'lockout')) {
                $analysis['lockouts']++;
            }

            // Статистика по IP
            if (isset($log['ip'])) {
                $ip = $log['ip'];
                $ipStats[$ip] = ($ipStats[$ip] ?? 0) + 1;
            }

            // Выявление паттернов атак
            if (isset($log['user_agent'])) {
                $this->analyzeAttackVector($log['user_agent'], $analysis);
            }
        }

        // Топ подозрительных IP
        arsort($ipStats);
        $analysis['suspicious_ips'] = array_slice($ipStats, 0, 10, true);

        return $analysis;
    }

    protected function analyzeAttackVector(string $userAgent, array &$analysis): void
    {
        $vectors = [
            'bot' => ['bot', 'crawler', 'spider'],
            'scanner' => ['nikto', 'sqlmap', 'nessus', 'acunetix'],
            'script' => ['python', 'curl', 'wget', 'powershell'],
            'proxy' => ['proxy', 'vpn', 'tor'],
        ];

        $ua = strtolower($userAgent);

        foreach ($vectors as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($ua, $keyword)) {
                    $analysis['top_attack_vectors'][$type] =
                        ($analysis['top_attack_vectors'][$type] ?? 0) + 1;
                    break;
                }
            }
        }
    }


    /**
     * Отчет анализа безопасности
     *
     * @param array $analysis
     * @return void
     */
    protected function displayResults(array $analysis): void
    {
        $this->info('=== Отчет анализа безопасности ===');
        $this->line("Всего событий: {$analysis['total_events']}");
        $this->line("Неудачных попыток входа: {$analysis['failed_logins']}");
        $this->line("Блокировок учетных записей: {$analysis['lockouts']}");

        $this->info("\nТоп подозрительных IP-адресов:");
        foreach ($analysis['suspicious_ips'] as $ip => $count) {
            $this->line("  {$ip}: {$count} событий");
        }

        $this->info("\nОбнаруженные векторы атак:");
        foreach ($analysis['top_attack_vectors'] as $vector => $count) {
            $this->line("  {$vector}: {$count}");
        }
    }

    private function exportResults(array $analysis)
    {
    }

    private function sendNotifications(array $analysis)
    {
    }
}
