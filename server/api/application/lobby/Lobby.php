<?php

class Lobby {
    private $db;
    private $deck;

    public function __construct($db, $deck = null) {
        $this->db = $db;
        $this->deck = $deck ?: new Deck($db);
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    private function isUserPlaying($userId) {
        $data = $this->db->getRoomId($userId);
        if (!$data || !$data->roomid) {
            return false;
        }

        $room = $this->db->getRoom($data->roomid);
        return $room && ($room->status === 'playing' || $room->status === 'waiting');
    }

    private function getOpenRoom() {
        $rooms = $this->db->getOpenRooms();
        foreach ($rooms as $room) {
            $playingMembersCount = $this->db->getPlayingMembersCount($room->id)->count;
            if ($playingMembersCount < 6) {
                return $room;
            }
        }
        return null;
    }

    private function generatePrivateCode() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, 25)];
        }
        return $code;
    }

    private function isPrivateCodeUnique($code) {
        return $this->db->isPrivateCodeUnique($code);
    }

    private function refreshRoomHash($roomId) {
        $newHash = md5(microtime(true) . rand(1000, 9999) . $roomId);
        $success = $this->db->updateRoomHash($roomId, $newHash);
        return $success ? $newHash : false;
    }

    private function refreshRoomHashOnly($roomId) {
        // Обновляет только hash, НЕ трогая last_update (для комнат с активными таймерами)
        $newHash = md5(microtime(true) . rand(1000, 9999) . $roomId);
        $success = $this->db->updateRoomHashOnly($roomId, $newHash);
        return $success ? $newHash : false;
    }

    // ============================================================
    // PUBLIC METHODS
    // ============================================================

    public function quickStart($userId) {
        // Проверяем, не находится ли пользователь уже в комнате
        $existingRoomData = $this->db->getRoomId($userId);
        if ($existingRoomData && $existingRoomData->roomid) {
            $existingRoom = $this->db->getRoom($existingRoomData->roomid);
            $existingMember = $this->db->getRoomMember($existingRoomData->roomid, $userId);
            
            // Если пользователь в комнате со ставкой или игра идёт, возвращаем эту комнату
            if ($existingRoom && $existingMember) {
                $roomIsActive = in_array($existingRoom->status, ['waiting', 'waiting_for_bets', 'playing', 'show_results']);
                $userHasBet = $existingMember->bet > 0;
                
                if ($roomIsActive && ($userHasBet || $existingRoom->status === 'playing')) {
                    // Обновляем хэш комнаты для синхронизации
                    // Для комнат с таймером (waiting_for_bets, show_results) НЕ обновляем last_update
                    $hasTimer = in_array($existingRoom->status, ['waiting_for_bets', 'show_results']);
                    if ($hasTimer) {
                        $this->refreshRoomHashOnly($existingRoomData->roomid);
                    } else {
                        $this->refreshRoomHash($existingRoomData->roomid);
                    }
                    return $this->db->getRoom($existingRoomData->roomid);
                }
            }
        }
        
        // Пользователь не в активной комнате или там нет ставки - удаляем из всех комнат
        $this->db->removeUserFromAllRooms($userId);
        $room = $this->getOpenRoom();
        $isNewRoom = false;

        if ($room) {
            $roomId = $room->id;
        } else {
            $isNewRoom = true;
            $initialHash = md5(random_int(0, PHP_INT_MAX));
            $roomId = $this->db->createRoom('open', 'waiting', null, $initialHash);
            if (!$roomId) {
                return ['error' => 807];
            }

            $deck = $this->deck->createShuffledDeck();
            if (isset($deck['error'])) {
                $this->db->deleteRoom($roomId);
                return $deck;
            }

            if (!$this->db->saveDeck($roomId, $deck)) {
                $this->db->deleteRoom($roomId);
                return ['error' => 805];
            }
        }

        if (!$this->db->addRoomMember($roomId, $userId, 'spectator', 0)) {
            if ($isNewRoom) {
                $this->db->deleteRoom($roomId);
            }
            return ['error' => 900];
        }

        $newHash = $this->refreshRoomHash($roomId);
        if ($newHash === false) {
            $this->db->removeUserFromRoom($roomId, $userId);
            if ($isNewRoom) {
                $this->db->deleteRoom($roomId);
            }
            return ['error' => 808];
        }

        $roomData = $this->db->getRoom($roomId);
        if (!$roomData) {
            return ['error' => 811];
        }

        return $roomData;
    }

    public function createPrivateRoom($userId) {
        // Проверяем, не находится ли пользователь уже в активной комнате со ставкой
        $existingRoomData = $this->db->getRoomId($userId);
        if ($existingRoomData && $existingRoomData->roomid) {
            $existingRoom = $this->db->getRoom($existingRoomData->roomid);
            $existingMember = $this->db->getRoomMember($existingRoomData->roomid, $userId);
            
            if ($existingRoom && $existingMember) {
                $roomIsActive = in_array($existingRoom->status, ['waiting', 'waiting_for_bets', 'playing', 'show_results']);
                $userHasBet = $existingMember->bet > 0;
                
                if ($roomIsActive && ($userHasBet || $existingRoom->status === 'playing')) {
                    // Пользователь уже в активной игре, нельзя создавать новую комнату
                    return ['error' => 800];
                }
            }
        }
        
        // Удаляем из всех комнат перед созданием новой
        $this->db->removeUserFromAllRooms($userId);
        
        $attempts = 0;
        $privateCode = null;
        do {
            $privateCode = $this->generatePrivateCode();
            $attempts++;
        } while (!$this->isPrivateCodeUnique($privateCode) && $attempts < 100);

        if ($attempts >= 100) {
            return ['error' => 801];
        }

        $initialHash = md5(random_int(0, PHP_INT_MAX));
        $roomId = $this->db->createRoom('private', 'waiting', $privateCode, $initialHash);
        if (!$roomId) {
            return ['error' => 807];
        }

        $deck = $this->deck->createShuffledDeck();
        if (isset($deck['error'])) {
            $this->db->deleteRoom($roomId);
            return $deck;
        }

        if (!$this->db->saveDeck($roomId, $deck)) {
            $this->db->removeUserFromRoom($roomId, $userId);
            $this->db->deleteRoom($roomId);
            return ['error' => 805];
        }

        if (!$this->db->addRoomMember($roomId, $userId, 'spectator', 0)) {
            $this->db->deleteRoom($roomId);
            return ['error' => 900];
        }

        $newHash = $this->refreshRoomHash($roomId);
        if ($newHash === false) {
            $this->db->removeUserFromRoom($roomId, $userId);
            $this->db->deleteRoom($roomId);
            return ['error' => 808];
        }

        $roomData = $this->db->getRoom($roomId);
        if (!$roomData) {
            return ['error' => 811];
        }

        return $roomData;
    }

    public function joinPrivateRoom($userId, $code) {
        // Проверяем, не находится ли пользователь уже в активной комнате со ставкой
        $existingRoomData = $this->db->getRoomId($userId);
        if ($existingRoomData && $existingRoomData->roomid) {
            $existingRoom = $this->db->getRoom($existingRoomData->roomid);
            $existingMember = $this->db->getRoomMember($existingRoomData->roomid, $userId);
            
            if ($existingRoom && $existingMember) {
                $roomIsActive = in_array($existingRoom->status, ['waiting', 'waiting_for_bets', 'playing', 'show_results']);
                $userHasBet = $existingMember->bet > 0;
                
                if ($roomIsActive && ($userHasBet || $existingRoom->status === 'playing')) {
                    // Пользователь уже в активной игре
                    // Если это та же комната, просто возвращаем её
                    $targetRoom = $this->db->getRoomByPrivateCode(strtoupper($code));
                    if ($targetRoom && $targetRoom->id == $existingRoomData->roomid) {
                        // Для комнат с таймером НЕ обновляем last_update
                        $hasTimer = in_array($existingRoom->status, ['waiting_for_bets', 'show_results']);
                        if ($hasTimer) {
                            $this->refreshRoomHashOnly($existingRoomData->roomid);
                        } else {
                            $this->refreshRoomHash($existingRoomData->roomid);
                        }
                        return $this->db->getRoom($existingRoomData->roomid);
                    }
                    // Иначе возвращаем ошибку
                    return ['error' => 800];
                }
            }
        }
        
        $code = strtoupper($code);
        if (!preg_match('/^[A-Z]{4}$/', $code)) {
            return ['error' => 802];
        }

        $room = $this->db->getRoomByPrivateCode($code);
        if (!$room) {
            return ['error' => 802];
        }
        
        $playingMembersCount = $this->db->getPlayingMembersCount($room->id);
        if ($playingMembersCount && $playingMembersCount->count >= 6) {
            return ['error' => 803];
        }

        if (!$this->db->addRoomMember($room->id, $userId, 'spectator', 0)) {
            return ['error' => 900];
        }

        // Для комнат с таймером НЕ обновляем last_update
        $hasTimer = in_array($room->status, ['waiting_for_bets', 'show_results']);
        if ($hasTimer) {
            $newHash = $this->refreshRoomHashOnly($room->id);
        } else {
            $newHash = $this->refreshRoomHash($room->id);
        }
        
        if ($newHash === false) {
            return ['error' => 808];
        }

        return $this->db->getRoom($room->id);
    }

    public function connectRoom($roomId) {
        $roomData = $this->db->getRoom($roomId);
        if (!$roomData) {
            return ['error' => 901];
        }

        // Для комнат с таймером НЕ обновляем last_update
        $hasTimer = in_array($roomData->status, ['waiting_for_bets', 'show_results']);
        if ($hasTimer) {
            $newHash = $this->refreshRoomHashOnly($roomId);
        } else {
            $newHash = $this->refreshRoomHash($roomId);
        }
        
        if ($newHash === false) {
            return ['error' => 808];
        }

        return $this->db->getRoom($roomId);
    }

    public function leaveRoom($userId) {
        $roomData = $this->db->getRoomId($userId);
        if (!$roomData || !$roomData->roomid) {
            return ['error' => 902];
        }

        $roomId = $roomData->roomid;
        $room = $this->db->getRoom($roomId);
        if (!$room) {
            return ['error' => 901];
        }

        // Проверяем, можно ли удалить игрока из комнаты
        $member = $this->db->getRoomMember($roomId, $userId);
        $roomIsActive = in_array($room->status, ['waiting_for_bets', 'playing', 'show_results']);
        $userHasBet = $member && $member->bet > 0;

        // Если у игрока есть ставка и игра активна, НЕ удаляем его из комнаты
        if ($roomIsActive && $userHasBet) {
            // Игрок остаётся в комнате, просто отключается
            // Когда он вернётся, он автоматически переподключится к той же комнате
            return ['success' => true, 'stayed_in_room' => true];
        }

        // В остальных случаях удаляем игрока из комнаты
        $this->db->removeUserFromRoom($roomId, $userId);

        $membersCount = $this->db->getMembersCount($roomId);
        if ($membersCount && $membersCount->count == 0) {
            $this->db->deleteRoom($roomId);
        } else {
            // Для комнат с таймером НЕ обновляем last_update
            $hasTimer = in_array($room->status, ['waiting_for_bets', 'show_results']);
            if ($hasTimer) {
                $this->refreshRoomHashOnly($roomId);
            } else {
                $this->refreshRoomHash($roomId);
            }
        }

        return ['success' => true];
    }

    public function getRatingTable() {
        return $this->db->getUsersByBalance();
    }
}
?>
