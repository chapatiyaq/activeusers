CREATE TABLE IF NOT EXISTS `activeusers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `record` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `activeusers_cache` (
  `wiki` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `record` mediumblob NOT NULL,
  PRIMARY KEY `wiki` (`wiki`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `activeusers_cache` (`wiki`) VALUES
('commons'),
('counterstrike'),
('dota2'),
('fighters'),
('hearthstone'),
('heroes'),
('overwatch'),
('rocketleague'),
('smash'),
('starcraft'),
('starcraft2'),
('warcraft');