<?php
/**
 * Класс для игровой логики игрока
 */
class Player
{
    private $db;

    /* Важные настройки и статусы выносим в константы */
    private const CARD_LENGTH      = 2;        # Длина карты в строке (2 символа)
    private const MAX_HAND_VALUE   = 21;       # Максимальная сумма без перебора
    private const MAX_CARDS        = 5;        # Максимум карт в руке
    private const DEALER_MIN_VALUE = 17;       # Минимальная сумма для остановки дилера
    private const ACE_ADJUSTMENT   = 10;       # Разница между высоким и низким тузом (11-1)
    private const ACE_SYMBOL       = 'E';      # Символ туза в колоде

    private const STATUS_PLAYER    = 'player';
    private const STATUS_SPECTATOR = 'spectator';

    function __construct($db)
    {
        $this->db = $db;
    }

    private function getCardValue($card) 
    {
        $values = [
            '2' => 2, 
            '3' => 3, 
            '4' => 4, 
            '5' => 5, 
            '6' => 6,
            '7' => 7, 
            '8' => 8, 
            '9' => 9, 
            'A' => 10,
            'B' => 10, 
            'C' => 10, 
            'D' => 10, 
            'E' => 11
        ];

        return $values[$card[0]] ?? 0;
    }

    private function calculateHandValue($cards) 
    {
        $sum = 0;
        $countAces = 0;
        
        foreach ($cards as $card) {
            $sum += $this->getCardValue($card);
            if ($card[0] === self::ACE_SYMBOL) {
                $countAces++;
            }
        }
        
        # Оптимизация тузов: если перебор, считаем туз за 1 вместо 11
        while ($sum > self::MAX_HAND_VALUE && $countAces > 0) {
            $sum -= self::ACE_ADJUSTMENT;
            $countAces--;
        }
        
        return $sum;
    }

    private function drawCardFromDeck($roomId)
    {
        $card = $this->db->drawCardAtomic($roomId, self::CARD_LENGTH);
        
        if (!$card) {
            return ['error' => 810]; 
        }
        
        return $card;
    }

    private function getMemberCards($roomId, $userId)
    {
        $member = $this->db->getRoomMember($roomId, $userId);
        
        if (!$member || !$member->cards) {
            return [];
        }
        
        $cardsString = hex2bin($member->cards);   # В БД храним HEX

        if ($cardsString === false || $cardsString === '') {
            return [];
        }

        # Работаем везде со строкой карт без разделителей — режем по 2 символа
        return str_split($cardsString, self::CARD_LENGTH);
    }

    private function refreshRoomHash($roomId)
    {
        $hash = md5(microtime() . random_int(0, PHP_INT_MAX));
        $this->db->updateRoomHash($roomId, $hash);
    }

    private function getActivePlayers($roomId)
    {
        $members = $this->db->getRoomMembers($roomId);
        $players = [];
        
        foreach ($members as $member) {
            if ($member->status === self::STATUS_PLAYER) {
                $players[] = $member;
            }
        }
        
        return $players;
    }

    private function findNextPlayer($players, $currentMemberId)
    {
        for ($i = 0; $i < count($players); $i++) {
            if ($players[$i]->member_id == $currentMemberId) {
                $nextIndex = $i + 1;
                
                if (isset($players[$nextIndex])) {
                    return $players[$nextIndex];
                }
                
                return null;   # Следующего игрока нет
            }
        }
        
        return null;
    }

    private function moveToNextPlayer($roomId)
    {
        $players = $this->getActivePlayers($roomId);

        if (empty($players)) {
            $this->dealerTakeCard($roomId);
            return;
        }

        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        $nextPlayer = $this->findNextPlayer($players, $currentMemberId);
        
        if ($nextPlayer) {
            $this->db->setCurrentPlayer($roomId, $nextPlayer->member_id);
        } else {
            $this->dealerTakeCard($roomId);
        }
    }

    private function getDealerCards($roomId)
    {
        $room = $this->db->getRoom($roomId);
        
        if (!$room || !$room->dealerCards) {
            return null;
        }
        
        $cards = str_split($room->dealerCards, self::CARD_LENGTH);
        
        return empty($cards) ? null : $cards;
    }

    private function dealerDrawCards($roomId, $dealerCards)
    {
        $dealerValue = $this->calculateHandValue($dealerCards);
        
        while ($dealerValue < self::DEALER_MIN_VALUE) {
            $newCard = $this->drawCardFromDeck($roomId);
            
            if (isset($newCard['error'])) {
                break;
            }
            
            $dealerCards[] = $newCard;
            $dealerValue = $this->calculateHandValue($dealerCards);
            
            $this->db->updateDealerCards($roomId, implode('', $dealerCards));
        }
        
        return $dealerCards;
    }

    private function dealerTakeCard($roomId) 
    {
        $this->db->resetCurrentMember($roomId);

        $dealerCards = $this->getDealerCards($roomId);
        if (!$dealerCards) {
            return;
        }

        $this->dealerDrawCards($roomId, $dealerCards);
        
        $this->refreshRoomHash($roomId);
    }

    private function validateUserInRoom($userId)
    {
        $roomData = $this->db->getRoomId($userId);
        
        if (!$roomData || !$roomData->room_id) {
            return ['error' => 902]; 
        }
        
        return ['roomId' => $roomData->room_id];
    }

    private function validatePlayerTurn($roomId, $userId, $member)
    {
        if ($member->status === self::STATUS_SPECTATOR) {
            return ['error' => 908]; 
        }
        
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        if ($currentMemberId != $member->member_id) {
            return ['error' => 909]; 
        }
        
        return true;
    }

    private function validateCardsLimit($cards)
    {
        if (count($cards) >= self::MAX_CARDS) {
            return ['error' => 910]; 
        }
        
        return true;
    }

    private function processCardTaken($roomId, $userId, $cards)
    {
        $this->db->updateMemberCards($roomId, $userId, $cards);
        
        $handValue = $this->calculateHandValue($cards);
        
        if ($handValue > self::MAX_HAND_VALUE) {
            $this->moveToNextPlayer($roomId);
        }
    }

    public function takeUserCard($userId)
    {
        # Проверка, что пользователь находится в комнате
        $roomValidation = $this->validateUserInRoom($userId);
        if (isset($roomValidation['error'])) {
            return $roomValidation;
        }
        $roomId = $roomValidation['roomId'];
        
        # Получение данных участника
        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 903]; 
        }
        
        # Проверка права и хода игрока
        $turnValidation = $this->validatePlayerTurn($roomId, $userId, $member);
        if (is_array($turnValidation)) {
            return $turnValidation;
        }

        # Получение карт и проверка лимита
        $cards = $this->getMemberCards($roomId, $userId);
        $limitValidation = $this->validateCardsLimit($cards);
        if (is_array($limitValidation)) {
            return $limitValidation;
        }
        
        # Берем карту из колоды
        $newCard = $this->drawCardFromDeck($roomId);
        if (isset($newCard['error'])) {
            return $newCard;
        }
        
        # Добавление карты и обработка результата
        $cards[] = $newCard;
        $this->processCardTaken($roomId, $userId, $cards);
        
        # Синхронизация с клиентами
        $this->refreshRoomHash($roomId);
        
        return true;
    }

    /**
     * Вспомогательная функция раздачи ОДНОЙ карты конкретному участнику (без проверок хода).
     * Используется для стартовой раздачи, когда нужно пройтись по всем игрокам.
     */
    private function giveCardToMember($roomId, $member)
    {
        $cards = $this->getMemberCards($roomId, $member->user_id);

        $limitValidation = $this->validateCardsLimit($cards);
        if (is_array($limitValidation)) {
            return $limitValidation;
        }

        $newCard = $this->drawCardFromDeck($roomId);
        if (isset($newCard['error'])) {
            return $newCard;
        }

        $cards[] = $newCard;
        $this->processCardTaken($roomId, $member->user_id, $cards);

        return true;
    }

    /**
     * Стартовая раздача: проходим по всем активным игрокам, начиная с current_member,
     * и выдаём каждому по одной карте.
     */
    public function dealCardsToPlayers($roomId)
    {
        $players = $this->getActivePlayers($roomId);

        if (empty($players)) {
            return ['error' => 911]; // Нет активных игроков
        }

        $currentMemberId = $this->db->getCurrentMemberId($roomId);

        // Если current_member не задан, начинаем с первого игрока в списке
        $startIndex = 0;
        foreach ($players as $index => $player) {
            if ($player->member_id == $currentMemberId) {
                $startIndex = $index;
                break;
            }
        }

        $playersCount = count($players);

        for ($i = 0; $i < $playersCount; $i++) {
            $idx = ($startIndex + $i) % $playersCount;
            $result = $this->giveCardToMember($roomId, $players[$idx]);

            if (is_array($result) && isset($result['error'])) {
                return $result;
            }
        }

        $this->refreshRoomHash($roomId);

        return true;
    }

    /**
     * Выдать одну карту дилеру и сразу посчитать значение его руки.
     * Возвращаем флаг перебора и текущую сумму.
     */
    public function dealCardToDealer($roomId)
    {
        $dealerCards = $this->getDealerCards($roomId);
        if (!$dealerCards) {
            $dealerCards = [];
        }

        $newCard = $this->drawCardFromDeck($roomId);
        if (isset($newCard['error'])) {
            return $newCard;
        }

        $dealerCards[] = $newCard;

        $this->db->updateDealerCards($roomId, implode('', $dealerCards));

        $value = $this->calculateHandValue($dealerCards);

        $this->refreshRoomHash($roomId);

        return [
            'bust'  => $value > self::MAX_HAND_VALUE,
            'value' => $value,
        ];
    }

    public function makeBet()
    {

    }
    public function getCard()
    {

    }
    public function pass()
    {

    }

}