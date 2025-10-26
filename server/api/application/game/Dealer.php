<?php

require_once('Player.php');
require_once('Deck.php');
require_once('gameLogic.php');

class Dealer extends Player
{
    public $visibleCards = []; # Видимые карты дилера
    public $hiddenCards = []; # Скрытые карты дилера
    private $cardsRevealed = false; # Флаг, который показывает раскрыты скрытые карты или нет

    public function __construct($id = 'dealer')
    {
        parent::__construct($id, 0); # Дилер имеет баланс = 0
        $this->visibleCards = [];
        $this->hiddenCards = [];
        $this->cardsRevealed = false;
    }

    # Обновление общего массива карт для совместимости с родительским классом
    private function updateAllCards()
    {
        $this->cards = array_merge($this->visibleCards, $this->hiddenCards);
    }

    # Функция добавляет карту дилеру
    public function addCard(Card $card, $hidden = false)
    {
        if ($hidden && !$this->cardsRevealed) {
            $this->hiddenCards[] = $card;
        } else {
            $this->visibleCards[] = $card;
        }

        $this->updateAllCards();

        return $this;
    }

    # Функция раскрывает скрытые карты, затем идет игра по правилам дилера
    /**
     * Правила:
     * 1) Раскрытие всех скрытых карт
     * 2) Добор карт, пока счет меньше 17
     * 3) Остановка при счете 17 или выше
     * 4) В заключении проверка на перебор после каждой карты
     */
    public function revealCards(Deck $deck) # $deck - колода для добора карт
    {
        $this->cardsRevealed = true;

        foreach ($this->hiddenCards as $card) {
            $this->visibleCards[] = $card;
        }

        $this->hiddenCards = [];
        $this->updateAllCards();

        while ($this->getScore() < 17) {
            $newCard = $deck->draw();
            if ($newCard === null) {
                break; # Конец колоды
            }

            $this->addCard($newCard, false);

            # Проверка на перебор
            if (isBust($this)) {
                break;
            }
        }
    }

    # Функция возвращает счет дилера
    # Если карты не раскрыты, считает только видимые, иначе считает все
    # $incHidden отвечает за то, что включать ли скрытые карты в подсчет или нет
    public function getScore($incHidden = null)
    {
        if ($incHidden === null) {
            $incHidden = $this->cardsRevealed;
        }

        if ($incHidden) {
            return parent::getScore();
        } else {
            return gameLogic::calculateHandScore($this->visibleCards);
        }
    }

    # Функция возвращает видимые карты дилера
    public function getVisibleCards() 
    {
        return $this->visibleCards;
    }

    # Функция возращает скрытые карты дилера
    public function getHiddenCards()
    {
        return $this->hiddenCards;
    }

    # Проверка на раскрытость карт дилера
    public function areCardsRevealed()
    {
        return $this->cardsRevealed;
    }

    # Сброс параметров дилера для новой игры
    public function reset()
    {
        $this->cards = [];
        $this->visibleCards = [];
        $this->hiddenCards = [];
        $this->cardsRevealed = false;
    }
}