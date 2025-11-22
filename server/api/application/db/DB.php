<?php
class DB
{
    private $pdo;
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
    public function execute($sql, $params = [])
    {
        $sth = $this->pdo->prepare($sql); // Подготавливаем запрос
        return $sth->execute($params); // Выполняем с параметрами
    }

    // получение ОДНОЙ записи
    public function query($sql, $params = [])
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
        return $sth->fetchAll(PDO::FETCH_OBJ);    }

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
    public function getUserStat($userId) {
        return $this->query("SELECT total_played, total_win, total_balance FROM users WHERE id=?", [$userId]);
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
        $this->execute(
            "INSERT INTO users (email, password, name) VALUES (?, ?, ?)",
            [$email, $password, $name]
        );
    }

    public function getRoomChatHash($roomId)
    {
        return $this->query("SELECT hash FROM message_hashes WHERE room_id=?", [$roomId]);
    }

    public function updateRoomChatHash($roomId, $hash)
    {
        return $this->execute("REPLACE INTO message_hashes (room_id, hash) VALUES (?, ?)", [$roomId, $hash]);
    }

    public function addMessage($userId, $message, $roomId)
    {
        $this->execute('INSERT INTO messages (room_id, user_id, message, created) VALUES (?, ?, ?, now())', [$roomId, $userId, $message]);
    }

    public function getMessages($roomId)
    {
        return $this->queryAll("SELECT u.name AS author, m.message AS message,
                                m.created AS created FROM messages as m 
                                LEFT JOIN users as u on u.id = m.user_id 
                                WHERE m.room_id = ?
                                ORDER BY m.created DESC",
                                [$roomId]
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
        return $this->query("SELECT id, type, status, current_member_id, private_code, hash FROM rooms WHERE id=?", [$roomId]);
    }

    public function getOpenRooms()
    {
        return $this->queryAll("SELECT id, type, status, current_member_id, private_code, hash FROM rooms WHERE type='open' AND status='playing'");
    }

    public function getMembersCount($roomId)
    {
        return $this->query(
            "SELECT count(*) AS count FROM room_members WHERE room_id=?",
            [$roomId]
        );
    }

    /**
     * Получает количество участников, не являющихся наблюдателями (т.е. игроков) в комнате.
     * Считает всех, чей статус не 'spectator'.
     * @param int $roomId ID комнаты
     * @return object Объект с полем 'count'
     */
    public function getPlayingMembersCount($roomId)
    {
        // Считаем всех, кто не 'spectator' (т.е. 'player', )
        return $this->query("SELECT COUNT(*) AS count FROM room_members WHERE room_id = ? AND status != 'spectator'", [$roomId]);
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

    public function removeUserFromAllRooms($userId) {
        return $this->execute(
            "DELETE FROM room_members WHERE user_id = ?",
            [$userId]
        );
    }

    public function removeUserFromRoom($roomId, $userId) {
            return $this->execute(
                "DELETE FROM room_members WHERE room_id = ? AND user_id = ?",
                [$roomId, $userId]
            );
        }

    public function addRoomMember($roomId, $userId, $status = 'spectator', $bet = 0) { 
        $this->removeUserFromAllRooms($userId); 
        try {
            return $this->execute(
                "INSERT INTO room_members (room_id, user_id, status, bet, cards) VALUES (?, ?, ?, ?, ?)",
                [$roomId, $userId, $status, $bet, ''] 
            );
        } catch (PDOException $e) {
            error_log("Error while adding user to room: " . $e->getMessage());
            return false;
        }
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
    public function updateBalance($userId, $amount) {
    // Используем SQL-функцию ADD для прибавления или вычитания.
    // Если $amount положительный, произойдет прибавление.
    // Если $amount отрицательный, произойдет вычитание.
    // Выражение 'balance + ?' гарантирует, что мы не перезаписываем, а обновляем баланс.
        return $this->execute(
            "UPDATE users SET balance = balance + ? WHERE id = ?",
            [$amount, $userId]
        );
    }/**
     * Сохраняет строку колоды напрямую в БД
     */
    public function saveDeck($roomId, $deck)
    {
        // $deck — это уже готовая строка "2H3D..."
        // Просто пишем её в базу
        return $this->execute("UPDATE rooms SET deckOfCards = ? WHERE id = ?", [$deck, $roomId]);
    }

    /**
     * Загружает строку колоды из БД
     */
    public function loadDeck($roomId)
    {
        $result = $this->query("SELECT deckOfCards FROM rooms WHERE id = ?", [$roomId]);
        
        if (!$result || empty($result->deckOfCards)) {
            return ''; // Если пусто, возвращаем пустую строку
        }

        // Возвращаем чистую строку "2H3D..." как есть
        return $result->deckOfCards;
    }
    /**
     * Получить информацию об участнике комнаты
     */
    public function getRoomMember($roomId, $userId) {
        return $this->query(
            "SELECT rm.id as member_id, rm.user_id, rm.bet, rm.cards, rm.status,
                    u.name, u.balance
             FROM room_members rm
             JOIN users u ON rm.user_id = u.id
             WHERE rm.room_id = ? AND rm.user_id = ?",
            [$roomId, $userId]
        );
    }
    
    /**
     * Получить всех участников комнаты с подробной информацией
     */
    public function getRoomMembers($roomId) {
        return $this->queryAll(
            "SELECT rm.id as member_id, rm.user_id, rm.bet, rm.cards, rm.status,
                    u.id, u.name, u.balance
             FROM room_members rm
             JOIN users u ON rm.user_id = u.id
             WHERE rm.room_id = ?
             ORDER BY rm.id ASC",
            [$roomId]
        );
    }
    
    /**
     * Обновить карты игрока в комнате
     */
    public function updateMemberCards($roomId, $userId, $cards) {
        $json = json_encode($cards);
        return $this->execute(
            "UPDATE room_members SET cards = ? WHERE room_id = ? AND user_id = ?",
            [$json, $roomId, $userId]
        );
    }
    
    /**
     * Обновить ставку игрока
     */
    public function updateMemberBet($roomId, $userId, $bet) {
        return $this->execute(
            "UPDATE room_members SET bet = ? WHERE room_id = ? AND user_id = ?",
            [$bet, $roomId, $userId]
        );
    }
    
    /**
     * Обновить статус игрока (player, spectator)
     */
    public function updateMemberStatus($roomId, $userId, $status) {
        return $this->execute(
            "UPDATE room_members SET status = ? WHERE room_id = ? AND user_id = ?",
            [$status, $roomId, $userId]
        );
    }
    
    /**
     * Установить текущего игрока и время начала хода
     */
    public function setCurrentPlayer($roomId, $memberId) {
        return $this->execute(
            "UPDATE rooms SET current_member_id = ?, turn_start_time = NOW() WHERE id = ?",
            [$memberId, $roomId]
        );
    }
    /**
     * Получить ID текущего ходящего игрока
     */
    public function getCurrentMemberId($roomId) {
        $result = $this->query(
            "SELECT current_member_id FROM rooms WHERE id = ?",
            [$roomId]
        );
        return $result ? $result->current_member_id : null;
    }

    /**
     * Удаление комнаты
     */
    public function deleteRoom($roomId) {
        return $this->execute("DELETE FROM rooms WHERE id = ?", [$roomId]);
    }

    /**
     * Обновление хэша комнаты
     */
    public function updateRoomHash($roomId, $hash) {
        return $this->execute("UPDATE rooms SET hash = ? WHERE id = ?", [$hash, $roomId]);
    }

    /**
     * Сброс текущего игрока в комнате
     */
    public function resetCurrentMember($roomId) {
        return $this->execute("UPDATE rooms SET current_member_id = NULL WHERE id = ?", [$roomId]);
    }

}