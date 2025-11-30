<?php
/**
 * Класс для игровой логики игрока
 */
class Player
{
    private $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Получить значение карты в очках
     */
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


    /**
     * Парсит строку карт в массив (каждые 2 символа - одна карта)
     */
    private function parseCardsString($cardsString)
    {
        $cardLength = 2; // Длина карты в строке
        
        if (!$cardsString) {
            return [];
        }
        return str_split($cardsString, $cardLength);
    }

    /**
     * Подсчитать сумму очков руки с учетом тузов
     */
    private function calculateHandValue($cards) 
    {
        $maxHandValue = 21;    // Максимальная сумма без перебора
        $aceAdjustment = 10;   // Разница между высоким и низким тузом (11-1)
        $aceSymbol = 'E';      // Символ туза в колоде
        
        $sum = 0;
        $countAces = 0;
        
        foreach ($cards as $card) {
            $sum += $this->getCardValue($card);
            if ($card[0] === $aceSymbol) {
                $countAces++;
            }
        }
        
        // Оптимизация тузов: если перебор, считаем туз за 1 вместо 11
        while ($sum > $maxHandValue && $countAces > 0) {
            $sum -= $aceAdjustment;
            $countAces--;
        }
        
        return $sum;
    }

    /**
     * Взять одну карту из колоды
     */
    private function drawCardFromDeck($roomId)
    {
        $cardLength = 2; // Длина карты в строке
        
        $deck = $this->db->loadDeck($roomId);
        
        // Проверяем, есть ли карты в колоде
        if (strlen($deck) < $cardLength) {
            return ['error' => 810]; // Колода пуста
        }
        
        // Берем первую карту
        $card = substr($deck, 0, $cardLength);
        
        // Сохраняем оставшуюся колоду
        $remainingDeck = substr($deck, $cardLength);
        $this->db->saveDeck($roomId, $remainingDeck);
        
        return $card;
    }

    private function getMemberCards($roomId, $userId)
    {
        $member = $this->db->getRoomMember($roomId, $userId);
        
        if (!$member) {
            return [];
        }
        
        if (!$member->cards) {
            return [];
        }
        
        // Декодируем из HEX формата
        $cardsString = hex2bin($member->cards);
        
        if (!$cardsString) {
            return [];
        }
        
        // Разбиваем строку карт по запятой
        return explode(',', $cardsString);
    }

    private function refreshRoomHash($roomId)
    {
        $hash = md5(microtime() . random_int(0, PHP_INT_MAX));
        $this->db->updateRoomHash($roomId, $hash);
    }

    /**
     * Получить список активных игроков (не зрителей)
     */
    private function getActivePlayers($roomId)
    {
        $playerStatus = 'player'; // Статус активного игрока
        
        $members = $this->db->getRoomMembers($roomId);
        $players = [];
        
        foreach ($members as $member) {
            if ($member->status === $playerStatus) {
                $players[] = $member;
            }
        }
        
        return $players;
    }

    /**
     * Найти следующего игрока в очереди
     */
    private function findNextPlayer($players, $currentMemberId)
    {
        for ($i = 0; $i < count($players); $i++) {
            if ($players[$i]->member_id == $currentMemberId) {
                $nextIndex = $i + 1;
                
                if (isset($players[$nextIndex])) {
                    return $players[$nextIndex];
                }
                
                return null; // Следующего игрока нет
            }
        }
        
        return null;
    }

    /**
     * Передать ход следующему игроку или дилеру
     */
    private function moveToNextPlayer($roomId)
    {
        // Получаем всех активных игроков
        $players = $this->getActivePlayers($roomId);

        // Если нет активных игроков - переходим к ходу дилера
        if (empty($players)) {
            $this->dealerTakeCard($roomId);
            return;
        }

        // Ищем следующего игрока
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        $nextPlayer = $this->findNextPlayer($players, $currentMemberId);
        
        if ($nextPlayer) {
            // Есть следующий игрок - передаем ему ход
            $this->db->setCurrentPlayer($roomId, $nextPlayer->member_id);
        } else {
            // Все игроки сделали ход - переходим к дилеру
            $this->dealerTakeCard($roomId);
        }
    }

    /**
     * Получить карты дилера из комнаты
     */
    private function getDealerCards($roomId)
    {
        $room = $this->db->getRoom($roomId);
        
        if (!$room || !$room->dealerCards) {
            return null;
        }
        
        return $this->parseCardsString($room->dealerCards);
    }

    /**
     * Дилер берет карты пока сумма меньше минимума
     */
    private function dealerDrawCards($roomId, $dealerCards)
    {
        $dealerMinValue = 17; // Минимальная сумма для остановки дилера
        
        $dealerValue = $this->calculateHandValue($dealerCards);
        
        while ($dealerValue < $dealerMinValue) {
            $newCard = $this->drawCardFromDeck($roomId);
            
            // Если колода пуста - прерываем
            if (isset($newCard['error'])) {
                break;
            }
            
            // Добавляем карту дилеру
            $dealerCards[] = $newCard;
            $dealerValue = $this->calculateHandValue($dealerCards);
            
            // Сохраняем обновленные карты дилера
            $this->db->updateDealerCards($roomId, implode('', $dealerCards));
        }
        
        return $dealerCards;
    }

    /**
     * Ход дилера (автоматический)
     */
    private function dealerTakeCard($roomId) 
    {
        // Сбрасываем текущего игрока - теперь ходит дилер
        $this->db->resetCurrentMember($roomId);

        // Получаем карты дилера
        $dealerCards = $this->getDealerCards($roomId);
        if (!$dealerCards) {
            return;
        }

        // Дилер берет карты по правилам (пока сумма < 17)
        $this->dealerDrawCards($roomId, $dealerCards);
        
        // Обновляем хэш для синхронизации клиентов
        $this->refreshRoomHash($roomId);
    }

    /**
     * Проверить, что пользователь в комнате и получить ID комнаты
     */
    private function validateUserInRoom($userId)
    {
        $roomData = $this->db->getRoomId($userId);
        
        if (!$roomData || !$roomData->room_id) {
            return ['error' => 902]; // Пользователь не в комнате
        }
        
        return ['roomId' => $roomData->room_id];
    }

    /**
     * Проверить права игрока (не зритель и его ход)
     */
    private function validatePlayerTurn($roomId, $userId, $member)
    {
        $spectatorStatus = 'spectator'; // Статус зрителя
        
        // Проверяем, что пользователь не зритель
        if ($member->status === $spectatorStatus) {
            return ['error' => 908]; // Зритель не может брать карты
        }
        
        // Проверяем, что сейчас ход этого игрока
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        if ($currentMemberId != $member->member_id) {
            return ['error' => 909]; // Не ваш ход
        }
        
        return true;
    }

    /**
     * Проверить лимит карт у игрока
     */
    private function validateCardsLimit($cards)
    {
        $maxCards = 5; // Максимум карт в руке
        
        if (count($cards) >= $maxCards) {
            return ['error' => 910]; // Максимум карт достигнут
        }
        
        return true;
    }

    /**
     * Обработать взятие карты игроком (сохранить и проверить перебор)
     */
    private function processCardTaken($roomId, $userId, $cards)
    {
        $maxHandValue = 21; // Максимальная сумма без перебора
        
        // Сохраняем обновленные карты
        $this->db->updateMemberCards($roomId, $userId, $cards);
        
        // Проверяем сумму очков
        $handValue = $this->calculateHandValue($cards);
        
        // Если перебор - автоматически передаем ход следующему
        if ($handValue > $maxHandValue) {
            $this->moveToNextPlayer($roomId);
        }
    }

    /**
     * Выдать карту пользователю (основной метод)
     */
    public function takeUserCard($userId)
    {
        // 1. Проверяем, что пользователь в комнате
        $roomValidation = $this->validateUserInRoom($userId);
        if (isset($roomValidation['error'])) {
            return $roomValidation;
        }
        $roomId = $roomValidation['roomId'];
        
        // 2. Получаем данные участника
        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 903]; // Участник не найден
        }
        
        // 3. Проверяем права и ход игрока
        $turnValidation = $this->validatePlayerTurn($roomId, $userId, $member);
        if (is_array($turnValidation)) {
            return $turnValidation;
        }

        // 4. Получаем карты и проверяем лимит
        $cards = $this->getMemberCards($roomId, $userId);
        $limitValidation = $this->validateCardsLimit($cards);
        if (is_array($limitValidation)) {
            return $limitValidation;
        }
        
        // 5. Берем карту из колоды
        $newCard = $this->drawCardFromDeck($roomId);
        if (isset($newCard['error'])) {
            return $newCard;
        }
        
        // 6. Добавляем карту и обрабатываем результат
        $cards[] = $newCard;
        $this->processCardTaken($roomId, $userId, $cards);
        
        // 7. Синхронизация с клиентами
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