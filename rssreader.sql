SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `config` (
  `param` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `param` (`param`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feeds` (
  `id_feed` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `favicon` varchar(255) DEFAULT NULL,
  `active` bit(1) NOT NULL default b'1',
  PRIMARY KEY (`id_feed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

CREATE TABLE IF NOT EXISTS `folders` (
  `id_folder` int(10) unsigned DEFAULT NULL,
  `id_user` int(10) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=915 ;

CREATE TABLE IF NOT EXISTS `readed_posts` (
  `id_post` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL,
  KEY `id_post` (`id_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `starred_posts` (
  `id_post` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `user_feed` (
  `id_feed` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_folder` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`param`, `value`) VALUES
('admin', '1'),
('feed_updatable', 'true'),
('max_feeds_per_update', '10'),
('max_posts_to_show', '50'),
('minutes_between_updates', '30'),
('show_favicons', 'true'),
('timezone', 'Europe/Madrid');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
