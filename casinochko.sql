-- phpMyAdmin SQL Dump
-- version 5.2.0
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 09 2025 г.
-- Версия сервера: 8.0.30+
-- Версия PHP: 7.2.34+

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
CREATE DATABASE IF NOT EXISTS `casinochko` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `casinochko`;

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--
DROP TABLE IF EXISTS `messages`;
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
DROP TABLE IF EXISTS `message_hashes`;
CREATE TABLE `message_hashes` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица хэшей сообщений';

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('open','private') NOT NULL,
  `status` enum('playing','closed') NOT NULL,
  `current_member_id` bigint UNSIGNED DEFAULT NULL,
  `private_code` varchar(4) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `deckOfCards` text COMMENT 'JSON-массив карт: ["AH","KD",...]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица комнат';

-- --------------------------------------------------------

--
-- Структура таблицы `room_members`
--
DROP TABLE IF EXISTS `room_members`;
CREATE TABLE `room_members` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bet` int DEFAULT 0,
  `types` tinyint(1) DEFAULT 0,
  `cards` text NOT NULL DEFAULT '' COMMENT 'Карты игрока в формате JSON: ["AH","KD",...]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица членов комнат';

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `balance` int DEFAULT 5000,
  `token` varchar(255) DEFAULT NULL,
  `total_played` int DEFAULT 0,
  `total_win` int DEFAULT 0,
  `total_balance` int DEFAULT 5000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `message_hashes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_private_code` (`private_code`),
  ADD KEY `type_status` (`type`,`status`);

ALTER TABLE `room_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_in_room` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT
--
ALTER TABLE `messages` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `message_hashes` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `rooms` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `room_members` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `message_hashes`
  ADD CONSTRAINT `message_hashes_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

ALTER TABLE `room_members`
  ADD CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- CHECK-констрейнт для формата private_code (только 4 заглавные буквы или NULL)
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `chk_private_code_format`
  CHECK (
    `private_code` IS NULL 
    OR (`private_code` REGEXP '^[A-Z]{4}$')
  );

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;