<?php

class GameLogic {
    private $db;

    const BET_TIMEOUT_S = 30;
    const ACTION_TIMEOUT_S = 15;
    const FULL_TIMEOUT_S = 600;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Получить информацию о комнате (игроки, карты, таймер)
     * Работает по принципу long polling с hash-проверкой
     */
    public function getInfoRoom($roomId, $userId, $clientHash)
    {
        $this->checkTimeouts($roomId);
        $room = $this->db->getRoom($roomId);
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
    
        if (!$room) {
            return ['error' => 901];
        }
    
        $currentHash = $room->hash;
        $this->db->touchRoom($roomId);
    
        if ($currentHash && $currentHash === $clientHash) {
            return true;
        }
    
        $players = $this->getPlayersInfo($roomId);
        $myCards = $this->getUserCards($roomId, $userId);
        $timer   = $this->getTimer($room);
    
        return [
            'players'         => $players,
            'myCards'         => $myCards,
            'timer'           => $timer,          // число секунд или null
            'status'          => $room->status,   // waiting / waiting_for_bets / playing / closed
            'hash'            => $currentHash,
            'currentPlayerId' => $currentMemberId,
        ];
    }


    private function checkTimeouts($roomId)
    {
        $room = $this->db->getRoom($roomId);
        if (!$room || $room->status === 'closed') {
            return;
        }

        $now = time();
        $members = $this->db->getRoomMembers($roomId);

        if (!$room->last_update) {
            $this->db->touchRoom($roomId);
            return;
        }

        $lastUpdateTimestamp = strtotime($room->last_update);
        if ($lastUpdateTimestamp === false) {
            $this->db->touchRoom($roomId);
            return;
        }

        $timePassedSinceLastUpdate = $now - $lastUpdateTimestamp;

        // Проверка на 10 минут
        if ($timePassedSinceLastUpdate > self::FULL_TIMEOUT_S) {
            $this->db->updateRoomStatus($roomId, 'closed');
            $this->db->cleanRoom($roomId);
            return;
        }

        // Проверка ставки 30 сек
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
        // Проверка 15 сек хода
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
        $members = $this->db->getRoomMembers($roomId);
        $players = [];

        foreach ($members as $member) {
            $cards = [];
            if (!empty($member->cards)) {
                $cardsString = hex2bin($member->cards);
                if ($cardsString !== false && $cardsString !== '') {
                    $cards = str_split($cardsString, 2);
                }
            }

            $players[] = [
                'memberId' => $member->member_id,
                'userId' => $member->user_id,
                'name' => $member->name,
                'balance' => (int)$member->balance,
                'bet' => (int)$member->bet,
                'cards' => $cards,
                'status' => $member->status ?? 'spectator'
            ];
        }

        return $players;
    }

    private function getUserCards($roomId, $userId)
    {
        $member = $this->db->getRoomMember($roomId, $userId);
        if (!$member || empty($member->cards)) {
            return [];
        }

        $cardsString = hex2bin($member->cards);
        if ($cardsString === false || $cardsString === '') {
            return [];
        }

        return str_split($cardsString, 2);
    }

    private function getTimer($room)
    {
        // Фаза ставок
        if (!$room->current_member_id && $room->status === 'waiting_for_bets') {
            $timeElapsed = time() - strtotime($room->last_update);
            return max(0, self::BET_TIMEOUT_S - $timeElapsed);
        }

        // Ход игрока
        if ($room->current_member_id && isset($room->turn_start_time)) {
            $timeElapsed = time() - strtotime($room->turn_start_time);
            return max(0, self::ACTION_TIMEOUT_S - $timeElapsed);
        }

        // Таймера нет
        return null;
    }

}
?>
