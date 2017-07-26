#shotstopper
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'saves','ontarget_scoring_att'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;


#best_goalkeeping
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'good_high_claim','good_one_on_one','saves','diving_save','dive_catch','gk_smother,punches'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;

#most liable
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'dangerous_play','red_card','second_yellow','yellow_card','penalty_conceded','fk_foul_lost',
'error_lead_to_goal','error_lead_to_shot'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;




#weakest defender
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'duel_lost','challenge_lost','fouls','dangerous_play','fk_foul_lost'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;



#Best Ball Winners
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'won_tackle','interception_won','ball_recovery','duel_won','last_man_tackle'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;



#Best Cross Percentage
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'accurate_cross_nocorner','total_cross_nocorner'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;


#Sharpest Shooters
SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'ontarget_scoring_att','total_scoring_att'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;



#dangerous passer

SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'accurate_fwd_zone_pass','accurate_through_ball',
'long_pass_own_to_opp_success','total_attacking_pass','successful_final_third_passes',
'big_chance_created','big_chance_scored','big_chance_missed','att_assist_openplay',
'att_assist_setplay','second_goal_assist'
)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;

#most influence

SELECT game_id,c.name AS team_name,a.player_id,b.name,a.stats_name,SUM(a.stats_value) AS total
FROM 
player_stats a
INNER JOIN 
master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
INNER JOIN 
master_team c
ON a.team_id = c.uid
WHERE game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND stats_name IN
(
'accurate_fwd_zone_pass','ontarget_scoring_att','effective_clearance',
'won_tackle','goal_assist','goals', 'aerial_won', 'interception_won', 
'big_chance_created','big_chance_scored','big_chance_missed','accurate_cross',
'outfielder_block','penalty_won','fouled_final_third','last_man_contest','last_man_tackle',
'offside_provoked','ontarget_scoring_att','accurate_cross','accurate_through_ball',
'ball_recovery','clearance_off_line','saves','gk_smother','good_high_claim','good_one_on_one',
'interceptions_in_box','penalty_won','second_goal_assist','total_att_assist','won_contest',
'accurate_keeper_sweeper','total_attacking_pass'

)
GROUP BY game_id,a.team_id,player_id,a.stats_name LIMIT 10000;


## player stats per team per game_id
SELECT game_id,player_id,b.name,stats_name,SUM(stats_value) AS total 
FROM optadb.player_stats a
INNER JOIN optadb.master_player b
ON a.player_id = b.uid AND a.team_id = b.team_id
WHERE 
game_id IN (SELECT game_id FROM matchinfo WHERE competition_id='c8' AND season_id='2013')
AND
player_id='p1814' GROUP BY game_id,a.team_id,player_id,stats_name;