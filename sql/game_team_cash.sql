
CREATE TABLE ffgame.game_team_cash (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_team_id` bigint(21) DEFAULT NULL,
  `cash` bigint(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_GAME_TEAM_ID` (`game_team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE ffgame.game_transactions (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_team_id` bigint(21) DEFAULT NULL,
  `transaction_dt` datetime DEFAULT NULL,
  `transaction_name` varchar(140) DEFAULT NULL,
  `amount` int(11) DEFAULT '0',
  `details` varchar(140) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_TRANSACTION` (`game_team_id`,`transaction_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;