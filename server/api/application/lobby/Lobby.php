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
     * 
     * @param DB $db Объект для работы с базой данных
     */
    function __construct($db)
    {
        $this->db = $db;
    }
    /**
     * Проверяет, участвует ли пользователь в активной игровой сессии
     * 
     * @param int $userId ID пользователя для проверки
     * @return bool true если пользователь сейчас в игре, false если нет
     */

    private function isUserPlaying($userId)
    {
        $roomId = $this->db->getRoomId($userId)->room_id;
        $room = $this->db->getRoom($roomId);
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
    private function getOpenRoom()
    {
        $rooms = $this->db->getOpenRooms();
        foreach ($rooms as $room) {
            $membersCount = $this->db->getMembersCount($room->id)->count;
            if ($membersCount < 6) {
                return $room;
            }
        }
        return null;
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
    public function quickStart($userId)
    {
        // этот пользователь уже играет -> error
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }
        // Ищем открытую комнату со свободными местами
        $room = $this->getOpenRoom();
        if ($room) {
            $this->db->addUserToRoom($room->id, $userId);
            return $room;
        }
        // Создаём новую комнату
        $roomId = $this->db->createRoom();

        // Добавляем игрока
        $this->db->addUserToRoom($roomId, $userId);
    }
}