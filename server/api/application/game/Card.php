<?php

class Card {
    public $suit; # Масть карты 
    public $rank; # Ранг карты 
    public $value; # Базовое значение карты для подсчета очков

    public function __construct($suit, $rank) {
        $this->suit = $suit;
        $this->rank = $rank;
        $this->value = $this->calculateValue($rank);
    }

     # Рассчитывает базовое значение карты по ее рангу
    private function calculateValue($rank) {
        switch ($rank) {
            case 'A':
                return 11; # Туз по умолчанию 11, может быть изменен на 1
            case 'K':
            case 'Q':
            case 'J':
                return 10; # Все карты стоят 10 очков
            default:
                return (int)$rank; # Числовые карты равны своему номиналу
        }
    }

     # Возвращает строковое представление карты
     # Формат: "ранг of масть" (например, "A of hearts")
    public function __toString() {
        return $this->rank . ' of ' . $this->suit;
    }
}