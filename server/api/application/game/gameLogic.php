<?php
/**
 * Класс для игровой логики и получения информации о комнате
 */
class GameLogic
{
    private $db;
    
    const BETTING_DURATION = 30;

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
                'timer' => $this->getTimeLeft($room)
            ];
        }

        // Hash не совпадает - отправляем полную информацию
        
        // 1. Получаем всех игроков комнаты
        $players = $this->getPlayersInfo($roomId);

        // 2. Получаем карты текущего пользователя
        $myCards = $this->getUserCards($roomId, $userId);
        
        // 3. Получаем информацию о таймере хода
        // таймер возвращается как число
        $timer = $this->getTimeLeft($room);

        return [
            'players' => $players,
            'myCards' => $myCards,
            'timer' => $timer,
            'hash' => $currentHash,
            'currentPlayerId' => $room->current_member_id
        ];
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
            // Декодируем карты игрока из JSON
            $cards = [];
            if (!empty($member->cards)) {
                $decoded = json_decode($member->cards, true);
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
                'status' => $member->status ?? 'active' // active, folded, waiting
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

        $cards = json_decode($member->cards, true);
        return is_array($cards) ? $cards : [];
    }

    // возвращает время в секундах (int) или 0
    private function getTimeLeft($room)
    {
        if (isset($room->betting_end_time) && $room->betting_end_time > time()) {
            return max(0, $room->betting_end_time - time());
        }
        return 0;
    }

    public function bet($roomId, $userId, $amount)
    {
        // 1. проверка таймера
        $deadline = $this->db->getBettingEndTime($roomId);
        $timeLeft = max(0, $deadline - time());
        
        if ($deadline > 0 && $timeLeft === 0) { 
             return ['error' => 'Time is up']; 
        }

        if (!is_numeric($amount) || $amount <= 0) return ['error' => 242];

        // 2. подготовка карт
        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) return ['error' => 705];

        // проверяем, есть ли уже карты (первая ставка или повышение)
        $isFirstBet = empty($member->cards) || $member->cards === '""' || $member->cards === '[]';
        $cardsArray = [];

        if ($isFirstBet) {
            $deckStr = $this->db->loadDeck($roomId);
            if (strlen($deckStr) < 4) return ['error' => 'Deck is empty'];

            // берем 2 карты с конца строки
            $cardsToGive = substr($deckStr, -4); 
            $newDeckStr = substr($deckStr, 0, -4); 
            $cardsArray = str_split($cardsToGive, 2); 
            
            $this->db->saveDeck($roomId, $newDeckStr);
        } else {
            $cardsArray = json_decode($member->cards, true);
        }

        // 3. транзакция (списание + ставка + статус)
        if (!$this->db->makeBetTransaction($userId, $roomId, $amount)) {
            return ['error' => 804];
        }
        
        // 4. сохранение карт
        if ($isFirstBet) {
            $this->db->updateMemberCards($roomId, $userId, $cardsArray);
        }

        // 5. обновление хэша
        $this->db->updateRoomHash($roomId, md5(microtime()));

        $updatedMember = $this->db->getRoomMember($roomId, $userId);

        return [
            'success' => true,
            'balance' => $updatedMember->balance,
            'bet' => (int)$updatedMember->bet,
            'cards' => $cardsArray,
            'status' => 'player'
        ];
    }

    public function startBettingRound($roomId) {
        $endTime = time() + self::BETTING_DURATION;
        $this->db->setBettingEndTime($roomId, $endTime);
        $this->db->updateRoomHash($roomId, md5(microtime()));
    }

    public static function calculateHandScore($hand) {
        $score = 0;
        $aceCount = 0;
        foreach ($hand as $card) {
            $value = 0;
            $rank = is_string($card) ? substr($card, 0, -1) : (isset($card->rank) ? $card->rank : 0);
            
            switch ($rank) {
                case 'A': $value = 11; break;
                case 'K': case 'Q': case 'J': case '10': $value = 10; break;
                default: $value = (int)$rank; break;
            }
            
            $score += $value;
            if ($value === 11) $aceCount++;
        }
        while ($score > 21 && $aceCount > 0) {
            $score -= 10;
            $aceCount--;
        }
        return $score;
    }
}
?>
