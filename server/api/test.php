<?php
error_reporting(1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');


require_once 'application/db/DB.php';

$db = new DB();
$user = $db->getUserByToken('9f53c66c7e833a8e7459e58789d891ae');


$userId = $user->id;
var_dump($userId);

$roomData = $db->getRoomId($userId);
var_dump($roomData);
$roomId = $roomData->roomid;
var_dump($roomId);

$delete = $db->removeUserFromRoom($roomId, $userId);
    
var_dump($delete);