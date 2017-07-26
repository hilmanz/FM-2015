CREATE TABLE ffgame.master_matchdays(
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `matchday` int(4) DEFAULT '1',
  `start_dt` datetime DEFAULT NULL,
  `end_dt` datetime DEFAULT NULL,
  `n_status` tinyint(2) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_MATCHDAY` (`matchday`),
  KEY `IDX_GAME_TIME` (`matchday`,`start_dt`,`end_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO ffgame.master_matchdays
(matchday,start_dt,end_dt)
SELECT matchday,(MIN(match_date)+ INTERVAL 6 HOUR) AS start_dt,
(MAX(match_date) + INTERVAL 6 HOUR) AS end_dt
FROM ffgame.game_fixtures GROUP BY matchday;