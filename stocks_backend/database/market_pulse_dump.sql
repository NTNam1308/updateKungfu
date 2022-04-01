-- MySQL dump 10.13  Distrib 5.7.35, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	5.7.35-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `market_pulse`
--

DROP TABLE IF EXISTS `market_pulse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `market_pulse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `market_pulse`
--

LOCK TABLES `market_pulse` WRITE;
/*!40000 ALTER TABLE `market_pulse` DISABLE KEYS */;
INSERT INTO `market_pulse` VALUES (1,'{\"list_main_content\":[{\"image\":\"<svg width=\\\"24px\\\" height=\\\"24px\\\" viewBox=\\\"0 0 24 24\\\" xmlns:rdf=\\\"http:\\/\\/www.w3.org\\/1999\\/02\\/22-rdf-syntax-ns#\\\" xmlns=\\\"http:\\/\\/www.w3.org\\/2000\\/svg\\\" version=\\\"1.1\\\" xmlns:cc=\\\"http:\\/\\/creativecommons.org\\/ns#\\\" xmlns:dc=\\\"http:\\/\\/purl.org\\/dc\\/elements\\/1.1\\/\\\">  <g transform=\\\"translate(0 -1028.4)\\\">   <path d=\\\"m5 1032.4c-1.1046 0-2 0.9-2 2v14c0 1.1 0.8954 2 2 2h6 2 6c1.105 0 2-0.9 2-2v-14c0-1.1-0.895-2-2-2h-6-2-6z\\\" fill=\\\"#bdc3c7\\\"\\/>   <path d=\\\"m5 3c-1.1046 0-2 0.8954-2 2v14c0 1.105 0.8954 2 2 2h6 2 6c1.105 0 2-0.895 2-2v-14c0-1.1046-0.895-2-2-2h-6-2-6z\\\" transform=\\\"translate(0 1028.4)\\\" fill=\\\"#ecf0f1\\\"\\/>   <path d=\\\"m5 3c-1.1046 0-2 0.8954-2 2v3 1h18v-1-3c0-1.1046-0.895-2-2-2h-6-2-6z\\\" transform=\\\"translate(0 1028.4)\\\" fill=\\\"#e74c3c\\\"\\/>   <path d=\\\"m7 5.5a1.5 1.5 0 1 1 -3 0 1.5 1.5 0 1 1 3 0z\\\" transform=\\\"translate(.5 1028.4)\\\" fill=\\\"#c0392b\\\"\\/>   <path d=\\\"m7 5.5a1.5 1.5 0 1 1 -3 0 1.5 1.5 0 1 1 3 0z\\\" transform=\\\"translate(12.5 1028.4)\\\" fill=\\\"#c0392b\\\"\\/>   <g fill=\\\"#bdc3c7\\\">    <path d=\\\"m5 1039.4v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2z\\\"\\/>    <path d=\\\"m5 1042.4v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2z\\\"\\/>    <path d=\\\"m5 1045.4v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2zm3 0v2h2v-2h-2z\\\"\\/>   <\\/g>   <path d=\\\"m24 12a12 12 0 1 1 -24 0 12 12 0 1 1 24 0z\\\" transform=\\\"matrix(.42014 0 0 .42014 13.458 1041.8)\\\" fill=\\\"#34495e\\\"\\/>   <path d=\\\"m18.5 1041.8c-3.038 0-5.5 2.5-5.5 5.5h0.917c0-2.5 2.052-4.6 4.583-4.6s4.583 2.1 4.583 4.6h0.917c0-3-2.462-5.5-5.5-5.5z\\\" fill=\\\"#2c3e50\\\"\\/>   <path d=\\\"m18.958 1046.4v0.9h0.459 2.75c0.253 0 0.458-0.2 0.458-0.4 0-0.3-0.205-0.5-0.458-0.5h-2.75-0.459z\\\" fill=\\\"#bdc3c7\\\"\\/>   <path d=\\\"m18.5 1043.7c-0.253 0-0.458 0.2-0.458 0.4v1.8 0.5h0.916v-0.5-1.8c0-0.2-0.205-0.4-0.458-0.4z\\\" fill=\\\"#bdc3c7\\\"\\/>   <rect transform=\\\"rotate(-45)\\\" height=\\\".45833\\\" width=\\\"3.2083\\\" y=\\\"753.12\\\" x=\\\"-730.83\\\" fill=\\\"#c0392b\\\"\\/>   <path d=\\\"m18.5 1045.9c-0.506 0-0.917 0.5-0.917 1s0.411 0.9 0.917 0.9 0.917-0.4 0.917-0.9-0.411-1-0.917-1zm0 0.5c0.253 0 0.458 0.2 0.458 0.5 0 0.2-0.205 0.4-0.458 0.4s-0.458-0.2-0.458-0.4c0-0.3 0.205-0.5 0.458-0.5z\\\" fill=\\\"#bdc3c7\\\"\\/>   <path d=\\\"m18.5 1041.4c-3.038 0-5.5 2.4-5.5 5.5 0 3 2.462 5.5 5.5 5.5s5.5-2.5 5.5-5.5c0-3.1-2.462-5.5-5.5-5.5zm0 0.9c2.531 0 4.583 2 4.583 4.6 0 2.5-2.052 4.5-4.583 4.5s-4.583-2-4.583-4.5c0-2.6 2.052-4.6 4.583-4.6z\\\" fill=\\\"#95a5a6\\\"\\/>   <path d=\\\"m13 12a1 1 0 1 1 -2 0 1 1 0 1 1 2 0z\\\" transform=\\\"matrix(.45833 0 0 .45833 13 1041.4)\\\" fill=\\\"#2c3e50\\\"\\/>   <path d=\\\"m19.293 1046.4c0.078 0.1 0.129 0.3 0.129 0.5 0 0.1-0.051 0.3-0.129 0.4h0.129 0.372c0.052-0.1 0.086-0.3 0.086-0.4 0-0.2-0.034-0.4-0.086-0.5h-0.372-0.129z\\\" fill=\\\"#95a5a6\\\"\\/>   <path d=\\\"m6 1c-0.5523 0-1 0.4477-1 1v3c0 0.5523 0.4477 1 1 1s1-0.4477 1-1v-3c0-0.5523-0.4477-1-1-1zm12 0c-0.552 0-1 0.4477-1 1v3c0 0.5523 0.448 1 1 1s1-0.4477 1-1v-3c0-0.5523-0.448-1-1-1z\\\" transform=\\\"translate(0 1028.4)\\\" fill=\\\"#95a5a6\\\"\\/>   <path d=\\\"m6 1029.4c-0.5523 0-1 0.4-1 1v2h2v-2c0-0.6-0.4477-1-1-1zm12 0c-0.552 0-1 0.4-1 1v2h2v-2c0-0.6-0.448-1-1-1z\\\" fill=\\\"#bdc3c7\\\"\\/>  <\\/g> <\\/svg>\",\"title\":\"H\\u00e0nh \\u0111\\u1ed9ng ng\\u00e0y th\\u1ee9 hai\",\"content\":\"Ch\\u1ec9 s\\u1ed1 VN30 gi\\u1ea3m nh\\u1eb9 nh\\u01b0ng v\\u1eabn kh\\u00f4ng \\u1ea3nh h\\u01b0\\u1edfng m\\u1ea1nh t\\u1edbi th\\u1ecb tr\\u01b0\\u1eddng\"},{\"image\":\"<svg version=\\\"1.1\\\" id=\\\"Layer_1\\\" xmlns=\\\"http:\\/\\/www.w3.org\\/2000\\/svg\\\" xmlns:xlink=\\\"http:\\/\\/www.w3.org\\/1999\\/xlink\\\" x=\\\"0px\\\" y=\\\"0px\\\" \\t width=\\\"24px\\\" height=\\\"24px\\\" viewBox=\\\"0 0 40.531 40.488\\\" enable-background=\\\"new 0 0 40.531 40.488\\\" xml:space=\\\"preserve\\\"> <path fill=\\\"none\\\" stroke=\\\"#282828\\\" stroke-miterlimit=\\\"10\\\" d=\\\"M39.916,5.307v29.936c0,2.585-2.096,4.68-4.678,4.68H5.3 \\tc-2.583,0-4.678-2.095-4.678-4.68V5.307c0-2.583,2.095-4.672,4.678-4.672h29.938C37.82,0.634,39.916,2.724,39.916,5.307z\\\"\\/> <path fill=\\\"#2F9E2E\\\" d=\\\"M19.657,17.864l-1.916-0.293l-1.905,12.438l-6.887-1.056l1.904-12.437l-1.915-0.294 \\tc-0.469-0.071-0.657-0.645-0.323-0.981l6.483-6.504c0.277-0.277,0.744-0.206,0.924,0.142l4.237,8.145 \\tC20.477,17.445,20.125,17.937,19.657,17.864\\\"\\/> <path fill=\\\"none\\\" stroke=\\\"#282828\\\" d=\\\"M20.196,22.008l1.916,0.293l1.905-12.438l6.887,1.055L29,23.356l1.915,0.293 \\tc0.47,0.072,0.657,0.646,0.323,0.982l-6.483,6.503c-0.277,0.278-0.744,0.206-0.924-0.142l-4.236-8.146 \\tC19.376,22.427,19.729,21.936,20.196,22.008z\\\"\\/> <\\/svg>\",\"title\":\"T\\u1ed5ng quan chung th\\u1ecb tr\\u01b0\\u1eddng\",\"content\":\"Th\\u1ecb tr\\u01b0\\u1eddng \\u0111ang trong xu h\\u01b0\\u1edbng t\\u0103ng\"},{\"image\":\"<svg version=\\\"1.1\\\" id=\\\"Capa_1\\\" xmlns=\\\"http:\\/\\/www.w3.org\\/2000\\/svg\\\" xmlns:xlink=\\\"http:\\/\\/www.w3.org\\/1999\\/xlink\\\" x=\\\"0px\\\" y=\\\"0px\\\" \\t viewBox=\\\"0 0 56 56\\\" style=\\\"enable-background:new 0 0 56 56;\\\" xml:space=\\\"preserve\\\"> <polygon style=\\\"fill:#61B872;\\\" points=\\\"41,5 15,5 0,5 0,16 56,16 56,5 \\\"\\/> <circle style=\\\"fill:#50965C;\\\" cx=\\\"19\\\" cy=\\\"9\\\" r=\\\"2\\\"\\/> <circle style=\\\"fill:#50965C;\\\" cx=\\\"25\\\" cy=\\\"9\\\" r=\\\"2\\\"\\/> <circle style=\\\"fill:#50965C;\\\" cx=\\\"31\\\" cy=\\\"9\\\" r=\\\"2\\\"\\/> <circle style=\\\"fill:#50965C;\\\" cx=\\\"37\\\" cy=\\\"9\\\" r=\\\"2\\\"\\/> <rect y=\\\"16\\\" style=\\\"fill:#EDEADA;\\\" width=\\\"56\\\" height=\\\"40\\\"\\/> <g> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"21\\\" cy=\\\"24\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"28\\\" cy=\\\"24\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"35\\\" cy=\\\"24\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"42\\\" cy=\\\"24\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"49\\\" cy=\\\"24\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"7\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"14\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"21\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"28\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"35\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"42\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"49\\\" cy=\\\"32\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"7\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"14\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"21\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"28\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"35\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"42\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"49\\\" cy=\\\"39\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"7\\\" cy=\\\"47\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"14\\\" cy=\\\"47\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"21\\\" cy=\\\"47\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"28\\\" cy=\\\"47\\\" r=\\\"1\\\"\\/> \\t<circle style=\\\"fill:#424A60;\\\" cx=\\\"35\\\" cy=\\\"47\\\" r=\\\"1\\\"\\/> <\\/g> <path style=\\\"fill:#D8A852;\\\" d=\\\"M37,0c-1.13,0-2.162,0.391-3,1.025c0.534,0.405,0.979,0.912,1.315,1.495C35.796,2.192,36.376,2,37,2 \\tc1.654,0,3,1.346,3,3s-1.346,3-3,3c-0.553,0-1,0.447-1,1s0.447,1,1,1c2.757,0,5-2.243,5-5S39.757,0,37,0z\\\"\\/> <path style=\\\"fill:#A37F46;\\\" d=\\\"M32.685,2.52C32.261,3.254,32,4.093,32,5h2C34,3.97,33.478,3.061,32.685,2.52z\\\"\\/> <path style=\\\"fill:#A37F46;\\\" d=\\\"M26.685,2.52C26.261,3.254,26,4.093,26,5h2C28,3.97,27.478,3.061,26.685,2.52z\\\"\\/> <path style=\\\"fill:#D8A852;\\\" d=\\\"M35.315,2.52C34.979,1.937,34.534,1.43,34,1.025C33.162,0.391,32.13,0,31,0s-2.162,0.391-3,1.025 \\tc0.534,0.405,0.979,0.912,1.315,1.495C29.796,2.192,30.376,2,31,2s1.204,0.192,1.685,0.52C33.478,3.061,34,3.97,34,5 \\tc0,1.654-1.346,3-3,3c-0.553,0-1,0.447-1,1s0.447,1,1,1c2.757,0,5-2.243,5-5C36,4.093,35.739,3.254,35.315,2.52z\\\"\\/> <path style=\\\"fill:#A37F46;\\\" d=\\\"M20.685,2.52C20.261,3.254,20,4.093,20,5h2C22,3.97,21.478,3.061,20.685,2.52z\\\"\\/> <path style=\\\"fill:#D8A852;\\\" d=\\\"M29.315,2.52C28.979,1.937,28.534,1.43,28,1.025C27.162,0.391,26.13,0,25,0s-2.162,0.391-3,1.025 \\tc0.534,0.405,0.979,0.912,1.315,1.495C23.796,2.192,24.376,2,25,2s1.204,0.192,1.685,0.52C27.478,3.061,28,3.97,28,5 \\tc0,1.654-1.346,3-3,3c-0.553,0-1,0.447-1,1s0.447,1,1,1c2.757,0,5-2.243,5-5C30,4.093,29.739,3.254,29.315,2.52z\\\"\\/> <path style=\\\"fill:#D8A852;\\\" d=\\\"M23.315,2.52C22.979,1.937,22.534,1.43,22,1.025C21.162,0.391,20.13,0,19,0c-2.757,0-5,2.243-5,5h2 \\tc0-1.654,1.346-3,3-3c0.624,0,1.204,0.192,1.685,0.52C21.478,3.061,22,3.97,22,5c0,1.654-1.346,3-3,3c-0.553,0-1,0.447-1,1 \\ts0.447,1,1,1c2.757,0,5-2.243,5-5C24,4.093,23.739,3.254,23.315,2.52z\\\"\\/> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <g> <\\/g> <\\/svg>\",\"title\":\"Ng\\u00e0y ph\\u00e2n ph\\u1ed1i\",\"content\":\"*B\\u1ed5 sung n\\u1ed9i dung sau\"}],\"list_mack_up\":[{\"mack\":\"HDB\"},{\"mack\":\"VPB\"},{\"mack\":\"HPG\"}],\"list_mack_down\":[{\"mack\":\"HDB\"},{\"mack\":\"VPB\"},{\"mack\":\"BSR\"}]}');
/*!40000 ALTER TABLE `market_pulse` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-10-26 16:57:15
