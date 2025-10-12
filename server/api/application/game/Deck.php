<?php

require_once('Card.php');

class Deck {
    private $cards = []; # Массив карт в колоде
    
     # Массив всех мастей в колоде
    private $suits = [
        'hearts',    # Черви
        'diamonds',  # Бубны
        'clubs',     # Трефы
        'spades'     # Пики
    ];
    
     # Массив всех рангов карт в колоде
    private $ranks = [  
        '2',   
        '3',   
        '4',   
        '5',   
        '6',   
        '7',   
        '8',   
        '9',   
        '10',  
        'J',   # Валет
        'Q',   # Дама
        'K',   # Король
        'A'    # Туз
    ];

    # Автоматически создает полную колоду из 52 карт при создании экземпляра класса
    public function __construct() {
        $this->createDeck();
    }

    # Создает полную колоду из 52 карт
    private function createDeck() {
        $this->cards = [];
        
        # Создание карты для каждой масти
        foreach ($this->suits as $suit) {
            # Создание карты каждого ранга для текущей масти
            foreach ($this->ranks as $rank) {
                $this->cards[] = new Card($suit, $rank);
            }
        }
    }

    # Перемешивает карты в колоде
    public function shuffle() {
        shuffle($this->cards);
        return $this;
    }

    # Вытягивает (раздает) одну карту из колоды
    public function draw() {
        if (empty($this->cards)) {
            return null; # Колода пуста - нечего раздавать
        }

        # Удаляет и возвращает последнюю карту
        return array_pop($this->cards);
    }

    # Возвращает все карты в колоде
    public function getCards() {
        return $this->cards;
    }

    # Возвращает количество карт в колоде
    public function getCount() {
        return count($this->cards);
    }

    # Сбрасывает колоду к исходному состоянию
    public function reset() {
        $this->createDeck();
        return $this;
    }
}