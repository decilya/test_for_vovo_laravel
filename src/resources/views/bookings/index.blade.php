@extends('layouts.app')

@section('content')
    <div class="container">

        <h2>Список бронирований</h2>

        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <input id="date_from" type="text" class="form-control datepicker"
                           name="date_from"
                           placeholder="С"
                           value="{{ old('date_from', request('date_from')) }}">
                </div>
                <div class="col-md-3">
                    <input id="date_to" type="text" class="form-control datepicker"
                           name="date_to"
                           placeholder="По"
                           value="{{ old('date_to', request('date_to')) }}">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control"
                           name="search"
                           placeholder="Поиск по названию или имени"
                           value="{{ old('search', request('search')) }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        Применить фильтры
                    </button>
                    <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
                        Сбросить
                    </a>
                </div>
            </div>
        </form>

        <a href="{{ route('bookings.create') }}" class="btn btn-success mb-3">
            Создать новое бронирование
        </a>

        @if ($bookings->isEmpty())
            <div class="alert alert-info">
                Бронирований не найдено
            </div>
        @else
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>
                        <a href="?sort=id&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            №
                        </a>
                    </th>
                    <th>
                        <a href="?sort=tour_name&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            Название тура
                        </a>
                    </th>
                    <th>
                        <a href="?sort=hunter_name&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            Охотник
                        </a>
                    </th>
                    <th>
                        <a href="?sort=guide_id&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            Гид
                        </a>
                    </th>
                    <th>
                        <a href="?sort=date&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            Дата
                        </a>
                    </th>
                    <th>
                        <a href="?sort=participants_count&order={{ $order === 'asc' ? 'desc' : 'asc' }}">
                            Участников
                        </a>
                    </th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->tour_name }}</td>
                        <td>{{ $booking->hunter_name }}</td>
                        <td>{{ $booking->guide?->name ?? 'Не назначен' }}</td>
                        <td>{{ $booking->date }}</td>
                        <td>{{ $booking->participants_count }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-info me-2">
                                    <i class="fas fa-eye"></i> Просмотреть
                                </a>
                                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-warning me-2">
                                    <i class="fas fa-pen"></i> Редактировать
                                </a>
                                <form action="{{ route('bookings.destroy', $booking) }}" method="POST"
                                      style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Вы уверены, что хотите удалить бронирование?')">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                </form>
                            </div>
                        </td>
                @endforeach
                </tbody>
            </table>
        @endif


        <div class="mt-4">
            {{ $bookings->withQueryString()->links() }}
        </div>



</div>

@endsection

    @section('scripts')
        <script>
            $(document).ready(function() {
                $('#date_from').datepicker({
                    language: 'ru',
                    format: 'yyyy-mm-dd',
                    autoclose: true
                });

                $('#date_to').datepicker({
                    language: 'ru',
                    format: 'yyyy-mm-dd',
                    autoclose: true
                });
            });

        </script>

    @endsection


