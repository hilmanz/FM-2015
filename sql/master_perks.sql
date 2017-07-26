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
/*Table structure for table `master_perks` */

DROP TABLE IF EXISTS `master_perks`;

CREATE TABLE `master_perks` (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `perk_name` varchar(140) DEFAULT NULL,
  `name` varchar(140) DEFAULT NULL,
  `description` varchar(140) DEFAULT NULL,
  `amount` bigint(21) DEFAULT '5000000',
  `data` text COMMENT 'serialized additional data for these perk.',
  PRIMARY KEY (`id`),
  KEY `IDX_PERKNAME` (`perk_name`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;

/*Data for the table `master_perks` */

insert  into `master_perks`(`id`,`perk_name`,`name`,`description`,`amount`,`data`) values (1,'IMMEDIATE_MONEY','Immediate Money','Immediate cash upon apply the sponsorship',5000000,NULL),(2,'ACCESSORIES','Arsenal Home Kit','Use Arsenal Home Jersey Kit for your team.',1,'a:3:{s:9:\"jersey_id\";i:1;s:4:\"name\";s:16:\"Arsenal Home Kit\";s:4:\"type\";s:6:\"jersey\";}\r\n'),(3,'ACCESSORIES','Chelsea Home Kit','Use Chelsea Home Jersey Kit for your team.',1,'a:3:{s:9:\"jersey_id\";i:2;s:4:\"name\";s:16:\"Chelsea Home Kit\";s:4:\"type\";s:6:\"jersey\";}\r\n'),(4,'ACCESSORIES','Manchester United Home Kit','Use Manchester United Jersey Kit for your team',1,'a:3:{s:9:\"jersey_id\";i:3;s:4:\"name\";s:26:\"Manchester United Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(5,'ACCESSORIES','Liverpool Home Kit','Use Liverpool Jersey Kit',1,'a:3:{s:9:\"jersey_id\";i:4;s:4:\"name\";s:18:\"Liverpool Home Kit\";s:4:\"type\";s:6:\"jersey\";}\r\n'),(6,'ACCESSORIES','Manchester City Home Kit','Use Manchester City Home Kit',1,'a:3:{s:9:\"jersey_id\";i:5;s:4:\"name\";s:24:\"Manchester City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(7,'ACCESSORIES','Aston Villa Home Kit','Aston Villa Home Kit',1,'a:3:{s:9:\"jersey_id\";s:1:\"6\";s:4:\"name\";s:20:\"Aston Villa Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(8,'ACCESSORIES','Cardiff City Home Kit','Cardiff City Home Kit',1,'a:3:{s:9:\"jersey_id\";s:1:\"7\";s:4:\"name\";s:21:\"Cardiff City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(9,'ACCESSORIES','Crystal Palace Home Kit','Crystal Palace Home Kit',1,'a:3:{s:9:\"jersey_id\";s:1:\"8\";s:4:\"name\";s:23:\"Crystal Palace Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(10,'ACCESSORIES','Everton Home Kit','Everton Home Kit',1,'a:3:{s:9:\"jersey_id\";s:1:\"9\";s:4:\"name\";s:16:\"Everton Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(11,'ACCESSORIES','Fullham Home Kit','Fullham Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"10\";s:4:\"name\";s:16:\"Fullham Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(12,'ACCESSORIES','Hull City Home Kit','Hull City Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"11\";s:4:\"name\";s:18:\"Hull City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(13,'ACCESSORIES','Newcastle United Home Kit','Newcastle United Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"12\";s:4:\"name\";s:25:\"Newcastle United Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(14,'ACCESSORIES','Norwich City Home Kit','Norwich City Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"13\";s:4:\"name\";s:21:\"Norwich City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(15,'ACCESSORIES','Southampton Home Kit','Southampton Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"14\";s:4:\"name\";s:20:\"Southampton Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(16,'ACCESSORIES','Stoke City Home Kit','Stoke City Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"15\";s:4:\"name\";s:19:\"Stoke City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(17,'ACCESSORIES','Sunderland Home Kit','Sunderland Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"16\";s:4:\"name\";s:19:\"Sunderland Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(18,'ACCESSORIES','Swansea City Home Kit','Swansea City Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"17\";s:4:\"name\";s:21:\"Swansea City Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(19,'ACCESSORIES','Tottenham Home Kit','Tottenham Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"18\";s:4:\"name\";s:18:\"Tottenham Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(20,'ACCESSORIES','West Bromwich Albion Home Kit','West Bromwich Albion Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"19\";s:4:\"name\";s:29:\"West Bromwich Albion Home Kit\";s:4:\"type\";s:6:\"jersey\";}'),(21,'ACCESSORIES','West Ham United Home Kit','West Ham United Home Kit',1,'a:3:{s:9:\"jersey_id\";s:2:\"20\";s:4:\"name\";s:24:\"West Ham United Home Kit\";s:4:\"type\";s:6:\"jersey\";}');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
