SELECT * FROM ffgame.game_team_lineups a
INNER JOIN ffgame.master_player b
ON a.player_id = b.uid 
WHERE game_team_id=387 ORDER BY position_no;

SELECT * FROM ffgame_stats.master_player_performance a
INNER JOIN ffgame.game_fixtures c
ON a.game_id = c.game_id
WHERE EXISTS (
SELECT 1 FROM ffgame.game_fixtures b WHERE b.game_id = a.game_id AND b.matchday = 4 LIMIT 1)
GROUP BY player_id;


SELECT * FROM ffgame_stats.master_player_stats a
INNER JOIN ffgame.game_fixtures c
ON a.game_id = c.game_id
WHERE a.stats_name = 'game_started' AND EXISTS (
SELECT 1 FROM ffgame.game_fixtures b WHERE b.game_id = a.game_id AND b.matchday = 4 LIMIT 1)
GROUP BY player_id;


EXPLAIN SELECT * FROM ffgame.game_team_lineups a
INNER JOIN ffgame_stats.master_player_stats b
ON a.player_id = b.player_id
WHERE a.game_team_id = 387 AND a.position_no <= 11 AND b.stats_name = 'game_started' 
AND b.game_id IN 
(
SELECT game_id FROM ffgame.game_fixtures c 
WHERE c.game_id = b.game_id AND c.matchday = 4)
GROUP BY b.player_id;


SELECT * FROM 
ffgame.game_team_lineups a
INNER JOIN
ffgame_stats.master_player_stats b
ON a.player_id = b.player_id
INNER JOIN ffgame.game_fixtures c
ON b.game_id = c.game_id
WHERE a.game_team_id=387 
AND b.stats_name = 'game_started'
AND c.matchday = 4
AND a.position_no < 12;

SELECT * FROM ffgame.master_player WHERE uid IN ('p12297','p14075','p14965','p51940','p40755');


SELECT d.uid,d.name FROM 
ffgame.game_team_players a
INNER JOIN
ffgame_stats.master_player_stats b
ON a.player_id = b.player_id
INNER JOIN ffgame.game_fixtures c
ON b.game_id = c.game_id
INNER JOIN ffgame.master_player d
ON a.player_id = d.uid
WHERE a.game_team_id=387 
AND b.stats_name = 'game_started'
AND c.matchday = 4;

SELECT COUNT(*) AS total FROM ffgame.game_fixtures WHERE period = 'FullTime' AND matchday = 1 AND is_processed=1;

SELECT matchday FROM ffgame.game_fixtures WHERE game_id='f694904' LIMIT 1;

#get lineup on the specified matchday
SELECT * FROM 
ffgame.game_team_lineups_history a
INNER JOIN
ffgame_stats.master_player_stats b
ON a.player_id = b.player_id
INNER JOIN ffgame.game_fixtures c
ON b.game_id = c.game_id
WHERE a.game_team_id=387 AND a.game_id = 'f694935'
AND b.stats_name = 'game_started'
AND c.matchday = 4
AND a.position_no < 12;

#get the budget that time.
SELECT SUM(budget+expenses) AS total_expenses
FROM (
SELECT budget,0 AS expenses FROM ffgame.game_team_purse WHERE game_team_id=387
UNION ALL
SELECT 0,SUM(amount) AS total FROM ffgame.game_team_expenditures WHERE match_day <= 4 AND game_team_id=387
) a;


SELECT * FROM ffgame.game_team_lineups_history WHERE game_id='f694935' AND game_team_id=387;
INSERT INTO ffgame.game_team_lineups_history
(game_id,game_team_id,player_id,position_no)
SELECT 'f694935',387,player_id,position_no
FROM ffgame.game_team_lineups WHERE game_team_id=387
ON DUPLICATE KEY UPDATE
position_no = VALUES(position_no);