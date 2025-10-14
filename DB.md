**users**
| name | type | comment |
|-|-|-|
| id | integer | primary key |
| email | string | unique |
| password | string | not null |
| balance | integer | 5000 by default |
| token | string | |
| total_played | integer | 0 by default |
| total_win | integer | 0 by default |
| total_balance | integer | 5000 by default |


**rooms**
| name | type | comment |
|-|-|-|
| id | integer | primary key |
| type | string | 'open', 'private' |
| status | string | 'playing', 'closed' |
| current_member_id | integer | |
| private_code | integer | |
| hash | string | |


**room_members**
| name | type | comment |
|-|-|-|
| id | integer | primary key |
| room_id | integer | not null |
| user_id | integer | not null |
| bet | integer | 100 by default |


**messages**
| name | type | comment |
|-|-|-|
| id | integer | primary key |
| room_id | integer | not null |
| user_id | integer | not null |
| message | string | not null |
| created | datetime | now() |


**message_hashes**
| name | type | comment |
|-|-|-|
| id | integer | primary key |
| room_id | integer | not null |
| hash | string | |
