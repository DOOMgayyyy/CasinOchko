<?php
/*
 Класс для работы с базой данных

 Инкапсулирует всю логику взаимодействия с БД через PDO
 Предоставляет безопасные методы для выполнения запросов
 */
class Lobby
{
    private $db;
    /**
     * Конструктор класса Lobby
     * * @param DB $db Объект для работы с базой данных
     */
    function __construct($db)
    {
        $this->db = $db;
    }
    /**
     * Проверяет, участвует ли пользователь в активной игровой сессии
     * * @param int $userId ID пользователя для проверки
     * @return bool true если пользователь сейчас в игре, false если нет
     */

    private function isUserPlaying($userId)
    {
        // !! Небольшое исправление: нужно проверять, существует ли ->room_id
        $data = $this->db->getRoomId($userId);
        if (!$data || !$data->room_id) {
            return false;
        }
        $roomId = $data->room_id;
        $room = $this->db->getRoom($roomId);
        return $room && $room->status === 'playing';
    }

    /**
     * Находит открытую комнату со свободными местами
     * * Производит поиск среди всех открытых комнат со статусом 'playing'
     * и проверяет количество участников в каждой. Возвращает первую 
     * найденную комнату, где меньше 6 участников.
     * * @return object|null Объект комнаты если найдена подходящая, иначе null
     */
    private function getOpenRoom()
    {
        $rooms = $this->db->getOpenRooms();
        // queryAll теперь возвращает массив объектов (PDO::FETCH_OBJ)
        foreach ($rooms as $room) {
            $membersCount = $this->db->getMembersCount($room->id)->count;
            if ($membersCount < 6) {
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
        $suits = ['H', 'D', 'C', 'S']; // Червы, Бубны, Трефы, Пики
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = $value . $suit;
            }
        }
        shuffle($deck); // Перемешиваем
        return $deck;
    }

    /**
     * Быстрое подключение пользователя к игровой комнате
     */
    public function quickStart($userId)
    {
        // этот пользователь уже играет -> error
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }
        
        // Ищем открытую комнату со свободными местами
        $room = $this->getOpenRoom();
        if ($room) {
            $success = $this->db->addUserToRoom($room->id, $userId);
            if (!$success) {
                return ['error' => 900]; // Ошибка добавления в комнату
            }
            return $room;
        }

        // Создаём новую комнату
        $roomId = $this->db->createRoom();

        // Добавляем игрока
        $success = $this->db->addUserToRoom($roomId, $userId);
        if (!$success) {
            return ['error' => 900]; // Ошибка добавления в комнату
        }

        $deck = $this->createShuffledDeck();

        // Сохраняем колоду в БД
        $this->db->saveDeck($roomId, $deck);

        // Возвращаем объект комнаты
        return $this->db->getRoom($roomId);
    }

    /**
     * Создает приватную комнату с уникальным 4-значным кодом
     * @param int $userId ID пользователя, создающего комнату
     * @return array|object Результат операции с кодом комнаты
     */
    public function createPrivateRoom($userId)
    {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        // Генерируем уникальный 4-буквенный код
        $attempts = 0;
        $privateCode = null;
        do {
            $privateCode = $this->generatePrivateCode();
            $attempts++;
        } while (!$this->isPrivateCodeUnique($privateCode) && $attempts < 100);

        if ($attempts >= 100) {
            return ['error' => 901]; // Не удалось сгенерировать уникальный код
        }

        // Создаём приватную комнату
        $roomId = $this->db->createPrivateRoom($privateCode);

        // Добавляем создателя
        $success = $this->db->addUserToRoom($roomId, $userId);
        if (!$success) {
            return ['error' => 900];
        }

        // Создаём и сохраняем колоду
        $deck = $this->createShuffledDeck();
        $this->db->saveDeck($roomId, $deck);

        // Возвращаем комнату
        return $this->db->getRoom($roomId);
    }

    /**
     * Присоединяет пользователя к приватной комнате по коду
     * @param int $userId ID пользователя
     * @param int $code 4-значный код приватной комнаты
     * @return array|object Результат операции
     */
    public function joinPrivateRoom($userId, $code)
    {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        // Приводим к верхнему регистру (на случай ввода строчных)
        $code = strtoupper($code);

        // Проверяем формат: только 4 буквы A-Z
        if (!preg_match('/^[A-Z]{4}$/', $code)) {
            return ['error' => 901]; // Неверный формат кода
        }

        $room = $this->db->getRoomByPrivateCode($code);
        if (!$room) {
            return ['error' => 901]; // Комната не найдена
        }

        $membersCount = $this->db->getMembersCount($room->id)->count;
        if ($membersCount >= 6) {
            return ['error' => 902]; // Комната заполнена
        }

        $success = $this->db->addUserToRoom($room->id, $userId);
        if (!$success) {
            return ['error' => 900];
        }

        return $this->db->getRoom($room->id);
    }
    
    private function generatePrivateCode()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, 25)];
        }
        return $code;
    }

    private function isPrivateCodeUnique($code)
    {
        $existing = $this->db->getRoomByPrivateCode($code);
        return !$existing;
    }

}