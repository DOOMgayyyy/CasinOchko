<?php

class Player {
    private $db;
    private $deck;

    const MIN_BET = 50;
    const MAX_CARDS = 5;

    public function __construct($db, $deck = null) {
        $this->db = $db;
        $this->deck = $deck ?: new Deck($db);
    }

    // ============================================================
    // BETTING PHASE
    // ============================================================

    public function makeBet($roomId, $userId, $betAmount) {
        if ($betAmount < self::MIN_BET) {
            return ['error' => 813];
        }
        
        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 902];
        }
        
        $currentBet = $member->bet ? $member->bet : 0;
        $newTotalBet = $currentBet + $betAmount;
        
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return ['error' => 705];
        }
        
        if ($user->balance < $betAmount) {
            return ['error' => 804];
        }
        
        $room = $this->db->getRoom($roomId);
        if (!$room || ($room->status !== 'waiting' && $room->status !== 'waiting_for_bets')) {
            return ['error' => 814];
        }
        
        try {
            $this->db->updateMemberBet($roomId, $userId, $newTotalBet);
            $this->db->updateMemberStatus($roomId, $userId, 'player');
            $this->db->updateBalance($userId, -$betAmount);
            
            // Если это первая ставка, переводим комнату в waiting_for_bets
            if ($room->status === 'waiting') {
                $this->db->updateRoomStatus($roomId, 'waiting_for_bets');
            } else {
                $this->db->updateRoomAction($roomId);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['error' => 9000, 'message' => $e->getMessage()];
        }
    }


    // ============================================================
    // GAME START
    // ============================================================

    public function startGame($roomId) {
        if (!$this->deck->reinitializeDeck($roomId)) {
            return ['success' => false, 'error' => 817];
        }

        try {
            $members = $this->db->getRoomMembers($roomId);
            $playingMembers = array_filter($members, function($m) {
                return $m->status === 'player' && $m->bet > 0;
            });

            if (empty($playingMembers)) {
                return ['success' => false, 'error' => 818];
            }

            // Раздаем карты игрокам
            foreach ($playingMembers as $member) {
                $card1 = $this->deck->getCard($roomId);
                $card2 = $this->deck->getCard($roomId);
                if ($card1 === false || $card2 === false) {
                    return ['success' => false, 'error' => 810];
                }

                $this->db->updateMemberCards($roomId, $member->user_id, [$card1, $card2]);
            }

            // Дилер берет карту
            $dealerCard = $this->deck->getCard($roomId);
            if ($dealerCard === false) {
                return ['success' => false, 'error' => 810];
            }

            $this->db->updateDealerCards($roomId, $dealerCard);

            // проверка на туз или 10/фигуру у дилера (потенциальный блэкджек)
            $dealerFirstValue = $dealerCard[0];
            $checkDealerBlackjack = ($dealerFirstValue === 'E' || $dealerFirstValue === 'A' || 
                                     $dealerFirstValue === 'B' || $dealerFirstValue === 'C' || 
                                     $dealerFirstValue === 'D');

            if ($checkDealerBlackjack) {
                $dealerSecondCard = $this->deck->getCard($roomId);
                if ($dealerSecondCard !== false) {
                    $dealerFullCards = [$dealerCard, $dealerSecondCard];
                    if (self::isBlackjack($dealerFullCards)) {
                        // Блэкджек дилера - немедленное завершение
                        $this->db->updateDealerCards($roomId, $dealerFullCards);
                        $this->db->updateRoomStatus($roomId, 'playing');
                        $this->calculateAndPayResults($roomId);
                        $this->startNewRound($roomId);
                        return ['success' => true, 'dealerBlackjack' => true];
                    }
                    // Нет блэкджека - скрываем вторую карту (не сохраняем)
                }
            }

            // Находим первого игрока, который может ходить
            $firstPlayer = null;
            foreach ($playingMembers as $member) {
                $cards = $this->db->getRoomMember($roomId, $member->user_id);
                if ($cards) {
                    $playerCards = $this->parseCards($cards->cards);
                    // пропускаем игроков с блэкджеком
                    if (!self::isBlackjack($playerCards)) {
                        $firstPlayer = $member;
                        break;
                    }
                }
            }

            // Если все игроки с блэкджеком - сразу к дилеру
            if (!$firstPlayer) {
                $this->db->updateRoomStatus($roomId, 'playing');
                $this->dealerTakeCard($roomId);
                $this->calculateAndPayResults($roomId);
                $this->startNewRound($roomId);
                return ['success' => true, 'allBlackjack' => true];
            }

            $this->db->setCurrentPlayer($roomId, $firstPlayer->member_id);
            $this->db->updateRoomStatus($roomId, 'playing');
            $this->db->updateRoomAction($roomId);
            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ============================================================
    // SCORE CALCULATION
    // ============================================================

    public static function calculateScore($cards) {
        if (empty($cards)) {
            return 0;
        }

        $score = 0;
        $aces = 0;

        foreach ($cards as $card) {
            if (empty($card) || strlen($card) < 2) continue;

            $value = $card[0];
            if ($value === 'E') {
                $aces++;
                $score += 11;
            } elseif ($value === 'B' || $value === 'C' || $value === 'D') {
                $score += 10;
            } elseif ($value === 'A') {
                $score += 10;
            } else {
                $score += (int)$value;
            }
        }

        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces--;
        }

        return $score;
    }

    public static function isBust($cards) {
        return self::calculateScore($cards) > 21;
    }

    public static function isBlackjack($cards) {
        return count($cards) === 2 && self::calculateScore($cards) === 21;
    }

    // ============================================================
    // PLAYER ACTIONS
    // ============================================================

    public function takeUserCard($roomId, $userId) {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'playing') {
            return ['error' => 815];
        }

        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 902];
        }

        if ($member->status !== 'player') {
            return ['error' => 908];
        }

        if ($member->member_id != $room->current_member_id) {
            return ['error' => 909];
        }

        $currentCards = $this->parseCards($member->cards);
        if (count($currentCards) >= self::MAX_CARDS) {
            return ['error' => 910];
        }

        $newCard = $this->deck->getCard($roomId);
        if ($newCard === false) {
            return ['error' => 810];
        }

        $currentCards[] = $newCard;
        $this->db->updateMemberCards($roomId, $userId, $currentCards);

        // проверка на 21 очко, а не только на перебор
        $currentScore = self::calculateScore($currentCards);
        $shouldPass = $currentScore >= 21 || count($currentCards) >= self::MAX_CARDS;

        if ($shouldPass) {
            $this->moveToNextPlayer($roomId);
        } else {
            $this->db->updateRoomAction($roomId);
        }

        return [
            'success' => true,
            'card' => $newCard,
            'shouldPass' => $shouldPass
        ];
    }

    private function moveToNextPlayer($roomId) {
        $members = $this->db->getRoomMembers($roomId);
        $room = $this->db->getRoom($roomId);

        $playingMembers = array_filter($members, function($m) {
            return $m->status === 'player' && $m->bet > 0;
        });

        if (empty($playingMembers)) {
            // Нет игроков - завершаем раунд
            $this->finishRound($roomId);
            return;
        }

        // Находим текущего игрока
        $currentIndex = -1;
        foreach ($playingMembers as $index => $member) {
            if ($member->member_id == $room->current_member_id) {
                $currentIndex = $index;
                break;
            }
        }

        $playingArray = array_values($playingMembers);

        // Ищем следующего активного игрока
        for ($i = $currentIndex + 1; $i < count($playingArray); $i++) {
            $nextMember = $playingArray[$i];
            $cards = $this->parseCards($nextMember->cards);
            $score = self::calculateScore($cards);

            //пропускаем игроков с перебором, 21 очком или максимумом карт
            if ($score < 21 && count($cards) < self::MAX_CARDS) {
                $this->db->setCurrentPlayer($roomId, $nextMember->member_id);
                $this->db->updateRoomAction($roomId);
                return;
            }
        }

        // Все игроки закончили - ход дилера
        $this->db->resetCurrentMember($roomId);
        $this->dealerTakeCard($roomId);
        $this->calculateAndPayResults($roomId);
    }
    
    private function finishRound($roomId) {
        $this->calculateAndPayResults($roomId);
        
    }

    public function pass($roomId, $userId) {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'playing') {
            return ['error' => 815];
        }

        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 902];
        }

        if ($member->status !== 'player') {
            return ['error' => 908];
        }

        if ($member->member_id != $room->current_member_id) {
            return ['error' => 909];
        }

        $this->moveToNextPlayer($roomId);
        return ['success' => true];
    }

    public function doubleBet($roomId, $userId) {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'playing') {
            return ['error' => 815];
        }

        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 902];
        }

        if ($member->status !== 'player') {
            return ['error' => 908];
        }

        if ($member->member_id != $room->current_member_id) {
            return ['error' => 909];
        }

        $currentCards = $this->parseCards($member->cards);
        if (count($currentCards) != 2) {
            return ['error' => 816];
        }

        if ($member->balance < $member->bet) {
            return ['error' => 804];
        }

        $this->db->updateMemberBet($roomId, $userId, $member->bet * 2);
        $this->db->updateBalance($userId, -$member->bet);

        $newCard = $this->deck->getCard($roomId);
        if ($newCard === false) {
            return ['error' => 810];
        }

        $currentCards[] = $newCard;
        $this->db->updateMemberCards($roomId, $userId, $currentCards);
        $this->db->updateRoomAction($roomId);
        $this->moveToNextPlayer($roomId);

        return [
            'success' => true,
            'card' => $newCard,
            'shouldPass' => true
        ];
    }

    // ============================================================
    // DEALER LOGIC
    // ============================================================

    public function dealerTakeCard($roomId) {
        $room = $this->db->getRoom($roomId);
        if (!$room) {
            return ['error' => 901];
        }

        $dealerCards = $this->parseCards($room->dealerCards);
        
        while (self::calculateScore($dealerCards) < 17) {
            $newCard = $this->deck->getCard($roomId);
            if ($newCard === false) break;
            $dealerCards[] = $newCard;
        }
        
        $this->db->updateDealerCards($roomId, $dealerCards);
        return ['success' => true];
    }
    
    public function calculateAndPayResults($roomId) {
        $room = $this->db->getRoom($roomId);
        if (!$room) {
            return ['success' => false, 'error' => 901];
        }
        
        $dealerCards = $this->parseCards($room->dealerCards);
        $dealerScore = self::calculateScore($dealerCards);
        $dealerBust = $dealerScore > 21;
        $dealerBlackjack = self::isBlackjack($dealerCards);
        
        $members = $this->db->getRoomMembers($roomId);
        
        foreach ($members as $member) {
            if ($member->status !== 'player' || $member->bet == 0) {
                continue;
            }
            
            $playerCards = $this->parseCards($member->cards);
            $playerScore = self::calculateScore($playerCards);
            $playerBust = $playerScore > 21;
            $playerBlackjack = self::isBlackjack($playerCards);
            
            $winAmount = 0;
            $resultStatus = 'lose';
            
            if ($playerBust) {
                // Игрок перебрал - проигрыш
                $resultStatus = 'bust';
                $winAmount = 0;
            } elseif ($playerBlackjack && $dealerBlackjack) {
                // Оба блэкджек - возврат ставки (push)
                $resultStatus = 'push';
                $winAmount = $member->bet;
            } elseif ($playerBlackjack) {
                // Блэкджек игрока - выплата 3:2
                $resultStatus = 'blackjack';
                $winAmount = $member->bet * 2.5;
            } elseif ($dealerBust) {
                // Дилер перебрал - игрок выигрывает
                $resultStatus = 'win';
                $winAmount = $member->bet * 2;
            } elseif ($playerScore > $dealerScore) {
                // Игрок больше - выигрыш
                $resultStatus = 'win';
                $winAmount = $member->bet * 2;
            } elseif ($playerScore == $dealerScore) {
                // Ничья (push) - возврат ставки
                $resultStatus = 'push';
                $winAmount = $member->bet;
            } else {
                $resultStatus = 'lose';
                $winAmount = 0;
            }
            
            // Устанавливаем статус результата
            $this->db->updateMemberStatus($roomId, $member->user_id, $resultStatus);
            
            // Начисляем выигрыш
            if ($winAmount > 0) {
                $this->db->updateBalance($member->user_id, $winAmount);
            }
        }
        
        // Переводим комнату в фазу показа результатов
        $this->db->updateRoomStatus($roomId, 'show_results');
        $this->db->updateRoomAction($roomId);
        
        return ['success' => true];
    }
    
    public function startNewRound($roomId) {
        $members = $this->db->getRoomMembers($roomId);
        
        foreach ($members as $member) {
            // Очищаем карты и ставки
            $this->db->updateMemberCards($roomId, $member->user_id, []);
            $this->db->updateMemberBet($roomId, $member->user_id, 0);
            
            // Сбрасываем всех в spectator (включая win/lose/blackjack/push/bust)
            $this->db->updateMemberStatus($roomId, $member->user_id, 'spectator');
        }
        
        $this->db->updateDealerCards($roomId, '');
        $this->db->setCurrentPlayer($roomId, null);
        $this->db->updateRoomStatus($roomId, 'waiting');
        $this->db->updateRoomAction($roomId);
        
        return ['success' => true];
    }
    
    // ============================================================
    // HELPERS
    // ============================================================
    
    private function parseCards($cardsString) {
        if (empty($cardsString)) {
            return [];
        }
        return str_split($cardsString, 2);
    }
}
?>
