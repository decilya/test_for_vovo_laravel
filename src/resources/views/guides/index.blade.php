@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Список гидов</h2>

        <div class="row>">
            <!-- Форма фильтрации -->
            <div class="mb-7">
                <form method="GET" action="{{ route('guides.index') }}">
                    <label for="min_experience">Минимальный опыт:</label>
                    <input type="number" class="form-control"
                           id="min_experience"
                           name="min_experience"
                           value="{{ old('min_experience', $minExperience) }}">
                    <button type="submit" class="btn btn-primary">
                        Применить фильтр
                    </button>
                </form>

                <a href="{{ route('guides.create') }}" class="btn btn-success mb-3">Добавить нового гида</a>
            </div>

            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Опыт (лет)</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                @foreach($guides as $guide)
                    <tr>
                        <td>{{ $guide->id }}</td>
                        <td>{{ $guide->name }}</td>
                        <td>{{ $guide->experience_years }}</td>
                        <td>
                            @if($guide->is_active)
                                <span class="text-success">Активен</span>
                            @else
                                <span class="text-danger">Неактивен</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('guides.show', $guide) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('guides.edit', $guide) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('guides.destroy', $guide) }}" method="POST"
                                      style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Вы уверены, что хотите удалить гида?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $guides->links() }}

            @push('scripts')
                <script>
                    $(document).ready(function () {
                        // Инициализация подсказок
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    });
                </script>
    @endpush
@endsection

