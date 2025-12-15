<?php

class DB {
    private $pdo;

    function __construct()
    {
        $host='127.0.0.1';
        $port='3306';
        $user='root';
        $pass='';
        $db='casinochko';
        $connect = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";
        $this->pdo = new PDO($connect, $user, $pass);
    }

    public function __destruct() {
        $this->pdo = null;
    }

    // ============================================================
    // BASE DATABASE METHODS
    // ============================================================

    public function execute($sql, $params) {
        $sth = $this->pdo->prepare($sql);
        return $sth->execute($params);
    }

    public function query($sql, $params) {
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetch(PDO::FETCH_OBJ);
    }

    private function queryAll($sql, $params) {
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetchAll(PDO::FETCH_OBJ);
    }

    // ============================================================
    // USERS
    // ============================================================

    public function getUserByLogin($name) {
        return $this->query("SELECT * FROM users WHERE login=?", [$name]);
    }

    public function getUserById($userId) {
        return $this->query("SELECT id, email, name, balance, token FROM users WHERE id=?", [$userId]);
    }

    public function getUserByEmail($email) {
        return $this->query("SELECT * FROM users WHERE email=?", [$email]);
    }

    public function getUserByToken($token) {
        return $this->query("SELECT * FROM users WHERE token=?", [$token]);
    }

    public function updateToken($userId, $token) {
        return $this->execute("UPDATE users SET token=? WHERE id=?", [$token, $userId]);
    }

    public function getUserStat($userId) {
        return $this->query("SELECT totalplayed, totalwin, totalbalance FROM users WHERE id=?", [$userId]);
    }

    public function updateUserStats($userId, $played, $win, $balance) {
        return $this->execute(
            "UPDATE users SET totalplayed = totalplayed + ?, totalwin = totalwin + ?, totalbalance = totalbalance + ? WHERE id = ?",
            [$played, $win, $balance, $userId]
        );
    }

    public function updateUserName($userId, $newName) {
        return $this->execute("UPDATE users SET name=? WHERE id=?", [$newName, $userId]);
    }

    public function isNameUnique($name, $excludingUserId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE name = ?";
        $params = [$name];

        if ($excludingUserId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludingUserId;
        }

        $result = $this->query($sql, $params);
        return $result->count == 0;
    }

    public function registration($email, $password, $name) {
        return $this->execute(
            "INSERT INTO users (email, password, name) VALUES (?, ?, ?)",
            [$email, $password, $name]
        );
    }

    public function updateBalance($userId, $amount) {
        return $this->execute(
            "UPDATE users SET balance = balance + ? WHERE id = ?",
            [$amount, $userId]
        );
    }

    public function getUsersByBalance() {
        return $this->queryAll("SELECT id, name, balance FROM users ORDER BY balance DESC LIMIT 100", []);
    }

    // ============================================================
    // CHAT
    // ============================================================

    public function getRoomChatHash($roomId) {
        return $this->query("SELECT hash FROM messagehashes WHERE roomid=?", [$roomId]);
    }

    public function updateRoomChatHash($roomId, $hash) {
        return $this->execute("REPLACE INTO messagehashes (roomid, hash) VALUES (?, ?)", [$roomId, $hash]);
    }

    public function addMessage($userId, $message, $roomId) {
        return $this->execute(
            "INSERT INTO messages (roomid, userid, message, created) VALUES (?, ?, ?, now())",
            [$roomId, $userId, $message]
        );
    }

    public function getMessages($roomId) {
        return $this->queryAll(
            "SELECT u.name AS author, m.message AS message, m.created AS created 
             FROM messages as m 
             LEFT JOIN users as u on u.id = m.userid 
             WHERE m.roomid = ? 
             ORDER BY m.created DESC",
            [$roomId]
        );
    }

    // ============================================================
    // ROOMS
    // ============================================================

    public function getRoomId($userId) {
        return $this->query("SELECT roomid FROM roommembers WHERE userid=?", [$userId]);
    }

    public function getRoom($roomId) {
        return $this->query(
            "SELECT id, type, status, current_member_id, privatecode, hash, turn_start_time, dealerCards, deckOfCards, last_update 
             FROM rooms WHERE id = ?",
            [$roomId]
        );
    }

    public function getOpenRooms() {
        return $this->queryAll(
            "SELECT id, type, status, current_member_id, privatecode, hash 
             FROM rooms 
             WHERE type='open' AND (status='waiting' OR status='playing')",
            []
        );
    }

    public function getMembersCount($roomId) {
        return $this->query("SELECT count(*) as count FROM roommembers WHERE roomid=?", [$roomId]);
    }

    public function getPlayingMembersCount($roomId) {
        return $this->query(
            "SELECT COUNT(*) AS count FROM roommembers WHERE roomid = ? AND status != 'spectator'",
            [$roomId]
        );
    }

    public function isPrivateCodeUnique($code) {
        $result = $this->query("SELECT COUNT(*) AS count FROM rooms WHERE privatecode = ?", [$code]);
        return $result->count == 0;
    }

    public function createRoom($type, $status, $privateCode, $hash) {
        $this->execute(
            "INSERT INTO rooms (type, status, privatecode, hash, last_update) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())",
            [$type, $status, $privateCode, $hash]
        );
        return $this->pdo->lastInsertId();
    }

    public function getRoomByPrivateCode($code) {
        return $this->query("SELECT * FROM rooms WHERE privatecode=?", [$code]);
    }

    public function deleteRoom($roomId) {
        return $this->execute("DELETE FROM rooms WHERE id = ?", [$roomId]);
    }

    public function getRoomHash($roomId) {
        $room = $this->query("SELECT hash FROM rooms WHERE id = ?", [$roomId]);
        return $room ? $room->hash : null;
    }

    public function updateRoomHash($roomId, $hash) {
        return $this->execute("UPDATE rooms SET hash = ?, last_update = UTC_TIMESTAMP() WHERE id = ?", [$hash, $roomId]);
    }

    public function updateRoomStatus($roomId, $status) {
        $hash = md5(time() . $roomId . rand(1, 10000));
        return $this->execute(
            "UPDATE rooms SET status = ?, hash = ?, last_update = UTC_TIMESTAMP() WHERE id = ?",
            [$status, $hash, $roomId]
        );
    }

    public function touchRoom($roomId) {
        return $this->execute("UPDATE rooms SET last_update = UTC_TIMESTAMP() WHERE id = ?", [$roomId]);
    }

    public function updateRoomAction($roomId) {
        $newHash = md5(time() . $roomId . rand(1, 10000));
        return $this->execute(
            "UPDATE rooms SET hash = ?, last_update = UTC_TIMESTAMP() WHERE id = ?",
            [$newHash, $roomId]
        );
    }

    public function setCurrentPlayer($roomId, $memberId) {
        return $this->execute(
            "UPDATE rooms SET current_member_id = ?, turn_start_time = UTC_TIMESTAMP(), last_update = UTC_TIMESTAMP() WHERE id = ?",
            [$memberId, $roomId]
        );
    }

    public function cleanRoom($roomId) {
        // Удаляем всех участников комнаты
        $this->execute("DELETE FROM roommembers WHERE roomid = ?", [$roomId]);

        // Удаляем все сообщения чата этой комнаты
        $this->execute("DELETE FROM messages WHERE roomid = ?", [$roomId]);

        // Удаляем хэш чата этой комнаты 
        $this->execute("DELETE FROM messagehashes WHERE roomid = ?", [$roomId]);

        // Удаляем саму комнату
        return $this->deleteRoom($roomId);
    }


    public function resetCurrentMember($roomId) {
        return $this->execute(
            "UPDATE rooms SET current_member_id = NULL, last_update = UTC_TIMESTAMP() WHERE id = ?",
            [$roomId]
        );
    }

    // ============================================================
    // ROOM MEMBERS
    // ============================================================

    public function getRoomMember($roomId, $userId) {
        return $this->query(
            "SELECT rm.id as member_id, rm.userid, rm.bet, rm.cards, rm.status, u.name, u.balance 
             FROM roommembers AS rm 
             JOIN users u ON rm.userid = u.id 
             WHERE rm.roomid = ? AND rm.userid = ?",
            [$roomId, $userId]
        );
    }

    public function getRoomMembers($roomId) {
        return $this->queryAll(
            "SELECT rm.id as member_id, rm.userid as user_id, rm.bet, rm.cards, rm.status, u.name, u.balance
            FROM roommembers AS rm
            JOIN users u ON rm.userid = u.id
            WHERE rm.roomid = ?
            ORDER BY rm.id ASC",
            [$roomId]
        );
    }



    public function addRoomMember($roomId, $userId, $status = 'spectator', $bet = 0) {
        try {
            $this->execute("DELETE FROM roommembers WHERE userid = ? AND roomid != ?", [$userId, $roomId]);
            return $this->execute(
                "INSERT INTO roommembers (roomid, userid, status, bet, cards) VALUES (?, ?, ?, ?, '')",
                [$roomId, $userId, $status, $bet]
            );
        } catch (PDOException $e) {
            error_log("Error while adding user to room: " . $e->getMessage());
            return false;
        }
    }

    public function removeUserFromAllRooms($userId) {
        return $this->execute("DELETE FROM roommembers WHERE userid = ?", [$userId]);
    }

    public function removeUserFromRoom($roomId, $userId) {
        return $this->execute("DELETE FROM roommembers WHERE roomid = ? AND userid = ?", [$roomId, $userId]);
    }

    public function getCurrentMemberId($roomId) {
        $room = $this->query("SELECT current_member_id FROM rooms WHERE id = ?", [$roomId]);
        return $room ? $room->current_member_id : null;
    }

    // ============================================================
    // CARDS AND BETS
    // ============================================================

    public function updateMemberCards($roomId, $userId, $cards) {
        // Карты хранятся как простая строка "2H3SAS"
        $cardsStr = is_array($cards) ? implode('', $cards) : $cards;

        return $this->execute(
            "UPDATE roommembers SET cards = ? WHERE roomid = ? AND userid = ?",
            [$cardsStr, $roomId, $userId]
        );
    }

    public function updateMemberBet($roomId, $userId, $bet) {
        return $this->execute(
            "UPDATE roommembers SET bet = ? WHERE roomid = ? AND userid = ?",
            [$bet, $roomId, $userId]
        );
    }

    public function updateMemberStatus($roomId, $userId, $status) {
        return $this->execute(
            "UPDATE roommembers SET status = ? WHERE roomid = ? AND userid = ?",
            [$status, $roomId, $userId]
        );
    }

    public function updateDealerCards($roomId, $dealerCards) {
        // Дилер карты тоже хранятся как строка
        $cardsStr = is_array($dealerCards) ? implode('', $dealerCards) : $dealerCards;
        return $this->execute("UPDATE rooms SET dealerCards = ? WHERE id = ?", [$cardsStr, $roomId]);
    }


    // ============================================================
    // DECK MANAGEMENT
    // ============================================================

    public function saveDeck($roomId, $deck) {
        return $this->execute("UPDATE rooms SET deckOfCards = ? WHERE id = ?", [$deck, $roomId]);
    }

    public function loadDeck($roomId) {
        $result = $this->query("SELECT deckOfCards FROM rooms WHERE id = ?", [$roomId]);
        if (!$result || empty($result->deckOfCards)) {
            return '';
        }
        return $result->deckOfCards;
    }

    public function drawCardAtomic($roomId, $cardLength) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT deckOfCards FROM rooms WHERE id = ? FOR UPDATE");
            $stmt->execute([$roomId]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$result || empty($result->deckOfCards) || strlen($result->deckOfCards) < $cardLength) {
                $this->pdo->rollBack();
                return false;
            }

            $deck = $result->deckOfCards;
            $card = substr($deck, 0, $cardLength);
            $remainingDeck = substr($deck, $cardLength);

            $updateStmt = $this->pdo->prepare("UPDATE rooms SET deckOfCards = ? WHERE id = ?");
            $updateStmt->execute([$remainingDeck, $roomId]);

            $this->pdo->commit();
            return $card;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in drawCardAtomic: " . $e->getMessage());
            return false;
        }
    }
}
?>
