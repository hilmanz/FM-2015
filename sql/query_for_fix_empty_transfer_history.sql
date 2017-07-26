INSERT INTO game_transfer_history
(tw_id,game_team_id,player_id,transfer_value,transfer_date,transfer_type)
SELECT 1 AS tw_id,game_team_id,player_id,transfer_value,NOW() AS transfer_date,1 AS transfer_type 
FROM ffgame.game_team_players a
INNER JOIN ffgame.master_player b
ON a.player_id = b.uid
WHERE game_team_id=393 AND team_id <> 't8';


INSERT INTO game_transfer_history
(tw_id,game_team_id,player_id,transfer_value,transfer_date,transfer_type)
SELECT 1 AS tw_id,393 AS game_team_id,uid AS player_id,transfer_value,NOW() AS transfer_date,2 AS transfer_type 
FROM ffgame.master_player a 
WHERE a.uid NOT IN (SELECT player_id FROM ffgame.game_team_players b WHERE b.game_team_id =393)
AND a.team_id = 't8';