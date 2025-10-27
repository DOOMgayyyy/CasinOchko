Хорошо, вот исправленная и актуализированная документация API, основанная строго на предоставленных PHP файлах.

-----

## Описание API

Здесь описано всё АПИ, используемое в приложении, с описанием структур данных
Содержание

1.  Общее
    1.1. Адрес сервера
    1.2. Используемый протокол
2.  Структуры данных
    2.1. Общий формат ответа
    2.2. Пользователь (User)
    2.3. Сообщение (Message)
    2.4. Рейтинг пользователя (UserRating)
    2.5. Статистика пользователя (UserStats)
3.  Список запросов
    3.1. Общие ошибки
4.  Подробно
    4.1. login
    4.2. logout
    4.3. registration
    4.4. updateUserName
    4.5. sendMessage
    4.6. getMessages
    4.7. getUserBalance
    4.8. getUserStat
    4.9. quickStart
    4.10. createPrivateRoom
    4.11. joinPrivateRoom
    4.12. getRatingTable
    4.13. addBalance
    4.14. subtractBalance

-----

## 1\. Общее

### 1.1. Адрес сервера

[http://casinochko.local/api](https://www.google.com/search?q=http://casinochko.local/api)

### 1.2. Используемый протокол

API полностью реализовано на http(s).
Формат возвращаемых значений JSON.
Параметры принимаются как GET-параметры строки запроса или как JSON body в POST-запросе. (Метод `updateUserName` традиционно использует POST, как указано в прошлой документации, хотя код позволяет оба варианта).

-----

## 2\. Структуры данных

### 2.1. Общий формат ответа

T - какие-то данные. В случае успешного ответа возвращается `result = 'ok'` и поле `data` с данными.
В случае ошибки возвращается `result = 'error'` и поле `error` с кодом и текстом ошибки.

```json
Answer<T>: {
    "result": "ok" | "error";
    "data"?: T;
    "error"?: {
        "code": number;
        "text": string;
    };
}
```

### 2.2. Пользователь (User)

Возвращается при `login`, `registration` и `updateUserName`.

```json
User: {
    "id": number;
    "email": string;
    "name": string;
    "balance": number;
    "token": string;
}
```

### 2.3. Сообщение (Message)

Структура сообщения в чате.

```json
Message: {
    "message": string;
    "author": string;
    "created": string;
}
```

### 2.4. Рейтинг пользователя (UserRating)

Структура пользователя в таблице рейтинга.

```json
UserRating: {
    "id": number;
    "name": string;
    "balance": number;
}
```

### 2.5. Статистика пользователя (UserStats)

Структура статистики пользователя.

```json
UserStats: {
    "total_played": number;
    "total_win": number;
    "total_balance": number;
}
```

-----

## 3\. Список запросов

Список всех методов, обрабатываемых API.

| Название | О чем |
| :--- | :--- |
| login | Авторизация пользователя |
| logout | Логаут пользователя |
| registration | Регистрация пользователя |
| updateUserName | Обновление имени пользователя |
| sendMessage | Отправить сообщение в чат |
| getMessages | Получить сообщения в чате |
| getUserBalance | Получить баланс пользователя |
| getUserStat | Получить статистику пользователя |
| quickStart | Быстрое подключение к игре |
| createPrivateRoom | Создать приватную комнату |
| joinPrivateRoom | Подключиться к приватной комнате |
| getRatingTable | Получить таблицу рейтинга |
| addBalance | Пополнить баланс |
| subtractBalance | Списать с баланса |

### 3.1. Общие ошибки

Список кодов ошибок, определенных в `Answer.php`.

  * **101** - Param method not setted
  * **102** - Method not found
  * **242** - Params not set fully
  * **705** - User is not found
  * **800** - Невозможно войти в комнату. Игрок уже играет
  * **801** - Failed to generate unique room code
  * **802** - Private room not found or invalid code
  * **803** - Room is full (maximum 6 players)
  * **804** - у тебя нет денег
  * **9000** - unknown error

-----

## 4\. Подробно

### 4.1. login

Авторизация пользователя в системе.
**Параметры**

```json
{
    "email": string; - email пользователя
    "password": string; - пароль пользователя (в открытом виде)
}
```

**Успешный ответ**
`Answer<User>`
**Ошибки**

  * **242** - Params not set fully
  * **1002** - Wrong login or password (неверный пароль)
  * **1005** - User is no exists (пользователь не найден)

### 4.2. logout

Выход пользователя из системы.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
`Answer<true>`
**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **1003** - Error to logout user (внутренняя ошибка выхода)

### 4.3. registration

Регистрация нового пользователя.
**Параметры**

```json
{
    "email": string; - email пользователя
    "password": string; - пароль пользователя (в открытом виде, сервер сам его хэширует)
    "name": string; - имя пользователя
}
```

**Успешный ответ**
`Answer<User>`
**Ошибки**

  * **242** - Params not set fully (или email невалиден)
  * **1007** - user with this email is already registered (email занят)
  * **1004** - Error to register user (внутренняя ошибка регистрации)

### 4.4. updateUserName

Обновление имени пользователя. (Рекомендуется использовать POST).
**Параметры**

```json
{
    "token": string; - токен авторизации
    "newName": string; - новое имя пользователя
}
```

**Успешный ответ**
`Answer<User>` (Возвращает обновленный объект пользователя)
**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **1009** - Error updating user name (внутренняя ошибка обновления)
  * **1010** - Name is already taken (имя занято)

### 4.5. sendMessage

Отправка сообщения в чат.
**Параметры**

```json
{
    "token": string; - токен авторизации
    "message": string; - текст сообщения
}
```

**Успешный ответ**
`Answer<true>` (Предположительно, на основе `Chat::sendMessage` в `Application.php`)
**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **706** - text message is empty
  * **707** - could not send message

### 4.6. getMessages

Получение всех сообщений чата.
**Параметры**

```json
{
    "token": string; - токен авторизации
    "hash": string; - хеш-сумма чата (для проверки обновлений)
}
```

**Успешный ответ**
(Структура ответа неполная, т.к. `Chat.php` отсутствует, но `DB.php` определяет `Message`)

```json
Answer<{
    "messages": Message[]; - список сообщений
    "hash": string; - новый хеш чата
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found

### 4.7. getUserBalance

Получение баланса пользователя.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
(Метод в `Application.php` возвращает баланс напрямую из объекта User)

```json
Answer<{
    "balance": number;
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found

### 4.8. getUserStat

Получение статистики пользователя.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
(Структура `UserStats` определена в `DB.php`)

```json
Answer<{
    "stats": UserStats;
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found

### 4.9. quickStart

Быстрое подключение к игровой комнате.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
`Answer<object>` (Возвращает объект комнаты. Логика в `Lobby.php` не завершена)
**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **800** - Невозможно войти в комнату. Игрок уже играет

### 4.10. createPrivateRoom

Создание приватной комнаты.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
(Основано на `Lobby::createPrivateRoom` и `Application::createPrivateRoom`)

```json
Answer<{
    "private": {
        "code": string; - 4-буквенный код комнаты
        "room_id": number; - ID созданной комнаты
    }
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **801** - Failed to generate unique room code

### 4.11. joinPrivateRoom

Подключение к приватной комнате по коду.
**Параметры**

```json
{
    "token": string; - токен авторизации
    "code": string; - 4-буквенный код комнаты
}
```

**Успешный ответ**
(Основано на `Lobby::joinPrivateRoom` и `Application::joinPrivateRoom`)

```json
Answer<{
    "room": {
        "room_id": number;
        "code": string;
    }
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found
  * **800** - Невозможно войти в комнату. Игрок уже играет
  * **802** - Private room not found or invalid code
  * **803** - Room is full (maximum 6 players)

### 4.12. getRatingTable

Получение топ-100 игроков по балансу.
**Параметры**

```json
{
    "token": string; - токен авторизации
}
```

**Успешный ответ**
(Основано на `Lobby::getRatingTable` и `DB::getUsersByBalance`)

```json
Answer<{
    "rating": UserRating[]
}>
```

**Ошибки**

  * **242** - Params not set fully
  * **705** - User is not found

### 4.13. addBalance

Пополнение баланса пользователя.
**Параметры**

```json
{
    "token": string; - токен авторизации
    "amount": number; - Сумма пополнения (должна быть > 0)
}
```

**Успешный ответ**

```json
Answer<{
    "balance": number; // новый баланс
}>
```

**Ошибки**

  * **242** - Params not set fully (или `amount` невалидный, т.е. не число или \<= 0)
  * **705** - User is not found
  * **9000** - unknown error (ошибка БД при обновлении)

### 4.14. subtractBalance

Снятие средств с баланса пользователя.
**Параметры**

```json
{
    "token": string; - токен авторизации
    "amount": number; - Сумма снятия (должна быть > 0)
}
```

**Успешный ответ**

```json
Answer<{
    "balance": number; // новый баланс
}>
```

**Ошибки**

  * **242** - Params not set fully (или `amount` невалидный, т.е. не число или \<= 0)
  * **705** - User is not found
  * **804** - у тебя нет денег (недостаточно средств на балансе)
  * **9000** - unknown error (ошибка БД при обновлении)