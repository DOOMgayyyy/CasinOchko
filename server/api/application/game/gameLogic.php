<?php
/**
 * Класс для игровой логики и получения информации о комнате
 */
class GameLogic
{
    private $db;
    const BET_TIMEOUT_S = 30;
    const ACTION_TIMEOUT_S = 15;
    const FULL_TIMEOUT_S = 600;

    function __construct($db)
    {
        $this->db = $db;
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
        $this->checkTimeouts($roomId);
        $room = $this->db->getRoom($roomId);
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        if (!$room) {
            return ['error' => 901]; // Комната не найдена
        }

        $currentHash = $room->hash;

        // Если hash совпадает - изменений нет
        if ($currentHash && $currentHash === $clientHash) {
            return [
                'players' => [],
                'myCards' => [],
                'timer' => null,
                'hash' => $clientHash,
                'currentPlayerId' => $currentMemberId,
                'changed' => false
            ];
        }

        // Hash не совпадает - отправляем полную информацию
        
        // 1. Получаем всех игроков комнаты
        $players = $this->getPlayersInfo($roomId);

        // 2. Получаем карты текущего пользователя
        $myCards = $this->getUserCards($roomId, $userId);

        // 3. Получаем информацию о таймере хода
        $timer = $this->getTimer($room);

        return [
            'players' => $players,
            'myCards' => $myCards,
            'timer' => $timer,
            'hash' => $currentHash,
            'currentPlayerId' => $currentMemberId,
            'changed' => true
        ];
    }

    /**
     * Получить информацию обо всех игроках в комнате
     * * @param int $roomId ID комнаты
     * @return array Массив с данными игроков
     */
    private function checkTimeouts($roomId)
    {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status === 'closed') {
            return;
        }

        $now = time();
        $members = $this->db->getRoomMembers($roomId);
        
        $lastUpdateTimestamp = strtotime($room->last_update);
        $timePassedSinceLastUpdate = $now - $lastUpdateTimestamp;

        //проверка на 10мин
        if ($timePassedSinceLastUpdate > self::FULL_TIMEOUT_S) {
            $this->db->updateRoomStatus($roomId, 'closed');
            $this->db->cleanRoom($roomId);
            return;
        }

        //проверка ставки 30сек
        
        if (!$room->current_member_id && $room->status === 'waiting_for_bets') { 
            
            $betPhaseExpired = ($timePassedSinceLastUpdate > self::BET_TIMEOUT_S);
            
            if ($betPhaseExpired) {
                $playersLeft = 0;
                
                foreach ($members as $member) {
                    if ($member->status === 'player' && (int)$member->bet === 0) {
                        $this->db->updateMemberStatus($roomId, $member->user_id, 'spectator');
                    }
                    if ($member->status !== 'spectator') {
                         $playersLeft++;
                    }
                }
                 
                $this->db->updateRoomAction($roomId); 

                if ($playersLeft < 1) { 
                     $this->db->updateRoomStatus($roomId, 'closed');
                     $this->db->cleanRoom($roomId);
                }
            }
        } 
        
        //проверка 15 сек хода
        elseif ($room->current_member_id && $room->status === 'playing') {
            
            $currentPlayer = null;
            foreach ($members as $member) {
                if ($member->member_id == $room->current_member_id) {
                    $currentPlayer = $member;
                    break;
                }
            }

            if ($currentPlayer) {
                $turnStartTime = strtotime($room->turn_start_time);
                $timePassed = $now - $turnStartTime;

                if ($timePassed > self::ACTION_TIMEOUT_S) {
                    $this->db->resetCurrentMember($roomId); 
                    $this->db->updateRoomAction($roomId); 
                }
            }
        }
    }


    private function getPlayersInfo($roomId)
    {
        $members = $this->db->getRoomMembers($roomId); // Этот метод уже есть в DB.php
        $players = [];

        foreach ($members as $member) {
            // Декодируем карты игрока из JSON
            $cards = [];
            if (!empty($member->cards)) {
                if (ctype_xdigit($member->cards)) {
                     $decoded_str = hex2bin($member->cards);
                     $decoded = explode(',', $decoded_str);
                } else {
                    $decoded = json_decode($member->cards, true);
                }

                if (is_array($decoded)) {
                    $cards = $decoded;
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
        // Этот метод нужно добавить в DB.php
        $member = $this->db->getRoomMember($roomId, $userId); 
        
        if (!$member || empty($member->cards)) {
            return [];
        }

        $cards_data = $member->cards;
        
        if (ctype_xdigit($cards_data)) {
             $decoded_str = hex2bin($cards_data);
             $cards = explode(',', $decoded_str);
        } else {
            $cards = json_decode($cards_data, true);
        }

        return is_array($cards) ? $cards : [];
    }

    /**
     * Получить информацию о таймере хода
     * * @param object $room Объект комнаты
     * @return array|null Информация о таймере или null
     */
    private function getTimer($room)
    {
        if (!$room->current_member_id) {
            if ($room->status === 'waiting_for_bets') {
                $timeElapsed = time() - strtotime($room->last_update);
                $timeLeft = max(0, self::BET_TIMEOUT_S - $timeElapsed);

                return [
                    'currentPlayerId' => null,
                    'timeLeft' => $timeLeft,
                    'totalTime' => self::BET_TIMEOUT_S,
                    'isBetPhase' => true
                ];
            }
            return null;
        }

        // Если есть timestamp начала хода
        if (isset($room->turn_start_time)) {
            $turnDuration = self::ACTION_TIMEOUT_S;
            $timeElapsed = time() - strtotime($room->turn_start_time);
            $timeLeft = max(0, $turnDuration - $timeElapsed);

            return [
                'timeLeft' => $timeLeft,
                'totalTime' => $turnDuration,
                'isBetPhase' => false
            ];
        }

        // Если timestamp не установлен, просто возвращаем ID текущего игрока
        return [
            'currentPlayerId' => $room->current_member_id,
            'timeLeft' => null,
            'totalTime' => null,
            'isBetPhase' => false
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