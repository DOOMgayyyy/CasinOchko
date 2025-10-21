<?php
class Stats {
    private $filePath;

    public function __construct($filePath = __DIR__ . '/../../data/stats.json') {
        $this->filePath = $filePath;
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!file_exists($this->filePath)) file_put_contents($this->filePath, json_encode(new stdClass()));
    }

    private function loadAll() {
        $raw = @file_get_contents($this->filePath);
        $data = json_decode($raw, true);
        if (!is_array($data)) $data = [];
        return $data;
    }

    private function saveAll(array $data) {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getStats($playerId) {
        $data = $this->loadAll();
        if (!isset($data[$playerId])) {
            $data[$playerId] = ['wins'=>0,'losses'=>0,'balance'=>1000];
            $this->saveAll($data);
        }
        return $data[$playerId];
    }

    public function updateStats($playerId, array $patch) {
        $data = $this->loadAll();
        if (!isset($data[$playerId])) {
            $data[$playerId] = ['wins'=>0,'losses'=>0,'balance'=>1000];
        }
        foreach ($patch as $k => $v) $data[$playerId][$k] = $v;
        $this->saveAll($data);
        return $data[$playerId];
    }

    public function increment($playerId, $key, $by = 1) {
        $stats = $this->getStats($playerId);
        if (!isset($stats[$key])) $stats[$key] = 0;
        $stats[$key] += $by;
        $this->updateStats($playerId, $stats);
        return $stats;
    }
}
?>
