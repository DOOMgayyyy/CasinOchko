<?php

class gameLogic {
    # Рассчитывает очки руки с учетом правил игры 
    public static function calculateHandScore(array $cards) {
        $score = 0;  # Общая сумма очков
        $aces = 0;   # Количество тузов в руке

        # Первый проход: подсчитываем базовые очки и количество тузов
        foreach ($cards as $card) {
            if ($card->rank === 'A') {
                $aces++;
                $score += 11; # Изначально туз считается как 11
            } else {
                $score += $card->value; # Добавляем базовое значение карты
            }
        }

        # Второй проход: оптимизируем значение тузов
        # Если сумма больше 21 и есть тузы, переводим их в единицы
        while ($score > 21 && $aces > 0) {
            $score -= 10; # Переводим туз с 11 на 1 (разница = 10)
            $aces--;      # Уменьшаем счетчик доступных для перевода тузов
        }

        return $score;
    }

    # Проверяет, является ли рука блэкджеком (натуральным числом 21)
    public static function isBlackjack(array $cards) {
        return count($cards) === 2 && self::calculateHandScore($cards) === 21;
    }

    # Проверяет, является ли рука перебором (больше 21 очка)
    public static function isBust(array $cards) {
        return self::calculateHandScore($cards) > 21;
    }
}