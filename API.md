Описание API
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

Список запросов
3.1. Общие ошибки

Подробно
4.1. login
4.2. logout
4.3. registration
4.4. updateUserName
4.5. sendMessage
4.6. getMessages
<<<<<<< Updated upstream


=======
4.7. getUserBalance
4.8. getUserStat
4.9. quickStart
4.10. createPrivateRoom
4.11. joinPrivateRoom
4.12. getRatingTable
4.13. addBalance
4.14. subtractBalance
>>>>>>> Stashed changes

1. Общее
1.1. Адрес сервера
http://nopainnogame.local/api
1.2. Используемый протокол
API полностью реализовано на http(s). 
Формат возвращаемых значений JSON.
Все методы, если это особо не оговорено, имеют тип GET. Метод updateUserName использует POST.
2. Структуры данных
2.1. Общий формат ответа
T - какие-то данные. В случае успешного ответа возвращается result = 'ok' и поле data с данными.
В случае ошибки возвращается result = 'error' и поле error с кодом и текстом ошибки
Answer<T>: {
    result: 'ok' | 'error';
    data?: T;
    error?: {
        code: number;
        text: string;
    };
}

2.2. Пользователь
User: {
    id: number;
    email: string;
    token: string;
    name?: string;
    balance?: number;
}

2.3. Сообщение
Message: {
    message: string;
    author: string;
    created: string;
}

2.4. Рейтинг пользователя
UserRating: {
    id: number;
    name: string;
    balance: number;
}

3. Список запросов

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

3.1. Общие ошибки

101 - Param method not setted
102 - Method not found
242 - Params not set fully
705 - User is not found
800 - User is already playing in another room
801 - Failed to generate unique room code
802 - Private room not found or invalid code
803 - Room is full (maximum 6 players)
9000 - unknown error

4. Подробно
4.1. login
Авторизация пользователя в системе
Параметры
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
    token: string; - токен авторизации
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
    email: string; - email пользователя (должен быть валидным, например, user@example.com)
    password: string; - пароль пользователя
    name: string; - имя пользователя
}

Примечание: Email должен быть валидным (проверяется сервером с помощью фильтра валидации email). Если email не соответствует формату, возвращается ошибка 242.
Успешный ответ
    Answer<User>

Ошибки

* 242 - Params not set fully или email невалиден
* 1007 - user with this email is already registered
* 1004 - Error to register user

4.4. updateUserName
Обновление имени пользователя
Тип запроса: POST
Параметры JSON body
{
    method: "updateUserName";
    token: string; - токен авторизации
    newName: string; - новое имя пользователя
}

Успешный ответ
    Answer<User>

Ошибки

* 242 - Params not set fully
* 705 - User is not found
* 1009 - Error updating user name
* 1010 - Name is already taken

4.5. sendMessage
Отправка сообщения в чат
Параметры
{
    token: string; - токен авторизации
    message: string; - текст сообщения
}

Успешный ответ
    Answer<true>

Ошибки

* 242 - Params not set fully
* 705 - User is not found
* 706 - text message is empty
* 707 - could not send message

4.6. getMessages
Получение всех сообщений чата
Параметры
{
    token: string; - токен авторизации
    hash: string; - хеш-сумма чата
}

Успешный ответ
    Answer<{
        messages: Message[]; - список сообщений
        hash: string; - новый хеш чата
    }>

Примечание: в случае отсутствия новых сообщений будет ответ:
    Answer<{
        hash: string; - новый хеш чата
    }>

Ошибки

* 242 - Params not set fully
* 705 - User is not found

4.7. getUserBalance
Получение баланса пользователя

4.8 getUserStat Получение статистики пользователя

Параметры: {token: string - токен авторизации}

Успешный ответ Answer <{
    stats: UserStats;
}>


Ошибки

242 - Params not set fully
705 - User is not found

Параметры
{
    "token": string; - токен авторизации
}
Успешный ответ
    Answer<{
        "balance": number;
    }>
Ошибки

* 242 - Params not set fully
* 705 - User is not found

4.8. getUserStat
Получение статистики пользователя
Параметры
{
    "token": string; - токен авторизации
}
Успешный ответ
    Answer<{
        stats: UserStats; // (Структура UserStats не определена, но используется в коде)
    }>
Ошибки

* 242 - Params not set fully
* 705 - User is not found

4.9. quickStart
Быстрое подключение к игровой комнате.
Ищет открытую комнату или создает новую.
Параметры
{
    "token": string; - токен авторизации
}
Успешный ответ
    Answer<object> // Возвращает объект комнаты (структура Room не определена)

Ошибки
* 242 - Params not set fully
* 705 - User is not found
* 800 - Невозможно войти в комнату. Игрок уже играет

4.10. createPrivateRoom
Создание приватной комнаты с уникальным 4-буквенным кодом (AAAA-ZZZZ)
Параметры
{
    token: string; - токен авторизации
}
Успешный ответ
    Answer<{
        private: {
            code: string; - 4-буквенный код комнаты (например: "ABCD")
            room_id: number; - ID созданной комнаты
        }
    }>
Создает комнату: type='private', status='closed', добавляет создателя в room_members (bet=0).
Ошибки

* 242 - Params not set fully
* 705 - User is not found
* 801 - Failed to generate unique room code

4.11. getBalance
Получение баланса пользователя

4.12 getUserStat Получение статистики пользователя

Параметры: {token: string - токен авторизации}

Успешный ответ Answer <{
    stats: UserStats;
}>

Ошибки

242 - Params not set fully
705 - User is not found

Параметры
{
    "token": string; - токен авторизации
    "code": string; - 4-буквенный код комнаты
}
Успешный ответ
    Answer<{
        room: object // Возвращает объект комнаты (структура Room не определена)
    }>
Ошибки
* 242 - Params not set fully
* 705 - User is not found
* (Другие ошибки, связанные с подключением к комнате, не реализованы в Lobby.php)

4.13 getRatingTable 
Получение топ-100 игроков по балансу
Параметры
{
    "token": "string"  // токен авторизации пользователя
}
Успешный ответ
    Answer<{
        rating: UserRating[]
    }>

Пример `data`:
{
    "rating": [
        {
            "id": 3,
            "name": "Артём",
            "balance": 1500
        },
        {
            "id": 1,
            "name": "Егор",
            "balance": 1200
        }
    ]
}

Ошибки
<<<<<<< Updated upstream
242	Params not set fully (не передан токен)
705	User is not found (токен невалидный, пользователь не найден)

4.14. addBalance
Метод для прибавления указанной суммы к балансу пользователя.

Параметры
{
    "token": string; - токен авторизации
    "amount": number; - СУММА
}

Успешный ответ
    Answer<{
        "balance": number; - Обновленный баланс
    }>

Ошибки
242 - Params not set fully
705 - User is not found 

4.15 subtractBalance
Метод для убавления указанной суммы с баланса пользователя с обязательной проверкой достаточности средств.

Параметры
{
    "token": string; - токен авторизации
    "amount": number; - Сумма для списания (должна быть > 0).
=======
* 242 - Params not set fully (не передан токен)
* 705 - User is not found (токен невалидный, пользователь не найден)

4.13. addBalance
Пополнение баланса пользователя.

Параметры
{
    "token": "string",  // токен авторизации
    "amount": "number"  // сумма пополнения 
}

Answer<{
        "balance": number; // новый баланс
    }>

Примечание: Total balance также увеличивается на эту сумму.

Ошибки

242 - Params not set fully, или amount невалидный (не число, или ≤0)
705 - User is not found

4.14. subtractBalance
Снятие средств с баланса пользователя.

Параметры
{
    "token": "string",  // токен авторизации
    "amount": "number"  // сумма снятия (должна быть > 0)
>>>>>>> Stashed changes
}

Успешный ответ
Answer<{
<<<<<<< Updated upstream
    "balance": number; // Обновленный баланс пользователя
}>

Ошибки
242 - Params not set fully
705 - User is not found
802 - у тебя нет денег
=======
        "balance": number; // новый баланс
}>

Примечание: Total balance не меняется.

Ошибки

242 - Params not set fully, или amount невалидный (не число, или ≤0)
705 - User is not found
802 - у тебя нет денег (недостаточно средств на балансе)
>>>>>>> Stashed changes
