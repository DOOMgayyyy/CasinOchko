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
     * ✅ НОВАЯ ФУНКЦИЯ
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
            // ✅ ИСПРАВЛЕНИЕ: Добавлена проверка на успех
            $success = $this->db->addUserToRoom($room->id, $userId);
            if (!$success) {
                return ['error' => 900]; // Ошибка добавления в комнату
            }
            return $room;
        }

        // Создаём новую комнату
        $roomId = $this->db->createRoom();

        // Добавляем игрока
        // ✅ ИСПРАВЛЕНИЕ: Добавлена проверка на успех
        $success = $this->db->addUserToRoom($roomId, $userId);
        if (!$success) {
            return ['error' => 900]; // Ошибка добавления в комнату
        }

        // ✅ РЕФАКТОРИНГ: Вызов новой функции для создания колоды
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
        // Проверяем, не играет ли пользователь уже
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        // Генерируем уникальный 4-значный код
        do {
            $privateCode = rand(1000, 9999);
            $existingRoom = $this->db->getRoomByPrivateCode($privateCode);
        } while ($existingRoom); // Повторяем, пока код не будет уникальным

        // Создаём приватную комнату
        $roomId = $this->db->createPrivateRoom($privateCode);

        // Добавляем создателя в комнату
        $success = $this->db->addUserToRoom($roomId, $userId);
        if (!$success) {
            return ['error' => 900]; // Ошибка добавления в комнату
        }

        // Создаём и сохраняем колоду
        $deck = $this->createShuffledDeck();
        $this->db->saveDeck($roomId, $deck);

        // Возвращаем комнату с кодом
        $room = $this->db->getRoom($roomId);
        return $room;
    }

    /**
     * Присоединяет пользователя к приватной комнате по коду
     * @param int $userId ID пользователя
     * @param int $code 4-значный код приватной комнаты
     * @return array|object Результат операции
     */
    public function joinPrivateRoom($userId, $code)
    {
        // Проверяем, не играет ли пользователь уже
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        // Ищем комнату по коду
        $room = $this->db->getRoomByPrivateCode($code);
        if (!$room) {
            return ['error' => 901]; // Комната не найдена
        }

        // Проверяем, есть ли свободные места (максимум 6 игроков)
        $membersCount = $this->db->getMembersCount($room->id)->count;
        if ($membersCount >= 6) {
            return ['error' => 902]; // Комната заполнена
        }

        // Добавляем пользователя в комнату
        $success = $this->db->addUserToRoom($room->id, $userId);
        if (!$success) {
            return ['error' => 900]; // Ошибка добавления в комнату
        }

        // Возвращаем актуальные данные комнаты
        return $this->db->getRoom($room->id);
    }

}