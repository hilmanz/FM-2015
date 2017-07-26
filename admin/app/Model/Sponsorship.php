<?php
App::uses('AppModel', 'Model');
/**
 * the player report will returns the following informations : 
 * User | Original Team | Email | Telp | Kota | Import Players Count* | Games* | Passing & Attacking* | Defending* | Goalkeeping* | Mistakes & Errors* | Total Points* | Money*
 */
class Sponsorship extends AppModel {
	public $name ='User';
	public $useTable = "game_sponsorships";
	public $useDbConfig = "ffgame";

	public function getPerks(){
		$rs = $this->query("SELECT * FROM ".$_SESSION['ffgamedb'].".master_perks Perks LIMIT 100");
		$perks = array();
		foreach($rs as $r){
			$perks[] = $r['Perks'];
		}
		unset($rs);
		return $perks;

	}
	public function getPerksBySponsorId($sponsor_id){
		$rs = $this->query("SELECT *
							FROM ".$_SESSION['ffgamedb'].".game_sponsor_perks a
							INNER JOIN ".$_SESSION['ffgamedb'].".master_perks b
							ON a.perk_id = b.id
							WHERE a.sponsor_id = {$sponsor_id} 
							LIMIT 1000");

		$perks = array();
		foreach($rs as $r){
			$p = $r['b'];
			$p['amount'] = $r['a']['amount'];
			$perks[] = $p;

		}
		unset($rs);
		return $perks;
	}
	public function addPerk($data){
		$rs = $this->query("SELECT COUNT(*) as total 
							FROM ".$_SESSION['ffgamedb'].".game_sponsor_perks 
							WHERE sponsor_id = {$data['sponsor_id']} 
							AND perk_id = {$data['perkID']}");
		
		if($rs[0][0]['total']!=0){
			return false;
		}else{
			$sql = "INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_sponsor_perks
						(sponsor_id,perk_id,amount)
						VALUES(
							{$data['sponsor_id']},{$data['perkID']},{$data['amount']})";
	
			$rs = $this->query($sql);	
			return true;
		}
	}
	// paginate and paginateCount implemented on a behavior.
	public function paginate(
									$conditions, 
									$fields, 
									$order, 
									$limit, 
									$page = 1, 
									$recursive = null, 
									$extra = array()) {
		

		//decide the paging
		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
		$rs = $this->query("SELECT * 
							FROM ".$_SESSION['ffgamedb'].".game_sponsorships Sponsors
							LIMIT {$start},{$limit}",false);
		return $rs;
	}
	
	public function paginateCount(
										$conditions = null, 
										$recursive = 0, 
										$extra = array()) {

		
		
	    // method body
		$rs = $this->query("SELECT COUNT(id) AS total FROM ".$_SESSION['ffgamedb'].".game_sponsorships");
		
		return $rs[0][0]['total'];
	}

}