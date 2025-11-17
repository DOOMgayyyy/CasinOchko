Хорошо, вот исправленная и актуализированная документация API, основанная строго на предоставленных PHP файлах.

-----

## Описание API

Здесь описано всё АПИ, используемое в приложении, с описанием структур данных

Содержание

Общее
1.1. Адрес сервера
1.2. Используемый протокол

Структуры данных
2.1. Общий формат ответа
2.2. Пользователь
2.3. Сообщение
2.4. Рейтинг пользователя
2.5. Статистика пользователя
2.6. Комната
2.7. Игрок в комнате
2.8. Таймер 

Список запросов
3.1. Общие ошибки

Подробно
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
4.15. getInfoRoom
4.16. leaveRoom

1. Общее
1.1. Адрес сервера
http://casinochko/api

1.2. Используемый протокол
API полностью реализовано на http(s). 
Формат возвращаемых значений JSON.
Все методы, если это особо не оговорено, имеют тип GET. Методы updateUserName, sendMessage используют POST.

2. Структуры данных
2.1. Общий формат ответа
T - какие-то данные. В случае успешного ответа возвращается result = 'ok' и поле data с данными.
В случае ошибки возвращается result = 'error' и поле error с кодом и текстом ошибки
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
    id: number;
    email: string;
    token: string;
    name?: string;
    balance?: number;
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

2.4. Рейтинг пользователя
UserRating: {
    id: number;
    name: string;
    balance: number;
}

2.5. Статистика пользователя
UserStats: {
    total_played: number;
    total_win: number;
    total_balance: number;
}

2.6. Комната
Room: {
    id: number;
    type: 'open' | 'private';
    status: 'playing' | 'closed';
    current_member_id?: number | null;
    private_code?: string | null;
    hash?: string | null;
    deckOfCards?: string; // JSON-массив карт
}

2.7. Игрок в комнате (Player)
Player: {
    "memberId": number,
    "userId": number,
    "name": string,
    "balance": number,
    "bet": number,
    "cards": string[], 
    "status": "active" | "folded" | "waiting"
}

2.8. Таймер (Timer)
Timer: {
    "currentPlayerId": number, // memberId игрока, который сейчас ходит
    "timeLeft": number | null,   // Секунд осталось
    "totalTime": number | null  // Всего секунд на ход
}

3. Список запросов

| Название | О чем |
| :--- | :--- |
| login | Авторизация пользователя |
| logout | Логаут пользователя |
| registration | Регистрация пользователя |
| updateUserName | Обновление имени пользователя |
| sendMessage | Отправить сообщение в чат комнаты |
| getMessages | Получить сообщения в чате комнаты |
| getUserBalance | Получить баланс пользователя |
| getUserStat | Получить статистику пользователя |
| quickStart | Быстрое подключение к игре |
| createPrivateRoom | Создать приватную комнату |
| joinPrivateRoom | Подключиться к приватной комнате |
| getRatingTable | Получить таблицу рейтинга |
| addBalance | Пополнение баланса |
| subtractBalance | Снятие средств с баланса |
| leaveRoom | Покинуть комнату |

3.1. Общие ошибки

101 - Param method not setted
102 - Method not found
242 - Params not set fully
705 - User is not found
800 - Невозможно войти в комнату. Игрок уже играет
801 - Failed to generate unique room code
802 - Private room not found or invalid code
803 - Room is full (maximum 6 players)
804 - у тебя нет денег
900 - Ошибка добавления в комнату
901 - Комната не найдена
902 - Пользователь не находится в комнате
903 - Не удалось покинуть комнату
9000 - unknown error

-----

## 4\. Подробно

### 4.1. login

Авторизация пользователя в системе.
**Параметры**

```json
{
    email: string; - email пользователя
    password: string; - пароль пользователя (в открытом виде)
}

Успешный ответ
    Answer<User>

Ошибки
* 242 - Params not set fully
* 1002 - Wrong login or password
* 1005 - User is no exists

4.2. logout
Выход пользователя из системы
Параметры
{
    "token": string; - токен авторизации
}

Успешный ответ
    Answer<true>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 1003 - Error to logout user

4.3. registration
Регистрация нового пользователя
Параметры
{
    email: string; - email пользователя (должен быть валидным)
    password: string; - пароль пользователя
    name: string; - имя пользователя
}

Примечание: Email должен быть валидным (проверяется сервером). Если email не соответствует формату, возвращается ошибка 242.
Успешный ответ
    Answer<User>

Ошибки
* 242 - Params not set fully или email невалиден
* 1007 - user with this email is already registered
* 1004 - Error to register user

4.4. updateUserName
Обновление имени пользователя
Параметры (POST)
{
    token: string; - токен авторизации
    newName: string; - новое имя
}

Успешный ответ
    Answer<User>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 1009 - Error updating user name
* 1010 - Name is already taken

4.5. sendMessage
Отправить сообщение в чат комнаты
Параметры (POST)
{
    token: string; - токен авторизации
    message: string; - текст сообщения
    room_id: number; - ID комнаты
}

Успешный ответ
    Answer<{
        hash: string; - новый хэш чата
    }>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 706 - text message is empty
* 707 - could not send message

4.6. getMessages
Получить сообщения в чате комнаты
Параметры
{
    token: string; - токен авторизации
    hash: string; - текущий хэш чата
    room_id: number; - ID комнаты
}

Успешный ответ
    Answer<{
        messages: Message[];
        hash: string; - актуальный хэш чата
    }>

Ошибки
* 242 - Params not set fully
* 705 - User is not found

4.7. getUserBalance
Получить баланс пользователя
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<{
        balance: number;
    }>

Ошибки
* 242 - Params not set fully
* 705 - User is not found

4.8. getUserStat
Получить статистику пользователя
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<{
        stats: UserStats;
    }>

Ошибки
* 242 - Params not set fully
* 705 - User is not found

4.9. quickStart
Быстрое подключение к игре
Ищет открытую комнату или создает новую.
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<Room>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 800 - Невозможно войти в комнату. Игрок уже играет
* 900 - Ошибка добавления в комнату

4.10. createPrivateRoom
Создание приватной комнаты с уникальным 4-буквенным кодом (AAAA-ZZZZ)
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<Room>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 800 - Невозможно войти в комнату. Игрок уже играет
* 801 - Failed to generate unique room code
* 900 - Ошибка добавления в комнату

4.11. joinPrivateRoom
Подключение к приватной комнате по 4-буквенному коду
Параметры
{
    token: string; - токен авторизации
    code: string; - 4-буквенный код комнаты
}

Успешный ответ
    Answer<Room>

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 800 - Невозможно войти в комнату. Игрок уже играет
* 901 - Комната не найдена или неверный формат кода
* 803 - Room is full (maximum 6 players)
* 900 - Ошибка добавления в комнату

4.12. getRatingTable
Получение топ-100 игроков по балансу
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<{
        rating: UserRating[];
    }>

Ошибки
* 242 - Params not set fully
* 705 - User is not found

4.13. addBalance
Пополнение баланса пользователя
Параметры
{
    token: string; - токен авторизации
    amount: number; - сумма пополнения (>0)
}

Успешный ответ
    Answer<{
        balance: number; - новый баланс
    }>

Примечание: Total balance также увеличивается на эту сумму.
Ошибки
* 242 - Params not set fully или amount невалиден
* 705 - User is not found
* 9000 - unknown error

4.14. subtractBalance
Снятие средств с баланса пользователя
Параметры
{
    token: string; - токен авторизации
    amount: number; - сумма снятия (>0)
}

Успешный ответ
    Answer<{
        balance: number; - новый баланс
    }>

Ошибки
* 242 - Params not set fully или amount невалиден
* 705 - User is not found
* 804 - у тебя нет денег
* 9000 - unknown error

4.15. getInfoRoom
Получение актуальной информации об игровой комнате. Работает по принципу long polling с использованием хэша.

Параметры
{
    token: string; - токен авторизации
    room_id: number; - ID комнаты
    hash: string; - текущий хэш клиента
}

Успешный ответ (при changed: true)
Answer<{
    "players": Player[], // (см. 2.7)
    "myCards": string[], // Карты текущего пользователя
    "timer": Timer | null, // (см. 2.8)
    "hash": string, // Новый хэш
    "changed": true // true, если данные изменились
}>
Успешный ответ (при changed: false)

Примечание: Если changed = false, сервер сообщает, что хэш не изменился, и остальные поля (players, myCards, timer) будут пустыми или null.
Answer<{
    "players": [],
    "myCards": [],
    "timer": null,
    "hash": string, // Хэш, который прислал клиент
    "changed": false // false, т.к. изменений нет
}>

Ошибки
242 - Params not set fully
705 - User is not found
901 - Комната не найдена

4.16. leaveRoom
Покинуть комнату. Удаляет пользователя из комнаты и обновляет состояние.
Параметры
{
    token: string; - токен авторизации
}

Успешный ответ
    Answer<{
        success: true;
        roomDeleted: boolean; - true, если комната была удалена (пустая)
    }>

Примечание: 
- Ставка игрока сгорает при выходе из комнаты (не возвращается на баланс)
- Если комната остается пустой, она удаляется из БД
- Обновляется hash комнаты для уведомления остальных игроков

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 901 - Комната не найдена
* 902 - Пользователь не находится в комнате
* 903 - Не удалось покинуть комнату