<?php
require_once('Card.php');
require_once('gameLogic.php');
require_once(__DIR__ . '/Stats.php');

class Player
{
    public $id;
    public $cards = [];
    public $balance;
    public $currentBet = 0;
    public $splitHands = []; 
    public $activeHandIndex = 0; 
    public $stats;

    public function __construct($id, $balance = 1000)
    {
        $this->id = $id;
        $savedStats = $this->stats->getStats();
        $this->balance = $savedStats['balance'];
        $this->cards = [];
        $this->splitHands = [];
        $this->activeHandIndex = 0;
        $this->stats = new Stats($id);
    }

    private function getCurrentHand()
    {
        if (!empty($this->splitHands)) {
            return $this->splitHands[$this->activeHandIndex];
        }
        return $this->cards;
    }

    private function &getCurrentHandRef()
    {
        if (!empty($this->splitHands)) {
            return $this->splitHands[$this->activeHandIndex];
        }
        return $this->cards;
    }

    public function addCard(Card $card)
    {
        $currentHand = &$this->getCurrentHandRef();

        if (count($currentHand) >= 5) {
            return $this;
        }

        $currentHand[] = $card;
        return $this;
    }

    public function getScore()
    {
        $currentHand = $this->getCurrentHand();
        return gameLogic::calculateHandScore($currentHand);
    }

    public function getHandScore($handIndex = null)
    {
        if ($handIndex !== null && isset($this->splitHands[$handIndex])) {
            return gameLogic::calculateHandScore($this->splitHands[$handIndex]);
        }
        return $this->getScore();
    }

    public function makeBet($amount)
    {
        if ($amount < 100) {
            return 0;
        }

        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->currentBet = $amount;
            return $amount;
        }

        return 0;
    }

    public function split($deck)
    {
        if (count($this->cards) != 2 || $this->cards[0]->rank !== $this->cards[1]->rank) {
            return false;
        }

        if ($this->balance < $this->currentBet) {
            return false;
        }

        $this->balance -= $this->currentBet;

        $hand1 = [$this->cards[0]];
        $hand2 = [$this->cards[1]];

        $hand1[] = $deck->draw();
        $hand2[] = $deck->draw();

        $this->splitHands = [$hand1, $hand2];
        $this->activeHandIndex = 0;

        $this->cards = [];

        return true;
    }

    public function nextHand()
    {
        if (!empty($this->splitHands) && $this->activeHandIndex < count($this->splitHands) - 1) {
            $this->activeHandIndex++;
            return true;
        }
        return false;
    }

    public function hasMoreHands()
    {
        return !empty($this->splitHands) && $this->activeHandIndex < count($this->splitHands) - 1;
    }

    public function getHandCount()
    {
        if (!empty($this->splitHands)) {
            return count($this->splitHands);
        }
        return 1;
    }

    public function getHand($index = null)
    {
        if ($index === null) {
            return $this->getCurrentHand();
        }

        if (!empty($this->splitHands) && isset($this->splitHands[$index])) {
            return $this->splitHands[$index];
        }

        return $this->cards;
    }


    //с татистика побед и поражений + баланс
    public function recordWin()
    {
        $amount = $this->currentBet;
        $this->balance += $amount; // Netto win
        $this->stats->recordGame(true, $amount);
    }

    public function recordLoss()
    {
        $amount = $this->currentBet;
        // Balance already decreased in makeBet()
        $this->stats->recordGame(false, $amount);
    }

    public function getStats()
    {
        return $this->stats->getStats();
    }
}


function isBust($hand)
{
    if ($hand instanceof Player) {
        return $hand->getScore() > 21;
    } elseif (is_array($hand)) {
        return gameLogic::calculateHandScore($hand) > 21;
    }
    return false;
}
