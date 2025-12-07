<?php

class Deck {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ============================================================
    // DECK MANAGEMENT
    // ============================================================

    public function createShuffledDeck() {
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E'];
        $suits = ['H', 'D', 'C', 'S'];
        $deck = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = $value . $suit;
            }
        }

        shuffle($deck);
        return implode('', $deck);
    }

    public function reinitializeDeck($roomId) {
        $newDeck = $this->createShuffledDeck();
        return $this->db->saveDeck($roomId, $newDeck);
    }

    public function getCard($roomId) {
        $deckString = $this->db->loadDeck($roomId);
        if (empty($deckString) || strlen($deckString) < 2) {
            return false;
        }

        $card = substr($deckString, 0, 2);
        $remainingDeck = substr($deckString, 2);

        $this->db->saveDeck($roomId, $remainingDeck);
        return $card;
    }

    public function getCards($roomId, $count) {
        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $card = $this->getCard($roomId);
            if ($card === false) {
                break;
            }

            $cards[] = $card;
        }

        return $cards;
    }
}
?>
