-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Дек 15 2025 г., 00:32
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
-- Структура таблицы `messagehashes`
--

CREATE TABLE `messagehashes` (
  `id` bigint UNSIGNED NOT NULL,
  `roomid` bigint UNSIGNED NOT NULL,
  `hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица хэшей для чата';

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` bigint UNSIGNED NOT NULL,
  `roomid` bigint UNSIGNED NOT NULL,
  `userid` bigint UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица сообщений в чате';

-- --------------------------------------------------------

--
-- Структура таблицы `roommembers`
--

CREATE TABLE `roommembers` (
  `id` bigint UNSIGNED NOT NULL,
  `roomid` bigint UNSIGNED NOT NULL,
  `userid` bigint UNSIGNED NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'spectator',
  `bet` int DEFAULT '0',
  `cards` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT 'HEX строка карт игрока'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица участников комнат';

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('open','private') NOT NULL,
  `status` enum('waiting','waiting_for_bets','playing','show_results','closed') NOT NULL DEFAULT 'waiting',
  `current_member_id` bigint UNSIGNED DEFAULT NULL,
  `privatecode` varchar(10) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `turn_start_time` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT CURRENT_TIMESTAMP,
  `deckOfCards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'Колода карт в виде строки',
  `dealerCards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'Карты дилера'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Таблица игровых комнат';

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
  `totalplayed` int DEFAULT '0',
  `totalwin` int DEFAULT '0',
  `totalbalance` int DEFAULT '5000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `balance`, `token`, `totalplayed`, `totalwin`, `totalbalance`) VALUES
(2, 'yana@awsi.com', '$2y$10$yhQWfuyAW63mcy3ZksXBFOuv4Y4m2CuCoFOucVO768BAHMekzYooe', 'owerlord', 8000, '527ad0d366406ff3acf9d677ae9f72f2', 0, 0, 5000),
(3, 'dev@dev.com', '$2y$10$hvDnmfqRDzCBShPSqWrm2.4YVxLBpS/.FKS.FG7DiBKCwjVnI0dpq', 'dev', 5000, '8a1954228142f4c6f15188d5649b560e', 0, 0, 5000),
(4, 'vibes@vibes.com', '25d55ad283aa400af464c76d713c07ad', 'bob', 1625, 'b91dd5baf650b40bce3fa31d11880e32', 0, 0, 5000);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `messagehashes`
--
ALTER TABLE `messagehashes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roomid` (`roomid`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `roomid` (`roomid`),
  ADD KEY `userid` (`userid`);

--
-- Индексы таблицы `roommembers`
--
ALTER TABLE `roommembers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_in_room` (`roomid`,`userid`),
  ADD KEY `userid` (`userid`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `privatecode` (`privatecode`),
  ADD KEY `type_status` (`type`,`status`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `token` (`token`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `messagehashes`
--
ALTER TABLE `messagehashes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `roommembers`
--
ALTER TABLE `roommembers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `messagehashes`
--
ALTER TABLE `messagehashes`
  ADD CONSTRAINT `fk_messagehashes_room` FOREIGN KEY (`roomid`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_room` FOREIGN KEY (`roomid`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `roommembers`
--
ALTER TABLE `roommembers`
  ADD CONSTRAINT `fk_roommembers_room` FOREIGN KEY (`roomid`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
