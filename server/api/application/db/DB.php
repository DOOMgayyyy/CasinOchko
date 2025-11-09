<?php

class DB
{
    private $pdo;
    // разкомент тут MYsql и смена названия базы данных
    function __construct()
    {
        $host = '127.0.0.1';// ip для подключения к бд
        $port = '3306';// порт 
        $user = 'root';// логин для входа в бд
        $pass = ''; // пароль для бд
        $db = 'casinochko';// название базы данных 
        $connect = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";// формирование команды для подключения к базе данных
        // cоздаем объект PDO для работы с БД
        $this->pdo = new PDO($connect, $user, $pass);
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    // выполнить запрос без возвращения данных, базовый метод для работы с Бд
    private function execute($sql, $params = [])
    {
        try {
            $sth = $this->pdo->prepare($sql); // Подготавливаем запрос
            $result = $sth->execute($params); // Выполняем с параметрами
            
            // Если выполнение не удалось, логируем ошибку (для отладки)
            if (!$result) {
                $errorInfo = $sth->errorInfo();
                error_log("DB Execute Error: " . $errorInfo[2] . " | SQL: " . $sql);
            }
            
            return $result;
        } catch (PDOException $e) {
            // В случае исключения PDO логируем и возвращаем false
            error_log("DB PDOException: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    // получение ОДНОЙ записи
    private function query($sql, $params = [])
    {
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetch(PDO::FETCH_OBJ);
    }


    // получение НЕСКОЛЬКИХ записей
    private function queryAll($sql, $params = [])
    {
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        // БЫЛО: return $sth->fetchAll(PDO::FETCH_ASSOC);
        return $sth->fetchAll(PDO::FETCH_OBJ); // <-- ИСПРАВЛЕНО
    }

    /*public function getUserByLogin($name) {
        return $this->query("SELECT * FROM users WHERE login=?", [$name]);
    }*/

    public function getUserById($userId)
    {
        return $this->query("SELECT id, email, name, balance, token FROM users WHERE id=?", [$userId]);
    }

    public function getUserByEmail($email)
    {
        return $this->query("SELECT * FROM users WHERE email=?", [$email]);
    }

    public function getUserByToken($token)
    {
        return $this->query("SELECT * FROM users WHERE token=?", [$token]);
    }

    public function updateToken($userId, $token)
    {
        $this->execute("UPDATE users SET token=? WHERE id=?", [$token, $userId]);
    }

    public function updateUserName($userId, $newName)
    {
        return $this->execute("UPDATE users SET name=? WHERE id=?", [$newName, $userId]);
    }
    public function isNameUnique($name, $excludingUserId = null)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE name = ?";
        $params = [$name];

        if ($excludingUserId !== null) {
            // Если указан ID, исключаем его из проверки (пользователь может сохранить свое имя)
            $sql .= " AND id != ?";
            $params[] = $excludingUserId;
        }

        // Выполняем запрос и возвращаем true, если COUNT(*) равен 0 (имя уникально)
        $count = $this->query($sql, $params)->{'COUNT(*)'};
        return $count == 0;
    }

    public function registration($email, $password, $name)
    {
        // Добавляем баланс 5000 для нового пользователя
        $this->execute(
            "INSERT INTO users (email, password, name) VALUES (?, ?, ?)",
            [$email, $password, $name]
        );
    }

    public function getChatHash()
    {
        return $this->query("SELECT * FROM hashes WHERE id=1");
    }

    public function updateChatHash($hash)
    {
        $this->execute("UPDATE hashes SET chat_hash=? WHERE id=1", [$hash]);
    }

    public function addMessage($userId, $message)
    {
        $this->execute('INSERT INTO messages (user_id, message, created) VALUES (?,?, now())', [$userId, $message]);
    }

    public function getMessages()
    {
        return $this->queryAll("SELECT u.name AS author, m.message AS message,
                                m.created AS created FROM messages as m 
                                LEFT JOIN users as u on u.id = m.user_id 
                                ORDER BY m.created DESC"
        );
    }

    public function getRoomId($userId)
    {
        return $this->query(
            "SELECT room_id FROM room_members WHERE user_id=?",
            [$userId]
        );
    }

    public function getRoom($roomId)
    {
        return $this->query("SELECT * FROM rooms WHERE id=?", [$roomId]);
    }

    public function getOpenRooms()
    {
        return $this->queryAll("SELECT * FROM rooms WHERE type='open' AND status='playing'");
    }

    public function getMembersCount($roomId)
    {
        return $this->query(
            "SELECT count(*) AS count FROM room_members WHERE room_id=?",
            [$roomId]
        );
    }

    public function createRoom($type = 'open', $status = 'playing')
    {
        $this->execute("INSERT INTO rooms (type, status) VALUES (?, ?)", [$type, $status]);
        return $this->pdo->lastInsertId();
    }

    public function createPrivateRoom($privateCode)
    {
        $this->execute("INSERT INTO rooms (type, status, private_code) VALUES (?, ?, ?)", ['private', 'playing', $privateCode]);
        return $this->pdo->lastInsertId();
    }

    public function getRoomByPrivateCode($privateCode)
    {
        return $this->query("SELECT * FROM rooms WHERE type='private' AND private_code=? AND status='playing'", [$privateCode]);
    }

    public function addUserToRoom($roomId, $userId, $bet = 0, $type = 0)
    {
        // Поле cards имеет NOT NULL, поэтому передаем пустую строку (карты будут выданы позже)
        return $this->execute(
            "INSERT INTO room_members (room_id, user_id, bet, types, cards) VALUES (?, ?, ?, ?, ?)",
            [$roomId, $userId, $bet, $type, '']
        );
    }

    public function saveDeck($roomId, $deck)
    {
        $json = json_encode($deck);
        $this->execute("UPDATE rooms SET deckOfCards=? WHERE id=?", [$json, $roomId]);
    }
    public function getDeck($roomId)
    {
        $data = $this->query("SELECT deckOfCards FROM rooms WHERE id=?", [$roomId]);
        return $data ? json_decode($data->deckOfCards, true) : [];
    }


}