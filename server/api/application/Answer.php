<?php

class Answer {
    static $CODES = array(
        '101' => 'Param method not setted',
        '102' => 'Method not found',
        '242' => 'Params not set fully',
        '705' => 'User is not found',
        '1001' => 'Is it unique login?',
        '1002' => 'Wrong login or password',
        '1003' => 'Error to logout user',
        '1004' => 'Error to register user',
        '1005' => 'User is no exists',
        '1006' => 'Other user is playing wright now. If you doesn`t, please change the password',
        '1007' => 'user with this email is already registered',
        '1009' => 'Error updating user name',
        '1010' => 'Name is already taken',
        '404' => 'not found',
        '605' => 'invalid teamId',
        '700' => 'No skins',
        '701' => 'Skin is not found',
        '706' => 'text message is empty',
        '707' => 'could not send message',
        '708' => 'invalid code from E-mail',
        '709' => ' session did not start or you need use previous method',
        '800' => 'Невозможно войти в комнату. Игрок уже играет',
        '801' => 'Failed to generate unique room code',
        '802' => 'Private room not found or invalid code',
        '803' => 'Room is full (maximum 6 players)',
        '804' => 'у тебя нет денег',
        '805' => 'Ошибка сохранения колоды карт',
        '806' => 'Ошибка сериализации колоды (JSON)',
        '807' => 'Ошибка создания новой комнаты',
        '808' => 'Ошибка обновления хэша комнаты',
        '809' => 'Ошибка добавления игрока в комнату',
        '810' => 'Колода пуста',
        '811' => 'Ошибка загрузки данных комнаты',
        '812' => 'Ошибка проверки уникальности приватного кода',
        '813' => 'Минимальная ставка не выполнена',
        '814' => 'Ставки больше не принимаются',
        '815' => 'Комната не в игре',
        '816' => 'Удвоение только с двумя картами',
        '817' => 'Ошибка колоды карт',
        '818' => 'Нет игроков для начала игры', 
        '900' => 'Ошибка добавления в комнату',
        '901' => 'Комната не найдена',
        '902' => 'Пользователь не в комнате',
        '903' => 'Участник комнаты не найден',
        '908' => 'Зритель не может брать карты',
        '909' => 'Не ваш ход',
        '910' => 'Максимум 5 карт в руке',
        '9000' => 'unknown error'
    );

    static function response($data)
    {
        if ($data) {
            // Проверяем, является ли $data массивом или объектом с ошибкой
            $isError = false;
            $errorCode = null;
            
            if (is_array($data) && array_key_exists('error', $data)) {
                $isError = true;
                $errorCode = $data['error'];
            } elseif (is_object($data) && property_exists($data, 'error')) {
                $isError = true;
                $errorCode = $data->error;
            }
            
            if ($isError && !is_bool($data)) {
                return [
                    'result' => 'error',
                    'error' => [
                        'code' => $errorCode,
                        'text' => self::$CODES[$errorCode] ?? 'Unknown error'
                    ]
                ];
            }
            
            // Преобразуем объект в массив для единообразного формата ответа
            if (is_object($data)) {
                $data = (array)$data;
            }
            
            return [
                'result' => 'ok',
                'data' => $data
            ];
        }
        
        // Если $data пусто или false, возвращаем ошибку
        $code = 9000;
        return [
            'result' => 'error',
            'error' => [
                'code' => $code,
                'text' => self::$CODES[$code]
            ]
        ];
    }
}