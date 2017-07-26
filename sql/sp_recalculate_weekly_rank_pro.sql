DELIMITER $$

USE `fantasy`$$

DROP PROCEDURE IF EXISTS `recalculate_weekly_rank_pro`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `recalculate_weekly_rank_pro`(IN lg VARCHAR(5),IN matchday INT(5))
BEGIN 
DECLARE isDone BOOLEAN DEFAULT FALSE;
DECLARE i INT DEFAULT 1;
DECLARE a BIGINT(11);
DECLARE b INT(11);
DECLARE c VARCHAR(20);
DECLARE curs CURSOR FOR 
	
	SELECT aa.team_id,0 AS points,0 AS foo FROM weekly_ranks aa
	INNER JOIN teams bb
	ON aa.team_id = bb.id
	INNER JOIN users cc
	ON cc.id = bb.user_id
	WHERE aa.league = lg AND aa.matchday=matchday AND cc.paid_member = 1 AND cc.paid_member_status =1 
	
	ORDER BY aa.rank ASC;
	
						
DECLARE CONTINUE HANDLER FOR NOT FOUND SET isDone = TRUE;
OPEN curs;
	SET isDone = FALSE;
	SET i = 1;
	REPEAT
		FETCH curs INTO a,b,c;
		IF a IS NOT NULL THEN
			INSERT INTO weekly_ranks_pro
			(team_id,matchday,rank,league)
			VALUES
			(a,matchday,i,lg)
			ON DUPLICATE KEY UPDATE
			rank = VALUES(rank);
			SET i = i + 1;
		END IF;
		
		SET a = NULL;
		SET b = NULL;
		SET c = NULL;
	UNTIL isDone END REPEAT;
CLOSE curs;
END$$

DELIMITER ;