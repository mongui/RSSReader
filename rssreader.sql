-- phpMyAdmin SQL Dump
-- version 3.4.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 15, 2013 at 12:11 PM
-- Server version: 5.5.20
-- PHP Version: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `rssreader`
--

DELIMITER $$
--
-- Functions
--
DROP FUNCTION IF EXISTS `regex_replace`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `regex_replace`(pattern VARCHAR(1000),replacement VARCHAR(1000),original VARCHAR(1000)) RETURNS varchar(1000) CHARSET utf8
    DETERMINISTIC
BEGIN 
 DECLARE temp VARCHAR(1000); 
 DECLARE ch VARCHAR(1); 
 DECLARE i INT;
 SET i = 1;
 SET temp = '';
 IF original REGEXP pattern THEN 
  loop_label: LOOP 
   IF i>CHAR_LENGTH(original) THEN
    LEAVE loop_label;  
   END IF;
   SET ch = SUBSTRING(original,i,1);
   IF NOT ch REGEXP pattern THEN
    SET temp = CONCAT(temp,ch);
   ELSE
    SET temp = CONCAT(temp,replacement);
   END IF;
   SET i=i+1;
  END LOOP;
 ELSE
  SET temp = original;
 END IF;
 RETURN temp;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `param` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `param` (`param`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feeds`
--

DROP TABLE IF EXISTS `feeds`;
CREATE TABLE IF NOT EXISTS `feeds` (
  `id_feed` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `favicon` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_feed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

DROP TABLE IF EXISTS `folders`;
CREATE TABLE IF NOT EXISTS `folders` (
  `id_folder` int(10) unsigned DEFAULT NULL,
  `id_user` int(10) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id_post` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_feed` int(10) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` varchar(255) DEFAULT NULL,
  `url` varchar(512) DEFAULT NULL,
  `title` text,
  `content` text,
  `clean_content` text,
  PRIMARY KEY (`id_post`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=824 ;

-- --------------------------------------------------------

--
-- Table structure for table `readed_posts`
--

DROP TABLE IF EXISTS `readed_posts`;
CREATE TABLE IF NOT EXISTS `readed_posts` (
  `id_post` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `starred_posts`
--

DROP TABLE IF EXISTS `starred_posts`;
CREATE TABLE IF NOT EXISTS `starred_posts` (
  `id_post` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(33) NOT NULL COMMENT 'MD5 = 32 chars.',
  `salt` varchar(25) CHARACTER SET ascii NOT NULL,
  `email` varchar(325) NOT NULL COMMENT '64 @ 253 . 6',
  `time_format` varchar(50) CHARACTER SET ascii NOT NULL DEFAULT 'd/m/Y H:i',
  `language` varchar(4) NOT NULL DEFAULT 'en',
  `auth_key` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_feed`
--

DROP TABLE IF EXISTS `user_feed`;
CREATE TABLE IF NOT EXISTS `user_feed` (
  `id_feed` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_folder` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
