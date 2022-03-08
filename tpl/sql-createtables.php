<?php

$sql_query = <<<END

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE IF NOT EXISTS `{$config->db_prefix}chathistory` (
  `id` bigint(20) NOT NULL,
  `datetime` datetime NOT NULL,
  `chat_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `is_text` tinyint(1) NOT NULL,
  `history_data` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$config->db_prefix}sessiondata` (
  `user_id` bigint(20) NOT NULL,
  `chat_id` bigint(20) NOT NULL,
  `param_name` varchar(64) NOT NULL,
  `param_value` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$config->db_prefix}settings` (
  `param_name` varchar(64) NOT NULL,
  `param_value` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$config->db_prefix}users` (
  `id` bigint(20) NOT NULL,
  `first_name` varchar(256) NOT NULL,
  `last_name` varchar(256) NOT NULL,
  `username` varchar(256) NOT NULL,
  `phone_number` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `{$config->db_prefix}chathistory`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `{$config->db_prefix}sessiondata`
  ADD PRIMARY KEY (`user_id`,`chat_id`, `param_name`);

ALTER TABLE `{$config->db_prefix}settings`
  ADD PRIMARY KEY (`param_name`);

ALTER TABLE `{$config->db_prefix}users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `{$config->db_prefix}chathistory`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
END;