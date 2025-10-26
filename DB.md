**users**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id пользователя |
| email | string | unique | Email пользователя  |
| password | string | not null | Пароль пользователя длины >= 8 и хэшированный по md5 |
| balance | integer | 5000 by default | Текущий баланс пользователя | 
| token | string | | Уникальный токен сессии пользователя |
| total_played | integer | 0 by default | Всего сыгранно пользователем игр |
| total_win | integer | 0 by default | Всего побед пользователя |
| total_balance | integer | 5000 by default | Вся прибыль пользователя |


**rooms**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id комнаты |
| type | string | 'open', 'private' | Тип комнаты 'открытый' или 'закрытый' см. тз |
| status | string | 'playing', 'closed' | Статус комнаты обозначает идёт игра или нет |
| current_member_id | integer | | Id текущего члена комнаты (игрока), чей ход в данный момент (для управления очередью ходов) | 
| private_code | integer | | Уникальный 4-значный код для присоединения к приватной комнате (генерируется при создании) |
| hash | string | | Хэш комнаты для синхронизации или проверки изменений (например, для реал-тайм обновлений) |


**room_members**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id записи о члене комнаты |
| room_id | integer | not null | Id комнаты, к которой принадлежит пользователь |
| user_id | integer | not null | Id пользователя, присоединившегося к комнате |
| bet | integer | 0 by default | Текущая ставка пользователя в этой комнате (по умолчанию 0 для зрителей) | 
| types | boolean | 'player', 'spectator' | Тип участия: true для 'player' (игрок со ставкой), false для 'spectator' (зритель без ставки) | 

**messages**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id сообщения | 
| room_id | integer | not null | Id комнаты, в которой отправлено сообщение |
| user_id | integer | not null | Id пользователя, отправившего сообщение |
| message | string | not null | Текст сообщения |
| created | datetime | now() | Дата и время создания сообщения (по умолчанию текущая дата/время) |


**message_hashes**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id записи хэша |
| room_id | integer | not null | Id комнаты, к которой относится хэш | 
| hash | string | | Хэш-сумма чата комнаты для проверки новых сообщений (используется для polling/синхронизации) |
