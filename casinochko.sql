-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 10 2025 г., 01:10
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
  `deckOfCards` text COMMENT 'JSON-массив карт: ["AH","KD",...]'
) ;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `type`, `status`, `current_member_id`, `private_code`, `hash`, `deckOfCards`) VALUES
(9, 'private', 'playing', NULL, 'YKYN', '30ee435595786e81cf8f32e80b49b786', '[\"QS\",\"5C\",\"2D\",\"7S\",\"KH\",\"8S\",\"AH\",\"9D\",\"9H\",\"JD\",\"3S\",\"3C\",\"4H\",\"KC\",\"KS\",\"QC\",\"10D\",\"8D\",\"2C\",\"2S\",\"8C\",\"6S\",\"6C\",\"7C\",\"AC\",\"8H\",\"10H\",\"9S\",\"7D\",\"AD\",\"4S\",\"KD\",\"4C\",\"10C\",\"QH\",\"6D\",\"6H\",\"AS\",\"2H\",\"10S\",\"3D\",\"QD\",\"JS\",\"5D\",\"4D\",\"7H\",\"JC\",\"5S\",\"9C\",\"JH\",\"5H\",\"3H\"]');

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
  `cards` text NOT NULL COMMENT 'Карты игрока в формате JSON: ["AH","KD",...]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица членов комнат';

--
-- Дамп данных таблицы `room_members`
--

INSERT INTO `room_members` (`id`, `room_id`, `user_id`, `bet`, `types`, `cards`) VALUES
(10, 9, 2, 0, 0, ''),
(11, 9, 3, 0, 0, '');

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
(2, 'yana@awsi.com', '$2y$10$yhQWfuyAW63mcy3ZksXBFOuv4Y4m2CuCoFOucVO768BAHMekzYooe', 'owerlord', 8000, '527ad0d366406ff3acf9d677ae9f72f2', 0, 0, 5000),
(3, 'dev@dev.com', '$2y$10$hvDnmfqRDzCBShPSqWrm2.4YVxLBpS/.FKS.FG7DiBKCwjVnI0dpq', 'dev', 5000, '8a1954228142f4c6f15188d5649b560e', 0, 0, 5000);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_private_code` (`private_code`),
  ADD KEY `type_status` (`type`,`status`);

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `room_members`
--
ALTER TABLE `room_members`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
