SELECT * FROM ffgame.master_team;

SELECT a.last_name,b.name AS team_name,a.transfer_value 
FROM ffgame.tmp_transfer a
INNER JOIN ffgame.master_team b
ON a.team_id = b.uid;


SELECT a.last_name,a.team_id,a.transfer_value,b.name,b.last_name,b.transfer_value 
FROM ffgame.tmp_transfer a
INNER JOIN ffgame.master_player b
ON (a.last_name = b.last_name OR b.name = a.last_name OR b.known_name = a.last_name) AND a.team_id = b.team_id;


SELECT * FROM ffgame.tmp_transfer a
WHERE EXISTS(SELECT 1 FROM ffgame.master_player b
WHERE (b.last_name = a.last_name OR b.name = a.last_name OR b.known_name = a.last_name) AND b.team_id = a.team_id LIMIT 1);

UPDATE ffgame.master_player SET transfer_value = 4400000			
WHERE (last_name = 'Kane' OR NAME = 'Kane' OR known_name = 'Kane') AND team_id='t6';




SELECT * FROM ffgame.tmp_transfer a
WHERE NOT EXISTS(SELECT 1 FROM ffgame.master_player b
WHERE (b.last_name = a.last_name OR b.name = a.last_name OR b.known_name = a.last_name) AND b.team_id = a.team_id LIMIT 1);


SELECT COUNT(*) FROM master_player;

SELECT * FROM master_player WHERE team_id='t3';

SELECT * FROM master_player WHERE team_id='t21';

SELECT * FROM ffgame.master_player WHERE transfer_value = '10000000';
