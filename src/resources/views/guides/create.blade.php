@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>
            @if(isset($guide))
                Редактирование гида
            @else
                Создание нового гида
            @endif
        </h2>

        <form method="POST" action="@if(isset($guide)){{ route('guides.update', $guide) }}@else{{ route('guides.store') }}@endif">
            @csrf
            @method(isset($guide) ? 'PUT' : 'POST')

            <div class="mb-3">
                <label for="name" class="form-label">Имя гида</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', isset($guide) ? $guide->name : '') }}">
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="experience_years" class="form-label">Опыт (лет)</label>
                <input type="number" class="form-control @error('experience_years') is-invalid @enderror"
                       id="experience_years" name="experience_years"
                       value="{{ old('experience_years', isset($guide) ? $guide->experience_years : '') }}">
                @error('experience_years')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox"
                       id="is_active" name="is_active"
                       @if(isset($guide) && $guide->is_active || old('is_active')) checked @endif>
                <label class="form-check-label" for="is_active">
                    Активен
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                @if(isset($guide))
                    Сохранить изменения
                @else
                    Добавить гида
                @endif
            </button>
            <a href="{{ route('guides.index') }}" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
@endsection


