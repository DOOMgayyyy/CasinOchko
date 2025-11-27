<?php


class Deck {
    /**
     * Конструктор класса Lobby
     * @param DB $db Объект для работы с базой данных
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    
    /**
     * Создает и перемешивает колоду (строка без разделителей)
     * Формат: ЗначениеМасть (например: 2H, AC, ED...)
     */
    private function createShuffledDeck()
    {
        // Сразу используем нужные символы:
        // 10->A, 11->B (Валет), 12->C (Дама), 13->D (Король), 14->E (Туз)
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E'];
        $suits = ['H', 'D', 'C', 'S']; 

        $deck = [];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                // Просто склеиваем значение и масть: "2H", "AE" и т.д.
                $deck[] = $value . $suit;
            }
        }

        if (!shuffle($deck)) {
            return ['error' => 806];
        }

        // Возвращаем строку без разделителей: "2H3D4C...AE..."
        return implode('', $deck);
    }
}