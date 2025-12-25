CREATE TABLE IF NOT EXISTS `l4d2_stats` (
 `Steamid` varchar(32) NOT NULL,
 `Name` tinyblob NOT NULL,
 `Points` int(11) NOT NULL DEFAULT '0',
 `Time1` int(11) NOT NULL DEFAULT '0',
 `Time2` int(11) NOT NULL DEFAULT '0',
 `Boomer` int(11) NOT NULL DEFAULT '0',
 `Charger` int(11) NOT NULL DEFAULT '0',
 `Hunter` int(11) NOT NULL DEFAULT '0',
 `Infected` int(11) NOT NULL DEFAULT '0',
 `Jockey` int(11) NOT NULL DEFAULT '0',
 `Smoker` int(11) NOT NULL DEFAULT '0',
 `Spitter` int(11) NOT NULL DEFAULT '0',
 `Tank` int(11) NOT NULL DEFAULT '0',
 `Witch` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`Steamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
