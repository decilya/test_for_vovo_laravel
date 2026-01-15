# Тестовое задание

Задача: Реализовать поиск по товарам с фильтрами
Реализовать HTTP-endpoint (например, GET /api/products), который возвращает список товаров с возможностью фильтрации и сортировки.

У товара должны быть поля:
* id
* name (string, индекс по LIKE или FULLTEXT если захочешь)
* price (decimal)
* category_id (foreign key на таблицу categories)
* in_stock (boolean)
* rating (float, 0–5)
* created_at
* updated_at

Фильтры (через query-параметры):
* q — поиск по подстроке в name
* price_from, price_to
* category_id
* in_stock (true/false)
* rating_from

Сортировка:
параметр sort с допустимыми значениями: price_asc, price_desc, rating_desc, newest.

Обязательна пагинация.

--------------------

После скачивания проекта запустите: 

```bash
$ make install
```

Миграции:
```bash
$ docker compose exec app php artisan migrage
```

Какие смотреть файлы:

# Код

Модели:
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Models/Category.php
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Models/Product.php

Репозитории:
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Repositories/Eloquent/ProductRepository.php

Сервисный слой:
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Services/ProductService.php

Запросы:
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Http/Requests/ProductFilterRequest.php

Контроллеры АПИ:
- https://github.com/decilya/test_for_vovo_laravel/blob/main/src/app/Http/Controllers/API/ProductController.php

https://github.com/decilya/test_for_vovo_laravel/blob/d2e6ed9e299ccffaee8f754f95f0a1277778e626/src/app/Http/Controllers/API/ProductController.php#L16

В проекте реализован сервисный слой, репозитории, дата сидер, обращение к БД выполнено через транзакции, так же приложил выполнение к форме по ссылке. С моим опытом я уже сделал кучу подобных тестовых, их так же можно посмотреть на моем гитхаб.

---------

# Получаем список товаров
curl "http://localhost/api/v1/products"

![Пример 1](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/01_01.jpeg)
![Пример 2](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/01_02.jpeg)

# Фильтрация
curl "http://localhost/api/v1/products?q=iPhone&price_from=50000&in_stock=true&sort=price_desc"
![Пример 3](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/02_01.jpeg)

# По категории
curl "http://localhost/api/v1/products?category_id=2"
![Пример 4](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/03_01.jpeg)

# С пагинацией
curl "http://localhost/api/v1/products?per_page=5&page=2"
![Пример 5](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/04_01.jpeg)

# Поиск по названию
curl "http://localhost/api/v1/products?q=Samsung"
![Пример 6](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/05_01.jpeg)

# Фильтр по рейтингу
curl "http://localhost/api/v1/products?rating_from=4.5"
![Пример 7](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/06_01.jpeg)

# Фильтр по наличию
curl "http://localhost/api/v1/products?in_stock=false"
![Пример 8](https://github.com/decilya/test_for_vovo_laravel/blob/main/src/public/images/07_01.jpeg)
