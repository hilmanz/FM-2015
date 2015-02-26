<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Analytics extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.

	//retrieving unique user daily stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_daily(){
		$sql = "
				SELECT id,the_date,the_day,COUNT(user_id) AS unique_user
				FROM (
				SELECT id,DATE(log_dt) the_date,DAYOFYEAR(log_dt) AS the_day, 
				user_id
				FROM 
				activity_logs
				GROUP BY DAYOFYEAR(log_dt),user_id) a 
				GROUP BY the_day
				ORDER BY id DESC LIMIT 30;";
		$rs = $this->query($sql);

		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['total'] = $rs[$i][0]['unique_user'];
			$results[] = $p;
		}
		$results = array_reverse($results);
		return $results;
	}
	

	//retrieving daily registrations
	public function daily_registrations(){
		$sql = "SELECT DATE(register_date) AS dt,COUNT(id) AS total 
				FROM fantasy.users a
				GROUP BY DATE(register_date) ORDER BY id ASC LIMIT 1000";
		$rs = $this->query($sql);
		
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i][0];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}

		
		return $results;
	}

	public function daily_registration_players_epl()
	{
		$sql = "SELECT DATE(register_date) AS dt,COUNT(id) AS total 
				FROM ffgame.game_users a WHERE register_date BETWEEN '2014-10-01'
				AND '2015-01-31'
				GROUP BY DATE(register_date) ORDER BY id ASC LIMIT 150";
		$rs = $this->query($sql);
		
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i][0];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}

		
		return $results;
	}

	public function daily_registration_players_ita()
	{
		$sql = "SELECT DATE(register_date) AS dt,COUNT(id) AS total 
				FROM ffgame_ita.game_users a WHERE register_date BETWEEN '2014-10-01'
				AND '2015-01-31'
				GROUP BY DATE(register_date) ORDER BY id ASC LIMIT 150";
		$rs = $this->query($sql);
		
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i][0];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}

		
		return $results;
	}

	public function active_user($league = 'epl')
	{
		$sql = "SELECT 
				    DATE_FORMAT(log_dt, '%Y-%m-%d') AS dt, COUNT(user_id) AS total
				FROM
				    fantasy.activity_logs
				WHERE league='{$league}' AND log_type='LOGIN' 
				AND DATE(log_dt) > DATE_SUB(CURDATE(), INTERVAL 7 DAY)
				GROUP BY DATE_FORMAT(log_dt, '%Y-%m-%d')";
		$rs = $this->query($sql);

		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i][0];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}

		
		return $results;
	}

	public function total_active_player_epl()
	{
		$sql = "SELECT count(id) as total FROM ffgame.game_users;";
		$rs = $this->query($sql);		
		return $rs;
	}

	public function total_active_player_ita()
	{
		$sql = "SELECT count(id) as total FROM ffgame_ita.game_users;";
		$rs = $this->query($sql);		
		return $rs;
	}

	//-->
	//retrieving unique user monthly stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_monthly(){
		$sql = "
				SELECT id,the_month,the_year,COUNT(user_id) AS unique_user
				FROM (
				SELECT id,MONTH(log_dt) AS the_month, YEAR(log_dt) AS the_year,
				user_id
				FROM 
				activity_logs WHERE log_dt BETWEEN '2014-05-01'
				AND '2015-01-31'
				GROUP BY MONTH(log_dt),user_id) a 
				GROUP BY the_month
				ORDER BY id DESC LIMIT 20";
		$rs = $this->query($sql);

		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['total'] = $rs[$i][0]['unique_user'];
			$results[] = $p;
		}

		$results = array_reverse($results);
		return $results;
	}

	//retrieving unique user weekly stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_weekly(){
		$sql = "
				SELECT id,DATE_ADD(MAKEDATE(the_year, 1), INTERVAL the_week WEEK) AS the_date,the_week,the_year,COUNT(user_id) AS unique_user
				FROM (
				SELECT id,WEEK(log_dt) AS the_week, YEAR(log_dt) AS the_year,
				user_id
				FROM 
				activity_logs
				GROUP BY WEEK(log_dt),user_id) a 
				GROUP BY the_week ORDER BY id DESC LIMIT 20;";
		$rs = $this->query($sql);

		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['total'] = $rs[$i][0]['unique_user'];
			$p['the_date'] = $rs[$i][0]['the_date'];
			$results[] = $p;
		}
		
		$results = array_reverse($results);
		return $results;
	}

	//query how many users use each team
	//player's data team can be retrieved from ".$_SESSION['ffgamedb'].".game_teams
	//meanwhile the team data can be retrieved from ".$_SESSION['ffgamedb'].".master_team
	public function team_used(){
		$sql = "SELECT b.name,COUNT(b.uid) AS total 
				FROM ".$_SESSION['ffgamedb'].".game_teams a
				INNER JOIN ".$_SESSION['ffgamedb'].".master_team b
				ON a.team_id = b.uid
				GROUP BY a.team_id ORDER BY total DESC LIMIT 20;";

		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['b'];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}
		return $results;
	}
	//query how many users use each player
	//player's data team can be retrieved from ".$_SESSION['ffgamedb'].".game_team_players
	//meanwhile the team data can be retrieved from ".$_SESSION['ffgamedb'].".master_player
	public function player_used(){
		$sql = "SELECT b.name,COUNT(b.uid) AS total 
				FROM ".$_SESSION['ffgamedb'].".game_team_players a
				INNER JOIN ".$_SESSION['ffgamedb'].".master_player b
				ON a.player_id = b.uid
				GROUP BY a.player_id
				ORDER BY total DESC LIMIT 40";

		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['b'];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}
		return $results;
	}

	//retrieve how many users use each formation(s)
	public function formation_used(){
		$sql = "SELECT formation,COUNT(formation) AS total 
				FROM ".$_SESSION['ffgamedb'].".game_team_formation a 
				WHERE formation <> 'Pilih Formasi' 
				GROUP BY formation ORDER BY total DESC LIMIT 50;";

		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}
		return $results;
	}


	public function transfer_most_buy($tw_id){
		$tw_id = intval($tw_id);
		$sql = "SELECT player_id,c.name,COUNT(player_id) AS total
				FROM ".$_SESSION['ffgamedb'].".game_transfer_history a
				INNER JOIN ".$_SESSION['ffgamedb'].".master_transfer_window b
				ON a.tw_id = b.id
				INNER JOIN ".$_SESSION['ffgamedb'].".master_player c
				ON c.uid = a.player_id
				WHERE a.tw_id = {$tw_id} AND transfer_type=1
				GROUP BY player_id
				ORDER BY total DESC
				LIMIT 20;";
		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['name'] = $rs[$i]['c']['name'];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}
		return $results;
	}
	public function transfer_most_sold($tw_id){
		$tw_id = intval($tw_id);
		$sql = "SELECT player_id,c.name,COUNT(player_id) AS total
				FROM ".$_SESSION['ffgamedb'].".game_transfer_history a
				INNER JOIN ".$_SESSION['ffgamedb'].".master_transfer_window b
				ON a.tw_id = b.id
				INNER JOIN ".$_SESSION['ffgamedb'].".master_player c
				ON c.uid = a.player_id
				WHERE a.tw_id = {$tw_id} AND transfer_type=2
				GROUP BY player_id
				ORDER BY total DESC
				LIMIT 20;";
		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['name'] = $rs[$i]['c']['name'];
			$p['total'] = $rs[$i][0]['total'];
			$results[] = $p;
		}
		return $results;
	}

	public function transfer_window(){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".master_transfer_window a";
		$rs = $this->query($sql);
		$results = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$results[] = $p;
		}
		return $results;
	}

	public function getTransferWindowDetail($tw_id){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".master_transfer_window a WHERE id = {$tw_id} LIMIT 1";
		$rs = $this->query($sql);
		return $rs[0]['a'];
	}
}