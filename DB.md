**users**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id пользователя |
| email | string | unique | Email пользователя  |
| password | string | not null | Пароль пользователя длины >= 8 и хэшированный по md5 |
| name | string | | Отображаемое имя пользователя |
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
| private_code | varchar(4) | null, unique | 4-буквенный код (A-Z) для присоединения к приватной комнате |
| hash | varchar(255) | null | Хэш комнаты – меняется при **любом входе игрока** (для синхронизации клиента) |
| deckOfCards | json | null | Перемешанная колода (52 карты) в формате JSON-массива |

**room_members**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id записи о члене комнаты |
| room_id | integer | not null | Id комнаты, к которой принадлежит пользователь |
| user_id | integer | not null | Id пользователя, присоединившегося к комнате |
| bet | integer | 0 by default | Текущая ставка пользователя в этой комнате (по умолчанию 0 для зрителей) | 
| types | enum('player','spectator') | default 'player' | Тип участия: 'player' (игрок со ставкой) или 'spectator' (зритель без ставки) | 

**messages**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id сообщения | 
| room_id | integer | not null | Id комнаты, в которой отправлено сообщение |
| user_id | integer | not null | Id пользователя, отправившего сообщение |
| message | text | not null | Текст сообщения |
| created | datetime | now() | Дата и время создания сообщения (по умолчанию текущая дата/время) |

**message_hashes**
| name | type | comment | Description |
|-|-|-|-|
| id | integer | primary key | Уникальный id записи хэша |
| room_id | integer | not null, unique | Id комнаты, к которой относится хэш (одна запись на комнату) | 
| hash | varchar(255) | not null | Хэш-сумма чата комнаты – меняется при **отправке любого сообщения** (для polling/синхронизации) |
