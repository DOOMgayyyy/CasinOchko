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
        $sth = $this->pdo->prepare($sql); // Подготавливаем запрос
        return $sth->execute($params); // Выполняем с параметрами
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
        return $sth->fetchAll(PDO::FETCH_ASSOC);
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

    public function updateUserName($userId, $newName) {
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

    public function isPrivateCodeUnique($code) {
        $sql = "SELECT COUNT(*) FROM rooms WHERE private_code = ?";
        $count = $this->query($sql, [$code])->{'COUNT(*)'};
        return $count == 0;
    }

    public function createRoom($type, $status, $privateCode, $hash) {
        $this->execute(
            "INSERT INTO rooms (type, status, private_code, hash) VALUES (?, ?, ?, ?)",
            [$type, $status, $privateCode, $hash]
        );
        // Возвращаем ID созданной комнаты
        return $this->pdo->lastInsertId();
    }

    public function addRoomMember($roomId, $userId, $bet = 0) {
        $this->execute(
            "INSERT INTO room_members (room_id, user_id, bet) VALUES (?, ?, ?)",
            [$roomId, $userId, $bet]
        );
    }

    public function getRoomByPrivateCode($code) {
        return $this->query("SELECT * FROM rooms WHERE private_code=?", [$code]);
    }
    public function getUsersByBalance()
    {
        return $this->queryAll("SELECT 
                id,
                name,
                balance
            FROM users
            ORDER BY balance DESC
            LIMIT 100
    ");
    }


}