<?php
require_once('Card.php');
require_once('gameLogic.php');

class Player
{
    public $id;
    public $cards = [];
    public $balance;
    public $currentBet = 0;
    public $splitHands = []; //для сплита рука
    public $activeHandIndex = 0; //индекс активной руки
    public $status;

    public function __construct($id, $balance = 1000) //заглушка 1000 баланс 
    {
        $this->id = $id;
        $this->balance = $balance;
        $this->cards = [];
        $this->splitHands = [];
        $this->activeHandIndex = 0;
        $this->status = 'player';
    }

    //устанавливаем статус
    public function setStatus($newStatus)
    {
        $validStatuses = ['player', 'spectator'];
        if (in_array($newStatus, $validStatuses)) {
            $this->status = $newStatus;
            return true;
        }
        return false;
    }

    public function isSpectator()
    {
        return $this->status === 'spectator';
    }


    //получение текущую активную руку
    private function getCurrentHand()
    {
        if (!empty($this->splitHands)) {
            return $this->splitHands[$this->activeHandIndex];
        }
        return $this->cards;
    }

    //получение ссылки текущей игры для изменений
    private function &getCurrentHandRef()
    {
        if (!empty($this->splitHands)) {
            return $this->splitHands[$this->activeHandIndex];
        }
        return $this->cards;
    }

    //добавление карты в текущую активную руку
    public function addCard(Card $card)
    {
        $currentHand = &$this->getCurrentHandRef();

        //5карт
        if (count($currentHand) >= 5) {
            return $this;
        }

        $currentHand[] = $card;
        return $this;
    }

    //расчет очков текущей активной руки
    public function getScore()
    {
        $currentHand = $this->getCurrentHand();
        return gameLogic::calculateHandScore($currentHand);
    }

    //получение счета конкрутной руки
    public function getHandScore($handIndex = null)
    {
        if ($handIndex !== null && isset($this->splitHands[$handIndex])) {
            return gameLogic::calculateHandScore($this->splitHands[$handIndex]);
        }
        return $this->getScore();
    }



    //сплит
    public function split($deck)
    {
        //проверка возможен ли сплит
        if (count($this->cards) != 2 || $this->cards[0]->rank !== $this->cards[1]->rank) {
            return false;
        }

        //проверка хватит ли на ставку 
        if ($this->balance < $this->currentBet) {
            return false;
        }

        $this->balance -= $this->currentBet; //снятие ставки за 2 руку

        //создание двух рук из 2 карт
        $hand1 = [$this->cards[0]];
        $hand2 = [$this->cards[1]];

        //добавляем по одной карте в каждую руку
        $hand1[] = $deck->draw();
        $hand2[] = $deck->draw();

        //сохраняем руки в splitHands
        $this->splitHands = [$hand1, $hand2];
        $this->activeHandIndex = 0;

        //очищаем основную руку
        $this->cards = [];

        return true;
    }

    //смена руки
    public function nextHand()
    {
        if (!empty($this->splitHands) && $this->activeHandIndex < count($this->splitHands) - 1) {
            $this->activeHandIndex++;
            return true;
        }
        return false;
    }

    //проверка на еще 1 руку для игры
    public function hasMoreHands()
    {
        return !empty($this->splitHands) && $this->activeHandIndex < count($this->splitHands) - 1;
    }

    //получение количесвто рук
    public function getHandCount()
    {
        if (!empty($this->splitHands)) {
            return count($this->splitHands);
        }
        return 1;
    }

    //получение руки по индексу
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
}

//проверка на перебор руки
function isBust($hand)
{
    if ($hand instanceof Player) {
        return $hand->getScore() > 21;
    } elseif (is_array($hand)) {
        return gameLogic::calculateHandScore($hand) > 21;
    }
    return false;
}

