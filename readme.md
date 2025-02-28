### Задание:

Нужно написать код с нуля. Конечно, всегда есть куда улучшать до бесконечности, но для оптимального обсуждения стоит ориентироваться на примерно 4 часа работы.
Использование библиотек или готовых компонентов приветствуется, изобретать велосипеды не нужно. Наш проект написан на Symfony поэтому использование их фреймворка или компонентов будет плюсом, но это не принципиально и решения на других компонентах тоже принимаются.

Задача. Нужен сервис, который представляет из себя обработчик платежей с логикой общения с покупателем.
Платёж в данном случае это POST-запрос к сервису на любой придуманный эндпоинт в определённом формате:
```json
{
    "token": "{uuid}",
    "status": "(authorized|confirmed|rejected|refunded)",
    "order_id": 1234567890,
    "amount": 20000,
    "currency": "(RUB|USD|EUR)",
    "error_code": "(76543|null)",
    "pan": "12341********234",
    "user_id": "876123654",
    "language_code": "(ru|en)"
}
```
Где `user_id` это уникальный идентификатор пользователя:
- если платежей от него ранее не было, то это означает "человек оплатил подписку";
- если платежи от него были ранее, то это означает "человек продлил подписку".
NB: в задании можно не добавлять реальное хранилище данных (опционально), достаточно иметь решение в коде на этот счёт.

После получения платежа нужно посылать ему уведомление в Телеграм. Нюансы для тестового задания:
- `user_id` это его идентификатор в ТГ.
- Нужно иметь код для работы с ТГ, в тестовом задании реальных запросов можно не слать, токен тг-бота можно захардкодить случайным.
- Сообщения должны быть различные в зависимости от статуса платежа (успех, ошибка).
- Сообщения должны быть различные в зависимости от того это новая подписка и продление.
- Сообщения должны быть переведён на два языка: en, ru.
- Сообщения должны поддерживать форматирование (жирный, курсив, и т.д.).
- Сам текст не принципиален, можно рыбу, главное чтобы он различался исходя из параметров выше.

Следует учесть что хайлоада здесь нет и нагрузка небольшая, однако бывают острые пики нагрузки: например, кто-то резко запустил рекламу на большую аудиторию. У Телеграм есть ограничения на запросы к их серверам в единицу времени (ориентируйтесь на 10 запросов / сек), однако надо в решении учесть, что в пиковой нагрузке количество платежей точно больше этого ограничения.

Бонусом в архитектуре решения будет учесть наличие другого платёжного шлюза: то есть, хотя бы теоретическое наличие другого формата данных на другой эндпоинт, но с сохранением логики обработки платежа из описания выше.

---

### Решение:

## 1. Установка
```sh
symfony new payment-service --webapp
cd payment-service
symfony server:start
```
```
[OK] Web server listening on https://127.0.0.1:8000
```

Установка зависимостей
```sh
composer require symfony/messenger symfony/serializer symfony/translation guzzlehttp/guzzle
```
messenger – обработка очередей. <br />
serializer – работа с JSON.<br />
translation – мультиязычность.<br />
guzzlehttp/guzzle – HTTP-запросы к Telegram API.<br />

### Cоздадим PaymentController, который будет принимать платежи
```sh
php bin/console make:controller PaymentController
```
```
src\Controller\PaymentController.php
```

Что делает этот код?<br />
Принимает POST /payment с JSON-данными.<br />
Проверяет, что JSON корректный.<br />
Передаёт данные в PaymentProcessor.<br />
Возвращает JSON-ответ.<br />

### Создадим сервис PaymentProcessor <br />
```
src/Service/PaymentProcessor.php
```

Что делает этот код?<br />
Логирует платеж.<br />
Проверяет user_id.<br />
Определяет, новая подписка или продление.<br />
Возвращает ответ.<br />

### Проверка работы API
```sh
symfony server:start
```

Протестируем запросом powershell
```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8000/payment" `
  -Method Post `
  -Headers @{ "Content-Type" = "application/json" } `
  -Body '{"token": "123e4567-e89b-12d3-a456-426614174000", "status": "confirmed", "order_id": 1234567890, "amount": 20000, "currency": "RUB", "error_code": null, "pan": "12341********234", "user_id": "876123654", "language_code": "ru"}'
```
Ответ
```sh
StatusCode        : 200
StatusDescription : OK
Content           : {"message":"New subscription processed","status":"confirmed"}
RawContent        : HTTP/1.1 200 OK
                    Cache-Control: no-cache, private
                    Content-Type: application/json
                    Date: Fri, 28 Feb 2025 17:27:30 GMT
                    Set-Cookie: main_deauth_profile_token=d59ad4; path=/; httponly; samesite=lax,mai...
Forms             : {}
Headers           : {[Cache-Control, no-cache, private], [Content-Type, application/json], [Date, Fri, 28 Feb 2025 17:27:30 GMT], [Set-Cookie, main_deauth_profile_token=d59ad4; path=/; httponly; samesite=lax,main_auth_pro
                    file_token=deleted; expires=Thu, 29 Feb 2024 17:27:29 GMT; Max-Age=0; path=/; httponly]...}
Images            : {}
InputFields       : {}
Links             : {}
ParsedHtml        : System.__ComObject
RawContentLength  : 61
```

## 2. Добавление Telegram-уведомлений
### Установка HTTP-клиента для отправки запросов
Symfony использует HttpClient для работы с внешними API. Установим его:
```sh
composer require symfony/http-client
```
### Создадим сервис TelegramNotifier, который будет формировать и отправлять сообщения.
```
src/Service/TelegramNotifier.php
```
Что делает этот код?<br />
Использует HttpClientInterface для отправки запросов в Telegram API.<br />
Логирует успешную отправку или ошибки.<br />
Форматирует сообщение в MarkdownV2.<br />

### Внедрение TelegramNotifier в PaymentProcessor
```sh
src/Service/PaymentProcessor.php отредактируем код
```
Что изменилось?<br />
Теперь при обработке платежа отправляется сообщение в Telegram.<br />
Форматируется текст в зависимости от статуса и языка.<br />

### Проверка работы Telegram-уведомлений
POST-запрос через PowerShell:
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/payment" `
  -Method Post `
  -Headers @{ "Content-Type" = "application/json" } `
  -Body '{"token": "123e4567-e89b-12d3-a456-426614174000", "status": "confirmed", "order_id": 1234567890, "amount": 20000, "currency": "RUB", "error_code": null, "pan": "12341********234", "user_id": "876123654", "language_code": "ru"}'
```

Ответ
```
message              status
-------              ------
Subscription renewed confirmed
```

## 3. Установка Symfony Messenger
Symfony Messenger позволит:
Обрабатывать уведомления асинхронно, не задерживая ответ на API-запрос. <br />
Учитывать ограничения Telegram API (не более 10 запросов в секунду). <br />
Обрабатывать высокую нагрузку (пики платежей). <br />

Установим Messenger с поддержкой очередей:
```
composer require symfony/messenger
```

Symfony Messenger может работать с разными очередями:<br />
Doctrine (БД) – просто.
Установим:
```
composer require symfony/doctrine-messenger
```
Включим в .env поддержку Doctrine:
```
MESSENGER_TRANSPORT_DSN=doctrine://default
```

Подключим sqlite, отредактируем .env
```
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```
Обновим схему БД:
```
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Создание сообщения для отправки уведомлений
Добавим класс Message, этот класс просто хранит данные для обработки в очереди:
```
src/Message/SendTelegramNotification.php
```

Создание обработчика сообщений (Message Handler)<br />
Добавим Handler, этот класс обрабатывает сообщения из очереди и отправляет уведомление:<br />
```
src/MessageHandler/SendTelegramNotificationHandler.php
```

Изменение PaymentProcessor для работы с очередью. Теперь мы не отправляем уведомления сразу, а ставим их в очередь.
```
src/Service/PaymentProcessor.php
```

Конфигурация Symfony Messenger
В файле config/packages/messenger.yaml добавим настройки транспорта:
```
framework:
    messenger:
        failure_transport: failed

        transports:
            async: doctrine://default
            failed: doctrine://default?queue_name=failed

        default_bus: messenger.bus.default

        buses:
            messenger.bus.default: ~

        routing:
            App\Message\SendTelegramNotification: async

```
async: doctrine://default — Основная очередь сообщений в базе данных (Doctrine).<br />
failed: doctrine://default?queue_name=failed — Очередь для неудачных сообщений.<br />
default_bus: messenger.bus.default — Основная шина сообщений.<br />
buses: messenger.bus.default — Объявление шины (без лишних параметров).<br />
routing: App\Message\SendTelegramNotification: async — Указано, что наши сообщения SendTelegramNotification попадут в очередь.

Создаем таблицы для очередей в базе данных:
```
php bin/console messenger:setup-transports
```

Запусr обработчика очереди (чтобы сообщения реально отправлялись):
```
php bin/console messenger:consume async --limit=10 --time-limit=60 --memory-limit=128M
```