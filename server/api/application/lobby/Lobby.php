<?php
/*
 Класс для работы с базой данных
 
 Инкапсулирует всю логику взаимодействия с БД через PDO
 Предоставляет безопасные методы для выполнения запросов
 */
class Lobby {
    private $db;
    /**
     * Конструктор класса Lobby
     * 
     * @param DB $db Объект для работы с базой данных
     */
    function __construct($db) {
        $this->db = $db;
    }
    /**
     * Проверяет, участвует ли пользователь в активной игровой сессии
     * 
     * @param int $userId ID пользователя для проверки
     * @return bool true если пользователь сейчас в игре, false если нет
     */

    private function isUserPlaying($userId) {
        $roomMemberEntry = $this->db->getRoomId($userId);

        if (!$roomMemberEntry || !$roomMemberEntry->room_id) {
            return false;
        }

        $room = $this->db->getRoom($roomMemberEntry->room_id);
        return $room && $room->status === 'playing';
    }

    /**
     * Находит открытую комнату со свободными местами
     * 
     * Производит поиск среди всех открытых комнат со статусом 'playing'
     * и проверяет количество участников в каждой. Возвращает первую 
     * найденную комнату, где меньше 6 участников.
     * 
     * @return object|null Объект комнаты если найдена подходящая, иначе null
     */
    private function getOpenRoom() {
        $rooms = $this->db->getOpenRooms();
        foreach ($rooms as $room) {
            $membersCount = $this->db->getMembersCount($room->id)->count;
            if ($membersCount < 6) {
                return $room;
            }
        }
        return null;
    }

    # Генерации уникального кода для приватной комнаты
    private function generateUniquePrivateCode() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        $codeLength = 4;
        $maxAttempts = 100;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $privateCode = '';
            for ($i = 0; $i < $codeLength; $i++) {
                $privateCode .= $chars[random_int(0, $charsLength - 1)];
            }

            # Проверка уникальности кода в БД
            if ($this->db->isPrivateCodeUnique($privateCode)) {
                return $privateCode;
            }

            $attempts++;
        }
        return null;
    }

    public function connectRoom($roomId) {
        $room = $this->db->getRoom($roomId);
        
        if (!$room) {
            return ['error' => 805];
        }

        $players = $this->db->getRoomMembers($roomId);

        return [
            'id' => $room->id,
            'type' => $room->type,
            'status' => $room->status,
            'private_code' => $room->private_code,
            'players' => $players
        ];
    }
        /**
     * Быстрое подключение пользователя к игровой комнате
     * 
     * Алгоритм работы:
     * 1. Проверяет, не играет ли пользователь уже в другой комнате
     * 2. Ищет существующую открытую комнату со свободными местами
     * 3. Если комнаты нет - создает новую (реализация создания пока отсутствует)
     * 
     * @param int $userId ID пользователя для подключения
     * @return array Результат операции:
     *               - ['error' => 800] если пользователь уже в игре
     *               - object комнаты если найдена подходящая
     *               - ... (нужно дописать для случая создания новой комнаты)
     * 
     * @todo Реализовать логику добавления игрока в найденную комнату
     * @todo Реализовать создание новой комнаты когда нет доступных
     */
    public function quickStart($userId) {
        // этот пользователь уже играет -> error
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }
        // есть открытая комната со свободными местами
        $room = $this->getOpenRoom();

        if ($room) {
            // добавить игрока в комнату
            //...
            $this->db->removeUserFromAllRooms($userId);
            $result = $this->db->addRoomMember($room->id, $userId);

            if (!$result) {
                return ['error' => 806];
            }

            return $this->connectRoom($room->id);   
        }
        // создать новую комнату
        //...
        $hash = md5(random_int(0, PHP_INT_MAX));
        $roomId = $this->db->createRoom('open', 'playing', null, $hash);

        $this->db->removeUserFromAllRooms($userId);
        $result = $this->db->addRoomMember($roomId, $userId);

        if (!$result) {
            return ['error' => 806];
        }

        return $this->connectRoom($roomId);
    }

    public function getRatingTable($currentUserId = null)
    {
        return $this->db->getUsersByBalance();
    }

    # Создание приватной комнаты
    public function createPrivateRoom($userId) {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

       $privateCode = $this->generateUniquePrivateCode();

       if ($privateCode === null) {
            return ['error' => 801];
       }
       
       $hash = md5(random_int(0, PHP_INT_MAX));

       $roomId = $this->db->createRoom('private', 'playing', $privateCode, $hash);

       $this->db->removeUserFromAllRooms($userId);

       $result = $this->db->addRoomMember($roomId, $userId);

       if (!$result) {
            return ['error' => 806];
       }

       return $this->connectRoom($roomId);
    }
    
    # Подключение к приватной комнате по 4-значному коду
    public function joinPrivateRoom($userId, $code) {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        $room = $this->db->getRoomByPrivateCode($code);

        if (!$room) {
            return ['error' => 802];
        }

        $membersCount = $this->db->getMembersCount($room->id)->count;

        if ($membersCount >= 6) {
            return ['error' => 803];
        }

        $this->db->removeUserFromAllRooms($userId);

        $result = $this->db->addRoomMember($room->id, $userId); 

        if (!$result) {
            return ['error' => 806];
        }

        return $this->connectRoom($room->id);
    }
}