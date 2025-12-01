<?php
class User
{
    private $db;
    function __construct($db){
        $this->db = $db;
    }

    public function getUser($token){
        return $this->db->getUserByToken($token);
    }

    public function login($email, $hash, $rnd){
        $user = $this->db->getUserByEmail($email);
        if ($user) {
            if (md5($user->password . $rnd) === $hash) {
                $token = md5(rand());
                $this->db->updateToken($user->id, $token);
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'balance' => $user->balance,
                    'token' => $token
                ];
            }
            return ['error' => 1002]; // Неверный пароль
        }
        return ['error' => 1005]; // Пользователь не существует
    }

    public function logout($token){
        $user = $this->db->getUserByToken($token);
        if ($user) {
            $this->db->updateToken($user->id, null);
            return true;
        }
        return ['error' => 1003];
    }

    public function registration($email, $password, $name) {
        //проверка email (уникальность)
        $user = $this->db->getUserByEmail($email);
        if ($user) {
            return ['error' => 1007]; // user with this email is already registered
        }

        //все гуд регестрируем
        $this->db->registration($email, $password, $name);



        $user = $this->db->getUserByEmail($email);
        if ($user) {
            $token = md5(rand());
            $this->db->updateToken($user->id, $token);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email, // Добавим email в ответ
                'balance' => $user->balance,
                'token' => $token
            ];
        }
        return ['error' => 1004]; // Error to register user
    }

    //обновление имени с проверкой уникальности 
    public function updateUserName($userId, $newName){
        if ($this->isNameUnique($newName, $userId)) {
            $success = $this->db->updateUserName($userId, $newName);
            if ($success) {
                return $this->db->getUserById($userId);
            }
            return ['error' => 1009];
        }
        return ['error' => 1010];
    }

    public function getUserStat($userId){
        return $this->db->getUserStat($userId); // Здесь будет возвращен расширенный объект
    }

    //проверка уникального имени
    private function isNameUnique($name, $excludingUserId = null){
        return $this->db->isNameUnique($name, $excludingUserId);
    }

    public function getUserBalance($token){
        $user = $this->db->getUserByToken($token);
        if ($user) {
            return [
                'balance' => $user->balance
            ];
        }
        return ['error' => 705];
    }

    public function addBalance($userId, $amount){
        if (!is_numeric($amount) || $amount <= 0) {
            return ['error' => 242];
        }
        $success = $this->db->updateBalance($userId, (int)$amount); // Здесь $amount положительный
        if ($success){
            $user = $this->db->getUserById($userId);
            return ['balance' => $user->balance];
        }
        return ['error' => 9000];
    }
    
    public function subtractBalance($userId, $amount) {
        
        if (!is_numeric($amount) || $amount <= 0) {
            return ['error' => 242];
        }
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return ['error' => 705]; 
        }
        if ($user->balance < $amount) {
            return ['error' => 804];  //денег нет
        }
    
        $success = $this->db->updateBalance($userId, -(int)$amount); // Здесь $amount отрицательный
        
        if ($success) {
            $user = $this->db->getUserById($userId);
            return ['balance' => $user->balance];
        }
        
        return ['error' => 9000]; 
    }
}