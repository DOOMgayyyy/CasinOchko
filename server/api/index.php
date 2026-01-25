<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once 'application/Answer.php';
require_once 'application/Application.php';

function result($params) {
    $method = $params['method'];
    if ($method) {
        $app = new Application();
    switch ($method) {
        // Authentication
        case 'login': return $app->login($params);
        case 'logout': return $app->logout($params);
        case 'registration': return $app->registration($params);
        case 'updateUserName': return $app->updateUserName($params);

        // Chat
        case 'sendMessage': return $app->sendMessage($params);
        case 'getMessages': return $app->getMessages($params);

        // Balance
        case 'claimAdReward': return $app->claimAdReward($params);
        case 'subtractBalance': return $app->subtractBalance($params);

        // Menu
        case 'getUserStat': return $app->getUserStat($params);
        case 'getUserBalance': return $app->getUserBalance($params);

        // Lobby
        case 'quickStart': return $app->quickStart($params);
        case 'createPrivateRoom': return $app->createPrivateRoom($params);
        case 'joinPrivateRoom': return $app->joinPrivateRoom($params);
        case 'getRatingTable': return $app->getRatingTable($params);
        case 'connectRoom': return $app->connectRoom($params);
        case 'leaveRoom': return $app->leaveRoom($params);

        // Game
        case 'getInfoRoom': return $app->getInfoRoom($params);
        case 'makeBet': return $app->makeBet($params);
        case 'takeUserCard': return $app->takeUserCard($params);
        case 'pass': return $app->pass($params);
        case 'doubleBet': return $app->doubleBet($params);
        case 'calculateScore': return $app->calculateScore($params);

        default: return ['error' => 102];
    }
}
 return ['error' => 101];  
}

// Определение параметра 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $params = array_merge($_GET, $input);
} else {
    $params = $_GET;
}
echo json_encode(Answer::response(result($params)), JSON_UNESCAPED_UNICODE);
