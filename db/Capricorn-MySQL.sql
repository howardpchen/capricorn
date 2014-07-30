SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `capricorn` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `capricorn`;

CREATE TABLE IF NOT EXISTS `AttendingIDDefinition` (
  `AttendingID` int(11) NOT NULL,
  `LastName` varchar(45) NOT NULL,
  `FirstName` varchar(45) NOT NULL,
  PRIMARY KEY (`AttendingID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `AttendingIDDefinition`
--

LOCK TABLES `AttendingIDDefinition` WRITE;
/*!40000 ALTER TABLE `AttendingIDDefinition` DISABLE KEYS */;
INSERT INTO `AttendingIDDefinition` VALUES (1,'Stein','Ben');
/*!40000 ALTER TABLE `AttendingIDDefinition` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `ExamCodeDefinition` (
  `InternalCode` varchar(18) NOT NULL DEFAULT '',
  `ORG` varchar(7) DEFAULT NULL,
  `ExamCode` varchar(10) DEFAULT NULL,
  `Description` varchar(73) DEFAULT NULL,
  `Department` varchar(10) DEFAULT NULL,
  `Rotation` varchar(9) DEFAULT NULL,
  `Section` varchar(6) DEFAULT NOT NULL,
  `Type` varchar(6) DEFAULT NOT NULL,
  `Notes` varchar(16) DEFAULT NOT NULL,
  PRIMARY KEY (`InternalCode`),
  KEY `ExamCode` (`ExamCode`,`ORG`),
  KEY `Rotation` (`ExamCode`,`Notes`,`Section`,`Type`,`ORG`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ExamCodeDefinition`
--

LOCK TABLES `ExamCodeDefinition` WRITE;
/*!40000 ALTER TABLE `ExamCodeDefinition` DISABLE KEYS */;
INSERT INTO `ExamCodeDefinition` VALUES ('1','hosp','RPID2503','XR CHEST 2 VIEWS','dept','UH Chest','CHEST','CR','notes: nil'),('2','hosp','RPID2605','XR PEDIATRIC BABYGRAM','dept','Peds','BABY','BABY','notes: nil');
/*!40000 ALTER TABLE `ExamCodeDefinition` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `ExamMeta` (
  `InternalID` varchar(30) NOT NULL,
  `AccessionNumber` varchar(16) DEFAULT NULL,
  `LastName` varchar(25) NOT NULL,
  `FirstName` varchar(25) NOT NULL,
  `PatientID` varchar(50) NOT NULL,
  `ExamCode` varchar(16) DEFAULT NULL,
  `TraineeID` int(11) DEFAULT NULL,
  `AttendingID` int(11) DEFAULT NULL,
  `OrganizationID` int(11) DEFAULT NULL,
  `Organization` varchar(8) NOT NULL,
  `CompletedDTTM` datetime DEFAULT NULL,
  `InquiryDTTM` datetime DEFAULT NULL,
  `DraftDTTM` datetime DEFAULT NULL,
  `PrelimDTTM` datetime DEFAULT NULL,
  `ResidentYear` int(11) DEFAULT NULL,
  PRIMARY KEY (`InternalID`),
  KEY `CompletedDTTM` (`CompletedDTTM`,`ExamCode`,`Organization`),
  KEY `TraineeID` (`TraineeID`,`CompletedDTTM`,`ExamCode`),
  INDEX `AccessionNumber` UNIQUE `AccessionNumber`
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ExamMeta`
--

LOCK TABLES `ExamMeta` WRITE;
/*!40000 ALTER TABLE `ExamMeta` DISABLE KEYS */;
INSERT INTO `ExamMeta` VALUES ('1','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-01 17:00:00','2014-06-01 17:05:00','2014-06-01 17:10:00','2014-06-01 17:11:02',1),('2','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-02 17:00:00','2014-06-02 17:05:00','2014-06-02 17:10:00','2014-06-02 17:11:02',1),('3','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-02 19:00:00','2014-06-02 19:05:00','2014-06-02 19:10:00','2014-06-02 19:11:02',1),('4','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-03 10:30:00','2014-06-03 10:35:00','2014-06-03 10:40:00','2014-06-03 19:41:02',1);
/*!40000 ALTER TABLE `ExamMeta` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `LoginMember` (
  `TraineeID` int(11) NOT NULL,
  `Username` varchar(25) NOT NULL,
  `PasswordHash` text NOT NULL,
  PRIMARY KEY (`TraineeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `LoginMember`
--

LOCK TABLES `LoginMember` WRITE;
/*!40000 ALTER TABLE `LoginMember` DISABLE KEYS */;
INSERT INTO `LoginMember` VALUES (1,'fbueler','$2BTThe03q1l2');
/*!40000 ALTER TABLE `LoginMember` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `ResidentCounts` (
  `UniqueID` varchar(40) NOT NULL,
  `TraineeID` int(11) NOT NULL,
  `ResidentYear` int(2) NOT NULL,
  `CountDT` date NOT NULL,
  `Section` varchar(6) NOT NULL,
  `Type` varchar(6) NOT NULL,
  `Notes` varchar(16) NOT NULL,
  `Count` int(7) NOT NULL,
  PRIMARY KEY (`UniqueID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `ResidentIDDefinition` (
  `TraineeID` int(11) NOT NULL,
  `FirstName` varchar(25) NOT NULL,
  `MiddleName` varchar(25) NULL DEFAULT '',
  `LastName` varchar(25) NOT NULL,
  `IsCurrentTrainee` bool NOT NULL DEFAULT 'N',
  `StartDate` date NOT NULL,
  `QGendaName` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`TraineeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ResidentIDDefinition`
--

LOCK TABLES `ResidentIDDefinition` WRITE;
/*!40000 ALTER TABLE `ResidentIDDefinition` DISABLE KEYS */;
INSERT INTO `ResidentIDDefinition` VALUES (1,'Ferris','D','Bueller','Y','2013-07-01','FBue');
/*!40000 ALTER TABLE `ResidentIDDefinition` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `ResidentRotation` (
  `ID` int(11) NOT NULL KEY AUTO_INCREMENT,
  `TraineeID` int(11) NOT NULL,
  `Rotation` varchar(25) NOT NULL,
  `RotationStartDate` date NOT NULL,
  `RotationEndDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `ResidentRotationRaw` (
  `UniqueID` varchar(40) NOT NULL,
  `TraineeID` int(11) NOT NULL,
  `Rotation` varchar(25) NOT NULL,
  `RotationStartDate` date NOT NULL,
  `RotationEndDate` date NOT NULL,
  PRIMARY KEY (`UniqueID`),
  KEY `RotationStartDate` (`RotationStartDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
