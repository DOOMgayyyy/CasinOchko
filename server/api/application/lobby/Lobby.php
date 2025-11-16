<?php
/*
 Класс для работы с лобби и комнатами
 */
class Lobby
{
    private $db;

    /**
     * Конструктор класса Lobby
     * @param DB $db Объект для работы с базой данных
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Проверяет, участвует ли пользователь в активной игровой сессии
     * @param int $userId ID пользователя для проверки
     * @return bool true если пользователь сейчас в игре, false если нет
     */
    private function isUserPlaying($userId)
    {
        $data = $this->db->getRoomId($userId);
        if (!$data || !$data->room_id) {
            return false;
        }
        $room = $this->db->getRoom($data->room_id);
        return $room && $room->status === 'playing';
    }

    /**
     * Находит открытую комнату со свободными местами
     * Производит поиск среди всех открытых комнат со статусом 'playing'
     * и проверяет количество активных игроков в каждой.
     * Возвращает первую найденную комнату, где меньше 6 активных игроков.
     * @return object|null Объект комнаты если найдена подходящая, иначе null
     */
    private function getOpenRoom()
    {
        $rooms = $this->db->getOpenRooms();
        foreach ($rooms as $room) {
            $playingMembersCount = $this->db->getPlayingMembersCount($room->id)->count;
            if ($playingMembersCount < 6) {
                return $room;
            }
        }
        return null;
    }

    /**
     * Создает и перемешивает стандартную 52-карточную колоду
     * @return array Массив карт
     */
    private function createShuffledDeck()
    {
        $deck = [];
        $suits = ['H', 'D', 'C', 'S'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = $value . $suit;
            }
        }
        if (!shuffle($deck)) {
            return ['error' => 806];
        }
        return $deck;
    }

    /**
     * Генерирует 4-буквенный код приватной комнаты (A-Z)
     * @return string Код из четырёх заглавных букв
     */
    private function generatePrivateCode()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, 25)];
        }
        return $code;
    }

    /**
     * Проверяет уникальность 4-буквенного кода
     * @param string $code Код для проверки
     * @return bool true если код свободен
     */
    private function isPrivateCodeUnique($code)
    {
        $existing = $this->db->getRoomByPrivateCode($code);
        return !$existing;
    }

    /**
     * Обновляет хэш комнаты (поле hash в таблице rooms)
     * @param int $roomId ID комнаты
     * @return string Новый хэш
     */
    private function refreshRoomHash($roomId)
    {
        $newHash = md5(microtime() . random_int(0, PHP_INT_MAX));
        $this->db->execute("UPDATE rooms SET hash = ? WHERE id = ?", [$newHash, $roomId]);
        return $newHash;
    }

    /**
     * Быстрое подключение пользователя к игровой комнате
     * @param int $userId ID пользователя
     * @return array|object Данные комнаты или ошибка
     */
    public function quickStart($userId)
    {
        // if ($this->isUserPlaying($userId)) {
        //     return ['error' => 800];
        // }

        $room = $this->getOpenRoom();
        if ($room) {
            $roomId = $room->id;
        } else {
            $initialHash = md5(random_int(0, PHP_INT_MAX));
            $roomId = $this->db->createRoom('open', 'playing', null, $initialHash);
            if (!$roomId)
                return ['error' => 807];

            $deck = $this->createShuffledDeck();
            if (isset($deck['error']))
                return $deck;
            if (!$this->db->saveDeck($roomId, $deck))
                return ['error' => 805];
        }
    
        if (!$this->db->addRoomMember($roomId, $userId, 'spectator', 0))
            return ['error' => 900];

        $this->refreshRoomHash($roomId);

        $roomData = $this->db->getRoom($roomId);
        if(!$roomData){
            return ['error'=> 811];
        }
        return $roomData;
    }

    /**
     * Создает приватную комнату с уникальным 4-буквенным кодом
     * @param int $userId ID пользователя, создающего комнату
     * @return array|object Данные комнаты или ошибка
     */
    public function createPrivateRoom($userId)
    {
        // if ($this->isUserPlaying($userId)) {
        //     return ['error' => 800];
        // }

        $attempts = 0;
        $privateCode = null;
        do {
            $privateCode = $this->generatePrivateCode();
            $attempts++;
        } while (!$this->isPrivateCodeUnique($privateCode) && $attempts < 100);

        if ($attempts >= 100) {
            return ['error' => 801];
        }

        $initialHash = md5(random_int(0, PHP_INT_MAX));
        $roomId = $this->db->createRoom('private', 'playing', $privateCode, $initialHash);

        $success = $this->db->addRoomMember($roomId, $userId, 'player', 0);
        if (!$success) {
            return ['error' => 900];
        }

        $this->refreshRoomHash($roomId);

        $deck = $this->createShuffledDeck();
        $this->db->saveDeck($roomId, $deck);

        return $this->db->getRoom($roomId);
    }

    /**
     * Присоединяет пользователя к приватной комнате по коду
     * @param int $userId ID пользователя
     * @param string $code 4-буквенный код комнаты
     * @return array|object Данные комнаты или ошибка
     */
    public function joinPrivateRoom($userId, $code)
    {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        $code = strtoupper($code);
        if (!preg_match('/^[A-Z]{4}$/', $code)) {
            return ['error' => 901];
        }

        $room = $this->db->getRoomByPrivateCode($code);
        if (!$room) {
            return ['error' => 901];
        }

        $success = $this->db->addRoomMember($room->id, $userId, 'spectator', 0);
        if (!$success) {
            return ['error' => 900];
        }

        $this->refreshRoomHash($room->id);

        return $this->db->getRoom($room->id);
    }

    /**
     * Возвращает рейтинг игроков по балансу
     * @return array Список пользователей
     */
    public function getRatingTable()
    {
        return $this->db->getUsersByBalance();
    }
}