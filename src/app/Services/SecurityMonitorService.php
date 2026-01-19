<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Throwable;

/**
 * Сервис мониторинга безопасности системы
 *
 * Этот сервис предоставляет инструменты для:
 * - Централизованного логирования событий безопасности
 * - Управления счетчиками безопасности с временем жизни
 * - Мониторинга подозрительной активности
 * - Анализа лог-файлов безопасности
 * - Генерации отчетов о безопасности
 * - Интеграции с другими компонентами системы безопасности
 *
 * @package App\Services
 */
class SecurityMonitorService
{
    // Константы для уровней риска безопасности
    public const RISK_LEVEL_LOW = 'low';
    public const RISK_LEVEL_MEDIUM = 'medium';
    public const RISK_LEVEL_HIGH = 'high';
    public const RISK_LEVEL_CRITICAL = 'critical';

    // Константы для описаний уровней риска
    public const RISK_DESCRIPTION_LOW = 'Система безопасности функционирует нормально, существенных угроз не обнаружено.';
    public const RISK_DESCRIPTION_MEDIUM = 'Обнаружены признаки потенциальных угроз. Требуется мониторинг и анализ.';
    public const RISK_DESCRIPTION_HIGH = 'Обнаружены серьезные угрозы безопасности. Требуются немедленные действия.';
    public const RISK_DESCRIPTION_CRITICAL = 'КРИТИЧЕСКИЙ УРОВЕНЬ УГРОЗ! Требуется срочное вмешательство и меры по устранению.';
    public const RISK_DESCRIPTION_UNDEFINED = 'Уровень риска не определен.';

    // Константы для сообщений об угрозах
    public const THREAT_MESSAGE_HIGH = '⚠️ ВЫСОКИЙ УРОВЕНЬ УГРОЗ! Обнаружено множество подозрительных активностей. Требуется немедленное внимание.';
    public const THREAT_MESSAGE_MEDIUM = '⚠️ СРЕДНИЙ УРОВЕНЬ УГРОЗ. Обнаружена подозрительная активность. Рекомендуется усилить мониторинг.';
    public const THREAT_MESSAGE_LOW = '✅ НИЗКИЙ УРОВЕНЬ УГРОЗ. Система безопасности функционирует нормально.';

    // Константы для приоритетов рекомендаций
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    // Константы для категорий рекомендаций
    public const CATEGORY_AUTHENTICATION = 'authentication';
    public const CATEGORY_IP_MONITORING = 'ip_monitoring';
    public const CATEGORY_GENERAL = 'general';

    // Константы для периодов анализа
    public const PERIOD_HOUR = 'hour';
    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';

    // Константы для переводов периодов
    public const PERIOD_TRANSLATIONS = [
        self::PERIOD_HOUR => 'час',
        self::PERIOD_DAY => 'день',
        self::PERIOD_WEEK => 'неделю',
        self::PERIOD_MONTH => 'месяц'
    ];

    // Константы для пороговых значений
    public const THRESHOLD_FAILED_LOGINS_WARNING = 20;
    public const THRESHOLD_FAILED_LOGINS_CRITICAL = 50;
    public const THRESHOLD_SUSPICIOUS_ACTIVITIES_WARNING = 5;
    public const THRESHOLD_SUSPICIOUS_ACTIVITIES_CRITICAL = 10;
    public const THRESHOLD_IP_ATTEMPTS_WARNING = 30;
    public const THRESHOLD_IP_ATTEMPTS_CRITICAL = 50;
    public const THRESHOLD_ATTACK_VECTORS_WARNING = 1;
    public const THRESHOLD_ATTACK_VECTORS_CRITICAL = 2;
    public const THRESHOLD_UNIQUE_IPS_WARNING = 20;

    // Константы для весов оценки рисков
    public const RISK_WEIGHT_FAILED_LOGINS = 30;
    public const RISK_WEIGHT_SUSPICIOUS_ACTIVITIES = 25;
    public const RISK_WEIGHT_ATTACK_VECTORS = 35;
    public const RISK_WEIGHT_UNIQUE_IPS = 10;
    public const RISK_MAX_SCORE = 100;

    // Константы для сообщений об ошибках
    public const ERROR_LOG_SECURITY_EVENT = 'SecurityMonitorService (логирование события безопасности): Ошибка в методе logSecurityEvent: ';
    public const ERROR_INCREMENT_COUNTER = 'SecurityMonitorService (увеличение счетчика с временем жизни): Ошибка в методе incrementCounter: ';
    public const ERROR_GET_COUNTER = 'SecurityMonitorService (получение текущего значения счетчика): Ошибка в методе getCounter: ';
    public const ERROR_RESET_COUNTER = 'SecurityMonitorService (сброс счетчика к нулевому значению): Ошибка в методе resetCounter: ';
    public const ERROR_CHECK_LIMIT = 'SecurityMonitorService (проверка превышения лимита счетчика): Ошибка в методе isLimitExceeded: ';
    public const ERROR_LOG_AND_TRACK = 'SecurityMonitorService (логирование события безопасности с автоматическим увеличением счетчика): Ошибка в методе logAndTrackEvent: ';
    public const ERROR_GET_STATS = 'SecurityMonitorService (получение статистики безопасности за период): Ошибка в методе getSecurityStats: ';
    public const ERROR_ANALYZE_LOGS = 'SecurityMonitorService (анализ логов безопасности за указанный период): Ошибка в методе analyzeSecurityLogs: ';
    public const ERROR_GET_LOG_FILES = 'SecurityMonitorService (получение списка лог-файлов безопасности для анализа): Ошибка в методе getSecurityLogFiles: ';
    public const ERROR_ANALYZE_SINGLE_FILE = 'SecurityMonitorService (анализ одного лог-файла безопасности): Ошибка в методе analyzeSingleLogFile: ';
    public const ERROR_DETECT_ATTACK_VECTORS = 'SecurityMonitorService (обнаружение векторов атак на основе анализа логов): Ошибка в методе detectAttackVectors: ';
    public const ERROR_CLEANUP_COUNTERS = 'SecurityMonitorService (очистка старых счетчиков безопасности): Ошибка в методе cleanupOldCounters: ';
    public const ERROR_GENERATE_REPORT = 'SecurityMonitorService (генерация подробного отчета по безопасности): Ошибка в методе generateSecurityReport: ';

    // Константы для успешных сообщений
    public const SUCCESS_LOG_AND_TRACK = 'Событие успешно залогировано и отслежено';
    public const SUCCESS_CLEANUP_COUNTERS = 'Очистка старых счетчиков безопасности завершена';
    public const SUCCESS_REPORT_GENERATED = 'Отчет по безопасности успешно сгенерирован';

    // Константы для сообщений об ошибках в результатах
    public const RESULT_ERROR_SECURITY_EVENT = 'Ошибка при обработке события безопасности';
    public const RESULT_ERROR_GET_STATS = 'Не удалось получить статистику безопасности';
    public const RESULT_ERROR_ANALYZE_LOGS = 'Ошибка анализа логов безопасности';
    public const RESULT_ERROR_GENERATE_REPORT = 'Ошибка генерации отчета по безопасности';

    /**
     * Логирование события безопасности в специальный канал
     *
     * Метод записывает информацию о событии безопасности в выделенный лог-канал.
     * Все события логируются в формате JSON для последующего анализа.
     *
     * Используется канал 'security', который должен быть настроен в config/logging.php
     *
     * @param string $event Название события безопасности (например: 'failed_login', 'suspicious_activity')
     * @param array $data Дополнительные данные события (массив ключ-значение)
     * @return void
     *
     * @example
     * $monitor->logSecurityEvent('failed_login', [
     *     'ip' => '192.168.1.1',
     *     'email' => 'user@example.com',
     *     'attempts' => 5
     * ]);
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        try {
            // Логируем событие в специальный канал безопасности
            Log::channel('security')->info($event, $data);

        } catch (Throwable $e) {
            // В случае ошибки логирования, записываем в стандартный лог
            Log::error(self::ERROR_LOG_SECURITY_EVENT . $e->getMessage());
        }
    }

    /**
     * Увеличение счетчика с временем жизни (TTL)
     *
     * Метод инкрементирует значение счетчика в кэше и устанавливает время его жизни.
     * Используется для отслеживания количества событий за определенный период времени
     * (например, количество неудачных попыток входа за последний час).
     *
     * @param string $key Уникальный ключ счетчика
     * @param int $ttl Время жизни счетчика в секундах (по умолчанию 1 час)
     * @return int Текущее значение счетчика после инкремента
     *
     * @example
     * // Подсчет неудачных попыток входа для IP
     * $attempts = $monitor->incrementCounter('failed_logins:192.168.1.1', 3600);
     *
     * // Подсчет запросов к API
     * $requests = $monitor->incrementCounter('api_requests:user_123', 60);
     */
    public function incrementCounter(string $key, int $ttl = 3600): int
    {
        try {
            // Получаем текущее значение счетчика или 0, если счетчик не существует
            $counter = Cache::get($key, 0);

            // Увеличиваем счетчик на 1
            $counter++;

            // Сохраняем обновленное значение с указанным временем жизни
            Cache::put($key, $counter, now()->addSeconds($ttl));

            return $counter;

        } catch (Throwable $e) {
            Log::error(self::ERROR_INCREMENT_COUNTER . $e->getMessage());

            // Возвращаем 0 в случае ошибки для предотвращения сбоев в логике приложения
            return 0;
        }
    }

    /**
     * Получение текущего значения счетчика
     *
     * Метод возвращает текущее значение счетчика без его изменения.
     * Если счетчик не существует или истек его TTL, возвращается 0.
     *
     * @param string $key Уникальный ключ счетчика
     * @return int Текущее значение счетчика
     *
     * @example
     * $attempts = $monitor->getCounter('failed_logins:192.168.1.1');
     * if ($attempts > 5) {
     *     // Блокировка доступа
     * }
     */
    public function getCounter(string $key): int
    {
        try {
            return Cache::get($key, 0);

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_COUNTER . $e->getMessage());
            return 0;
        }
    }

    /**
     * Сброс счетчика к нулевому значению
     *
     * Метод удаляет счетчик из кэша, эффективно сбрасывая его значение.
     * Используется после успешных операций (например, после успешного входа)
     * или при ручной очистке статистики.
     *
     * @param string $key Уникальный ключ счетчика
     * @return bool true - если счетчик был сброшен, false - в случае ошибки
     *
     * @example
     * // Сброс счетчика неудачных попыток после успешного входа
     * $monitor->resetCounter('failed_logins:' . $userIp);
     */
    public function resetCounter(string $key): bool
    {
        try {
            Cache::forget($key);
            return true;

        } catch (Throwable $e) {
            Log::error(self::ERROR_RESET_COUNTER . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверка превышения лимита счетчика
     *
     * Метод проверяет, превышает ли текущее значение счетчика заданный лимит.
     * Используется для реализации защиты от brute-force атак.
     *
     * @param string $key Уникальный ключ счетчика
     * @param int $limit Максимально допустимое значение счетчика
     * @return bool true - если лимит превышен, false - если в пределах нормы
     *
     * @example
     * if ($monitor->isLimitExceeded('login_attempts:192.168.1.1', 5)) {
     *     // Блокировать дальнейшие попытки
     *     return response()->json(['error' => 'Слишком много попыток'], 429);
     * }
     */
    public function isLimitExceeded(string $key, int $limit): bool
    {
        try {
            $currentValue = $this->getCounter($key);
            return $currentValue >= $limit;

        } catch (Throwable $e) {
            Log::error(self::ERROR_CHECK_LIMIT . $e->getMessage());

            // В случае ошибки считаем, что лимит не превышен, чтобы не блокировать легитимных пользователей
            return false;
        }
    }

    /**
     * Логирование события безопасности с автоматическим увеличением счетчика
     *
     * Комбинированный метод, который одновременно:
     * 1. Логирует событие безопасности
     * 2. Увеличивает соответствующий счетчик
     * 3. Проверяет превышение лимита
     *
     * @param string $event Название события безопасности
     * @param string $counterKey Ключ счетчика для отслеживания
     * @param array $data Дополнительные данные события
     * @param int $ttl Время жизни счетчика в секундах
     * @return array Массив с результатами: ['logged' => bool, 'counter' => int, 'limit_exceeded' => bool]
     *
     * @example
     * $result = $monitor->logAndTrackEvent(
     *     'failed_login',
     *     'failed_logins:192.168.1.1',
     *     ['email' => 'user@example.com'],
     *     3600
     * );
     */
    public function logAndTrackEvent(string $event, string $counterKey, array $data = [], int $ttl = 3600): array
    {
        try {
            // Логируем событие
            $this->logSecurityEvent($event, $data);

            // Увеличиваем счетчик
            $counterValue = $this->incrementCounter($counterKey, $ttl);

            // Проверяем стандартные лимиты для данного типа события
            $limitExceeded = false;
            if (str_contains($event, 'failed_login') && $counterValue >= 5) {
                $limitExceeded = true;
            } elseif (str_contains($event, 'suspicious_activity') && $counterValue >= 3) {
                $limitExceeded = true;
            }

            return [
                'logged' => true,
                'counter' => $counterValue,
                'limit_exceeded' => $limitExceeded,
                'message' => self::SUCCESS_LOG_AND_TRACK
            ];

        } catch (Throwable $e) {
            Log::error(self::ERROR_LOG_AND_TRACK . $e->getMessage());

            return [
                'logged' => false,
                'counter' => 0,
                'limit_exceeded' => false,
                'error' => self::RESULT_ERROR_SECURITY_EVENT
            ];
        }
    }

    /**
     * Получение статистики безопасности за период
     *
     * Метод анализирует логи безопасности за указанный период
     * и возвращает агрегированную статистику.
     *
     * @param string $period Период для анализа ('hour', 'day', 'week', 'month')
     * @return array Статистика безопасности
     */
    public function getSecurityStats(string $period = 'day'): array
    {
        try {
            $cacheKey = 'security_stats:' . $period . ':' . date('Y-m-d-H');

            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($period) {
                // Получаем анализ логов за указанный период
                $logAnalysis = $this->analyzeSecurityLogs($period);

                return [
                    'period' => $period,
                    'total_events' => $logAnalysis['total_events'] ?? 0,
                    'failed_logins' => $logAnalysis['failed_logins'] ?? 0,
                    'suspicious_activities' => $logAnalysis['suspicious_activities'] ?? 0,
                    'blocked_ips' => $logAnalysis['blocked_ips'] ?? 0,
                    'top_ips' => $logAnalysis['top_ips'] ?? [],
                    'attack_vectors' => $logAnalysis['attack_vectors'] ?? [],
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Статистика безопасности за ' . $this->translatePeriod($period),
                    'log_files_analyzed' => $logAnalysis['log_files_analyzed'] ?? 0,
                    'analysis_time' => $logAnalysis['analysis_time'] ?? '0ms'
                ];
            });

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_STATS . $e->getMessage());

            return [
                'error' => true,
                'message' => self::RESULT_ERROR_GET_STATS,
                'period' => $period
            ];
        }
    }

    /**
     * Анализ логов безопасности за указанный период
     *
     * Метод анализирует лог-файлы безопасности и извлекает статистику:
     * - Количество событий разных типов
     * - Топ подозрительных IP адресов
     * - Векторы атак
     * - Аномальную активность
     *
     * @param string $period Период анализа ('hour', 'day', 'week', 'month')
     * @return array Результаты анализа логов
     */
    public function analyzeSecurityLogs(string $period = 'day'): array
    {
        $startTime = microtime(true);

        try {
            // Определяем временной диапазон для анализа
            $timeRange = $this->getTimeRangeForPeriod($period);
            $startDate = $timeRange['start'];
            $endDate = $timeRange['end'];

            // Получаем список лог-файлов для анализа
            $logFiles = $this->getSecurityLogFiles($startDate, $endDate);

            $analysis = [
                'total_events' => 0,
                'failed_logins' => 0,
                'suspicious_activities' => 0,
                'blocked_ips' => 0,
                'top_ips' => [],
                'attack_vectors' => [],
                'log_files_analyzed' => count($logFiles),
                'period_start' => $startDate->toDateTimeString(),
                'period_end' => $endDate->toDateTimeString(),
                'ip_stats' => [],
                'event_types' => [],
                'errors_detected' => []
            ];

            // Анализируем каждый лог-файл
            foreach ($logFiles as $logFile) {
                $fileAnalysis = $this->analyzeSingleLogFile($logFile, $startDate, $endDate);

                // Агрегируем результаты
                $analysis['total_events'] += $fileAnalysis['total_events'];
                $analysis['failed_logins'] += $fileAnalysis['failed_logins'];
                $analysis['suspicious_activities'] += $fileAnalysis['suspicious_activities'];
                $analysis['blocked_ips'] += $fileAnalysis['blocked_ips'];

                // Объединяем статистику по IP
                foreach ($fileAnalysis['ip_stats'] as $ip => $count) {
                    $analysis['ip_stats'][$ip] = ($analysis['ip_stats'][$ip] ?? 0) + $count;
                }

                // Объединяем типы событий
                foreach ($fileAnalysis['event_types'] as $event => $count) {
                    $analysis['event_types'][$event] = ($analysis['event_types'][$event] ?? 0) + $count;
                }

                // Объединяем ошибки
                $analysis['errors_detected'] = array_merge(
                    $analysis['errors_detected'],
                    $fileAnalysis['errors_detected']
                );
            }

            // Формируем топ IP адресов
            arsort($analysis['ip_stats']);
            $analysis['top_ips'] = array_slice($analysis['ip_stats'], 0, 10, true);

            // Анализируем векторы атак
            $analysis['attack_vectors'] = $this->detectAttackVectors($analysis);

            // Вычисляем время анализа
            $analysis['analysis_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

            return $analysis;

        } catch (Throwable $e) {
            Log::error(self::ERROR_ANALYZE_LOGS . $e->getMessage());

            return [
                'error' => true,
                'message' => self::RESULT_ERROR_ANALYZE_LOGS,
                'analysis_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
            ];
        }
    }

    /**
     * Получение временного диапазона для периода анализа
     *
     * @param string $period Период анализа
     * @return array Массив с начальной и конечной датами
     */
    private function getTimeRangeForPeriod(string $period): array
    {
        $now = Carbon::now();

        return match($period) {
            self::PERIOD_HOUR => [
                'start' => $now->copy()->subHour(),
                'end' => $now
            ],
            self::PERIOD_DAY => [
                'start' => $now->copy()->subDay(),
                'end' => $now
            ],
            self::PERIOD_WEEK => [
                'start' => $now->copy()->subWeek(),
                'end' => $now
            ],
            self::PERIOD_MONTH => [
                'start' => $now->copy()->subMonth(),
                'end' => $now
            ],
            default => [
                'start' => $now->copy()->subDay(),
                'end' => $now
            ]
        };
    }

    /**
     * Получение списка лог-файлов безопасности для анализа
     *
     * @param Carbon $startDate Начальная дата анализа
     * @param Carbon $endDate Конечная дата анализа
     * @return array Список путей к лог-файлам
     */
    private function getSecurityLogFiles(Carbon $startDate, Carbon $endDate): array
    {
        $logFiles = [];
        $logDirectory = storage_path('logs');

        try {
            // Проверяем существование директории логов
            if (!File::exists($logDirectory)) {
                return [];
            }

            // Получаем все файлы в директории логов
            $files = File::files($logDirectory);

            foreach ($files as $file) {
                $fileName = $file->getFilename();

                // Фильтруем только security лог-файлы
                if (str_contains($fileName, 'security') && pathinfo($fileName, PATHINFO_EXTENSION) === 'log') {
                    // Для daily логов проверяем дату в имени файла
                    if (preg_match('/security-(\d{4}-\d{2}-\d{2})\.log/', $fileName, $matches)) {
                        $fileDate = Carbon::parse($matches[1]);

                        // Проверяем, попадает ли файл в диапазон анализа
                        if ($fileDate->between($startDate, $endDate)) {
                            $logFiles[] = $file->getPathname();
                        }
                    } else {
                        // Для основного файла security.log проверяем дату модификации
                        if ($fileName === 'security.log') {
                            $fileModified = Carbon::createFromTimestamp(File::lastModified($file->getPathname()));
                            if ($fileModified->between($startDate, $endDate)) {
                                $logFiles[] = $file->getPathname();
                            }
                        }
                    }
                }
            }

            return $logFiles;

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_LOG_FILES . $e->getMessage());
            return [];
        }
    }

    /**
     * Анализ одного лог-файла безопасности
     *
     * @param string $filePath Путь к лог-файлу
     * @param Carbon $startDate Начальная дата анализа
     * @param Carbon $endDate Конечная дата анализа
     * @return array Результаты анализа файла
     */
    private function analyzeSingleLogFile(string $filePath, Carbon $startDate, Carbon $endDate): array
    {
        $analysis = [
            'total_events' => 0,
            'failed_logins' => 0,
            'suspicious_activities' => 0,
            'blocked_ips' => 0,
            'ip_stats' => [],
            'event_types' => [],
            'errors_detected' => [],
            'file_name' => basename($filePath)
        ];

        try {
            // Проверяем существование файла
            if (!File::exists($filePath)) {
                $analysis['errors_detected'][] = 'Файл не найден: ' . basename($filePath);
                return $analysis;
            }

            // Читаем файл построчно
            $lines = File::lines($filePath);

            foreach ($lines as $line) {
                // Пропускаем пустые строки
                if (empty(trim($line))) {
                    continue;
                }

                // Парсим JSON строку лога
                $logData = json_decode($line, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Если не JSON, пытаемся извлечь данные другим способом
                    $this->analyzeNonJsonLogLine($line, $analysis);
                    continue;
                }

                // Проверяем временную метку лога
                if (isset($logData['timestamp'])) {
                    $logTime = Carbon::parse($logData['timestamp']);

                    // Пропускаем логи вне диапазона анализа
                    if (!$logTime->between($startDate, $endDate)) {
                        continue;
                    }
                }

                // Анализируем запись лога
                $this->analyzeLogEntry($logData, $analysis);
            }

            return $analysis;

        } catch (Throwable $e) {
            Log::error(self::ERROR_ANALYZE_SINGLE_FILE . $e->getMessage());

            $analysis['errors_detected'][] = 'Ошибка анализа файла: ' . $e->getMessage();
            return $analysis;
        }
    }

    /**
     * Анализ не-JSON строки лога
     *
     * @param string $line Строка лога
     * @param array &$analysis Массив анализа (передается по ссылке)
     */
    private function analyzeNonJsonLogLine(string $line, array &$analysis): void
    {
        try {
            // Увеличиваем счетчик событий
            $analysis['total_events']++;

            // Пытаемся извлечь информацию из текстового лога
            if (str_contains($line, 'Неудачная попытка входа')) {
                $analysis['failed_logins']++;

                // Пытаемся извлечь IP адрес
                if (preg_match('/ip[\'":\s]+([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $line, $matches)) {
                    $ip = $matches[1];
                    $analysis['ip_stats'][$ip] = ($analysis['ip_stats'][$ip] ?? 0) + 1;
                }
            }

            if (str_contains($line, 'подозрительная активность') || str_contains($line, 'suspicious')) {
                $analysis['suspicious_activities']++;
            }

            if (str_contains($line, 'блокировка') || str_contains($line, 'lockout') || str_contains($line, 'blocked')) {
                $analysis['blocked_ips']++;
            }

        } catch (Throwable $e) {
            // Тихая обработка ошибки анализа строки
        }
    }

    /**
     * Анализ одной записи JSON лога
     *
     * @param array $logData Данные записи лога
     * @param array &$analysis Массив анализа (передается по ссылке)
     */
    private function analyzeLogEntry(array $logData, array &$analysis): void
    {
        try {
            // Увеличиваем счетчик событий
            $analysis['total_events']++;

            // Определяем тип события
            $eventType = $logData['event'] ?? $logData['message'] ?? 'unknown';
            $analysis['event_types'][$eventType] = ($analysis['event_types'][$eventType] ?? 0) + 1;

            // Анализируем конкретные типы событий
            if (str_contains(strtolower($eventType), 'failed') || str_contains(strtolower($eventType), 'неудач')) {
                $analysis['failed_logins']++;
            }

            if (str_contains(strtolower($eventType), 'suspicious') || str_contains(strtolower($eventType), 'подозрит')) {
                $analysis['suspicious_activities']++;
            }

            if (str_contains(strtolower($eventType), 'lockout') || str_contains(strtolower($eventType), 'block') || str_contains(strtolower($eventType), 'блокир')) {
                $analysis['blocked_ips']++;
            }

            // Собираем статистику по IP адресам
            if (isset($logData['ip'])) {
                $ip = $logData['ip'];
                $analysis['ip_stats'][$ip] = ($analysis['ip_stats'][$ip] ?? 0) + 1;
            }

            // Дополнительная информация для отладки
            if (isset($logData['level']) && in_array(strtolower($logData['level']), ['error', 'critical', 'alert'])) {
                $analysis['errors_detected'][] = [
                    'event' => $eventType,
                    'message' => $logData['message'] ?? 'Сообщение отсутствует',
                    'timestamp' => $logData['timestamp'] ?? 'Время не указано'
                ];
            }

        } catch (Throwable $e) {
            // Тихая обработка ошибки анализа записи
        }
    }

    /**
     * Обнаружение векторов атак на основе анализа логов
     *
     * @param array $analysis Результаты анализа логов
     * @return array Обнаруженные векторы атак
     */
    private function detectAttackVectors(array $analysis): array
    {
        $attackVectors = [];

        try {
            // Обнаружение brute-force атак
            if ($analysis['failed_logins'] > self::THRESHOLD_FAILED_LOGINS_CRITICAL) {
                $attackVectors[] = [
                    'type' => 'brute_force',
                    'confidence' => 'high',
                    'description' => 'Обнаружено множество неудачных попыток входа (' . $analysis['failed_logins'] . ')',
                    'recommendation' => 'Увеличьте лимиты rate limiting, добавьте капчу'
                ];
            }

            // Обнаружение подозрительных IP адресов
            if (!empty($analysis['top_ips'])) {
                foreach ($analysis['top_ips'] as $ip => $count) {
                    if ($count > self::THRESHOLD_IP_ATTEMPTS_CRITICAL) {
                        $attackVectors[] = [
                            'type' => 'suspicious_ip',
                            'ip' => $ip,
                            'attempts' => $count,
                            'confidence' => 'medium',
                            'description' => 'IP адрес ' . $ip . ' совершил ' . $count . ' подозрительных действий',
                            'recommendation' => 'Рассмотреть блокировку IP или усилить мониторинг'
                        ];
                    }
                }
            }

            // Обнаружение массовых подозрительных активностей
            if ($analysis['suspicious_activities'] > self::THRESHOLD_SUSPICIOUS_ACTIVITIES_CRITICAL) {
                $attackVectors[] = [
                    'type' => 'mass_suspicious_activity',
                    'confidence' => 'medium',
                    'description' => 'Обнаружено ' . $analysis['suspicious_activities'] . ' подозрительных активностей',
                    'recommendation' => 'Провести детальный анализ логов, настроить автоматические уведомления'
                ];
            }

            // Обнаружение ошибок в логах как возможных векторов атак
            if (count($analysis['errors_detected']) > 10) {
                $attackVectors[] = [
                    'type' => 'error_based_attack',
                    'confidence' => 'low',
                    'description' => 'Обнаружено ' . count($analysis['errors_detected']) . ' ошибок, которые могут указывать на попытки эксплуатации уязвимостей',
                    'recommendation' => 'Проверить логи на наличие SQL инъекций, XSS и других уязвимостей'
                ];
            }

        } catch (Throwable $e) {
            Log::error(self::ERROR_DETECT_ATTACK_VECTORS . $e->getMessage());
        }

        return $attackVectors;
    }

    /**
     * Перевод периода на русский язык
     *
     * @param string $period Период на английском
     * @return string Период на русском
     */
    private function translatePeriod(string $period): string
    {
        return self::PERIOD_TRANSLATIONS[$period] ?? $period;
    }

    /**
     * Очистка старых счетчиков безопасности
     *
     * Метод удаляет счетчики, которые больше не актуальны.
     * Рекомендуется запускать периодически через планировщик задач.
     *
     * @return array Результаты очистки
     *
     * @example
     * // В App\Console\Kernel.php
     * $schedule->call(function () {
     *     app(SecurityMonitorService::class)->cleanupOldCounters();
     * })->daily();
     */
    public function cleanupOldCounters(): array
    {
        try {
            // В реальной реализации здесь была бы очистка старых записей
            // Поскольку мы используем Cache с TTL, автоматическая очистка происходит сама

            Log::info('SecurityMonitorService: Запущена очистка старых счетчиков безопасности');

            return [
                'success' => true,
                'message' => self::SUCCESS_CLEANUP_COUNTERS,
                'timestamp' => now()->toDateTimeString()
            ];

        } catch (Throwable $e) {
            Log::error(self::ERROR_CLEANUP_COUNTERS . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Ошибка при очистке старых счетчиков',
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Генерация подробного отчета по безопасности
     *
     * @param string $period Период для отчета
     * @return array Подробный отчет по безопасности
     */
    public function generateSecurityReport(string $period = 'day'): array
    {
        $startTime = microtime(true);

        try {
            // Получаем базовую статистику
            $stats = $this->getSecurityStats($period);

            if (isset($stats['error']) && $stats['error']) {
                throw new \Exception('Не удалось получить статистику безопасности');
            }

            // Анализируем логи для получения детальной информации
            $logAnalysis = $this->analyzeSecurityLogs($period);

            // Генерируем рекомендации на основе анализа
            $recommendations = $this->generateSecurityRecommendations($stats, $logAnalysis);

            // Формируем итоговый отчет
            $report = [
                'report_id' => 'SEC-' . date('YmdHis') . '-' . strtoupper($period),
                'period' => $period,
                'generated_at' => now()->toDateTimeString(),
                'executive_summary' => $this->generateExecutiveSummary($stats, $logAnalysis),
                'detailed_statistics' => $stats,
                'log_analysis' => $logAnalysis,
                'recommendations' => $recommendations,
                'risk_assessment' => $this->assessSecurityRisk($stats, $logAnalysis),
                'generation_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                'status' => 'completed'
            ];

            // Кешируем отчет на 1 час
            Cache::put('security_report:' . $report['report_id'], $report, now()->addHour());

            return $report;

        } catch (Throwable $e) {
            Log::error(self::ERROR_GENERATE_REPORT . $e->getMessage());

            return [
                'error' => true,
                'message' => self::RESULT_ERROR_GENERATE_REPORT . ': ' . $e->getMessage(),
                'generation_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                'status' => 'failed'
            ];
        }
    }

    /**
     * Генерация краткого резюме для руководства
     *
     * @param array $stats Статистика безопасности
     * @param array $logAnalysis Анализ логов
     * @return array Краткое резюме
     */
    private function generateExecutiveSummary(array $stats, array $logAnalysis): array
    {
        $totalEvents = $stats['total_events'] ?? 0;
        $failedLogins = $stats['failed_logins'] ?? 0;
        $suspiciousActivities = $stats['suspicious_activities'] ?? 0;
        $attackVectors = count($stats['attack_vectors'] ?? []);

        // Определяем общий уровень угрозы
        $threatLevel = $this->determineThreatLevel($failedLogins, $suspiciousActivities, $attackVectors);

        return [
            'total_events' => $totalEvents,
            'threat_level' => $threatLevel,
            'key_findings' => [
                'failed_logins' => $failedLogins,
                'suspicious_activities' => $suspiciousActivities,
                'attack_vectors_detected' => $attackVectors,
                'unique_ips' => count($logAnalysis['ip_stats'] ?? [])
            ],
            'summary_message' => $this->getThreatLevelMessage($threatLevel)
        ];
    }

    /**
     * Определение уровня угрозы на основе статистики
     *
     * @param int $failedLogins Количество неудачных попыток входа
     * @param int $suspiciousActivities Количество подозрительных активностей
     * @param int $attackVectors Количество обнаруженных векторов атак
     * @return string Уровень угрозы
     */
    private function determineThreatLevel(int $failedLogins, int $suspiciousActivities, int $attackVectors): string
    {
        if ($failedLogins > self::THRESHOLD_FAILED_LOGINS_CRITICAL ||
            $suspiciousActivities > self::THRESHOLD_SUSPICIOUS_ACTIVITIES_CRITICAL ||
            $attackVectors > self::THRESHOLD_ATTACK_VECTORS_CRITICAL) {
            return self::RISK_LEVEL_HIGH;
        }

        if ($failedLogins > self::THRESHOLD_FAILED_LOGINS_WARNING ||
            $suspiciousActivities > self::THRESHOLD_SUSPICIOUS_ACTIVITIES_WARNING ||
            $attackVectors > self::THRESHOLD_ATTACK_VECTORS_WARNING) {
            return self::RISK_LEVEL_MEDIUM;
        }

        return self::RISK_LEVEL_LOW;
    }

    /**
     * Получение сообщения об уровне угрозы
     *
     * @param string $threatLevel Уровень угрозы
     * @return string Сообщение об уровне угрозы
     */
    private function getThreatLevelMessage(string $threatLevel): string
    {
        return match($threatLevel) {
            self::RISK_LEVEL_HIGH => self::THREAT_MESSAGE_HIGH,
            self::RISK_LEVEL_MEDIUM => self::THREAT_MESSAGE_MEDIUM,
            self::RISK_LEVEL_LOW => self::THREAT_MESSAGE_LOW,
            default => self::THREAT_MESSAGE_LOW
        };
    }

    /**
     * Генерация рекомендаций по безопасности
     *
     * @param array $stats Статистика
     * @param array $logAnalysis Анализ логов
     * @return array Рекомендации
     */
    private function generateSecurityRecommendations(array $stats, array $logAnalysis): array
    {
        $recommendations = [];

        // Рекомендации на основе неудачных попыток входа
        if ($stats['failed_logins'] > self::THRESHOLD_FAILED_LOGINS_WARNING) {
            $recommendations[] = [
                'priority' => self::PRIORITY_HIGH,
                'category' => self::CATEGORY_AUTHENTICATION,
                'title' => 'Усиление защиты от brute-force атак',
                'description' => 'Обнаружено ' . $stats['failed_logins'] . ' неудачных попыток входа. Рекомендуется:',
                'actions' => [
                    'Увеличить лимиты rate limiting для эндпоинтов аутентификации',
                    'Реализовать прогрессивную задержку между попытками',
                    'Добавить капчу после 3-5 неудачных попыток',
                    'Настроить автоматические уведомления при обнаружении атак'
                ]
            ];
        }

        // Рекомендации на основе подозрительных IP
        if (!empty($stats['top_ips'])) {
            foreach ($stats['top_ips'] as $ip => $count) {
                if ($count > self::THRESHOLD_IP_ATTEMPTS_WARNING) {
                    $recommendations[] = [
                        'priority' => self::PRIORITY_MEDIUM,
                        'category' => self::CATEGORY_IP_MONITORING,
                        'title' => 'Блокировка подозрительного IP адреса',
                        'description' => 'IP адрес ' . $ip . ' совершил ' . $count . ' подозрительных действий',
                        'actions' => [
                            'Рассмотреть временную блокировку IP в файрволе',
                            'Добавить IP в черный список приложения',
                            'Проанализировать географическое происхождение IP',
                            'Настроить мониторинг активности с данного IP'
                        ]
                    ];
                }
            }
        }

        // Общие рекомендации
        $recommendations[] = [
            'priority' => self::PRIORITY_LOW,
            'category' => self::CATEGORY_GENERAL,
            'title' => 'Регулярный анализ логов безопасности',
            'description' => 'Для поддержания высокого уровня безопасности рекомендуется:',
            'actions' => [
                'Ежедневно проверять отчеты безопасности',
                'Регулярно обновлять правила безопасности',
                'Проводить аудит системы раз в месяц',
                'Обучать сотрудников основам кибербезопасности'
            ]
        ];

        return $recommendations;
    }

    /**
     * Оценка рисков безопасности
     *
     * @param array $stats Статистика
     * @param array $logAnalysis Анализ логов
     * @return array Оценка рисков
     */
    private function assessSecurityRisk(array $stats, array $logAnalysis): array
    {
        $riskScore = 0;

        // Критерии оценки рисков
        $criteria = [
            'failed_logins' => [
                'threshold' => self::THRESHOLD_FAILED_LOGINS_WARNING,
                'weight' => self::RISK_WEIGHT_FAILED_LOGINS,
                'description' => 'Неудачные попытки входа'
            ],
            'suspicious_activities' => [
                'threshold' => self::THRESHOLD_SUSPICIOUS_ACTIVITIES_WARNING,
                'weight' => self::RISK_WEIGHT_SUSPICIOUS_ACTIVITIES,
                'description' => 'Подозрительные активности'
            ],
            'attack_vectors' => [
                'threshold' => self::THRESHOLD_ATTACK_VECTORS_WARNING,
                'weight' => self::RISK_WEIGHT_ATTACK_VECTORS,
                'description' => 'Обнаруженные векторы атак'
            ],
            'unique_ips' => [
                'threshold' => self::THRESHOLD_UNIQUE_IPS_WARNING,
                'weight' => self::RISK_WEIGHT_UNIQUE_IPS,
                'description' => 'Уникальные IP адреса'
            ]
        ];

        // Вычисляем оценку риска
        foreach ($criteria as $key => $config) {
            $value = $stats[$key] ?? $logAnalysis[$key] ?? 0;

            if ($value > $config['threshold']) {
                $excess = min($value - $config['threshold'], $config['threshold'] * 5);
                $riskScore += ($excess / ($config['threshold'] * 5)) * $config['weight'];
            }
        }

        // Нормализуем оценку
        $riskScore = min($riskScore, self::RISK_MAX_SCORE);

        // Определяем уровень риска
        $riskLevel = $this->determineRiskLevel($riskScore);

        return [
            'score' => round($riskScore, 1),
            'level' => $riskLevel,
            'max_score' => self::RISK_MAX_SCORE,
            'assessment_date' => now()->toDateString(),
            'description' => $this->getRiskDescription($riskLevel)
        ];
    }

    /**
     * Определение уровня риска на основе оценки
     *
     * @param float $riskScore Оценка риска
     * @return string Уровень риска
     */
    private function determineRiskLevel(float $riskScore): string
    {
        if ($riskScore > 70) {
            return self::RISK_LEVEL_CRITICAL;
        } elseif ($riskScore > 50) {
            return self::RISK_LEVEL_HIGH;
        } elseif ($riskScore > 30) {
            return self::RISK_LEVEL_MEDIUM;
        } else {
            return self::RISK_LEVEL_LOW;
        }
    }

    /**
     * Получение описания уровня риска
     *
     * @param string $riskLevel Уровень риска
     * @return string Описание уровня риска
     */
    private function getRiskDescription(string $riskLevel): string
    {
        $descriptions = [
            self::RISK_LEVEL_LOW => self::RISK_DESCRIPTION_LOW,
            self::RISK_LEVEL_MEDIUM => self::RISK_DESCRIPTION_MEDIUM,
            self::RISK_LEVEL_HIGH => self::RISK_DESCRIPTION_HIGH,
            self::RISK_LEVEL_CRITICAL => self::RISK_DESCRIPTION_CRITICAL
        ];

        return $descriptions[$riskLevel] ?? self::RISK_DESCRIPTION_UNDEFINED;
    }
}
