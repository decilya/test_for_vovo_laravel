<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Сервис-провайдер для настройки Telescope - инструмента отладки Laravel
 *
 * Этот провайдер расширяет стандартный TelescopeApplicationServiceProvider
 * и добавляет кастомную логику для контроля доступа и фильтрации данных.
 *
 * @package App\Providers
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Регистрация сервисов Telescope
     *
     * Метод выполняет начальную настройку Telescope:
     * - Включает ночной режим (по необходимости)
     * - Настраивает скрытие чувствительных данных
     * - Применяет фильтры для записи данных
     * - Настраивает фильтрацию батчей
     *
     * @return void
     */
    public function register(): void
    {
        try {
            // Включение ночного режима Telescope (раскомментировать при необходимости)
            // Telescope::night();

            // Настройка скрытия чувствительных данных запросов
            $this->hideSensitiveRequestDetails();

            // Применение фильтров для записи данных в зависимости от среды
            Telescope::filter(function ($entry) {
                if ($this->app->environment('local')) {
                    // В локальной среде записываем все данные
                    return true;
                }

                // В продакшене записываем только важные события
                return $entry->isReportableException() ||
                    $entry->isFailedRequest() ||
                    $entry->isFailedJob() ||
                    $entry->isScheduledTask() ||
                    $entry->hasMonitoredTag();
            });

            // Фильтрация батчей записей Telescope
            Telescope::filterBatch(function ($batch) {
                return $this->shouldRecordBatch($batch);
            });

        } catch (Throwable $e) {
            // Логируем ошибку, но не прерываем работу приложения
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (регистрация сервисов Telescope):
                        Ошибка в методе register: ' . $e->getMessage()
            );
        }
    }

    /**
     * Скрытие чувствительных данных запросов от логирования Telescope
     *
     * Метод предотвращает запись в Telescope следующих данных:
     * - Параметров запроса: токены CSRF, пароли
     * - Заголовков запроса: куки, CSRF-токены
     *
     * В локальной среде все данные показываются для отладки.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails(): void
    {
        try {
            // В локальной среде показываем все данные для отладки
            if ($this->app->environment('local')) {
                return;
            }

            // Скрытие чувствительных параметров запроса
            Telescope::hideRequestParameters([
                '_token',           // CSRF-токен Laravel
                'password',         // Пароли пользователей
                'password_confirmation', // Подтверждение пароля
                'current_password', // Текущий пароль для смены
                'token',            // Общие токены
                'api_token',        // API токены
                'secret',           // Секретные ключи
                'credit_card',      // Данные кредитных карт
                'cvv',              // CVV код карты
                'ssn',              // Номер социального страхования
            ]);

            // Скрытие чувствительных заголовков запроса
            Telescope::hideRequestHeaders([
                'cookie',           // Куки браузера
                'x-csrf-token',     // CSRF-токен в заголовке
                'x-xsrf-token',     // XSRF-токен в заголовке
                'authorization',    // Токены авторизации
                'php-auth-user',    // Basic Auth пользователь
                'php-auth-pw',      // Basic Auth пароль
            ]);

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (скрытие чувствительных данных запросов):
                            Ошибка в методе hideSensitiveRequestDetails: ' . $e->getMessage()
            );
        }
    }

    /**
     * Определение необходимости записи батча данных Telescope
     *
     * Метод решает, следует ли сохранять батч записей Telescope:
     * - В локальной среде сохраняются все батчи
     * - В продакшене сохраняются только батчи с ошибками
     *
     * @param Collection $batch Массив записей Telescope
     * @return bool true - сохранить батч, false - не сохранять
     */
    protected function shouldRecordBatch(Collection  $batch): bool
    {
        try {
            // В локальной среде записываем все батчи для отладки
            if ($this->app->environment('local')) {
                return true;
            }

            // В продакшене записываем только батчи с ошибками
            return collect($batch)->contains(function ($entry) {
                return $entry->isReportableException() ||
                    $entry->isFailedRequest() ||
                    $entry->isFailedJob();
            });

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (определение необходимости записи батча данных Telescope):
                                Ошибка в методе shouldRecordBatch: ' . $e->getMessage()
            );

            // В случае ошибки не записываем батч для безопасности
            return false;
        }
    }

    /**
     * Настройка сервисов авторизации Telescope
     *
     * Метод настраивает:
     * 1. Определение правил доступа (gate)
     * 2. Функцию проверки авторизации для Telescope
     *
     * @return void
     */
    protected function authorization(): void
    {
        try {
            // Настройка правил доступа (gate)
            $this->gate();

            // Настройка функции проверки авторизации
            Telescope::auth(function ($request) {
                // В локальной среде доступ открыт всем
                if (app()->environment('local')) {
                    return true;
                }

                // В продакшене проверяем авторизацию пользователя
                if (!$request->user()) {
                    return false;
                }

                // Проверяем email пользователя в списке администраторов
                return in_array($request->user()->email, [
                    'admin@example.com',
                    // Добавьте другие email администраторов
                    'administrator@yourdomain.com',
                    'superadmin@yourdomain.com',
                ]);
            });

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (настройка сервисов авторизации Telescope):
                             Ошибка в методе authorization: ' . $e->getMessage()
            );
        }
    }

    /**
     * Регистрация правил доступа (gate) для Telescope
     *
     * Метод определяет, кто может получить доступ к панели Telescope
     * в нелокальных средах (production, staging и т.д.).
     *
     * @return void
     */
    protected function gate(): void
    {
        try {
            Gate::define('viewTelescope', function ($user) {
                // В локальной среде доступ разрешен всем
                if (app()->environment('local')) {
                    return true;
                }

                // Проверяем наличие пользователя
                if (!$user) {
                    return false;
                }

                // Проверяем роль пользователя (если используете роли)
                // if ($user->hasRole('admin')) {
                //     return true;
                // }

                // Проверяем email пользователя в списке администраторов
                return in_array($user->email, [
                    'admin@example.com',
                    'administrator@yourdomain.com',
                    'superadmin@yourdomain.com',
                    // Добавьте другие email администраторов вашего приложения
                ]);
            });

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (регистрация правил доступа для Telescope):
                               Ошибка в методе gate: ' . $e->getMessage()
            );
        }
    }

    /**
     * Настройка тегов мониторинга Telescope
     *
     * Метод позволяет задать кастомные теги для мониторинга.
     * Теги используются для фильтрации и поиска записей в Telescope.
     *
     * Пример использования в коде:
     * Telescope::tag(function ($entry) {
     *     return $entry->type === 'request'
     *         ? ['status:'.$entry->content['response_status']]
     *         : [];
     * });
     *
     * @return void
     */
    protected function configureTags(): void
    {
        try {
            // Пример настройки тегов для запросов
            Telescope::tag(function ($entry) {
                $tags = [];

                // Добавляем теги для запросов по статусу ответа
                if ($entry->type === 'request') {
                    $status = $entry->content['response_status'] ?? 'unknown';
                    $tags[] = 'status:' . $status;

                    // Добавляем тег для медленных запросов
                    if (($entry->content['duration'] ?? 0) > 1000) {
                        $tags[] = 'slow_request';
                    }
                }

                // Добавляем теги для исключений
                if ($entry->type === 'exception') {
                    $exceptionClass = $entry->content['class'] ?? 'unknown';
                    $tags[] = 'exception:' . $exceptionClass;
                }

                return $tags;
            });

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (настройка тегов мониторинга Telescope):
                                 Ошибка в методе configureTags: ' . $e->getMessage()
            );
        }
    }

    /**
     * Настройка кастомных фильтров для определенных типов записей
     *
     * Метод позволяет настроить дополнительные фильтры для конкретных типов записей:
     * - Запросы (requests)
     * - Исключения (exceptions)
     * - Задачи (jobs)
     * - Логи (logs)
     *
     * @return void
     */
    protected function configureCustomFilters(): void
    {
        try {
            // Фильтр для запросов API
            Telescope::filter(function ($entry) {
                if ($entry->type === 'request') {
                    $path = $entry->content['uri'] ?? '';

                    // Не записываем запросы к статическим файлам
                    if (preg_match('/\.(css|js|jpg|png|gif|ico)$/', $path)) {
                        return false;
                    }

                    // Не записывать запросы к health-check эндпоинтам
                    if (str_contains($path, 'health') || str_contains($path, 'ping')) {
                        return false;
                    }
                }

                return true;
            });

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (настройка кастомных фильтров для определенных типов записей):
                                Ошибка в методе configureCustomFilters: ' . $e->getMessage()
            );
        }
    }

    /**
     * Настройка ограничений для Telescope в продакшене
     *
     * Метод применяет ограничения для уменьшения нагрузки на продакшене:
     * - Ограничение количества записей
     * - Ограничение времени хранения записей
     * - Отключение ненужных функций
     *
     * @return void
     */
    protected function configureProductionLimits(): void
    {
        try {
            // Применяем настройки только для продакшена
            if (!$this->app->environment('production')) {
                return;
            }

            // Ограничение времени хранения записей (по умолчанию 7 дней)
            config(['telescope.storage.database.prune_hours' => 168]); // 7 дней

            // Ограничение количества записей каждого типа (для предотвращения переполнения БД)
            $limits = [
                'exceptions' => 100,   // Максимум 100 исключений
                'requests' => 1000,    // Максимум 1000 запросов
                'jobs' => 500,         // Максимум 500 задач
                'logs' => 1000,        // Максимум 1000 логов
            ];

            // Применяем ограничения через конфигурацию
            foreach ($limits as $type => $limit) {
                // Здесь может быть кастомная логика применения ограничений
                // В реальном проекте это может быть реализовано через кастомные фильтры
            }

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (настройка ограничений для Telescope в продакшене):
                                Ошибка в методе configureProductionLimits: ' . $e->getMessage()
            );
        }
    }

    /**
     * Метод запускается после регистрации всех сервисов
     *
     * Здесь можно выполнить дополнительные настройки,
     * которые требуют полной инициализации приложения.
     *
     * @return void
     */
    public function boot(): void
    {
        try {
            parent::boot();

            // Настраиваем кастомные теги мониторинга
            $this->configureTags();

            // Настраиваем дополнительные фильтры
            $this->configureCustomFilters();

            // Настраиваем ограничения для продакшена
            $this->configureProductionLimits();

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'TelescopeServiceProvider (метод запуска после регистрации всех сервисов):
                            Ошибка в методе boot: ' . $e->getMessage()
            );
        }
    }
}
