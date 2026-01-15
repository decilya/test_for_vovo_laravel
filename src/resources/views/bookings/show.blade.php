@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Детали бронирования №{{ $booking->id }}</h2>
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary float-end">Назад к списку</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                Основная информация
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Название тура:</strong> {{ $booking->tour_name }}
                        </div>
                        <div class="mb-3">
                            <strong>Имя охотника:</strong> {{ $booking->hunter_name }}
                        </div>
                        <div class="mb-3">
                            <strong>Гид:</strong> {{ $booking->guide?->name ?? 'Не назначен' }}
                        </div>
                        <div class="mb-3">
                            <strong>Дата бронирования:</strong> {{ $booking->date  }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Количество участников:</strong> {{ $booking->participants_count }}
                        </div>
                        <div class="mb-3">
                            <strong>Создано:</strong> {{ $booking->created_at->format('d.m.Y H:i') }}
                        </div>
                        <div class="mb-3">
                            <strong>Обновлено:</strong> {{ $booking->updated_at->format('d.m.Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                Действия
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-warning">
                        <i class="fas fa-pen"></i> Редактировать
                    </a>

                    <form action="{{ route('bookings.destroy', $booking) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить бронирование?')">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                    </form>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <span id="booking-id">{{ $booking->id }}</span>
                            <a href="#" class="copy-booking-id text-decoration-none">
                                <i class="fas fa-copy"></i> Скопировать ID
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 50">
            <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">Уведомление</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Закрыть"></button>
                </div>
                <div class="toast-body" id="toast-body"></div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Инициализация всплывающих подсказок
                $('[data-bs-toggle="tooltip"]').tooltip();

                // Добавление функционала для копирования ID бронирования
                $('.copy-booking-id').on('click', function(e) {
                    e.preventDefault();
                    var bookingId = $('#booking-id').text();
                    navigator.clipboard.writeText(bookingId)
                        .then(() => {
                            showToast('success', 'ID бронирования скопирован в буфер обмена');
                        })
                        .catch(err => {
                            showToast('error', 'Не удалось скопировать ID бронирования');
                        });
                });

                // Функция для показа уведомлений
                function showToast(type, message) {
                    const toast = new bootstrap.Toast($('#toast'));
                    $('#toast-body').text(message);
                    $('#toast').removeClass('bg-success bg-danger').addClass('bg-' + type);
                    toast.show();
                }

                // Динамическое обновление статуса бронирования
                setInterval(function() {
                    $.ajax({
                        url: `/bookings/${$booking->id}/status`,
                        method: 'GET'
                    }).done(function(response) {
                        if (response.status) {
                            $('.booking-status').text(response.status);
                            if (response.status === 'Отменено') {
                                $('.booking-status').addClass('text-danger');
                            } else if (response.status === 'Выполняется') {
                                $('.booking-status').addClass('text-warning');
                            } else if (response.status === 'Завершено') {
                                $('.booking-status').addClass('text-success');
                            }
                        }
                    }).fail(function() {
                        showToast('error', 'Ошибка при обновлении статуса');
                    });
                }, 60000); // Обновление каждые 60 секунд

                // Обработка клика по кнопке удаления
                $('.btn-delete').on('click', function() {
                    if (!confirm('Вы уверены, что хотите удалить бронирование?')) {
                        return false;
                    }
                });
            });
        </script>
    @endpush
@endsection
