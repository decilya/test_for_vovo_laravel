@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Создать бронирование</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="bookingForm" method="POST" action="{{ route('bookings.store') }}">
            @csrf
            <div class="form-group">
                <label for="tour_name">Название тура:</label>
                <input type="text" class="form-control" id="tour_name" name="tour_name" required>
            </div>
            <div class="form-group">
                <label for="hunter_name">Имя охотника:</label>
                <input type="text" class="form-control" id="hunter_name" name="hunter_name" required>
            </div>
            <div class="form-group">
                <label for="guide_id">Выбор гида:</label>
                <select class="form-control" id="guide_id" name="guide_id" required>
                    <option value="">Выберите гида</option>
                    @foreach ($guides as $guide)
                        <option value="{{ $guide->id }}">
                            {{ $guide->name }} (опыт: {{ $guide->experience_years }} лет)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="date">Дата бронирования:</label>
                <input type="text" class="form-control datepicker" id="date" name="date" required>
                <div class="date-status"></div>
            </div>
            <div class="form-group">
                <label for="participants_count">Количество участников:</label>
                <input type="number" class="form-control" id="participants_count" name="participants_count" min="1" max="10" required>
            </div>
            <button type="submit" class="btn btn-primary">Создать бронирование</button>
        </form>

        <!-- Модальное окно подтверждения -->
        <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Подтверждение бронирования</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы уверены, что хотите забронировать тур?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary confirm-booking">Подтвердить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
        @endsection

        @section('scripts')
            <script>
                $(document).ready(function() {
                    // Инициализация datepicker
                    $('.datepicker').datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true
                    });

                    // AJAX проверка даты
                    $('#date').on('change', function() {
                        let date = $(this).val();
                        let guideId = $('#guide_id').val();

                        if (!date || !guideId) return;

                        $.ajax({
                            url: '/check-date',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                date: date,
                                guide_id: guideId
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.date-status').html('<div class="alert alert-success">' + response.message + '</div>');
                                } else {
                                    $('.date-status').html('<div class="alert alert-success">' + response.message + '</div>');
                                }

                            },
                            error: function() {
                                $('.date-status').html('<div class="alert alert-danger">Ошибка проверки даты</div>');
                            }
                        });
                    });

                    // Обработка отправки формы
                    $('#bookingForm').on('submit', function(e) {
                        e.preventDefault();

                        // Открываем модальное окно подтверждения
                        $('#confirmModal').modal('show');

                        $('.confirm-booking').on('click', function() {
                            $('#bookingForm').unbind('submit').submit();
                            $('#confirmModal').modal('hide');
                        });
                    });

                    // Валидация выбора гида
                    $('#guide_id').on('change', function() {
                        let guideId = $(this).val();
                        if (!guideId) {
                            $('.date-status').html('');
                        }
                    });
                });
            </script>


@endsection

