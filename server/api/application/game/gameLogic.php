<?php

class GameLogic {
    private $db;

    const BET_TIMEOUT_S = 5;
    const ACTION_TIMEOUT_S = 15;
    const RESULTS_TIMEOUT_S = 200;
    const FULL_TIMEOUT_S = 600;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Получить информацию о комнате (игроки, карты, таймер)
     * Работает по принципу long polling с hash-проверкой
     */
    public function getInfoRoom($roomId, $userId, $clientHash) {
        $this->checkTimeouts($roomId);
        $room = $this->db->getRoom($roomId);
        $currentMemberId = $this->db->getCurrentMemberId($roomId);
        
        if (!$room) {return ['error' => 901];}

        $currentHash = $room->hash;

        // Обновляем last_update ТОЛЬКО в режиме ожидания (для проверки активности)
        // НЕ обновляем во время фазы ставок и игры, чтобы таймеры работали!
        if ($room->status === 'waiting') {
            $this->db->touchRoom($roomId);
        }

        // Всегда вычисляем таймер, так как он меняется каждую секунду
        $timer = $this->getTimer($room);

        // Если hash совпадает и таймера нет, возвращаем true (нет изменений)
        if ($currentHash && $currentHash === $clientHash && $timer === null) {
            return true;
        }

        // Если hash совпадает, но есть таймер - возвращаем только таймер
        if ($currentHash && $currentHash === $clientHash && $timer !== null) {
            return [
                'timer' => $timer,
                'hash' => $currentHash,
                'changed' => false
            ];
        }

        // Если hash не совпадает - возвращаем все данные
        $players = $this->getPlayersInfo($roomId);
        $myCards = $this->getUserCards($roomId, $userId);
        $dealerCards = !empty($room->dealerCards) ? $room->dealerCards: '';

        return [
            'players' => $players,
            'myCards' => $myCards,
            'dealerCards' => $dealerCards,
            'userId' => (int)$userId,
            'timer' => $timer,
            'status' => $room->status,
            'hash' => $currentHash,
            'currentMemberId' => $currentMemberId ? (int)$currentMemberId : null,
            'changed' => true
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
        
        // Используем UTC для корректного парсинга времени из БД
        $lastUpdateTimestamp = strtotime($room->last_update . ' UTC');
        if ($lastUpdateTimestamp === false) {
            return;
        }

        $timePassedSinceLastUpdate = $now - $lastUpdateTimestamp;

        // Проверка на 10 минут бездействия
        if ($timePassedSinceLastUpdate > self::FULL_TIMEOUT_S) {
            $this->db->updateRoomStatus($roomId, 'closed');
            $this->db->cleanRoom($roomId);
            return;
        }

        // Фаза показа результатов (5 сек)
        if ($room->status === 'show_results') {
            $resultsPhaseExpired = ($timePassedSinceLastUpdate > self::RESULTS_TIMEOUT_S);

            if ($resultsPhaseExpired) {
                // Время показа результатов истекло - начинаем новый раунд
                require_once 'Player.php';
                $player = new Player($this->db);
                $player->startNewRound($roomId);
            }

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
                // Используем UTC для корректного парсинга времени из БД
                $turnStartTime = strtotime($room->turn_start_time . ' UTC');
                $timePassed = $now - $turnStartTime;

                if ($timePassed > self::ACTION_TIMEOUT_S) {
                    // Вызываем pass для корректной логики смены хода
                    require_once 'Player.php';
                    $player = new Player($this->db);
                    $player->pass($roomId, $currentPlayer->user_id);
                }
            }
        }
    }

    private function getPlayersInfo($roomId)
    {
        $members = $this->db->getRoomMembers($roomId);
        $players = [];
        
        foreach ($members as $member) {
            $cards = $member->cards ?? '';
            
            $players[] = [
                'memberId' => (int)$member->member_id,
                'userId' => (int)$member->user_id, 
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
            return '';
        }

        return $member->cards;
    }

    private function getTimer($room)
    {
        $now = time();
        
        // Фаза показа результатов (5 секунд)
        if ($room->status === 'show_results') {
            // Используем UTC для корректного парсинга времени из БД
            $lastUpdateTimestamp = strtotime($room->last_update . ' UTC');
            $timeElapsed = $now - $lastUpdateTimestamp;
            return max(0, self::RESULTS_TIMEOUT_S - $timeElapsed);
        }
        
        // Фаза ставок
        if (!$room->current_member_id && $room->status === 'waiting_for_bets') {
            // Используем UTC для корректного парсинга времени из БД
            $lastUpdateTimestamp = strtotime($room->last_update . ' UTC');
            $timeElapsed = $now - $lastUpdateTimestamp;
            return max(0, self::BET_TIMEOUT_S - $timeElapsed);
        }

        // Ход игрока
        if ($room->current_member_id && isset($room->turn_start_time)) {
            // Используем UTC для корректного парсинга времени из БД
            $turnStartTimestamp = strtotime($room->turn_start_time . ' UTC');
            $timeElapsed = $now - $turnStartTimestamp;
            return max(0, self::ACTION_TIMEOUT_S - $timeElapsed);
        }

        // Таймера нет
        return null;
    }
}

