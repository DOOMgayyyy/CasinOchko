<?php

require_once ('db/DB.php');
require_once ('user/User.php');
require_once ('lobby/Lobby.php');
require_once ('game/gameLogic.php');
require_once ('game/Deck.php');
require_once ('game/Player.php');

class Application {
    private $db;
    private $user;
    private $lobby;
    private $gameLogic;
    private $deck;
    private $player;

    public function __construct() {
        $this->db = new DB();
        $this->user = new User($this->db);
        $this->lobby = new Lobby($this->db);
        $this->deck = new Deck($this->db);
        $this->player = new Player($this->db, $this->deck);
        $this->gameLogic = new GameLogic($this->db);
    }

    // ============================================================
    // AUTHENTICATION
    // ============================================================

    public function login($params) {
        if ($params['email'] && $params['hash'] && $params['rnd']) {
            return $this->user->login($params['email'], $params['hash'], $params['rnd']);
        }
        return ['error' => 242];
    }

    public function logout($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->logout($params['token']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function registration($params) {
        if ($params['email'] && !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 242];
        }
        if ($params['email'] && $params['password'] && $params['name']) {
            return $this->user->registration($params['email'], $params['password'], $params['name']);
        }
        return ['error' => 242];
    }

    public function checkSession($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'balance' => $user->balance,
                    'token' => $params['token']
                ];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // ============================================================
    // USER MANAGEMENT
    // ============================================================

    public function updateUserName($params) {
        if ($params['token'] && $params['newName']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->updateUserName($user->id, $params['newName']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function getUserStat($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return ['stats' => $this->user->getUserStat($user->id)];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function getUserBalance($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return ['balance' => $user->balance];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // ============================================================
    // BALANCE
    // ============================================================

    public function addBalance($params) {
        if ($params['token'] && $params['amount']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->addBalance($user->id, $params['amount']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function subtractBalance($params) {
        if ($params['token'] && $params['amount']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->subtractBalance($user->id, $params['amount']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // ============================================================
    // CHAT
    // ============================================================

    public function sendMessage($params) {
        if ($params['token'] && $params['message'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                $this->db->addMessage($user->id, $params['message'], $params['room_id']);
                $newHash = md5(microtime());
                $this->db->updateRoomChatHash($params['room_id'], $newHash);
                return ['hash' => $newHash];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function getMessages($params) {
        if ($params['token'] && $params['hash'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                $currentHashRow = $this->db->getRoomChatHash($params['room_id']);
                $currentHash = $currentHashRow ? $currentHashRow->hash : '';
                $clientHash = $params['hash'];

                if ($currentHash && $currentHash === $clientHash) {
                    return ['messages' => [], 'hash' => $clientHash];
                }

                $messages = $this->db->getMessages($params['room_id']);
                if (!$currentHash) {
                    $newHash = md5(microtime());
                    $this->db->updateRoomChatHash($params['room_id'], $newHash);
                } else {
                    $newHash = $currentHash;
                }
                return ['messages' => $messages, 'hash' => $newHash];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // ============================================================
    // LOBBY
    // ============================================================

    public function quickStart($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->quickStart($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function createPrivateRoom($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->createPrivateRoom($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function joinPrivateRoom($params) {
        if ($params['token'] && $params['code']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->joinPrivateRoom($user->id, $params['code']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function connectRoom($params) {
        if ($params['token'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->connectRoom($params['room_id']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function getRatingTable($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return ['rating' => $this->lobby->getRatingTable()];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function leaveRoom($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->leaveRoom($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // ============================================================
    // GAME
    // ============================================================

    public function getInfoRoom($params) {
        if ($params['token'] && $params['hash'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }
            return $this->gameLogic->getInfoRoom(
                $params['room_id'],
                $user->id,
                $params['hash']
            );
        }
        return ['error' => 242];
    }

    /**
     * Сделать ставку
     */
    public function makeBet($params) {
        if ($params['token'] && isset($params['amount'])) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }

            $roomId = null;
            if (isset($params['room_id'])) {
                $roomId = $params['room_id'];
            } else {
                $roomData = $this->db->getRoomId($user->id);
                if ($roomData && $roomData->room_id) {
                    $roomId = $roomData->room_id;
                } else {
                    return ['error' => 902];
                }
            }

            return $this->player->makeBet($roomId, $user->id, $params['amount']);
        }
        return ['error' => 242];
    }

    /**
     * Взять карту
     */
    public function takeUserCard($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }

            $roomId = null;
            if (isset($params['room_id'])) {
                $roomId = $params['room_id'];
            } else {
                $roomData = $this->db->getRoomId($user->id);
                if ($roomData && $roomData->room_id) {
                    $roomId = $roomData->room_id;
                } else {
                    return ['error' => 902];
                }
            }

            return $this->player->takeUserCard($roomId, $user->id);
        }
        return ['error' => 242];
    }

    /**
     * Пропустить ход
     */
    public function pass($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }

            $roomId = null;
            if (isset($params['room_id'])) {
                $roomId = $params['room_id'];
            } else {
                $roomData = $this->db->getRoomId($user->id);
                if ($roomData && $roomData->room_id) {
                    $roomId = $roomData->room_id;
                } else {
                    return ['error' => 902];
                }
            }

            return $this->player->pass($roomId, $user->id);
        }
        return ['error' => 242];
    }

    /**
     * Удвоить ставку
     */
    public function doubleBet($params) {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }

            $roomId = null;
            if (isset($params['room_id'])) {
                $roomId = $params['room_id'];
            } else {
                $roomData = $this->db->getRoomId($user->id);
                if ($roomData && $roomData->room_id) {
                    $roomId = $roomData->room_id;
                } else {
                    return ['error' => 902];
                }
            }

            return $this->player->doubleBet($roomId, $user->id);
        }
        return ['error' => 242];
    }

    /**
     * Вычислить счёт карт
     */
    public function calculateScore($params) {
        if ($params['token'] && isset($params['cards'])) {
            $user = $this->user->getUser($params['token']);
            if (!$user) {
                return ['error' => 705];
            }

            // Преобразуем строку карт в массив (по 2 символа)
            $cardsString = $params['cards'];
            if (empty($cardsString)) {
                $cards = [];
            } else {
                $cards = str_split($cardsString, 2);
            }

            // Вычисляем счёт используя метод из Player
            $score = Player::calculateScore($cards);
            
            return ['score' => $score];
        }
        return ['error' => 242];
    }
}
?>
