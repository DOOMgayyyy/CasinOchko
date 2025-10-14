<?php

class Lobby {
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function isUserPlaying($userId) {
        $roomId = $this->db->getRoomId($userId)->room_id;
        $room = $this->db->getRoom($roomId);
        return $room && $room->status === 'playing';
    }

    private function getOpenRoom() {
        $rooms = $this->db->getOpenRooms();
        foreach ($rooms as $room) {
            $membersCount = $this->db->getMembersCount($room->id)->count;
            if ($membersCount < 6) {
                return $room;
            }
        }
        return null;
    }

    public function quickStart($userId) {
        // этот пользователь уже играет -> error
        if ($this->isUserPlaying($userId)) {
            return ['error' => 800];
        }
        // есть открытая комната со свободными местами
        $room = $this->getOpenRoom();
        if ($room) {
            // добавить игрока в комнату
            //...
            return $room;
        }
        // создать новую комнату
        //...
    }
}