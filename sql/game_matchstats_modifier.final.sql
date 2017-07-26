/*
SQLyog Ultimate v8.54 
MySQL - 5.5.31-0+wheezy1 : Database - ffgame
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `game_matchstats_modifier` */

DROP TABLE IF EXISTS `game_matchstats_modifier`;

CREATE TABLE `game_matchstats_modifier` (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `name` varchar(140) DEFAULT NULL,
  `g` int(11) DEFAULT '0',
  `d` int(11) DEFAULT '0',
  `m` int(11) DEFAULT '0',
  `f` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_NAME` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

/*Data for the table `game_matchstats_modifier` */

insert  into `game_matchstats_modifier`(`id`,`name`,`g`,`d`,`m`,`f`) values (1,'game_started',2,2,2,2),(2,'total_sub_on',1,1,1,1),(3,'goals',7,6,5,5),(4,'att_freekick_goal',3,2,2,2),(5,'att_pen_goal',1,1,1,1),(6,'att_ibox_target',3,2,1,1),(7,'att_obox_target',3,3,2,2),(8,'goal_assist_openplay',5,4,3,3),(9,'goal_assist_setplay',3,2,2,2),(10,'att_assist_openplay',3,2,2,2),(11,'att_assist_setplay',3,2,2,2),(12,'second_goal_assist',3,2,2,2),(13,'big_chance_created',3,3,3,3),(14,'accurate_through_ball',1,1,1,1),(15,'accurate_cross_nocorner',1,1,1,1),(16,'accurate_pull_back',1,1,1,1),(17,'won_contest',1,1,1,2),(18,'long_pass_own_to_opp_success',1,1,2,2),(19,'accurate_long_balls',1,1,1,1),(20,'accurate_flick_on',1,1,1,1),(21,'accurate_layoffs',1,1,1,1),(22,'penalty_won',1,1,1,1),(23,'won_corners',1,1,1,1),(24,'fk_foul_won',1,1,1,1),(25,'duel_won',1,1,1,1),(26,'aerial_won',1,1,1,1),(27,'ball_recovery',1,1,1,1),(28,'won_tackle',1,1,1,2),(29,'interception_won',1,1,1,1),(30,'interceptions_in_box',3,3,3,3),(31,'offside_provoked',1,1,1,1),(32,'outfielder_block',1,1,1,2),(33,'effective_blocked_cross',1,1,1,2),(34,'effective_head_clearance',1,1,1,2),(35,'effective_clearance',1,1,1,2),(36,'clearance_off_line',3,3,3,3),(37,'good_high_claim',3,0,0,0),(38,'saves',3,0,0,0),(39,'penalty_conceded',-3,-3,-3,-3),(40,'fk_foul_lost',-1,-1,-1,-1),(41,'poss_lost_all',0,0,0,0),(42,'challenge_lost',-1,-1,-1,-1),(43,'error_lead_to_shot',-3,-3,-3,-3),(44,'error_lead_to_goal',-5,-5,-5,-5),(45,'total_offside',-1,-1,-1,-1),(46,'red_card',-10,-10,-10,-10),(47,'yellow_card',-1,-1,-1,-1);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
