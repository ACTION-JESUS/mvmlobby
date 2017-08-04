SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `group_lobby` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `lobby_id` varchar(25) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `player_initialized` tinyint(1) NOT NULL DEFAULT '0',
  `invitations_sent` tinyint(1) NOT NULL DEFAULT '0',
  `mission_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `slots_available` int(11) NOT NULL DEFAULT '5',
  `mvm_group_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_group_lobby_1_idx` (`player_id`),
  KEY `fk_group_lobby_2_idx` (`mission_id`),
  KEY `fk_group_lobby_3_idx` (`region_id`),
  KEY `fk_group_lobby_4_idx` (`mvm_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

CREATE TABLE IF NOT EXISTS `mission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tour_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `map_bitmask` int(11) NOT NULL,
  `sortorder` int(11) NOT NULL,
  `map_name` varchar(50) DEFAULT NULL,
  `wiki_url` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

CREATE TABLE IF NOT EXISTS `mvm_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gid` varchar(25) NOT NULL,
  `custom_url` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `player` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `steamid` varchar(25) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `region` varchar(3) DEFAULT NULL,
  `site_status` varchar(10) NOT NULL,
  `image_url_small` varchar(200) DEFAULT NULL,
  `image_url_medium` varchar(200) DEFAULT NULL,
  `image_url_large` varchar(200) DEFAULT NULL,
  `total_tours` int(11) NOT NULL,
  `tod_tickets` int(11) NOT NULL,
  `summary_last_updated` datetime DEFAULT NULL,
  `inventory_last_updated` datetime DEFAULT NULL,
  `last_known_status_code` int(11) DEFAULT NULL,
  `profile_visibility` tinyint(4) DEFAULT NULL,
  `inventory_status` tinyint(4) DEFAULT NULL,
  `inventory_unavailable` tinyint(1) DEFAULT NULL,
  `is_playing_tf2` tinyint(1) DEFAULT NULL,
  `current_mvm_mission_id` int(11) DEFAULT NULL,
  `current_game_name` varchar(50) DEFAULT NULL,
  `current_game_ipport` varchar(20) DEFAULT NULL,
  `last_active_time` datetime DEFAULT NULL,
  `session_salt` bigint(20) DEFAULT NULL,
  `session_expiration` datetime NOT NULL,
  `robots_killed` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `steamid` (`steamid`),
  KEY `idx_player_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4674 ;

CREATE TABLE IF NOT EXISTS `player_friend` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `friend_id` bigint(20) NOT NULL,
  `favorite` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`player_id`,`friend_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4494 ;

CREATE TABLE IF NOT EXISTS `player_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `mvm_group_id` bigint(20) NOT NULL,
  `is_moderator` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id` (`player_id`,`mvm_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1392 ;

CREATE TABLE IF NOT EXISTS `player_notes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `target_player_id` bigint(20) NOT NULL,
  `post_date` datetime NOT NULL,
  `is_public` tinyint(1) NOT NULL,
  `note` varchar(512) NOT NULL,
  PRIMARY KEY (`player_id`,`target_player_id`,`is_public`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_target_player_id` (`target_player_id`),
  KEY `idx_player_id` (`player_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=71 ;

CREATE TABLE IF NOT EXISTS `player_total_tours_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `date_changed` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `player_tour` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `tour_last_changed_date` datetime DEFAULT NULL,
  `mission_bitmask` int(11) DEFAULT NULL,
  PRIMARY KEY (`player_id`,`tour_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12438 ;

CREATE TABLE IF NOT EXISTS `player_tour_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `date_changed` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_tour_date` (`player_id`,`tour_id`,`date_changed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=88201 ;

CREATE TABLE IF NOT EXISTS `region` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(2) NOT NULL,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `servers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ipport` varchar(21) NOT NULL,
  `last_known_used` date DEFAULT NULL,
  `is_source_engine_game` tinyint(1) DEFAULT NULL,
  `mission_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `player_count` int(11) DEFAULT NULL,
  `bad_read_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipport` (`ipport`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32794 ;

CREATE TABLE IF NOT EXISTS `server_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(25) NOT NULL,
  `region_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

CREATE TABLE IF NOT EXISTS `steam_status` (
  `id` int(11) NOT NULL,
  `description` varchar(30) NOT NULL,
  `color` varchar(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `temp_group_members` (
  `steamid` varchar(25) NOT NULL,
  PRIMARY KEY (`steamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tour` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `difficulty` varchar(25) NOT NULL,
  `defindex` int(11) NOT NULL,
  `sortorder` int(11) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `text_id` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;


ALTER TABLE `group_lobby`
  ADD CONSTRAINT `fk_group_lobby_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_2` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_3` FOREIGN KEY (`region_id`) REFERENCES `region` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_4` FOREIGN KEY (`mvm_group_id`) REFERENCES `mvm_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `player_total_tours_history`
  ADD CONSTRAINT `fk_player_id` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
