<?php
App::uses('AppModel', 'Model');
/**
 * the player report will returns the following informations : 
 * User | Original Team | Email | Telp | Kota | Import Players Count* | Games* | Passing & Attacking* | Defending* | Goalkeeping* | Mistakes & Errors* | Total Points* | Money*
 */
class PlayerReport extends AppModel {
	public $name ='User';
	public $useTable = false;
	// paginate and paginateCount implemented on a behavior.
	public function paginate(
									$conditions, 
									$fields, 
									$order, 
									$limit, 
									$page = 1, 
									$recursive = null, 
									$extra = array()) {
		


		$sort = '';
		if(isset($extra['sort'])){
			switch($extra['sort']){
				case 'import':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'money':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'games':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'passing_and_attacking':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'defending':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'goalkeeping':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'mistakes_and_errors':
					$rs =  $this->sortBySummary($conditions, 
									$fields, 
									$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'points':
					$rs =  $this->sortByPoints($conditions, 
									$fields, 
									'total_points '.$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				case 'rank':
					$rs =  $this->sortByRanks($conditions, 
									$fields, 
									'rank '.$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
				default:
					$rs =  $this->sortByUserId($conditions, 
									$fields, 
									$extra['sort'].' '.$extra['direction'], 
									$limit, 
									$page, 
									$recursive,
									$extra);
				break;
			}
			
		}else{
			$rs =  $this->normalSelect($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra);
		}
		foreach($rs as $n=>$v){
			$rs[$n]['no'] = $n + (($page * $limit) - $limit)+1;
		}
		return $rs;

	}
	private function sortBySummary($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra){

		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}

		switch($extra['sort']){
			case 'import':
				$orderBy = 'import_player_counts';
			break;
			case 'money':
				$orderBy = 'money';
			break;
			case 'games':
				$orderBy = 'games';
			break;
			case 'passing_and_attacking':
				$orderBy = 'passing_and_attacking';
			break;
			case 'defending':
				$orderBy = 'defending';
			break;
			case 'goalkeeping':
				$orderBy = 'goalkeeping';
			break;
			case 'mistakes_and_errors':
				$orderBy = 'mistakes_and_errors';
			break;
		}

		$rs = $this->query("SELECT * FROM team_summary Summary
							INNER JOIN ".$_SESSION['ffgamedb'].".game_teams GameTeam
							ON Summary.game_team_id = GameTeam.id
							INNER JOIN ".$_SESSION['ffgamedb'].".game_users GameUser
							ON GameTeam.user_id = GameUser.id
							ORDER BY {$orderBy} {$order} 
							LIMIT {$start},{$limit};");
		//additional data
		foreach($rs as $n=>$v){
			$r = $this->query("SELECT * FROM users as User
							INNER JOIN teams Team on
							Team.user_id = User.id 
							INNER JOIN ".$_SESSION['ffgamedb'].".master_team MasterTeam
							ON Team.team_id = MasterTeam.uid 
							WHERE User.fb_id = '{$v['GameUser']['fb_id']}'
							LIMIT 1");
			$rs[$n]['User'] = $r[0]['User'];
			$rs[$n]['Team'] = $r[0]['Team'];
			$rs[$n]['MasterTeam'] = $r[0]['MasterTeam'];

			$r = $this->query("SELECT (points+extra_points) AS total_points,rank 
								FROM points Point
								WHERE team_id={$rs[$n]['Team']['id']} LIMIT 1");

			$rs[$n]['Point'] = $r[0]['Point'];
			$rs[$n]['Point']['points'] = $r[0][0]['total_points'];
			
			$rs[$n]['ImportPlayerCounts'] = intval($v['Summary']['import_player_counts']);

			//money
			
			
			$rs[$n]['Money'] = intval($v['Summary']['money']);
		}

		return $rs;
	}
	private function sortByUserId($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra){

		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
		$rs = $this->query("SELECT * FROM users as User
							INNER JOIN teams Team on
							Team.user_id = User.id 
							INNER JOIN ".$_SESSION['ffgamedb'].".master_team MasterTeam
							ON Team.team_id = MasterTeam.uid
							ORDER BY {$order}
							LIMIT {$start},{$limit}");

		//additional data
		foreach($rs as $n=>$v){
			$r = $this->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_users GameUser
								INNER JOIN ".$_SESSION['ffgamedb'].".game_teams GameTeam
								ON GameTeam.user_id = GameUser.id

								WHERE GameUser.fb_id = '{$v['User']['fb_id']}' LIMIT 1;");
			$rs[$n]['GameData'] = $r[0];

			$r = $this->query("SELECT (points+extra_points) AS total_points,rank 
								FROM points Point
								WHERE team_id={$v['Team']['id']} LIMIT 1");
			$rs[$n]['Point'] = $r[0]['Point'];
			$rs[$n]['Point']['points'] = $r[0][0]['total_points'];

			$game_team_id = $rs[$n]['GameData']['GameTeam']['id'];
			
			$r = $this->query("SELECT * FROM team_summary Summary
								WHERE game_team_id={$game_team_id} LIMIT 1;");
			
			$rs[$n]['Summary'] = $r[0]['Summary'];

			$rs[$n]['ImportPlayerCounts'] = intval($r[0]['Summary']['import_player_counts']);

			//money
			
			
			$rs[$n]['Money'] = intval($r[0]['Summary']['money']);
			$rs[$n]['Summary'] = $r[0]['Summary'];
		}

		return $rs;
	}
	private function sortByRanks($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra){

		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
	

		$rs = $this->query("SELECT User.*,Team.*,MasterTeam.*
							FROM users AS User
							INNER JOIN teams Team ON
							Team.user_id = User.id 
							INNER JOIN ".$_SESSION['ffgamedb'].".master_team MasterTeam
							ON Team.team_id = MasterTeam.uid
							INNER JOIN points Point
							ON Point.team_id = Team.id
							ORDER BY Point.rank {$extra['direction']}
							LIMIT {$start},{$limit}");
		//additional data
		foreach($rs as $n=>$v){
			$r = $this->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_users GameUser
								INNER JOIN ".$_SESSION['ffgamedb'].".game_teams GameTeam
								ON GameTeam.user_id = GameUser.id

								WHERE GameUser.fb_id = '{$v['User']['fb_id']}' LIMIT 1;");
			$rs[$n]['GameData'] = $r[0];

			$r = $this->query("SELECT (points+extra_points) AS total_points,rank 
								FROM points Point
								WHERE team_id={$v['Team']['id']} LIMIT 1");
			$rs[$n]['Point'] = $r[0]['Point'];
			$rs[$n]['Point']['points'] = $r[0][0]['total_points'];

			$game_team_id = $rs[$n]['GameData']['GameTeam']['id'];
			
			$r = $this->query("SELECT * FROM team_summary Summary
								WHERE game_team_id={$game_team_id} LIMIT 1;");
			
			$rs[$n]['ImportPlayerCounts'] = intval($r[0]['Summary']['import_player_counts']);

			//money
			
			
			$rs[$n]['Money'] = intval($r[0]['Summary']['money']);
			$rs[$n]['Summary'] = $r[0]['Summary'];
		}

		return $rs;
	}
	private function sortByPoints($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra){

		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
	

		$rs = $this->query("SELECT User.*,Team.*,MasterTeam.*,
							(Point.points + Point.extra_points) AS total_points 
							FROM users AS User
							INNER JOIN teams Team ON
							Team.user_id = User.id 
							INNER JOIN ".$_SESSION['ffgamedb'].".master_team MasterTeam
							ON Team.team_id = MasterTeam.uid
							INNER JOIN points Point
							ON Point.team_id = Team.id
							ORDER BY {$order}
							LIMIT {$start},{$limit}");
		//additional data
		foreach($rs as $n=>$v){
			$r = $this->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_users GameUser
								INNER JOIN ".$_SESSION['ffgamedb'].".game_teams GameTeam
								ON GameTeam.user_id = GameUser.id

								WHERE GameUser.fb_id = '{$v['User']['fb_id']}' LIMIT 1;");
			$rs[$n]['GameData'] = $r[0];

			$r = $this->query("SELECT (points+extra_points) AS total_points,rank 
								FROM points Point
								WHERE team_id={$v['Team']['id']} LIMIT 1");
			$rs[$n]['Point'] = $r[0]['Point'];
			$rs[$n]['Point']['points'] = $r[0][0]['total_points'];

			$game_team_id = $rs[$n]['GameData']['GameTeam']['id'];
			
			$r = $this->query("SELECT * FROM team_summary Summary
								WHERE game_team_id={$game_team_id} LIMIT 1;");
			
			$rs[$n]['ImportPlayerCounts'] = intval($r[0]['Summary']['import_player_counts']);

			//money
			
			
			$rs[$n]['Money'] = intval($r[0]['Summary']['money']);
			$rs[$n]['Summary'] = $r[0]['Summary'];
		}

		return $rs;
	}
	private function normalSelect($conditions, 
									$fields, 
									$order, 
									$limit, 
									$page, 
									$recursive,
									$extra){
		
		$search = '';
		if(isset($conditions['OR'])){
			$search = "WHERE (({$conditions['OR'][0]}) OR ({$conditions['OR'][1]}))";
		}
		//decide the paging
		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
		$rs = $this->query("SELECT * FROM users User
							INNER JOIN teams Team
							ON Team.user_id = User.id
							INNER JOIN ".$_SESSION['ffgamedb'].".master_team MasterTeam
							ON Team.team_id = MasterTeam.uid
							{$search}
							LIMIT {$start},{$limit}",false);
		//additional data
		foreach($rs as $n=>$v){
			$r = $this->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_users GameUser
								INNER JOIN ".$_SESSION['ffgamedb'].".game_teams GameTeam
								ON GameTeam.user_id = GameUser.id
								WHERE GameUser.fb_id = '{$v['User']['fb_id']}' LIMIT 1;",false);
			$rs[$n]['GameData'] = $r[0];

			$r = $this->query("SELECT (points+extra_points) AS total_points,rank 
								FROM points Point
								WHERE team_id={$v['Team']['id']} LIMIT 1");
			$rs[$n]['Point'] = $r[0]['Point'];
			$rs[$n]['Point']['points'] = $r[0][0]['total_points'];

			$game_team_id = $rs[$n]['GameData']['GameTeam']['id'];
			
			$r = $this->query("SELECT * FROM team_summary Summary
								WHERE game_team_id='{$game_team_id}' LIMIT 1;",false);
			
			$rs[$n]['ImportPlayerCounts'] = intval(@$r[0]['Summary']['import_player_counts']);

			//money
			
			
			$rs[$n]['Money'] = intval(@$r[0]['Summary']['money']);
			$rs[$n]['Summary'] = @$r[0]['Summary'];
		}
		
		return $rs;
	}

	public function paginateCount(
										$conditions = null, 
										$recursive = 0, 
										$extra = array()) {

		$search = '';
		if(isset($conditions['OR'])){
			$search = "WHERE (({$conditions['OR'][0]}) OR ({$conditions['OR'][1]}))";
		}
	    // method body
		$rs = $this->query("SELECT COUNT(User.id) as total FROM users User 
							INNER JOIN teams Team
						    ON Team.user_id = User.id
						    {$search}
						    ");
		
		return $rs[0][0]['total'];
	}
}