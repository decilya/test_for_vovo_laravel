@extends('layouts.app')

@section('title', 'Зарегистрированные пользователи')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Зарегистрированные пользователи</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Добавить пользователя
        </a>
    </div>

    @if ($users->isEmpty())
        <div class="alert alert-info">
            Нет зарегистрированных пользователей.
        </div>
    @else
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Никнейм</th>
                <th>Аватар</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ e($user->nickname) }}</td>
                    <td>
                        @if ($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}"
                                 alt="Аватар {{ e($user->nickname) }}"
                                 class="rounded-circle"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <img src="{{ asset('images/default-avatar.png') }}"
                                 alt="Дефолтный аватар"
                                 class="rounded-circle"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i> Редактировать
                        </a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Пагинация -->
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    @endif
@endsection
