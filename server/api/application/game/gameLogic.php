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

        // Обновляем last_update ТОЛЬКО в режиме ожидания (для проверки активности)
        // НЕ обновляем во время фазы ставок и игры, чтобы таймеры работали!
        if ($room->status === 'waiting') {
            $this->db->touchRoom($roomId);
        }

        if ($currentHash && $currentHash === $clientHash) {
            return true;
        }

        $players = $this->getPlayersInfo($roomId);
        $myCards = $this->getUserCards($roomId, $userId);
        $timer = $this->getTimer($room);

        return [
            'players' => $players,
            'myCards' => $myCards,
            'userId' => $userId,
            'timer' => $timer,
            'status' => $room->status,
            'hash' => $currentHash,
            'currentMemberId' => $currentMemberId,
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
            // УБРАТЬ touchRoom отсюда!
            return;
        }

        $timePassedSinceLastUpdate = $now - $lastUpdateTimestamp;

        // Проверка на 10 минут бездействия
        if ($timePassedSinceLastUpdate > self::FULL_TIMEOUT_S) {
            $this->db->updateRoomStatus($roomId, 'closed');
            $this->db->cleanRoom($roomId);
            return;
        }

        // Проверка фазы ставок (30 сек)
        if (!$room->current_member_id && $room->status === 'waiting_for_bets') {
            $betPhaseExpired = ($timePassedSinceLastUpdate > self::BET_TIMEOUT_S);

            if ($betPhaseExpired) {
                $playersLeft = 0;

                // Убираем игроков без ставок
                foreach ($members as $member) {
                    if ($member->status === 'player' && (int)$member->bet === 0) {
                        $this->db->updateMemberStatus($roomId, $member->user_id, 'spectator');
                    }
                    if ($member->status !== 'spectator') {
                        $playersLeft++;
                    }
                }

                if ($playersLeft < 1) {
                    // Нет игроков - закрываем комнату
                    $this->db->updateRoomStatus($roomId, 'closed');
                    $this->db->cleanRoom($roomId);
                } else {
                    // Есть игроки - запускаем игру
                    require_once 'Player.php';
                    $player = new Player($this->db);
                    $result = $player->startGame($roomId);

                    if (!$result['success']) {
                        // Если не удалось запустить - закрываем
                        $this->db->updateRoomStatus($roomId, 'closed');
                        $this->db->cleanRoom($roomId);
                    }
                }
            }
        }

        // Проверка хода игрока (15 сек)
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
                    // Время истекло - автоматический пасс
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
                $cards = str_split($member->cards, 2);
            }
            
            $players[] = [
                'memberId' => $member->member_id,
                'userId' => $member->user_id,  // ← ИСПРАВЛЕНО: было $userId
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
            return [];  // Возвращаем пустой массив
        }

        // Возвращаем карты как МАССИВ для фронта: ["9C", "6H", "DH", "8S"]
        return str_split($member->cards, 2);
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
