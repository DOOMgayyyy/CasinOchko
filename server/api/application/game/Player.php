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
            return ['error' => 'MIN_BET', 'message' => 'Минимальная ставка: ' . self::MIN_BET];
        }

        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member) {
            return ['error' => 902];
        }

        // Получаем текущую ставку участника
        $currentBet = $member->bet ? $member->bet : 0;
        // Вычисляем новую общую ставку
        $newTotalBet = $currentBet + $betAmount;

        // Получаем актуальный баланс пользователя из базы
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return ['error' => 705];
        }

        // Проверяем, достаточно ли средств для новой ставки
        if ($user->balance < $betAmount) {
            return ['error' => 804];
        }

        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'waiting') {
            return ['error' => 'ROOM_NOT_WAITING', 'message' => 'Ставки больше не принимаются'];
        }

        try {
            $this->db->updateMemberBet($roomId, $userId, $newTotalBet);
            $this->db->updateMemberStatus($roomId, $userId, 'player');
            $this->db->updateBalance($userId, -$betAmount);
            $this->db->updateRoomAction($roomId);
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
            return ['success' => false, 'error' => 'DECK_ERROR'];
        }

        try {
            $members = $this->db->getRoomMembers($roomId);
            $playingMembers = array_filter($members, function($m) {
                return $m->status === 'player' && $m->bet > 0;
            });

            if (empty($playingMembers)) {
                return ['success' => false, 'error' => 'NO_PLAYERS'];
            }

            foreach ($playingMembers as $member) {
                $card1 = $this->deck->getCard($roomId);
                $card2 = $this->deck->getCard($roomId);
                if ($card1 === false || $card2 === false) {
                    return ['success' => false, 'error' => 'NO_DECK'];
                }

                $this->db->updateMemberCards($roomId, $member->user_id, [$card1, $card2]);
            }

            $dealerCard = $this->deck->getCard($roomId);
            if ($dealerCard === false) {
                return ['success' => false, 'error' => 'NO_DECK'];
            }

            $this->db->updateDealerCards($roomId, $dealerCard);
            $firstPlayer = reset($playingMembers);
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
            return ['error' => 'ROOM_NOT_PLAYING'];
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
        $this->db->updateRoomAction($roomId);

        $shouldPass = self::isBust($currentCards);
        return [
            'success' => true,
            'card' => $newCard,
            'shouldPass' => $shouldPass
        ];
    }

    public function pass($roomId, $userId) {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'playing') {
            return ['error' => 'ROOM_NOT_PLAYING'];
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

        return ['success' => true];
    }

    public function doubleBet($roomId, $userId) {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status !== 'playing') {
            return ['error' => 'ROOM_NOT_PLAYING'];
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
            return ['error' => 'CANNOT_DOUBLE', 'message' => 'Удвоение только с двумя картами'];
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
        // Передаем массив, метод updateDealerCards сам преобразует в строку
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

        $members = $this->db->getRoomMembers($roomId);
        foreach ($members as $member) {
            if ($member->status !== 'player' || $member->bet == 0) {
                continue;
            }

            $playerCards = $this->parseCards($member->cards);
            $playerScore = self::calculateScore($playerCards);
            $playerBust = $playerScore > 21;

            $winAmount = 0;
            if ($playerBust) {
                $winAmount = 0;
            } elseif ($dealerBust) {
                $winAmount = $member->bet * 2;
            } elseif ($playerScore > $dealerScore) {
                if (self::isBlackjack($playerCards)) {
                    $winAmount = $member->bet * 2.5;
                } else {
                    $winAmount = $member->bet * 2;
                }
            } elseif ($playerScore == $dealerScore) {
                $winAmount = $member->bet;
            }

            if ($winAmount > 0) {
                $this->db->updateBalance($member->user_id, $winAmount);
            }
        }

        $this->db->updateRoomAction($roomId);
        return ['success' => true];
    }

    public function startNewRound($roomId) {
        $members = $this->db->getRoomMembers($roomId);
        foreach ($members as $member) {
            $this->db->updateMemberCards($roomId, $member->user_id, []);
            $this->db->updateMemberBet($roomId, $member->user_id, 0);
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
