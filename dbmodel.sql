
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

CREATE TABLE IF NOT EXISTS `INFLUENCE` (
  `card_id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(8) NOT NULL COMMENT 'city',
  `card_type_arg` varchar(10) NULL COMMENT 'influence/candidate/assassin',
  `card_location` varchar(16) NOT NULL COMMENT 'player_id|deck|influence_slot',
  `card_location_arg` TINYINT COMMENT 'may be null',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `LOCATION` (
  `card_id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(8) NOT NULL COMMENT 'city',
  `card_type_arg` varchar(16) NOT NULL COMMENT 'battle name',
  `card_location` varchar(16) NOT NULL COMMENT 'deck, board, player, unclaimed,persian',
  `card_location_arg` TINYINT COMMENT 'slot #',
  `attacker` varchar(16) COMMENT 'main attacker',
  `defender` varchar(16) COMMENT 'main defender',
  `permissions` varchar(85) COMMENT 'comma-delimited cities',
  `persia1` varchar(16) COMMENT 'shared by Persian players',
  `persia2` varchar(16) COMMENT 'shared by Persian player!',
  `persia3` varchar(16) COMMENT 'shared by 3 persian players!!',
  `persia4` varchar(16) COMMENT 'shared by 4 persian players!!!',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `MILITARY` (
  `id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `city` varchar(8) NOT NULL COMMENT 'city',
  `type` varchar(8) NOT NULL COMMENT 'hoplite/trireme',
  `strength` TINYINT UNSIGNED NOT NULL COMMENT 'strength',
  `location` varchar(16) NOT NULL COMMENT 'city/deadpool/location/player',
  `battlepos` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'attacker/defender+main/ally',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0 = neutral, 1 = allies, -1 = enemies
-- redundant but we track both binary relationships city1<->city2
CREATE TABLE IF NOT EXISTS `WARS` (
  `id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(8) NOT NULL COMMENT 'city',
  `argos` TINYINT NOT NULL DEFAULT 0,
  `athens` TINYINT NOT NULL DEFAULT 0,
  `corinth` TINYINT NOT NULL DEFAULT 0,
  `megara` TINYINT NOT NULL DEFAULT 0,
  `sparta` TINYINT NOT NULL DEFAULT 0,
  `thebes` TINYINT NOT NULL DEFAULT 0,
  `persia` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- custom entries to player table
ALTER TABLE `player` ADD `special_tile` varchar(18) NOT NULL COMMENT 'label for Special Tile';
ALTER TABLE `player` ADD `special_tile_used` BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `special_tile_pass` BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `argos` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `athens` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `corinth` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `megara` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `sparta` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `thebes` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `persia` BOOLEAN NOT NULL DEFAULT 0;