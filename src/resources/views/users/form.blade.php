@extends('layouts.app')

@section('title')
    {{ $user ? 'Редактировать пользователя' : 'Добавить пользователя' }}
@endsection

@section('content')
    <div class="container">
        <h1 class="mb-4">{{ $user ? 'Редактировать пользователя' : 'Добавить нового пользователя' }}</h1>

        <!-- Блок с ошибками валидации -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Форма редактирования/создания пользователя -->
        <form
            action="{{ $user ? route('users.update', $user) : route('users.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="needs-validation"
        >
            @csrf
            @if ($user) @method('PUT') @endif

            <div class="mb-3">
                <label for="nickname" class="form-label">Никнейм</label>
                <input
                    type="text"
                    class="form-control @error('nickname') is-invalid @enderror"
                    id="nickname"
                    name="nickname"
                    value="{{ old('nickname', $user?->nickname) }}"
                    required
                >
                @error('nickname')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email', $user?->email) }}"
                    required
                >
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                >
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="avatar" class="form-label">Аватар</label>
                <input
                    type="file"
                    class="form-control @error('avatar') is-invalid @enderror"
                    id="avatar"
                    name="avatar"
                >
                @error('avatar')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @if ($user && $user->avatar)
                    <div class="mt-2">
                        <img
                            src="{{ asset('storage/' . $user->avatar) }}"
                            alt="Текущий аватар"
                            class="img-thumbnail"
                            style="max-height: 100px;"
                        >
                    </div>
                @endif
            </div>

            <div class="d-flex gap-2">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    {{ $user ? 'Обновить' : 'Добавить' }} пользователя
                </button>
                <a
                    href="{{ route('users.list') }}"
                    class="btn btn-secondary"
                >
                    Вернуться к списку
                </a>

                @if ($user && $user->avatar)
                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteAvatarModal"
                    >
                        Удалить аватар
                    </button>
                @endif
            </div>
        </form>

        @if ($user && $user->avatar)
            <div class="modal fade" id="deleteAvatarModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Удалить аватар</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Вы уверены, что хотите удалить аватар пользователя?
                        </div>
                        <div class="modal-footer">
                            <form action="{{ route('users.removeAvatar', $user) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
