@extends('layouts.app')

@section('title', 'Просмотр гида')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Информация о гиде: {{ $guide->name }}</h2>
                <a href="{{ route('guides.index') }}" class="btn btn-secondary float-end">Назад к списку</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                Детали гида
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Имя:</strong> {{ $guide->name }}
                        </div>
                        <div class="mb-3">
                            <strong>Опыт (лет):</strong> {{ $guide->experience_years }}
                        </div>
                        <div class="mb-3">
                            <strong>Статус:</strong>
                            @if($guide->is_active)
                                <span class="text-success">Активен</span>
                            @else
                                <span class="text-danger">Неактивен</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ route('guides.edit', $guide) }}" class="btn btn-warning">
                            <i class="fas fa-pen"></i> Редактировать
                        </a>
                    </div>
                    <div>
                        <form action="{{ route('guides.destroy', $guide) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Вы уверены, что хотите удалить гида?')">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
