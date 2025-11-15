-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 15 2025 г., 17:56
-- Версия сервера: 8.0.30
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `casinochko`
--

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица сообщений в чате';

-- --------------------------------------------------------

--
-- Структура таблицы `message_hashes`
--

CREATE TABLE `message_hashes` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица хэшей сообщений';

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('open','private') NOT NULL,
  `status` enum('playing','closed') NOT NULL,
  `current_member_id` bigint UNSIGNED DEFAULT NULL,
  `private_code` varchar(4) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `deckOfCards` text COMMENT 'номинал карты - 16-ричное число, масть - один из символов: HDCS',
  `turn_start_time` datetime DEFAULT NULL COMMENT 'Время начала текущего хода'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица комнат';

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `type`, `status`, `current_member_id`, `private_code`, `hash`, `deckOfCards`, `turn_start_time`) VALUES
(128, 'private', 'playing', NULL, 'NKQB', '1652ad94fb2841555519661c93218360', '[\"AC\",\"8C\",\"5D\",\"4S\",\"AH\",\"7D\",\"6H\",\"3C\",\"KC\",\"9H\",\"6C\",\"QD\",\"6S\",\"4H\",\"2S\",\"10C\",\"QH\",\"JS\",\"10H\",\"KH\",\"2H\",\"JC\",\"5S\",\"4D\",\"8D\",\"6D\",\"4C\",\"AD\",\"JH\",\"2D\",\"10S\",\"9S\",\"10D\",\"9C\",\"5C\",\"JD\",\"7S\",\"5H\",\"7C\",\"KS\",\"3H\",\"7H\",\"QC\",\"8S\",\"3D\",\"AS\",\"9D\",\"3S\",\"QS\",\"2C\",\"8H\",\"KD\"]', NULL),
(129, 'open', 'playing', NULL, NULL, '8afa8b4768a9bcdda7e9d47b7d0408a6', '[\"KS\",\"7D\",\"9D\",\"KC\",\"5C\",\"9C\",\"2S\",\"QS\",\"10S\",\"9H\",\"3H\",\"8S\",\"7H\",\"2D\",\"8C\",\"9S\",\"10C\",\"JH\",\"KH\",\"AD\",\"5D\",\"JS\",\"3D\",\"2H\",\"6C\",\"8D\",\"8H\",\"6D\",\"4D\",\"JD\",\"4C\",\"7S\",\"QH\",\"4H\",\"AS\",\"QD\",\"6H\",\"JC\",\"QC\",\"5H\",\"6S\",\"3C\",\"AC\",\"2C\",\"KD\",\"7C\",\"AH\",\"10D\",\"3S\",\"4S\",\"10H\",\"5S\"]', NULL),
(130, 'private', 'playing', NULL, 'ZSVH', 'b173229b350cef36a3da8b3898757ba7', '[\"KC\",\"6C\",\"4C\",\"2D\",\"10H\",\"5C\",\"10C\",\"QC\",\"4H\",\"5D\",\"2S\",\"3C\",\"8C\",\"8S\",\"7S\",\"7C\",\"9D\",\"6D\",\"AS\",\"5S\",\"QH\",\"2C\",\"KD\",\"7D\",\"JC\",\"3D\",\"4D\",\"4S\",\"7H\",\"AD\",\"6S\",\"3H\",\"JH\",\"QS\",\"5H\",\"JD\",\"2H\",\"AC\",\"8D\",\"9H\",\"9C\",\"10S\",\"10D\",\"6H\",\"KS\",\"9S\",\"AH\",\"8H\",\"3S\",\"JS\",\"QD\",\"KH\"]', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `room_members`
--

CREATE TABLE `room_members` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bet` int DEFAULT '0',
  `types` tinyint(1) DEFAULT '0',
  `cards` text NOT NULL COMMENT 'Карты игрока в формате: номинал (hex) + масть (HDCS)',
  `status` varchar(20) DEFAULT 'active' COMMENT 'Статус игрока: active, folded, waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица членов комнат';

--
-- Дамп данных таблицы `room_members`
--

INSERT INTO `room_members` (`id`, `room_id`, `user_id`, `bet`, `types`, `cards`, `status`) VALUES
(138, 130, 3, 0, 0, '', 'active');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `balance` int DEFAULT '5000',
  `token` varchar(255) DEFAULT NULL,
  `total_played` int DEFAULT '0',
  `total_win` int DEFAULT '0',
  `total_balance` int DEFAULT '5000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `balance`, `token`, `total_played`, `total_win`, `total_balance`) VALUES
(1, 'dev@dev.com', '$2y$10$GxI0vspK6N6IulLUOvsB6.Zw5Ky4MkmCCAfzme34kbEo6qtv4yn5y', 'devac', 5000, 'd06d7980f2e7057cd7dd3801ef08451e', 0, 0, 100),
(2, 'kam@lo.com', '25d55ad283aa400af464c76d713c07ad', 'Kamilla', 100, 'c06d7980f2e7057cd7dd3801ef08451e', 0, 0, 100),
(3, 'yana@awsi.com', '25d55ad283aa400af464c76d713c07ad', 'join', 100, '2e993aaa33b961153109d744cb27a6d5', 10000, 0, 100),
(5, 'job@com.ru', '25d55ad283aa400af464c76d713c07ad', 'Jober', 5000, NULL, 0, 0, 100);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `message_hashes`
--
ALTER TABLE `message_hashes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `room_members`
--
ALTER TABLE `room_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_in_room` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `message_hashes`
--
ALTER TABLE `message_hashes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT для таблицы `room_members`
--
ALTER TABLE `room_members`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `message_hashes`
--
ALTER TABLE `message_hashes`
  ADD CONSTRAINT `message_hashes_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `room_members`
--
ALTER TABLE `room_members`
  ADD CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
