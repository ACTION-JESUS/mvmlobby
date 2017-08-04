-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 28, 2017 at 08:56 PM
-- Server version: 5.5.35
-- PHP Version: 5.3.10-1ubuntu3.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mvmlobby`
--

-- --------------------------------------------------------

--
-- Table structure for table `group_lobby`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `map`
--

CREATE TABLE IF NOT EXISTS `map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `map`
--

INSERT INTO `map` (`id`, `name`) VALUES
(1, 'Decoy'),
(2, 'Coal Town'),
(3, 'Mannworks'),
(4, 'Big Rock'),
(5, 'Mannhattan'),
(6, 'Rottenburg');

-- --------------------------------------------------------

--
-- Table structure for table `mission`
--

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

--
-- Dumping data for table `mission`
--

INSERT INTO `mission` (`id`, `tour_id`, `map_id`, `name`, `map_bitmask`, `sortorder`, `map_name`, `wiki_url`) VALUES
(1, 1, 1, 'Doe''s Doom', 8, 1, 'mvm_decoy_intermediate', 'http://wiki.teamfortress.com/wiki/Doe''s_Doom_(mission)'),
(2, 1, 1, 'Day of Wreckoning', 16, 2, 'mvm_decoy_intermediate2', 'http://wiki.teamfortress.com/wiki/Day_of_Wreckening_(mission)'),
(3, 1, 2, 'Cave-in', 2, 3, 'mvm_coaltown_intermediate', 'http://wiki.teamfortress.com/wiki/Cave-in_(mission)'),
(4, 1, 2, 'Quarry', 4, 4, 'mvm_coaltown_intermediate2', 'http://wiki.teamfortress.com/wiki/Quarry_(mission)'),
(5, 1, 3, 'Mean Machines', 32, 5, 'mvm_mannworks_intermediate', 'http://wiki.teamfortress.com/wiki/Mean_Machines_(mission)'),
(6, 1, 3, 'Mann Hunt', 64, 6, 'mvm_mannworks_intermediate2', 'http://wiki.teamfortress.com/wiki/Mannhunt_(mission)'),
(7, 2, 1, 'Disk Deletion', 256, 1, 'mvm_decoy_advanced', 'http://wiki.teamfortress.com/wiki/Disk_Deletion_(mission)'),
(8, 2, 1, 'Data Demolition', 512, 2, 'mvm_decoy_advanced2', 'http://wiki.teamfortress.com/wiki/Data_Demolition_(mission)'),
(9, 2, 2, 'Ctrl+Alt+Destruction', 2, 3, 'mvm_coaltown_advanced', 'http://wiki.teamfortress.com/wiki/Ctrl%2BAlt%2BDestruction_(mission)'),
(10, 2, 2, 'CPU Slaughter', 4, 4, 'mvm_coaltown_advanced2', 'http://wiki.teamfortress.com/wiki/CPU_Slaughter_(mission)'),
(11, 2, 3, 'Machine Massacre', 32768, 5, 'mvm_mannworks_advanced', 'http://wiki.teamfortress.com/wiki/Machine_Massacre_(mission)'),
(12, 2, 3, 'Mech Mutilation', 65536, 6, 'mvm_mannworks_advanced2', 'http://wiki.teamfortress.com/wiki/Mech_Mutilation_(mission)'),
(13, 3, 4, 'Broken Parts', 2, 1, 'mvm_bigrock_advanced1', 'http://wiki.teamfortress.com/wiki/Broken_Parts_(mission)'),
(14, 3, 4, 'Bone Shaker', 4, 2, 'mvm_bigrock_advanced2', 'http://wiki.teamfortress.com/wiki/Bone_Shaker_(mission)'),
(15, 3, 1, 'Disintegration', 8, 3, 'mvm_decoy_advanced3', 'http://wiki.teamfortress.com/wiki/Disintegration_(mission)'),
(16, 4, 5, 'Empire Escalation', 2, 1, 'mvm_mannhattan_advanced1', 'http://wiki.teamfortress.com/wiki/Empire_Escalation_(mission)'),
(17, 4, 5, 'Metro Malice', 4, 2, 'mvm_mannhattan_advanced2', 'http://wiki.teamfortress.com/wiki/Metro_Malice_(mission)'),
(18, 4, 6, 'Hamlet Hostility', 8, 3, 'mvm_rottenburg_advanced1', 'http://wiki.teamfortress.com/wiki/Hamlet_Hostility_(mission)'),
(19, 4, 6, 'Bavarian Botbash', 16, 4, 'mvm_rottenburg_advanced2', 'http://wiki.teamfortress.com/wiki/Bavarian_Botbash_(mission)'),
(20, 5, 1, 'Desperation', 4, 1, 'mvm_decoy_expert1', 'http://wiki.teamfortress.com/wiki/Desperation_(mission)'),
(21, 5, 2, 'Cataclysm', 2, 2, 'mvm_coaltown_expert1', 'http://wiki.teamfortress.com/wiki/Cataclysm_(mission)'),
(22, 5, 3, 'Mannslaughter', 8, 3, 'mvm_mannworks_expert1', 'http://wiki.teamfortress.com/wiki/Mannslaughter_(mission)');

-- --------------------------------------------------------

--
-- Table structure for table `mvm_group`
--

CREATE TABLE IF NOT EXISTS `mvm_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gid` varchar(25) NOT NULL,
  `custom_url` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `mvm_group`
--

INSERT INTO `mvm_group` (`id`, `gid`, `custom_url`, `name`) VALUES
(1, '103582791434932048', 'twocitiesveterans', 'Two Cities Veterans');

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22624 ;


-- --------------------------------------------------------

--
-- Table structure for table `player_friend`
--

CREATE TABLE IF NOT EXISTS `player_friend` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `friend_id` bigint(20) NOT NULL,
  `favorite` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`player_id`,`friend_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10152 ;


-- --------------------------------------------------------

--
-- Table structure for table `player_group`
--

CREATE TABLE IF NOT EXISTS `player_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `mvm_group_id` bigint(20) NOT NULL,
  `is_moderator` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id` (`player_id`,`mvm_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1084 ;

-- --------------------------------------------------------

--
-- Table structure for table `player_notes`
--

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2125 ;

-- --------------------------------------------------------

--
-- Table structure for table `player_total_tours_history`
--

CREATE TABLE IF NOT EXISTS `player_total_tours_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `date_changed` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=113206 ;

-- --------------------------------------------------------

--
-- Table structure for table `player_tour`
--

CREATE TABLE IF NOT EXISTS `player_tour` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `tour_last_changed_date` datetime DEFAULT NULL,
  `mission_bitmask` int(11) DEFAULT NULL,
  PRIMARY KEY (`player_id`,`tour_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=38601 ;


-- --------------------------------------------------------

--
-- Table structure for table `player_tour_history`
--

CREATE TABLE IF NOT EXISTS `player_tour_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_id` bigint(20) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `tours_completed` int(11) NOT NULL,
  `date_changed` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_tour_date` (`player_id`,`tour_id`,`date_changed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=397226 ;

-- --------------------------------------------------------

--
-- Table structure for table `region`
--

CREATE TABLE IF NOT EXISTS `region` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(2) NOT NULL,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `region`
--

INSERT INTO `region` (`id`, `key`, `name`) VALUES
(1, 'NA', 'North America'),
(2, 'SA', 'South America'),
(3, 'EU', 'Europe'),
(4, 'AU', 'Australia'),
(5, 'RU', 'Russia'),
(6, 'AS', 'Asia'),
(7, 'AF', 'Africa');

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=269166 ;

-- --------------------------------------------------------

--
-- Table structure for table `server_location`
--

CREATE TABLE IF NOT EXISTS `server_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(25) NOT NULL,
  `region_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `server_location`
--

INSERT INTO `server_location` (`id`, `location`, `region_id`) VALUES
(1, 'Virginia', 1),
(2, 'Luxemburg', 3),
(3, 'Dubai', 6),
(4, 'Singapore', 6),
(5, 'Washington', 1),
(6, 'Stockholm', 3),
(7, 'Tokyo', 6);

-- --------------------------------------------------------

--
-- Table structure for table `server_region_time_summary`
--

CREATE TABLE IF NOT EXISTS `server_region_time_summary` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `summary_date` datetime NOT NULL,
  `player_count` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=90366 ;

-- --------------------------------------------------------

--
-- Table structure for table `server_time_summary`
--

CREATE TABLE IF NOT EXISTS `server_time_summary` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `summary_date` datetime NOT NULL,
  `player_count` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15401 ;

-- --------------------------------------------------------

--
-- Table structure for table `steam_status`
--

CREATE TABLE IF NOT EXISTS `steam_status` (
  `id` int(11) NOT NULL,
  `description` varchar(30) NOT NULL,
  `color` varchar(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `steam_status`
--

INSERT INTO `steam_status` (`id`, `description`, `color`) VALUES
(0, 'Offline', '#222'),
(1, 'Online', '#00f'),
(2, 'Busy', '#222'),
(3, 'Away', '#222'),
(4, 'Snooze', '#222'),
(5, 'Looking to Trade', '#222'),
(6, 'Looking to Play', '#00f');

-- --------------------------------------------------------

--
-- Table structure for table `temp_group_members`
--

CREATE TABLE IF NOT EXISTS `temp_group_members` (
  `steamid` varchar(25) NOT NULL,
  PRIMARY KEY (`steamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tour`
--

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

--
-- Dumping data for table `tour`
--

INSERT INTO `tour` (`id`, `name`, `difficulty`, `defindex`, `sortorder`, `short_name`, `text_id`) VALUES
(1, 'Operation Oil Spill', 'Intermediate', 870, 1, 'Oil Spill', 'oilspill'),
(2, 'Operation Steel Trap', 'Advanced', 726, 2, 'Steel Trap', 'steeltrap'),
(3, 'Operation Mecha Engine', 'Advanced', 975, 3, 'Mecha Engine', 'mechaengine'),
(4, 'Operation Two Cities', 'Advanced', 1066, 4, 'Two Cities', 'twocities'),
(5, 'Operation Gear Grinder', 'Expert', 871, 5, 'Gear Grinder', 'geargrinder');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `group_lobby`
--
ALTER TABLE `group_lobby`
  ADD CONSTRAINT `fk_group_lobby_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_2` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_3` FOREIGN KEY (`region_id`) REFERENCES `region` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_group_lobby_4` FOREIGN KEY (`mvm_group_id`) REFERENCES `mvm_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `player_total_tours_history`
--
ALTER TABLE `player_total_tours_history`
  ADD CONSTRAINT `fk_player_id` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
