-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Окт 08 2025 г., 17:38
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
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `balance` int NOT NULL DEFAULT '5000',
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `balance`, `token`) VALUES
(1, 'vasya@gmail.com', '6504641b0e9392df70c83029d5f6068d', 'Vasya Pupkin', 0, NULL),
(3, 'masha@ya.ru', '317288ae363ed746c29344a47ae7969e', 'Masha Ivanova', 0, 'ce7a363c59b9d1a751d33127e6a0b6ca'),
(4, 'huy@gmail.com', '123456', 'Suka Blyat', 0, NULL),
(6, 'JollyGolf@gmail.com', '$2y$10$jW28bFt8v.1mBOJPI77fo.gMcYLlsxTUlcbxxq5fPvJ6P1GmMVPQS', 'Shevcov', 0, 'd4b6b2c8a77f458fd1a5f2eebdf1829c'),
(7, 'joiuyt@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'HuySuka', 0, '90111b645395ced6309199874641d0a2'),
(8, 'dev@gmail.com', 'a320abd2b1bfc8d7350b48ee089b75c3', 'devops', 0, 'e7b3aef3eda1dd583ca45d6d2699dcff'),
(9, 'joiuybt@gmail.com', 'c88cef86d0d26225d9fc58ee78348a02', 'HuySukas', 0, '696b5a3e654d316d7a4cbfcd61b2bf31'),
(10, 'jinglic@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'DOOMgay', 5000, NULL),
(11, 'suaknah@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'DODEP2025', 5000, 'f173d9b6521b378fb5d9c62e4ff7f221');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
