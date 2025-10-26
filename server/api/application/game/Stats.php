<?php
class Stats
{
    private $filePath;
    private $playerId;
    private $stats;

    public function __construct($playerId)
    {
        $this->playerId = $playerId;
        // путь к файлу с данными
        $this->filePath = __DIR__ . '/../../data/stats.json';
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }

        $this->loadStats();
    }

    private function loadStats()
    {
        $data = json_decode(file_get_contents($this->filePath), true) ?? [];
        // если у игрока нет статистики — создаём
        if (!isset($data[$this->playerId])) {
            $data[$this->playerId] = [
                'wins' => 0,
                'losses' => 0,
                'balance' => 1000
            ];
            file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
        $this->stats = $data[$this->playerId];
    }

    private function saveStats()
    {
        $data = json_decode(file_get_contents($this->filePath), true) ?? [];
        $data[$this->playerId] = $this->stats;
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function recordGame($isWin, $amount)
    {
        if ($isWin) {
            $this->stats['wins']++;
            $this->stats['balance'] += $amount;
        } else {
            $this->stats['losses']++;
            $this->stats['balance'] -= $amount;
        }
        $this->saveStats();
    }

    public function getStats()
    {
        return $this->stats;
    }
}
