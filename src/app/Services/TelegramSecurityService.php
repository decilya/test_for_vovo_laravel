<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Сервис для работы с Telegram API
 *
 * Этот сервис предоставляет инструменты для:
 * - Отправки уведомлений о безопасности в Telegram
 * - Отправки сообщений с разметкой HTML
 * - Отправки сообщений с кнопками (inline keyboard)
 * - Отправки ежедневных отчетов по безопасности
 * - Управления веб-хуками Telegram
 * - Кеширования результатов для оптимизации запросов
 *
 *
 *  Ответ бота-батька: "Done! Congratulations on your new bot. You will find it at t.me/LaravelTestForVovoAndOtherBot. You can now add a description, about section and profile picture for your bot, see /help for a list of commands. By the way, when you've finished creating your cool bot, ping our Bot Support if you want a better username for it. Just make sure the bot is fully operational before you do this.
 *
 *  Use this token to access the HTTP API:
 *  8227573099:AAGwmuU7x68kg2lGJQJGh0IlgM8J749OwUU
 *  Keep your token secure and store it safely, it can be used by anyone to control your bot.
 *
 *  For a description of the Bot API, see this page: https://core.telegram.org/bots/api" (с) батя ботов
 *
 * @LaravelTestForVovoAndOtherBot - бот
 *
 *
 * @package App\Services
 */
class TelegramSecurityService
{
    // Константы для типов сообщений
    public const MESSAGE_TYPE_SECURITY_ALERT = 'security_alert';
    public const MESSAGE_TYPE_DAILY_REPORT = 'daily_report';
    public const MESSAGE_TYPE_LOGIN_NOTIFICATION = 'login_notification';
    public const MESSAGE_TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const MESSAGE_TYPE_LOCKOUT = 'lockout';
    public const MESSAGE_TYPE_GENERAL = 'general';

    // Константы для уровней важности сообщений
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';
    public const  PRIORITY_CRITICAL = 'critical';

    // Константы для parse_mode
    public const PARSE_MODE_HTML = 'HTML';
    public const PARSE_MODE_MARKDOWN = 'Markdown';
    public const PARSE_MODE_MARKDOWN_V2 = 'MarkdownV2';

    // Константы для callback данных кнопок
    public const CALLBACK_BLOCK_IP = 'block_ip';
    public const CALLBACK_REPORT = 'report';
    public const CALLBACK_MARK_CHECKED = 'mark_checked';
    public const CALLBACK_IGNORE = 'ignore';

    // Константы для эмодзи
    public const EMOJI_SECURITY = '🛡️';
    public const EMOJI_ALERT = '🚨';
    public const EMOJI_WARNING = '⚠️';
    public const EMOJI_INFO = 'ℹ️';
    public const EMOJI_SUCCESS = '✅';
    public const EMOJI_ERROR = '❌';
    public const EMOJI_REPORT = '📊';
    public const EMOJI_LOCK = '🔒';
    public const EMOJI_UNLOCK = '🔓';
    public const EMOJI_BLOCK = '⛔';
    public const EMOJI_CHECK = '✓';

    // Константы для сообщений об ошибках
    public const ERROR_SEND_MESSAGE = 'TelegramService (отправка сообщения): Ошибка в методе sendMessage: ';
    public const ERROR_SEND_WITH_BUTTONS = 'TelegramService (отправка сообщения с кнопками): Ошибка в методе sendMessageWithButtons: ';
    public const ERROR_SEND_SECURITY_ALERT = 'TelegramService (отправка уведомления о безопасности): Ошибка в методе sendSecurityAlert: ';
    public const ERROR_SEND_LOGIN_NOTIFICATION = 'TelegramService (отправка уведомления о входе): Ошибка в методе sendLoginNotification: ';
    public const ERROR_SEND_SUSPICIOUS_ACTIVITY = 'TelegramService (отправка уведомления о подозрительной активности): Ошибка в методе sendSuspiciousActivityAlert: ';
    public const ERROR_SEND_DAILY_REPORT = 'TelegramService (отправка ежедневного отчета): Ошибка в методе sendDailySecurityReport: ';
    public const ERROR_GET_BOT_INFO = 'TelegramService (получение информации о боте): Ошибка в методе getBotInfo: ';
    public const ERROR_SET_WEBHOOK = 'TelegramService (установка веб-хука): Ошибка в методе setWebhook: ';
    public const ERROR_FORMAT_SECURITY_ALERT = 'TelegramService (форматирование уведомления о безопасности): Ошибка в методе formatSecurityAlert: ';
    public const ERROR_FORMAT_TELEGRAM_MESSAGE = 'TelegramService (форматирование сообщения для Telegram): Ошибка в методе formatTelegramMessage: ';
    public const ERROR_TRUNCATE_MESSAGE = 'TelegramService (обрезка сообщения): Ошибка в методе truncateMessage: ';
    public const ERROR_GET_LEVEL_EMOJI = 'TelegramService (получение эмодзи для уровня важности): Ошибка в методе getLevelEmoji: ';
    public const ERROR_GET_LEVEL_TITLE = 'TelegramService (получение заголовка для уровня важности): Ошибка в методе getLevelTitle: ';

    // Константы для сообщений об успехе
    public const SUCCESS_MESSAGE_SENT = 'Сообщение успешно отправлено';
    public const SUCCESS_WEBHOOK_SET = 'Веб-хук успешно установлен';
    public const SUCCESS_REPORT_SENT = 'Ежедневный отчет успешно отправлен';

    // Константы для сообщений об ошибках в результатах
    public const RESULT_ERROR_CHAT_ID_NOT_CONFIGURED = 'Chat ID Telegram не настроен';
    public const RESULT_ERROR_BOT_TOKEN_NOT_CONFIGURED = 'Bot Token Telegram не настроен';
    public const RESULT_ERROR_TELEGRAM_DISABLED = 'Интеграция с Telegram отключена';
    public const RESULT_ERROR_API_REQUEST_FAILED = 'Ошибка запроса к API Telegram';
    public const RESULT_ERROR_INVALID_RESPONSE = 'Неверный ответ от API Telegram';

    // Константы для заголовков уровней важности
    public const LEVEL_TITLE_HIGH = 'ВЫСОКИЙ ПРИОРИТЕТ';
    public const LEVEL_TITLE_MEDIUM = 'СРЕДНИЙ ПРИОРИТЕТ';
    public const LEVEL_TITLE_LOW = 'НИЗКИЙ ПРИОРИТЕТ';
    public const LEVEL_TITLE_INFO = 'ИНФОРМАЦИЯ';
    public const LEVEL_TITLE_CRITICAL = 'КРИТИЧЕСКИЙ';


    // Константы для эмодзи уровней важности
    public const LEVEL_EMOJI_HIGH = '🔴';
    public const LEVEL_EMOJI_MEDIUM = '🟡';
    public const LEVEL_EMOJI_LOW = '🟢';
    public const LEVEL_EMOJI_INFO = '🔵';
    public const LEVEL_EMOJI_CRITICAL = '⛔';

    /**
     * @var string Токен бота Telegram
     */
    protected string $botToken;

    /**
     * @var string Базовый URL API Telegram
     */
    protected string $apiUrl;

    /**
     * @var bool Флаг активности интеграции с Telegram
     */
    protected bool $enabled;

    /**
     * @var int Максимальная длина сообщения Telegram (в символах)
     */
    protected const MAX_MESSAGE_LENGTH = 4096;

    /**
     * @var int Таймаут HTTP запросов к API Telegram (в секундах)
     */
    protected const HTTP_TIMEOUT = 10;

    /**
     * @var int Таймаут соединения с API Telegram (в секундах)
     */
    protected const HTTP_CONNECT_TIMEOUT = 5;

    /**
     * Конструктор сервиса Telegram
     *
     * Инициализирует сервис с параметрами из конфигурации:
     * - Получает токен бота из конфигурации или .env
     * - Устанавливает базовый URL API Telegram
     * - Проверяет, включена ли интеграция с Telegram
     * - Проверяет наличие необходимых конфигурационных параметров
     */
    public function __construct()
    {
        try {
            $this->botToken = $this->getBotToken();
            $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
            $this->enabled = $this->isTelegramEnabled();

        } catch (Throwable $e) {
            Log::error('TelegramService (инициализация сервиса): Ошибка в конструкторе: ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    /**
     * Получение токена бота Telegram
     *
     * @return string Токен бота или пустая строка в случае ошибки
     */
    private function getBotToken(): string
    {
        try {
            // Сначала пытаемся получить из конфигурации
            $token = config('telegram.bot_token');

            // Если нет в конфигурации, пытаемся получить из .env
            if (empty($token)) {
                $token = env('TELEGRAM_BOT_TOKEN', '');
            }

            return $token ?: '';

        } catch (Throwable $e) {
            Log::error('TelegramService (получение токена бота Telegram): Ошибка в методе getBotToken: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Проверка включения интеграции с Telegram
     *
     * @return bool true - если интеграция включена, false - в противном случае
     */
    private function isTelegramEnabled(): bool
    {
        try {
            // Проверяем наличие токена
            if (empty($this->botToken)) {
                Log::warning('TelegramService: Bot Token не настроен, интеграция отключена');
                return false;
            }

            // Проверяем флаг включения из конфигурации
            $enabled = config('telegram.enabled', true);

            // Проверяем переменную окружения
            if (env('TELEGRAM_ENABLED') !== null) {
                $enabled = filter_var(env('TELEGRAM_ENABLED'), FILTER_VALIDATE_BOOLEAN);
            }

            return $enabled;

        } catch (Throwable $e) {
            Log::error('TelegramService (проверка включения интеграции с Telegram): Ошибка в методе isTelegramEnabled: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправка сообщения в Telegram
     *
     * Основной метод для отправки текстовых сообщений в Telegram.
     * Поддерживает HTML разметку, отключение уведомлений и другие параметры.
     *
     * @param string $message Текст сообщения для отправки
     * @param string|null $chatId ID чата для отправки (если null, используется chat_id из конфигурации)
     * @param string $parseMode Режим парсинга текста (HTML, Markdown, MarkdownV2)
     * @param bool $disableNotification Отключить уведомление о сообщении
     * @return bool true - если сообщение отправлено успешно, false - в случае ошибки
     *
     * @example
     * $telegram->sendMessage(
     *     '<b>Важное сообщение</b>',
     *     '-1001234567890',
     *     'HTML',
     *     false
     * );
     */
    public function sendMessage(
        string $message,
        string $chatId = null,
        string $parseMode = self::PARSE_MODE_HTML,
        bool $disableNotification = false
    ): bool {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                Log::warning('TelegramService: Интеграция с Telegram отключена');
                return false;
            }

            // Получаем chat_id из параметра или конфигурации
            $chatId = $chatId ?? $this->getDefaultChatId();

            // Проверяем наличие chat_id
            if (empty($chatId)) {
                Log::error(self::RESULT_ERROR_CHAT_ID_NOT_CONFIGURED);
                return false;
            }

            // Обрезаем сообщение до максимальной длины
            $truncatedMessage = $this->truncateMessage($message, self::MAX_MESSAGE_LENGTH);

            // Отправляем запрос к API Telegram
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT)
                ->post("{$this->apiUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $truncatedMessage,
                    'parse_mode' => $parseMode,
                    'disable_notification' => $disableNotification,
                    'disable_web_page_preview' => true,
                ]);

            // Проверяем успешность запроса
            if ($response->failed()) {
                $this->logApiError($response, 'sendMessage');
                return false;
            }

            Log::info(self::SUCCESS_MESSAGE_SENT . ' в чат: ' . $chatId);
            return true;

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_MESSAGE . $e->getMessage(), [
                'chat_id' => $chatId ?? 'не указан',
                'message_length' => strlen($message)
            ]);
            return false;
        }
    }

    /**
     * Получение chat_id по умолчанию из конфигурации
     *
     * @return string|null chat_id или null в случае ошибки
     */
    private function getDefaultChatId(): ?string
    {
        try {
            // Сначала пытаемся получить из конфигурации
            $chatId = config('telegram.security_chat_id');

            // Если нет в конфигурации, пытаемся получить из .env
            if (empty($chatId)) {
                $chatId = env('TELEGRAM_SECURITY_CHAT_ID');
            }

            return $chatId ?: null;

        } catch (Throwable $e) {
            Log::error('TelegramService (получение chat_id по умолчанию из конфигурации): Ошибка в методе getDefaultChatId: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Логирование ошибок API Telegram
     *
     * @param \Illuminate\Http\Client\Response $response Ответ от API Telegram
     * @param string $method Название метода, в котором произошла ошибка
     */
    private function logApiError(\Illuminate\Http\Client\Response $response, string $method): void
    {
        try {
            $statusCode = $response->status();
            $responseBody = $response->body();

            Log::error("TelegramService: Ошибка API Telegram в методе {$method}", [
                'status_code' => $statusCode,
                'response' => $responseBody,
                'description' => $this->getApiErrorDescription($statusCode)
            ]);

        } catch (Throwable $e) {
            Log::error('TelegramService (логирование ошибок API Telegram): Ошибка в методе logApiError: ' . $e->getMessage());
        }
    }

    /**
     * Получение описания ошибки API Telegram по коду статуса
     *
     * @param int $statusCode Код статуса HTTP
     * @return string Описание ошибки
     */
    private function getApiErrorDescription(int $statusCode): string
    {
        $descriptions = [
            400 => 'Неверный запрос - проверьте параметры',
            401 => 'Неавторизован - проверьте токен бота',
            403 => 'Запрещено - бот не имеет доступа к чату',
            404 => 'Не найдено - чат или метод не существует',
            429 => 'Слишком много запросов - превышен лимит',
            500 => 'Внутренняя ошибка сервера Telegram',
            502 => 'Плохой шлюз - проблемы с серверами Telegram',
            503 => 'Сервис недоступен - технические работы'
        ];

        return $descriptions[$statusCode] ?? 'Неизвестная ошибка API Telegram';
    }

    /**
     * Отправка сообщения с inline клавиатурой (кнопками)
     *
     * Метод отправляет сообщение с кнопками под ним.
     * Кнопки могут быть использованы для быстрых действий.
     *
     * @param string $message Текст сообщения
     * @param array $buttons Массив кнопок для inline клавиатуры
     * @param string|null $chatId ID чата для отправки
     * @param string $parseMode Режим парсинга текста
     * @return bool true - если сообщение отправлено успешно, false - в случае ошибки
     *
     * @example
     * $buttons = [
     *     ['text' => 'Блокировать IP', 'callback_data' => 'block_ip_192.168.1.1'],
     *     ['text' => 'Пометить как проверенное', 'callback_data' => 'mark_checked_123']
     * ];
     * $telegram->sendMessageWithButtons('Сообщение с кнопками', $buttons);
     */
    public function sendMessageWithButtons(
        string $message,
        array $buttons,
        string $chatId = null,
        string $parseMode = self::PARSE_MODE_HTML
    ): bool {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Получаем chat_id из параметра или конфигурации
            $chatId = $chatId ?? $this->getDefaultChatId();

            // Проверяем наличие chat_id
            if (empty($chatId)) {
                Log::error(self::RESULT_ERROR_CHAT_ID_NOT_CONFIGURED);
                return false;
            }

            // Формируем inline клавиатуру
            $keyboard = [
                'inline_keyboard' => $this->formatButtons($buttons)
            ];

            // Обрезаем сообщение до максимальной длины
            $truncatedMessage = $this->truncateMessage($message, self::MAX_MESSAGE_LENGTH);

            // Отправляем запрос к API Telegram
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->post("{$this->apiUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $truncatedMessage,
                    'parse_mode' => $parseMode,
                    'reply_markup' => json_encode($keyboard),
                    'disable_web_page_preview' => true,
                ]);

            return $response->successful();

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_WITH_BUTTONS . $e->getMessage(), [
                'chat_id' => $chatId ?? 'не указан',
                'buttons_count' => count($buttons)
            ]);
            return false;
        }
    }

    /**
     * Форматирование кнопок для inline клавиатуры
     *
     * @param array $buttons Массив кнопок
     * @return array Отформатированные кнопки (сгруппированные по 2 в ряд)
     */
    private function formatButtons(array $buttons): array
    {
        try {
            // Группируем кнопки по 2 в ряд
            return array_chunk($buttons, 2);

        } catch (Throwable $e) {
            Log::error('TelegramService (форматирование кнопок для inline клавиатуры): Ошибка в методе formatButtons: ' . $e->getMessage());
            return [$buttons]; // Возвращаем как есть в случае ошибки
        }
    }

    /**
     * Отправка уведомления о безопасности
     *
     * Специализированный метод для отправки уведомлений о событиях безопасности.
     * Автоматически форматирует сообщение с эмодзи и заголовком.
     *
     * @param array $data Данные для уведомления
     * @param string $level Уровень важности уведомления (high, medium, low)
     * @return bool true - если уведомление отправлено успешно, false - в случае ошибки
     *
     * @example
     * $telegram->sendSecurityAlert([
     *     'event' => 'failed_login',
     *     'ip' => '192.168.1.1',
     *     'email' => 'user@example.com'
     * ], 'high');
     */
    public function sendSecurityAlert(array $data, string $level = self::PRIORITY_MEDIUM): bool
    {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Получаем эмодзи и заголовок для уровня важности
            $emoji = $this->getLevelEmoji($level);
            $title = $this->getLevelTitle($level);

            // Форматируем сообщение о безопасности
            $formattedMessage = $this->formatSecurityAlert($data, $level);

            // Добавляем эмодзи и заголовок
            $fullMessage = "{$emoji} <b>{$title}</b>\n\n{$formattedMessage}";

            // Отправляем сообщение, отключая уведомления для низкого приоритета
            $disableNotification = ($level === self::PRIORITY_LOW || $level === 'info');

            return $this->sendMessage(
                message: $fullMessage,
                chatId: null, // Используем chat_id по умолчанию
                parseMode: self::PARSE_MODE_HTML,
                disableNotification: $disableNotification
            );

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_SECURITY_ALERT . $e->getMessage(), [
                'level' => $level,
                'data_keys' => array_keys($data)
            ]);
            return false;
        }
    }

    /**
     * Отправка уведомления о входе в систему
     *
     * Метод отправляет уведомление об успешной или неудачной попытке входа.
     *
     * @param string $ip IP адрес пользователя
     * @param string $userAgent User Agent браузера
     * @param string $email Email пользователя
     * @param bool $isSuccessful true - успешный вход, false - неудачная попытка
     * @param string|null $chatId ID чата для отправки (если null, используется chat_id по умолчанию)
     * @return bool true - если уведомление отправлено успешно, false - в случае ошибки
     *
     * @example
     * // Успешный вход
     * $telegram->sendLoginNotification('192.168.1.1', 'Chrome/91.0', 'user@example.com', true);
     *
     * // Неудачная попытка
     * $telegram->sendLoginNotification('192.168.1.1', 'Firefox/89.0', 'user@example.com', false);
     */
    public function sendLoginNotification(
        string $ip,
        string $userAgent,
        string $email,
        bool $isSuccessful = true,
        string $chatId = null
    ): bool {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Определяем иконку и статус в зависимости от результата
            $icon = $isSuccessful ? self::EMOJI_SUCCESS : self::EMOJI_ERROR;
            $status = $isSuccessful ? 'УСПЕШНЫЙ' : 'НЕУДАЧНЫЙ';
            $priority = $isSuccessful ? self::PRIORITY_LOW : self::PRIORITY_MEDIUM;

            // Формируем сообщение
            $message = "<b>{$icon} Попытка входа в систему</b>\n\n";
            $message .= "<b>Статус:</b> {$status}\n";
            $message .= "<b>Email:</b> <code>{$email}</code>\n";
            $message .= "<b>IP:</b> <code>{$ip}</code>\n";
            $message .= "<b>Время:</b> " . now()->format('d.m.Y H:i:s') . "\n";
            $message .= "<b>User Agent:</b>\n<code>{$userAgent}</code>\n";

            // Добавляем предупреждение для неудачных попыток
            if (!$isSuccessful) {
                $message .= "\n" . self::EMOJI_WARNING . " <i>Требуется внимание</i>";
            }

            // Отправляем сообщение
            return $this->sendMessage(
                message: $message,
                chatId: $chatId,
                parseMode: self::PARSE_MODE_HTML,
                disableNotification: $isSuccessful // Отключаем уведомления для успешных входов
            );

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_LOGIN_NOTIFICATION . $e->getMessage(), [
                'ip' => $ip,
                'email' => $email,
                'is_successful' => $isSuccessful
            ]);
            return false;
        }
    }

    /**
     * Отправка уведомления о подозрительной активности
     *
     * Метод отправляет критическое уведомление о подозрительной активности
     * с кнопками для быстрых действий администратора.
     *
     * @param array $activity Данные о подозрительной активности
     * @return bool true - если уведомление отправлено успешно, false - в случае ошибки
     *
     * @example
     * $telegram->sendSuspiciousActivityAlert([
     *     'ip' => '192.168.1.1',
     *     'email' => 'attacker@example.com',
     *     'attempts' => 15,
     *     'risk_level' => 'high'
     * ]);
     */
    public function sendSuspiciousActivityAlert(array $activity): bool
    {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Формируем основное сообщение
            $message = self::EMOJI_ALERT . " <b>ОБНАРУЖЕНА ПОДОЗРИТЕЛЬНАЯ АКТИВНОСТЬ</b>\n\n";

            // Добавляем данные активности
            foreach ($activity as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
                $formattedKey = ucfirst(str_replace('_', ' ', $key));
                $message .= "<b>{$formattedKey}:</b>\n";
                $message .= "<code>" . htmlspecialchars((string)$value) . "</code>\n\n";
            }

            // Создаем кнопки для быстрых действий
            $buttons = $this->createSuspiciousActivityButtons($activity);

            // Отправляем сообщение с кнопками
            return $this->sendMessageWithButtons(
                message: $message,
                buttons: $buttons,
                chatId: null, // Используем chat_id по умолчанию
                parseMode: self::PARSE_MODE_HTML
            );

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_SUSPICIOUS_ACTIVITY . $e->getMessage(), [
                'activity_keys' => array_keys($activity)
            ]);
            return false;
        }
    }

    /**
     * Создание кнопок для уведомления о подозрительной активности
     *
     * @param array $activity Данные о подозрительной активности
     * @return array Массив кнопок для inline клавиатуры
     */
    private function createSuspiciousActivityButtons(array $activity): array
    {
        try {
            $buttons = [];

            // Кнопка блокировки IP
            if (isset($activity['ip'])) {
                $buttons[] = [
                    'text' => self::EMOJI_BLOCK . ' Блокировать IP',
                    'callback_data' => self::CALLBACK_BLOCK_IP . '_' . $activity['ip']
                ];
            }

            // Кнопка детального отчета
            if (isset($activity['ip']) || isset($activity['id'])) {
                $identifier = $activity['ip'] ?? $activity['id'] ?? 'unknown';
                $buttons[] = [
                    'text' => self::EMOJI_REPORT . ' Детальный отчет',
                    'callback_data' => self::CALLBACK_REPORT . '_' . $identifier
                ];
            }

            // Кнопка пометки как проверенного
            if (isset($activity['id'])) {
                $buttons[] = [
                    'text' => self::EMOJI_CHECK . ' Пометить как проверенное',
                    'callback_data' => self::CALLBACK_MARK_CHECKED . '_' . $activity['id']
                ];
            }

            // Кнопка игнорирования
            $buttons[] = [
                'text' => 'Игнорировать',
                'callback_data' => self::CALLBACK_IGNORE
            ];

            return $buttons;

        } catch (Throwable $e) {
            Log::error('TelegramService (создание кнопок для уведомления о подозрительной активности): Ошибка в методе createSuspiciousActivityButtons: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Отправка ежедневного отчета по безопасности
     *
     * Метод отправляет сводный отчет о событиях безопасности за день.
     *
     * @param array $stats Статистика безопасности
     * @return bool true - если отчет отправлен успешно, false - в случае ошибки
     *
     * @example
     * $telegram->sendDailySecurityReport([
     *     'total_events' => 150,
     *     'failed_logins' => 45,
     *     'lockouts' => 3,
     *     'suspicious_ips' => ['192.168.1.1' => 30, '192.168.1.2' => 15]
     * ]);
     */
    public function sendDailySecurityReport(array $stats): bool
    {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Формируем заголовок отчета
            $message = self::EMOJI_REPORT . " <b>ЕЖЕДНЕВНЫЙ ОТЧЕТ ПО БЕЗОПАСНОСТИ</b>\n\n";

            // Добавляем период отчета
            $periodStart = now()->subDay()->format('d.m.Y');
            $periodEnd = now()->format('d.m.Y');
            $message .= "<b>Период:</b> {$periodStart} - {$periodEnd}\n\n";

            // Добавляем статистику
            $message .= "<b>Статистика:</b>\n";
            $message .= "• Всего событий: <b>{$stats['total_events']}</b>\n";
            $message .= "• Неудачных входов: <b>{$stats['failed_logins']}</b>\n";
            $message .= "• Блокировок: <b>{$stats['lockouts']}</b>\n";
            $message .= "• Подозрительных IP: <b>" . count($stats['suspicious_ips'] ?? []) . "</b>\n\n";

            // Добавляем топ подозрительных IP
            if (!empty($stats['suspicious_ips'])) {
                $message .= "<b>Топ подозрительных IP:</b>\n";
                $counter = 1;
                foreach (array_slice($stats['suspicious_ips'], 0, 5) as $ip => $count) {
                    $message .= "{$counter}. <code>{$ip}</code> - {$count} событий\n";
                    $counter++;
                }
                $message .= "\n";
            }

            // Добавляем рекомендации
            $message .= "<b>Рекомендации:</b>\n";
            if ($stats['failed_logins'] > 100) {
                $message .= self::EMOJI_WARNING . " Высокий уровень неудачных попыток входа. Рекомендуется:\n";
                $message .= "• Увеличить лимиты rate limiting\n";
                $message .= "• Добавить CAPTCHA\n";
                $message .= "• Проверить логи на наличие атак\n";
            } else {
                $message .= "• Уровень угроз в пределах нормы\n";
                $message .= "• Система безопасности функционирует стабильно\n";
            }

            $message .= "\n<i>Отчет сгенерирован автоматически</i>";

            // Отправляем отчет
            $success = $this->sendMessage(
                message: $message,
                chatId: null, // Используем chat_id по умолчанию
                parseMode: self::PARSE_MODE_HTML,
                disableNotification: true // Отчеты не требуют срочного внимания
            );

            if ($success) {
                Log::info(self::SUCCESS_REPORT_SENT);
            }

            return $success;

        } catch (Throwable $e) {
            Log::error(self::ERROR_SEND_DAILY_REPORT . $e->getMessage(), [
                'stats_keys' => array_keys($stats)
            ]);
            return false;
        }
    }

    /**
     * Получение информации о боте Telegram
     *
     * Метод отправляет запрос к API Telegram для получения информации о боте.
     * Результаты кешируются для оптимизации последующих запросов.
     *
     * @return array|null Информация о боте или null в случае ошибки
     */
    public function getBotInfo(): ?array
    {
        try {
            // Ключ для кеширования информации о боте
            $cacheKey = 'telegram_bot_info';
            $cacheTtl = now()->addHours(24); // Кешируем на 24 часа

            // Пытаемся получить информацию из кеша
            return Cache::remember($cacheKey, $cacheTtl, function () {
                // Отправляем запрос к API Telegram
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->get("{$this->apiUrl}/getMe");

                if ($response->successful()) {
                    $result = $response->json('result');

                    if ($result) {
                        Log::info('TelegramService: Информация о боте успешно получена', $result);
                        return $result;
                    }
                }

                // Если запрос не удался, логируем ошибку
                $this->logApiError($response, 'getBotInfo');
                return null;
            });

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_BOT_INFO . $e->getMessage());
            return null;
        }
    }

    /**
     * Установка веб-хука для обработки callback'ов
     *
     * Метод устанавливает веб-хук для получения обновлений от Telegram.
     * Веб-хук необходим для обработки callback'ов от inline кнопок.
     *
     * @param string $url URL для веб-хука
     * @return bool true - если веб-хук установлен успешно, false - в случае ошибки
     */
    public function setWebhook(string $url): bool
    {
        try {
            // Проверяем, включена ли интеграция с Telegram
            if (!$this->enabled) {
                return false;
            }

            // Отправляем запрос на установку веб-хука
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->post("{$this->apiUrl}/setWebhook", [
                    'url' => $url,
                    'max_connections' => 40,
                    'allowed_updates' => ['message', 'callback_query'],
                    'drop_pending_updates' => true,
                ]);

            if ($response->successful() && $response->json('ok', false)) {
                Log::info(self::SUCCESS_WEBHOOK_SET . ' на URL: ' . $url);
                return true;
            }

            // Если запрос не удался, логируем ошибку
            $this->logApiError($response, 'setWebhook');
            return false;

        } catch (Throwable $e) {
            Log::error(self::ERROR_SET_WEBHOOK . $e->getMessage(), ['url' => $url]);
            return false;
        }
    }

    /**
     * Форматирование уведомления о безопасности
     *
     * @param array $data Данные для уведомления
     * @param string $level Уровень важности
     * @return string Отформатированное сообщение
     */
    private function formatSecurityAlert(array $data, string $level): string
    {
        try {
            $message = "<b>Событие:</b> {$data['event']}\n";
            $message .= "<b>IP:</b> <code>{$data['ip']}</code>\n";
            $message .= "<b>Время:</b> {$data['timestamp']}\n";

            // Добавляем опциональные поля
            if (isset($data['email'])) {
                $message .= "<b>Email:</b> <code>{$data['email']}</code>\n";
            }

            if (isset($data['risk_score'])) {
                $message .= "<b>Уровень риска:</b> {$data['risk_score']}/100\n";
            }

            if (isset($data['user_agent'])) {
                $message .= "<b>User Agent:</b>\n<code>{$data['user_agent']}</code>\n";
            }

            // Добавляем рекомендации для высокого уровня важности
            if ($level === self::PRIORITY_HIGH || $level === self::PRIORITY_CRITICAL) {
                $message .= "\n<b>Рекомендуемые действия:</b>\n";
                $message .= "• Проверить логи на наличие атак\n";
                $message .= "• Рассмотреть блокировку IP\n";
                $message .= "• Уведомить администратора безопасности\n";
            }

            return $message;

        } catch (Throwable $e) {
            Log::error(self::ERROR_FORMAT_SECURITY_ALERT . $e->getMessage());
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    /**
     * Обрезка сообщения до максимальной длины
     *
     * @param string $message Исходное сообщение
     * @param int $maxLength Максимальная длина сообщения
     * @return string Обрезанное сообщение
     */
    private function truncateMessage(string $message, int $maxLength): string
    {
        try {
            if (mb_strlen($message) <= $maxLength) {
                return $message;
            }

            // Обрезаем сообщение и добавляем индикатор обрезки
            $truncated = mb_substr($message, 0, $maxLength - 10);
            return $truncated . "... [обрезано]";

        } catch (Throwable $e) {
            Log::error(self::ERROR_TRUNCATE_MESSAGE . $e->getMessage(), [
                'message_length' => mb_strlen($message),
                'max_length' => $maxLength
            ]);
            return substr($message, 0, $maxLength);
        }
    }

    /**
     * Получение эмодзи для уровня важности
     *
     * @param string $level Уровень важности
     * @return string Эмодзи
     */
    private function getLevelEmoji(string $level): string
    {
        try {
            return match($level) {
                self::PRIORITY_HIGH => self::LEVEL_EMOJI_HIGH,
                self::PRIORITY_CRITICAL => self::LEVEL_EMOJI_CRITICAL,
                self::PRIORITY_MEDIUM => self::LEVEL_EMOJI_MEDIUM,
                self::PRIORITY_LOW => self::LEVEL_EMOJI_LOW,
                'info' => self::LEVEL_EMOJI_LOW,
                default => self::LEVEL_EMOJI_INFO
            };

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_LEVEL_EMOJI . $e->getMessage(), ['level' => $level]);
            return self::LEVEL_EMOJI_INFO;
        }
    }

    /**
     * Получение заголовка для уровня важности
     *
     * @param string $level Уровень важности
     * @return string Заголовок
     */
    private function getLevelTitle(string $level): string
    {
        try {
            return match($level) {
                self::PRIORITY_HIGH => self::LEVEL_TITLE_HIGH,
                self::PRIORITY_MEDIUM => self::LEVEL_TITLE_MEDIUM,
                self::PRIORITY_LOW => self::LEVEL_TITLE_LOW,
                'info' => self::LEVEL_TITLE_INFO,
                'critical' => self::LEVEL_TITLE_CRITICAL,
                default => self::LEVEL_TITLE_INFO
            };

        } catch (Throwable $e) {
            Log::error(self::ERROR_GET_LEVEL_TITLE . $e->getMessage(), ['level' => $level]);
            return self::LEVEL_TITLE_INFO;
        }
    }

    /**
     * Форматирование сообщения для Telegram
     *
     * Универсальный метод для форматирования сообщений разных типов.
     *
     * @param string $type Тип сообщения
     * @param array $data Данные для сообщения
     * @return string Отформатированное сообщение
     */
    private function formatTelegramMessage(string $type, array $data): string
    {
        try {
            $messages = [
                self::MESSAGE_TYPE_LOCKOUT => $this->formatLockoutMessage($data),
                self::MESSAGE_TYPE_SUSPICIOUS_ACTIVITY => $this->formatSuspiciousActivityMessage($data),
                self::MESSAGE_TYPE_LOGIN_NOTIFICATION => $this->formatLoginNotificationMessage($data),
                self::MESSAGE_TYPE_DAILY_REPORT => $this->formatDailyReportMessage($data),
                self::MESSAGE_TYPE_GENERAL => $this->formatGeneralMessage($data)
            ];

            return $messages[$type] ?? $this->formatGeneralMessage($data);

        } catch (Throwable $e) {
            Log::error(self::ERROR_FORMAT_TELEGRAM_MESSAGE . $e->getMessage(), [
                'type' => $type,
                'data_keys' => array_keys($data)
            ]);
            return "⚠️ <b>Ошибка формирования сообщения</b>\n\nТип: {$type}";
        }
    }

    /**
     * Форматирование сообщения о блокировке
     *
     * @param array $data Данные о блокировке
     * @return string Отформатированное сообщение
     */
    private function formatLockoutMessage(array $data): string
    {
        return self::EMOJI_LOCK . " <b>Блокировка аккаунта</b>\n\n"
            . "<b>IP:</b> <code>{$data['ip']}</code>\n"
            . "<b>Время:</b> {$data['timestamp']}\n"
            . "<b>URL:</b> {$data['url']}\n"
            . "<b>Метод:</b> {$data['method']}\n"
            . "<b>User Agent:</b>\n<code>{$data['user_agent']}</code>";
    }

    /**
     * Форматирование сообщения о подозрительной активности
     *
     * @param array $data Данные о подозрительной активности
     * @return string Отформатированное сообщение
     */
    private function formatSuspiciousActivityMessage(array $data): string
    {
        return self::EMOJI_ALERT . " <b>Подозрительная активность</b>\n\n"
            . "<b>Уровень риска:</b> {$data['risk_level']}\n"
            . "<b>IP:</b> <code>{$data['ip']}</code>\n"
            . "<b>Email:</b> <code>{$data['email']}</code>\n"
            . "<b>Попытки:</b> {$data['attempts']}\n"
            . "<b>Страна:</b> " . ($data['country'] ?? 'Неизвестно') . "\n"
            . "<b>Время:</b> {$data['timestamp']}";
    }

    /**
     * Форматирование сообщения об уведомлении о входе
     *
     * @param array $data Данные о входе
     * @return string Отформатированное сообщение
     */
    private function formatLoginNotificationMessage(array $data): string
    {
        $icon = $data['is_successful'] ? self::EMOJI_SUCCESS : self::EMOJI_ERROR;
        $status = $data['is_successful'] ? 'УСПЕШНЫЙ' : 'НЕУДАЧНЫЙ';

        return "{$icon} <b>Попытка входа в систему</b>\n\n"
            . "<b>Статус:</b> {$status}\n"
            . "<b>Email:</b> <code>{$data['email']}</code>\n"
            . "<b>IP:</b> <code>{$data['ip']}</code>\n"
            . "<b>Время:</b> {$data['timestamp']}\n"
            . "<b>User Agent:</b>\n<code>{$data['user_agent']}</code>";
    }

    /**
     * Форматирование сообщения ежедневного отчета
     *
     * @param array $data Данные отчета
     * @return string Отформатированное сообщение
     */
    private function formatDailyReportMessage(array $data): string
    {
        return self::EMOJI_REPORT . " <b>ЕЖЕДНЕВНЫЙ ОТЧЕТ ПО БЕЗОПАСНОСТИ</b>\n\n"
            . "<b>Период:</b> {$data['period_start']} - {$data['period_end']}\n\n"
            . "<b>Статистика:</b>\n"
            . "• Всего событий: <b>{$data['total_events']}</b>\n"
            . "• Неудачных входов: <b>{$data['failed_logins']}</b>\n"
            . "• Блокировок: <b>{$data['lockouts']}</b>\n"
            . "• Подозрительных IP: <b>" . count($data['suspicious_ips'] ?? []) . "</b>";
    }

    /**
     * Форматирование общего сообщения
     *
     * @param array $data Данные сообщения
     * @return string Отформатированное сообщение
     */
    private function formatGeneralMessage(array $data): string
    {
        return "⚠️ <b>Уведомление безопасности</b>\n\n"
            . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
