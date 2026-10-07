# 🛍️ Lead Import

Використано невеликий MVC паттерн на чистому PHP

### 🐳 Запуск проекта

```bash
git clone git@github.com:marradch/test-product-clean.git
cd test-product-clean
docker-compose up -d --build
````

Приложение будет доступно по адресу:
**[http://localhost](http://localhost)**

## 🗃️ БД

Виконати скріпт за адресою для ініціювання структури БД
```bash
/database/database.sql
```

## Ендпоінти

http://localhost/
визивається імпорт контроллер

## ⚙️ Оточення

Файл `.env` повинен містити

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_NAME=test_import_db
DB_USER=postgres
DB_PASS=123456
```
```

## 📁 Структура проекта

```
├── database/              # SQL-файли для схемы
├── public/                # index.php – вхід у додаток
├── src/
│   ├── Controller/        # Контроллер
│   └── Service/           # Реалізація імпорту
├── composer.json
├── docker-compose.yml
└── README.md
```

## 🧱 Стек технологій

* PHP 8.3
* PostgreSQL
* PSR-7 (Laminas Diactoros)
* Composer
* Docker
