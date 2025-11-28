<?php
require_once('db/DB.php');
require_once('user/User.php');
require_once('lobby/Lobby.php');
require_once('game/gameLogic.php');
require_once('game/Deck.php');

// require_once('chat/Chat.php'); 

class Application
{
    private $user;
    // private $chat; 
    private $lobby;
    private $db; 
    private $gameLogic;

    function __construct()
    {
        $db = new DB();
        $this->db = $db;
        $this->user = new User($db);
        // $this->chat = new Chat($db); 
        $this->lobby = new Lobby($db);
        $this->gameLogic = new GameLogic($db);
        $this->deck = new Deck($db);
    }

    public function login($params)
    {
        if ($params['email'] && $params['hash'] && $params['rnd']) {
            return $this->user->login($params['email'], $params['hash'], $params['rnd']);

        }

        return ['error' => 242];
    }

    public function logout($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->logout($params['token']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function registration($params)
    {

        if ($params['email'] && !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 242];
        }

        if ($params['email'] && $params['password'] && $params['name']) {
            // Пароль уже приходит хешированный с клиента
            return $this->user->registration($params['email'], $params['password'], $params['name']);
        }
        return ['error' => 242];
    }

    // Обновление имени
    public function updateUserName($params)
    {
        if ($params['token'] && $params['newName']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->user->updateUserName($user->id, $params['newName']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function sendMessage($params)
    {
        // Теперь чат привязан к комнате, нужен room_id
        if ($params['token'] && $params['message'] && $params['room_id']) { 
            $user = $this->user->getUser($params['token']);
            if ($user) {
                // (Опционально) Здесь можно добавить проверку, состоит ли юзер в этой комнате

                // Вставляем сообщение в БД
                $this->db->addMessage($user->id, $params['message'], $params['room_id']);
                
                // Обновляем хэш чата для этой комнаты
                $newHash = md5(microtime());
                $this->db->updateRoomChatHash($params['room_id'], $newHash);

                return ['hash' => $newHash]; // Возвращаем новый хэш
            }
            return ['error' => 705];
        }
        return ['error' => 242]; // Не хватает 'room_id' или других параметров
    }

    public function getMessages($params)
    {
        // Требуется room_id для получения сообщений комнаты
        if ($params['token'] && $params['hash'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                $currentHashRow = $this->db->getRoomChatHash($params['room_id']);
                $currentHash = $currentHashRow ? $currentHashRow->hash : '';
                $clientHash = $params['hash'];

                if ($currentHash && $currentHash === $clientHash) {
                    // Хэши совпадают, нет новых сообщений
                    return ['messages' => [], 'hash' => $clientHash];
                }

                // Хэши не совпадают (или хэша нет), отправляем новые сообщения
                $messages = $this->db->getMessages($params['room_id']);
                
                // Если хэша не было, создадим его
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
        return ['error' => 242]; // Не хватает 'room_id' или 'hash'
    }

    // menu
    public function getUserStat($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user){
                return ['stats' => $this->user->getUserStat($user->id)];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function getUserBalance($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user){
               return ['balance' => $user->balance];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    // lobby
    public function quickStart($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->quickStart($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function createPrivateRoom($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);

            if ($user) {
                return $this->lobby->createPrivateRoom($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function joinPrivateRoom($params)
    {
        if ($params['token'] && $params['code']) {
            $user = $this->user->getUser($params['token']);

            if ($user) {
                return $this->lobby->joinPrivateRoom($user->id, $params['code']);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function connectRoom($params)
    {
        if ($params['token'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);

            if ($user) {
                return $this->lobby->connectRoom($params['room_id']);
            }

            return ['error' => 705];
        }

        return ['error' => 242];
    }

    public function getRatingTable($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return ['rating' => $this->lobby->getRatingTable()];
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function leaveRoom($params)
    {
        if ($params['token']) {
            $user = $this->user->getUser($params['token']);
            if ($user) {
                return $this->lobby->leaveRoom($user->id);
            }
            return ['error' => 705];
        }
        return ['error' => 242];
    }

    public function addBalance($params){
        if ($params['token'] && $params['amount']) { 
            
            $user = $this->user->getUser($params['token']);
            
            if ($user) {
                
                return $this->user->addBalance($user->id, $params['amount']);
            }
            return ['error' => 705]; 
        }
        return ['error' => 242];
    }

    public function subtractBalance($params){
        if ($params['token'] && $params['amount']) { 
            
            $user = $this->user->getUser($params['token']);
            
            if ($user) {
                
                return $this->user->subtractBalance($user->id, $params['amount']);
            }
            return ['error' => 705]; 
        }
        return ['error' => 242];
    }
    public function getInfoRoom($params)
    {
        if ($params['token'] && $params['hash'] && $params['room_id']) {
            $user = $this->user->getUser($params['token']);

            if (!$user) {
                return ['error' => 705]; // User not found
            }

            return $this->gameLogic->getInfoRoom(
                $params['room_id'],
                $user->id,
                $params['hash']
            );
        }

        return ['error' => 242]; // Params not set fully
    }
}