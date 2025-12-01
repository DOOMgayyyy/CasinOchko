<?php
/**
 * Класс для игровой логики игрока
 */
class Player
{
    private $db;
    
    private $cardLength = 2;           # Длина карты в строке (2 символа)
    private $maxHandValue = 21;        # Максимальная сумма без перебора
    private $maxCards = 5;             # Максимум карт в руке
    private $dealerMinValue = 17;      # Минимальная сумма для остановки дилера
    private $aceAdjustment = 10;       # Разница между высоким и низким тузом (11-1)
    private $aceSymbol = 'E';          # Символ туза в колоде
    
    private $statusPlayer = 'player';     
    private $statusSpectator = 'spectator'; 

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
            if ($card[0] === $this->aceSymbol) {
                $countAces++;
            }
        }
        
        # Оптимизация тузов: если перебор, считаем туз за 1 вместо 11
        while ($sum > $this->maxHandValue && $countAces > 0) {
            $sum -= $this->aceAdjustment;
            $countAces--;
        }
        
        return $sum;
    }

    private function drawCardFromDeck($roomId)
    {
        $card = $this->db->drawCardAtomic($roomId, $this->cardLength);
        
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
        
        $cardsString = hex2bin($member->cards);
        
        if (!$cardsString) {
            return [];
        }
        
        return explode(',', $cardsString);
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
            if ($member->status === $this->statusPlayer) {
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
        
        $cards = str_split($room->dealerCards, $this->cardLength);
        
        return empty($cards) ? null : $cards;
    }

    private function dealerDrawCards($roomId, $dealerCards)
    {
        $dealerValue = $this->calculateHandValue($dealerCards);
        
        while ($dealerValue < $this->dealerMinValue) {
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
        if ($member->status === $this->statusSpectator) {
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
        if (count($cards) >= $this->maxCards) {
            return ['error' => 910]; 
        }
        
        return true;
    }

    private function processCardTaken($roomId, $userId, $cards)
    {
        $this->db->updateMemberCards($roomId, $userId, $cards);
        
        $handValue = $this->calculateHandValue($cards);
        
        if ($handValue > $this->maxHandValue) {
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