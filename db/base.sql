-- MySQL dump 10.13  Distrib 5.7.9, for linux-glibc2.5 (x86_64)
--
-- Host: 127.0.0.1    Database: accounts
-- ------------------------------------------------------
-- Server version	5.6.30-0ubuntu0.14.04.1-log

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
-- Table structure for table `account`
--

DROP TABLE IF EXISTS `account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account` (
  `idaccount` int(11) NOT NULL AUTO_INCREMENT,
  `idaccounttype` varchar(20) COLLATE utf8_bin NOT NULL,
  `iduser` int(11) NOT NULL,
  `grossbalance` decimal(15,5) DEFAULT '0.00000',
  `uncleared` decimal(15,5) DEFAULT '0.00000',
  `netbalance` decimal(15,5) DEFAULT '0.00000',
  `price` decimal(15,5) NOT NULL DEFAULT '1.00000',
  `extra` text COLLATE utf8_bin,
  `minvalue` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `entrydate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idaccount`),
  UNIQUE KEY `unique_userid_type` (`iduser`,`idaccounttype`),
  KEY `fk_account_accounttype_idx` (`idaccounttype`),
  CONSTRAINT `fk_account_accounttype` FOREIGN KEY (`idaccounttype`) REFERENCES `accounttype` (`idaccounttype`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accounttype`
--

DROP TABLE IF EXISTS `accounttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounttype` (
  `idaccounttype` varchar(20) COLLATE utf8_bin NOT NULL,
  `name` varchar(45) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`idaccounttype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statement`
--

DROP TABLE IF EXISTS `statement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statement` (
  `idstatement` int(11) NOT NULL AUTO_INCREMENT,
  `idaccount` int(11) NOT NULL,
  `idaccounttype` varchar(20) COLLATE utf8_bin NOT NULL,
  `idtype` enum('B','D','W','DB','WB','R') COLLATE utf8_bin NOT NULL COMMENT 'B: Balance - Inicia um novo valor desprezando os antigos\nD: Deposit: Adiciona um valor imediatamente ao banco\nW: Withdrawal\nR: Reject\nWD: Withdrawal (blocked, uncleared)\n',
  `amount` decimal(15,5) NOT NULL,
  `price` decimal(15,5) DEFAULT '1.00000',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grossbalance` decimal(15,5) DEFAULT NULL,
  `uncleared` decimal(15,5) DEFAULT NULL,
  `netbalance` decimal(15,5) DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `idstatementparent` int(11) DEFAULT NULL,
  `reference` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idstatement`),
  KEY `fk_statement_account1_idx` (`idaccount`),
  KEY `fk_statement_statement1_idx` (`idstatementparent`),
  KEY `idx_statement_idtype_date` (`idtype`,`date`) USING BTREE COMMENT 'Índice para filtros com tipo e ordenação por data decrescente',
  KEY `fk_statement_accounttype_idx` (`idaccounttype`),
  CONSTRAINT `fk_statement_accounttype` FOREIGN KEY (`idaccounttype`) REFERENCES `accounttype` (`idaccounttype`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_statement_account1` FOREIGN KEY (`idaccount`) REFERENCES `account` (`idaccount`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_statement_statement1` FOREIGN KEY (`idstatementparent`) REFERENCES `statement` (`idstatement`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'accounts'
--

--
-- Dumping routines for database 'accounts'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-06-07 21:13:14
