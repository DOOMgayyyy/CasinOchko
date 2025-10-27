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

    public function login($email, $password){ // Убираем $hash и $rnd, принимаем $password
        // 1. Ищем пользователя по E-mail
        $user = $this->db->getUserByEmail($email);
        if ($user) {
            // 2. Проверяем пароль с помощью password_verify
            // $user->password теперь будет хэшем из password_hash()
            if (password_verify($password, $user->password)) {
                // Пароль верный! Генерируем токен
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
        
        // Хэшируем пароль перед записью в БД
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Передаем в БД уже хэшированный пароль
        $this->db->registration($email, $hashedPassword, $name);
        
        $user = $this->db->getUserByEmail($email);
        if ($user) {
            $token = md5(rand());
            $this->db->updateToken($user->id, $token);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
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

    public function subtractBalance($userId, $amount) {

        if (!is_numeric($amount) || $amount <= 0) {
            return ['error' => 242];
        }

        $user = $this->db->getUserById($userId);
        if (!$user) {
            return ['error' => 705]; 
        }

        if ($user->balance < $amount) {
            return ['error' => 802];  // Денег нет
        }
    
        // Если все проверки пройдены, выполняем обновление.
        // Передаем отрицательное значение в updateBalance
        $success = $this->db->updateBalance($userId, -(int)$amount); 
        
        if ($success) {
            $user = $this->db->getUserById($userId);
            return ['balance' => $user->balance];
        }
        
        return ['error' => 9000]; 
    }
}