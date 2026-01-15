<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Очищаем таблицы
        $this->cleanTables();

        // Создаем пользователей (только базовые поля)
        $this->createUsers();

        // Создаем категории (только базовые поля)
        $this->createCategories();

        // Создаем товары (только базовые поля)
        $this->createProducts();
    }

    /**
     * Очистка таблиц перед заполнением
     */
    private function cleanTables(): void
    {
        // Отключаем проверку внешних ключей для очистки
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Очищаем в правильном порядке
        Product::query()->truncate();
        Category::query()->truncate();

        // Проверяем, есть ли уже основные пользователи
        $existingEmails = ['admin@example.com', 'customer@example.com'];
        $existingUsers = User::whereIn('email', $existingEmails)->count();

        if ($existingUsers === 0) {
            // Если основных пользователей нет, очищаем всех
            DB::table('users')->truncate();
        } else {
            // Удаляем только тестовых пользователей
            User::whereNotIn('email', $existingEmails)->delete();
        }

        // Включаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Таблицы очищены');
    }

    /**
     * Создание пользователей (только базовые поля из миграции)
     * Базовые поля: id, name, email, email_verified_at, password, remember_token, created_at, updated_at
     */
    private function createUsers(): void
    {
        // Создаем основных пользователей
        $users = [
            [
                'name' => 'Администратор',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Менеджер',
                'email' => 'manager@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Покупатель',
                'email' => 'customer@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Основные пользователи созданы');
    }

    /**
     * Создание категорий (только базовые поля из миграции)
     * Базовые поля: id, name, slug, created_at, updated_at
     */
    private function createCategories(): void
    {
        $categories = [
            ['name' => 'Электроника', 'slug' => 'electronics'],
            ['name' => 'Смартфоны', 'slug' => 'smartphones'],
            ['name' => 'Ноутбуки', 'slug' => 'laptops'],
            ['name' => 'Телевизоры', 'slug' => 'tv'],
            ['name' => 'Наушники', 'slug' => 'headphones'],
            ['name' => 'Бытовая техника', 'slug' => 'appliances'],
            ['name' => 'Холодильники', 'slug' => 'refrigerators'],
            ['name' => 'Стиральные машины', 'slug' => 'washing-machines'],
            ['name' => 'Одежда', 'slug' => 'clothing'],
            ['name' => 'Мужская одежда', 'slug' => 'mens-clothing'],
            ['name' => 'Женская одежда', 'slug' => 'womens-clothing'],
            ['name' => 'Обувь', 'slug' => 'shoes'],
            ['name' => 'Дом и сад', 'slug' => 'home-garden'],
            ['name' => 'Мебель', 'slug' => 'furniture'],
            ['name' => 'Освещение', 'slug' => 'lighting'],
            ['name' => 'Красота и здоровье', 'slug' => 'beauty-health'],
            ['name' => 'Косметика', 'slug' => 'cosmetics'],
            ['name' => 'Спорт', 'slug' => 'sports'],
            ['name' => 'Книги', 'slug' => 'books'],
            ['name' => 'Игрушки', 'slug' => 'toys'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('Категории созданы');
    }

    /**
     * Создание товаров (только базовые поля из миграции)
     * Базовые поля: id, name, price, category_id, in_stock, rating, created_at, updated_at
     */
    private function createProducts(): void
    {
        // Создаем массив товаров с базовыми полями
        $products = [
            // Смартфоны (категория 2)
            [
                'name' => 'iPhone 15 Pro 256GB',
                'price' => 129999.00,
                'category_id' => 2,
                'in_stock' => true,
                'rating' => 4.8,
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'price' => 119999.00,
                'category_id' => 2,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Xiaomi Redmi Note 13 Pro',
                'price' => 34999.00,
                'category_id' => 2,
                'in_stock' => true,
                'rating' => 4.5,
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'price' => 89999.00,
                'category_id' => 2,
                'in_stock' => false,
                'rating' => 4.6,
            ],
            [
                'name' => 'OnePlus 12',
                'price' => 79999.00,
                'category_id' => 2,
                'in_stock' => true,
                'rating' => 4.4,
            ],

            // Ноутбуки (категория 3)
            [
                'name' => 'MacBook Pro 16" M3 Pro',
                'price' => 249999.00,
                'category_id' => 3,
                'in_stock' => true,
                'rating' => 4.9,
            ],
            [
                'name' => 'Dell XPS 15 9530',
                'price' => 189999.00,
                'category_id' => 3,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'price' => 159999.00,
                'category_id' => 3,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'ASUS ROG Zephyrus G14',
                'price' => 139999.00,
                'category_id' => 3,
                'in_stock' => true,
                'rating' => 4.5,
            ],
            [
                'name' => 'HP Spectre x360',
                'price' => 129999.00,
                'category_id' => 3,
                'in_stock' => false,
                'rating' => 4.3,
            ],

            // Телевизоры (категория 4)
            [
                'name' => 'Samsung QLED 4K 65"',
                'price' => 89999.00,
                'category_id' => 4,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'LG OLED 55"',
                'price' => 79999.00,
                'category_id' => 4,
                'in_stock' => true,
                'rating' => 4.8,
            ],
            [
                'name' => 'Sony Bravia 4K 75"',
                'price' => 149999.00,
                'category_id' => 4,
                'in_stock' => true,
                'rating' => 4.9,
            ],
            [
                'name' => 'Xiaomi TV 4S 55"',
                'price' => 39999.00,
                'category_id' => 4,
                'in_stock' => true,
                'rating' => 4.2,
            ],

            // Наушники (категория 5)
            [
                'name' => 'Apple AirPods Pro 2',
                'price' => 24999.00,
                'category_id' => 5,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'price' => 29999.00,
                'category_id' => 5,
                'in_stock' => true,
                'rating' => 4.8,
            ],
            [
                'name' => 'Samsung Galaxy Buds2 Pro',
                'price' => 19999.00,
                'category_id' => 5,
                'in_stock' => true,
                'rating' => 4.5,
            ],

            // Холодильники (категория 7)
            [
                'name' => 'Холодильник Bosch Serie 6',
                'price' => 89999.00,
                'category_id' => 7,
                'in_stock' => true,
                'rating' => 4.5,
            ],
            [
                'name' => 'Холодильник LG DoorCooling+',
                'price' => 109999.00,
                'category_id' => 7,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Холодильник Samsung Bespoke',
                'price' => 129999.00,
                'category_id' => 7,
                'in_stock' => false,
                'rating' => 4.6,
            ],

            // Стиральные машины (категория 8)
            [
                'name' => 'Стиральная машина Samsung WW90T',
                'price' => 64999.00,
                'category_id' => 8,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Стиральная машина LG AI DD',
                'price' => 79999.00,
                'category_id' => 8,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Стиральная машина Indesit IWSC',
                'price' => 32999.00,
                'category_id' => 8,
                'in_stock' => true,
                'rating' => 4.2,
            ],

            // Мужская одежда (категория 10)
            [
                'name' => 'Кожаная куртка мужская',
                'price' => 24999.00,
                'category_id' => 10,
                'in_stock' => true,
                'rating' => 4.3,
            ],
            [
                'name' => 'Джинсы мужские Levis',
                'price' => 7999.00,
                'category_id' => 10,
                'in_stock' => true,
                'rating' => 4.4,
            ],
            [
                'name' => 'Рубашка мужская Oxford',
                'price' => 4999.00,
                'category_id' => 10,
                'in_stock' => true,
                'rating' => 4.2,
            ],

            // Женская одежда (категория 11)
            [
                'name' => 'Платье вечернее',
                'price' => 12999.00,
                'category_id' => 11,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Блузка женская шелковая',
                'price' => 8999.00,
                'category_id' => 11,
                'in_stock' => true,
                'rating' => 4.5,
            ],
            [
                'name' => 'Юбка кожаная',
                'price' => 11999.00,
                'category_id' => 11,
                'in_stock' => false,
                'rating' => 4.3,
            ],

            // Обувь (категория 12)
            [
                'name' => 'Кроссовки Nike Air Max',
                'price' => 12999.00,
                'category_id' => 12,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Туфли женские на каблуке',
                'price' => 8999.00,
                'category_id' => 12,
                'in_stock' => true,
                'rating' => 4.4,
            ],
            [
                'name' => 'Ботинки зимние мужские',
                'price' => 14999.00,
                'category_id' => 12,
                'in_stock' => true,
                'rating' => 4.5,
            ],

            // Мебель (категория 14)
            [
                'name' => 'Диван угловой',
                'price' => 45999.00,
                'category_id' => 14,
                'in_stock' => true,
                'rating' => 4.4,
            ],
            [
                'name' => 'Кровать двуспальная',
                'price' => 32999.00,
                'category_id' => 14,
                'in_stock' => true,
                'rating' => 4.3,
            ],
            [
                'name' => 'Стул офисный',
                'price' => 8999.00,
                'category_id' => 14,
                'in_stock' => true,
                'rating' => 4.2,
            ],

            // Освещение (категория 15)
            [
                'name' => 'Люстра хрустальная',
                'price' => 15999.00,
                'category_id' => 15,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Настольная лампа LED',
                'price' => 2999.00,
                'category_id' => 15,
                'in_stock' => true,
                'rating' => 4.3,
            ],
            [
                'name' => 'Торшер напольный',
                'price' => 7999.00,
                'category_id' => 15,
                'in_stock' => true,
                'rating' => 4.4,
            ],

            // Косметика (категория 17)
            [
                'name' => 'Крем для лица с гиалуроновой кислотой',
                'price' => 2499.00,
                'category_id' => 17,
                'in_stock' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Тушь для ресниц объемная',
                'price' => 1299.00,
                'category_id' => 17,
                'in_stock' => true,
                'rating' => 4.5,
            ],
            [
                'name' => 'Помада матовая',
                'price' => 999.00,
                'category_id' => 17,
                'in_stock' => true,
                'rating' => 4.4,
            ],

            // Спорт (категория 18)
            [
                'name' => 'Гантели наборные 20 кг',
                'price' => 3999.00,
                'category_id' => 18,
                'in_stock' => true,
                'rating' => 4.3,
            ],
            [
                'name' => 'Йога-мат',
                'price' => 1999.00,
                'category_id' => 18,
                'in_stock' => true,
                'rating' => 4.2,
            ],
            [
                'name' => 'Фитбол 65 см',
                'price' => 1499.00,
                'category_id' => 18,
                'in_stock' => false,
                'rating' => 4.1,
            ],

            // Книги (категория 19)
            [
                'name' => 'Мартин Иден - Джек Лондон',
                'price' => 799.00,
                'category_id' => 19,
                'in_stock' => true,
                'rating' => 4.8,
            ],
            [
                'name' => '1984 - Джордж Оруэлл',
                'price' => 699.00,
                'category_id' => 19,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Мастер и Маргарита - Михаил Булгаков',
                'price' => 899.00,
                'category_id' => 19,
                'in_stock' => true,
                'rating' => 4.9,
            ],

            // Игрушки (категория 20)
            [
                'name' => 'Конструктор LEGO City',
                'price' => 3999.00,
                'category_id' => 20,
                'in_stock' => true,
                'rating' => 4.9,
            ],
            [
                'name' => 'Кукла Barbie',
                'price' => 2999.00,
                'category_id' => 20,
                'in_stock' => true,
                'rating' => 4.7,
            ],
            [
                'name' => 'Машинка радиоуправляемая',
                'price' => 4999.00,
                'category_id' => 20,
                'in_stock' => true,
                'rating' => 4.5,
            ],
        ];

        // Создаем товары
        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('Товары созданы');
        $this->command->info('Всего товаров: ' . Product::count());
        $this->command->info('Всего категорий: ' . Category::count());
        $this->command->info('Всего пользователей: ' . User::count());
    }
}
