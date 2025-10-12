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

    # Определяет победителя раунда между игроком и дилером
    public static function determineWinner(Player $player, Dealer $dealer) {
        $playerScore = $player->getScore();
        $dealerScore = $dealer->getScore(true);

        if ($playerScore > 21) {
            return 'dealer_win';
        }

        if ($dealerScore > 21) {
            return 'player_win';
        }

        if ($playerScore > $dealerScore) {
            return 'player_win';
        } elseif ($playerScore < $dealerScore) {
            return 'dealer_win';
        } else {
            return 'push'; # Ничья
        }
    }

    public static function payout(Player $player, $result) {
        $bet = $player->currentBet;
        $payout = 0;

        if ($result === 'player_win') {
            # Проверка на блэкджек
            $playerHand = $player->getHand();
            $isBlackjack = self::isBlackjack($playerHand);

            if ($isBlackjack) {
                $payout = $bet * 2.5; # Возврат ставки + 1.5x, если блэкджек
            } else {
                $payout = $bet * 2; # Возврат ставки + 1x
            }

            $player->balance += $payout;

        } elseif ($result === 'dealer_win') {
            $payout = 0;
        } elseif ($result === 'push') {
            $payout = $bet;
            $player->balance += $payout; # Возврат ставки, если ничья
        }

        $player->currentBet = 0;

        return $payout;
    }
}