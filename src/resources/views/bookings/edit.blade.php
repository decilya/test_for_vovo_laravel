@extends('layouts.app')

@section('title', 'Редактирование бронирования')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Редактирование бронирования №{{ $booking->id }}</h2>
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary float-end">Назад к списку</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                Форма редактирования
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('bookings.update', $booking) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="tour_name" class="form-label">Название тура</label>
                        <input type="text" class="form-control @error('tour_name') is-invalid @enderror"
                               id="tour_name" name="tour_name" value="{{ old('tour_name', $booking->tour_name) }}">
                        @error('tour_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Остальные поля -->
                    <div class="mb-3">
                        <label for="hunter_name" class="form-label">Имя охотника</label>
                        <input type="text" class="form-control @error('hunter_name') is-invalid @enderror"
                               id="hunter_name" name="hunter_name" value="{{ old('hunter_name', $booking->hunter_name) }}">
                        @error('hunter_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="guide_id" class="form-label">Гид</label>
                        <select class="form-select @error('guide_id') is-invalid @enderror"
                                id="guide_id" name="guide_id">
                            <option value="">Не назначен</option>
                            @foreach($guides as $guide)
                                <option value="{{ $guide->id }}"
                                    {{ old('guide_id', $booking->guide_id) == $guide->id ? 'selected' : '' }}>
                                    {{ $guide->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('guide_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Дата бронирования</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror"
                               id="date" name="date" value="{{ old('date', $booking->date) }}">
                        @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="participants_count" class="form-label">Количество участников</label>
                        <input type="number" class="form-control @error('participants_count') is-invalid @enderror"
                               id="participants_count" name="participants_count"
                               value="{{ old('participants_count', $booking->participants_count) }}">
                        @error('participants_count')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    $('.datepicker').datepicker({
                        language: 'ru',
                        format: 'yyyy-mm-dd',
                        autoclose: true
                    });

                });
            </script>
    @endpush
@endsection

