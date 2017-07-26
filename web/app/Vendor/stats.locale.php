<?php

function stats_translated($stats_name,$locale='id'){
	/**
	* these is a temporary solution until we use the cakephp's localization feature
	*/
	$STATS_NAME['id'] = array(
		 //games
		 'game_started'=>'Dipasang di pertandingan BPL',
		 'sub_on'=>'Main sebagai cadangan',
		 //passing and attacking
		  'goals'=>'Mencetak gol',
	        'att_freekick_goal'=>'Mencetak Goal dari Tendangan Bebas',
	        'att_pen_goal'=>'Mencetak Goal dari Tendangan Pinalti',
	        'att_ibox_target'=>'Tendangan akurat dari dalam kotak pinalti',
	        'att_obox_target'=>'Tendangan akurat dari luar kotak pinalti',
	        'goal_assist_openplay'=>'Melakukan Assist',
	        'goal_assist_setplay'=>'Memberikan assist dari setplay yang membuahkan gol',
	        'att_assist_openplay'=>'memberikan assist dari openplay',
	        'att_assist_setplay'=>'Memberikan assist dari setplay',
	        'second_goal_assist'=>'Mengawali serangan yang berakhir dengan terjadinya sebuah gol',
	        'big_chance_created'=>'Berperan menciptakan sebuah peluang matang',
	        'accurate_through_ball'=>'Umpan Terobosan Akurat',
	        'accurate_cross_nocorner'=>'Melakukan crossing dengan akurat',
	        'accurate_pull_back'=>'Accurate Pull Back',
	        'won_contest'=>'Berhasil menggocek lawan',
	        'long_pass_own_to_opp_success'=>'Meluncurkan serangan dari dalam daerah pertahanan',
	        'accurate_long_balls'=>'Umpan Lambung Akurat',
	        'accurate_flick_on'=>'Meneruskan umpan udara dengan menggunakan sundulan',
	        'accurate_layoffs'=>'Accurate Layoffs',
	        'penalty_won'=>'Mendapatkan tendangan pinalti',
	        'won_corners'=>'Mendapatkan tendangan pojok',
	        'fk_foul_won'=>'Mendapatkan Tendangan Bebas',
	        'ontarget_scoring_att'=>'Melakukan tembakan ke arah gawang',
	        'att_ibox_goal'=>'Mencetak gol dari dalam kotak pinalti',
	        'att_obox_goal'=>'Mencetak gol dari luar kotak pinalti',



	    
	    //defending

	    'aerial_won'=>'Memenangi duel di udara',
        'ball_recovery'=>'Menguasai bola liar / lepas',
        'duel_won'=>'Memenangi duel perebutan bola',
        'effective_blocked_cross'=>'Berhasil menggagalkan crossing lawan',
        'effective_clearance'=>'Berhasil menghalau serangan lawan',
        'effective_head_clearance'=>'Berhasil menghalau serangan lawan dengan menggunakan sundulan',
        'interceptions_in_box'=>'Memotong umpan lawan di dalam kotak pinalti',
        'interception_won' => 'Memotong umpan lawan',
        'won_tackle' => 'Melakukan tackling dan merebut bola',
        'offside_provoked' => 'Berhasil melakukan perangkap offside ',
        'clearance_off_line'    =>  'Menghalau bola sebelum melewati garis',
        'outfielder_block' => 'Menahan /menutup tendangan ke arah gawang',

        //goalkeeping
       
        'good_high_claim'=> 'Berhasil menangkap crossing lawan',
        'saves'=> 'Menyelamatkan tendangan lawan',
       	'penalty_save'=> 'Menyelamatkan Pinalti',

        //mistakes and errors
        'fk_foul_lost'=>    'Kehilangan tendangan bebas',
        'poss_lost_all'=>   'Kehilangan possession',
	    'penalty_conceded'=>'Menyebabkan terjadinya pinalti',
        'red_card'=>'Mendapat kartu merah',
        'yellow_card'=>'Mendapat kartu kuning',
        'challenge_lost'=>'Gagal melakukan tackling sehingga dilewati lawan',
        'dispossessed'=>'Kehilangan bola',
        'fouls'=>'Melakukan pelanggaran',
        'overrun'=>'Terlalu jauh mendorong bola saat berusaha dribbling',
        'total_offside'=>'Tertangkap offside',
        'unsuccessful_touch'=>'Gagal mengontrol bola',
        'error_lead_to_shot'=>'Melakukan kesalahan yang mengakibatkan peluang buat lawan',
        'error_lead_to_goal'=>'Melakukan kesalahan yang mengakibatkan gol lawan'

	);
	
	return @$STATS_NAME[$locale][$stats_name];
}
?>