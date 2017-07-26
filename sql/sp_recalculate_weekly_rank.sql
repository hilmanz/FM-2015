DELIMITER $$

USE `fantasy`$$

DROP PROCEDURE IF EXISTS `recalculate_weekly_rank`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `recalculate_weekly_rank`(IN lg VARCHAR(5),IN matchday INT(5))
BEGIN 
DECLARE isDone BOOLEAN DEFAULT FALSE;
DECLARE i INT DEFAULT 1;
DECLARE a BIGINT(11);
DECLARE b INT(11);
DECLARE c VARCHAR(20);
DECLARE curs CURSOR FOR 
	SELECT a.team_id,SUM(a.points+a.extra_points) AS points,a.matchday
	FROM weekly_points a
	INNER JOIN teams b
	ON a.team_id = b.id AND b.league = lg
	WHERE a.matchday=matchday AND a.league=lg
	GROUP BY a.team_id
	ORDER BY SUM(a.points+a.extra_points) DESC,a.team_id ASC;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET isDone = TRUE;
OPEN curs;
	SET isDone = FALSE;
	SET i = 1;
	REPEAT
		FETCH curs INTO a,b,c;
		IF a IS NOT NULL THEN
			INSERT INTO weekly_ranks
			(team_id,matchday,rank,league)
			VALUES
			(a,c,i,lg)
			ON DUPLICATE KEY UPDATE
			rank = VALUES(rank);
		END IF;
		SET i = i + 1;
		SET a = NULL;
		SET b = NULL;
		SET c = NULL;
	UNTIL isDone END REPEAT;
CLOSE curs;
END$$

DELIMITER ;