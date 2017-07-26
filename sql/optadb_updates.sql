ALTER TABLE `optadb`.`matchinfo` ADD INDEX `IDX_PERIOD` (`period`);
ALTER TABLE `optadb`.`matchinfo` ADD INDEX `IDX_SESSION_COMPETITION` (`competition_id`, `season_id`, `home_team`, `away_team`);
ALTER TABLE `optadb`.`player_stats` ADD INDEX `IDX_TEAM_STATS` (`team_id`, `stats_name`);
ALTER TABLE `optadb`.`player_stats` ADD INDEX `IDX_PLAYER_STATS_PER_GAME` (`game_id`, `player_id`, `stats_name`);


CREATE TABLE optadb.master_player_summary (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(20) DEFAULT NULL,
  `team_id` varchar(20) DEFAULT NULL,
  `player_id` varchar(20) DEFAULT NULL,
  `most_influence` float(7,2) DEFAULT '0.00',
  `def_influence` float(7,2) DEFAULT '0.00',
  `mid_influence` float(7,2) DEFAULT '0.00',
  `fw_influence` float(7,2) DEFAULT '0.00',
  `def_score` float(7,2) DEFAULT '0.00',
  `atk_score` float(7,2) DEFAULT '0.00',
  `creativity` float(7,2) DEFAULT '0.00',
  `most_accurate_pass` float(7,2) DEFAULT '0.00',
  `least_accurate_pass` float(7,2) DEFAULT '0.00',
  `shoot_accuracy` float(7,2) DEFAULT '0.00',
  `chance_created` float(7,2) DEFAULT '0.00',
  `dangerous_pass` float(7,2) DEFAULT '0.00',
  `assist` float(7,2) DEFAULT '0.00',
  `best_cross_percentage` float(7,2) DEFAULT '0.00',
  `worst_cross_percentage` float(7,2) DEFAULT '0.00',
  `ball_wins` float(7,2) DEFAULT '0.00',
  `def_fails` float(7,2) DEFAULT '0.00',
  `liable` float(7,2) DEFAULT '0.00',
  `gk_score` float(7,2) DEFAULT '0.00',
  `shot_stopping_percentage` float(7,2) DEFAULT '0.00',
  `best_at_crosses` float(7,2) DEFAULT '0.00',
  `one_v_one` float(7,2) DEFAULT '0.00',
  `deadkick` float(7,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_PLAYER_PER_GAME` (`game_id`,`team_id`,`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE optadb.master_team_summary (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(32) DEFAULT NULL,
  `team_id` varchar(32) DEFAULT NULL,
  `chances_created` int(11) DEFAULT '0',
  `goals` int(11) DEFAULT '0',
  `goals_conceded` int(11) DEFAULT '0',
  `chances_conceded` int(11) DEFAULT '0',
  `attack_effeciency` float(10,4) DEFAULT '0.0000',
  `defense_effeciency` float(10,4) DEFAULT '0.0000',
  `ball_recovery` int(11) DEFAULT NULL,
  `duels_won` int(11) DEFAULT NULL,
  `challenge_won_ratio` float(10,4) DEFAULT '0.0000',
  `fouling` float(10,4) DEFAULT '0.0000',
  `error_led_to_goals` int(11) DEFAULT '0',
  `error_led_to_shots` int(11) DEFAULT '0',
  `poor_control` int(11) DEFAULT '0',
  `counter_attack_goals` int(11) DEFAULT '0',
  `counter_attack_shots` int(11) DEFAULT '0',
  `counter_attacks` int(11) DEFAULT '0',
  `counter_attack_effeciency` float(10,4) DEFAULT '0.0000',
  `aerial_duels_won` int(11) DEFAULT '0',
  `headers_on_goal` int(11) DEFAULT '0',
  `headed_clearance` int(11) DEFAULT '0',
  `crosses_dealt` int(11) DEFAULT '0',
  `aerial_effenciency` float(10,4) DEFAULT '0.0000',
  `last_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_TEAMS` (`game_id`,`team_id`),
  KEY `IDX_GAME_ID` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE optadb.statsjob_queue (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(32) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->pending , 1->finished.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_GAME_ID` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `optadb`.`master_player_summary`     ADD COLUMN `accurate_cross` INT(11) DEFAULT '0' NULL AFTER `deadkick`,     ADD COLUMN `total_cross` INT(11) DEFAULT '0' NULL AFTER `accurate_cross`;
ALTER TABLE `optadb`.`master_player_summary`     ADD COLUMN `ontarget_scoring_att` INT(11) DEFAULT '0' NULL AFTER `total_cross`,     ADD COLUMN `total_scoring_att` INT(11) DEFAULT '0' NULL AFTER `ontarget_scoring_att`;