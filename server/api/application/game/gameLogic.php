<?php
/**
 * Класс для игровой логики и получения информации о комнате
 */
class GameLogic
{
    private $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Получить информацию обо всех игроках в комнате
     * * @param int $roomId ID комнаты
     * @return array Массив с данными игроков
     */
    private function getPlayersInfo($roomId)
    {
        $members = $this->db->getRoomMembers($roomId); // Этот метод уже есть в DB.php
        $players = [];

        foreach ($members as $member) {
            $cards = [];
            if (!empty($member->cards)) {
                // В room_members.cards храним HEX строки без разделителей
                $cardsString = hex2bin($member->cards);
                if ($cardsString !== false && $cardsString !== '') {
                    $cards = str_split($cardsString, 2); // Режем строку по 2 символа
                }
            }

            $players[] = [
                'memberId' => $member->member_id, // Убедитесь, что getRoomMembers возвращает member_id
                'userId' => $member->user_id, // В DB.php используется user_id, а не id
                'name' => $member->name,
                'balance' => (int)$member->balance,
                'bet' => (int)$member->bet,
                'cards' => $cards,
                'status' => $member->status ?? 'spectator' // player status (player, spectator)
            ];
        }

        return $players;
    }

    /**
     * Получить карты конкретного пользователя
     * * @param int $roomId ID комнаты
     * @param int $userId ID пользователя
     * @return array Массив карт пользователя
     */
    private function getUserCards($roomId, $userId)
    {
        $member = $this->db->getRoomMember($roomId, $userId); 
        
        if (!$member || empty($member->cards)) {
            return [];
        }

        // Аналогично Player::getMemberCards — работаем со строкой из HEX
        $cardsString = hex2bin($member->cards);
        if ($cardsString === false || $cardsString === '') {
            return [];
        }

        return str_split($cardsString, 2);
    }

    /**
     * Получить информацию о таймере хода
     * * @param object $room Объект комнаты
     * @return array|null Информация о таймере или null
     */
    private function getTimer($room)
    {
        if (!$room->current_member_id) {
            return null;
        }

        // Если есть timestamp начала хода
        if (isset($room->turn_start_time)) {
            $turnDuration = 30; // Длительность хода в секундах
            $timeElapsed = time() - strtotime($room->turn_start_time);
            $timeLeft = max(0, $turnDuration - $timeElapsed);

            return [
                'timeLeft' => $timeLeft,
                'totalTime' => $turnDuration
            ];
        }

        // Если timestamp не установлен, просто возвращаем ID текущего игрока
        return [
            'timeLeft' => null,
            'totalTime' => null
        ];
    }

        /**
     * Получить информацию о комнате (игроки, карты, таймер)
     * Работает по принципу long polling с hash-проверкой
     * * @param int $roomId ID комнаты
     * @param int $userId ID пользователя (для получения его карт)
     * @param string $clientHash Hash от клиента для проверки изменений
     * @return array Информация о комнате или пустой массив если hash совпадает
     */
    public function getInfoRoom($roomId, $userId, $clientHash)
    {
        // Получаем текущий hash комнаты
        $room = $this->db->getRoom($roomId);
        if (!$room) {
            return ['error' => 901]; // Комната не найдена
        }
        $currentHash = $room->hash;

        // Если hash совпадает - изменений нет
        if ($currentHash === $clientHash) {
            return true;
        }

        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        
        // Hash не совпадает - отправляем полную информацию
        
        // 1. Получаем всех игроков комнаты
        $players = $this->getPlayersInfo($roomId);

        // 2. Получаем карты текущего пользователя
        $myCards = $this->getUserCards($roomId, $userId);

        // 3. Получаем информацию о таймере хода
        $timer = $this->getTimer($room);

        //...... <= 0

        return [
            'players' => $players,
            'myCards' => $myCards,
            'timer' => $timer,// это ЧИСЛО!!! мазафака
            'hash' => $currentHash,
            'currentPlayerId' => $currentMemberId,
        ];
    }
}