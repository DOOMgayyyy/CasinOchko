<?php
require_once('Player.php');

$player = new Player('user1');

// делаем ставку 100
$player->makeBet(100);

// допустим, игрок выиграл
$player->recordWin(200); // баланс +200 (итого +100 чистыми)

// допустим, проиграл
$player->recordLoss(100); // баланс -100

echo "<pre>";
print_r($player->getStats());
echo "</pre>";
