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

    // ============================================================
    // PUBLIC METHODS
    // ============================================================

    public function quickStart($userId) {
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

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
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

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
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }

        $code = strtoupper($code);
        if (!preg_match('/^[A-Z]{4}$/', $code)) {
            return ['error' => 802];
        }

        $room = $this->db->getRoomByPrivateCode($code);
        if (!$room) {
            return ['error' => 802];
        }

        $membersCount = $this->db->getMembersCount($room->id);
        if ($membersCount && $membersCount->count >= 6) {
            return ['error' => 803];
        }

        if (!$this->db->addRoomMember($room->id, $userId, 'spectator', 0)) {
            return ['error' => 900];
        }

        $newHash = $this->refreshRoomHash($room->id);
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

        $newHash = $this->refreshRoomHash($roomId);
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

        $this->db->removeUserFromRoom($roomId, $userId);

        $membersCount = $this->db->getMembersCount($roomId);
        if ($membersCount && $membersCount->count == 0) {
            $this->db->deleteRoom($roomId);
        } else {
            $this->refreshRoomHash($roomId);
        }

        return ['success' => true];
    }

    public function getRatingTable() {
        return $this->db->getUsersByBalance();
    }
}
?>
