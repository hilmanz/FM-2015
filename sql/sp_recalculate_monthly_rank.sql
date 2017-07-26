DELIMITER $$

USE `fantasy`$$

DROP PROCEDURE IF EXISTS `recalculate_monthly_rank`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `recalculate_monthly_rank`(IN lg VARCHAR(5),IN mth INT(3),IN yr INT(4))
BEGIN 
DECLARE isDone BOOLEAN DEFAULT FALSE;
DECLARE i INT DEFAULT 1;
DECLARE a BIGINT(11);
DECLARE b INT(3);
DECLARE c INT(4);
DECLARE d INT(11);
DECLARE curs CURSOR FOR 
	SELECT team_id,bln,thn,SUM(points) AS total_points
	FROM (
	SELECT team_id,YEAR(matchdate) AS thn,MONTH(matchdate) AS bln, (points + extra_points) AS points
	FROM weekly_points WHERE league=lg) g 
	WHERE league=lg AND bln = mth AND thn = yr
	GROUP BY thn,bln,team_id ORDER BY total_points DESC,team_id ASC;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET isDone = TRUE;
OPEN curs;
	SET isDone = FALSE;
	SET i = 1;
	REPEAT
		FETCH curs INTO a,b,c,d;
		IF a IS NOT NULL THEN
			INSERT INTO monthly_points
			(team_id,bln,thn,points,rank,league)
			VALUES
			(a,b,c,d,i,lg)
			ON DUPLICATE KEY UPDATE
			points = VALUES(points),
			rank = VALUES(rank);
	
		END IF;
		SET i = i + 1;
		SET a = NULL;
		SET b = NULL;
		SET c = NULL;
		SET d = NULL;
	UNTIL isDone END REPEAT;
CLOSE curs;
END$$

DELIMITER ;