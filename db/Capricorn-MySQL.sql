-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2015 at 09:36 PM
-- Server version: 5.5.32
-- PHP Version: 5.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


--
-- Database: `capricorn`
--

CREATE DATABASE IF NOT EXISTS `capricorn` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `capricorn`;

-- --------------------------------------------------------

--
-- Table structure for table `attendingiddefinition`
--

CREATE TABLE IF NOT EXISTS `AttendingIDDefinition` (
  `AttendingID` varchar(11) NOT NULL,
  `LastName` varchar(35) NOT NULL,
  `FirstName` varchar(35) NOT NULL,
  `Section` varchar(10) DEFAULT NULL COMMENT 'Attending''s primary section',
  PRIMARY KEY (`AttendingID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `AttendingIDDefinition` WRITE;
INSERT INTO `AttendingIDDefinition` VALUES (1,'Stein','Ben', 'TEST SECTION');
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ExamCodeDefinition`
--

CREATE TABLE IF NOT EXISTS `ExamCodeDefinition` (
  `ORG` varchar(7) NOT NULL DEFAULT '',
  `ExamCode` varchar(10) NOT NULL DEFAULT '',
  `CPTCode` varchar(100) DEFAULT NULL COMMENT 'Corresponding CPT Code',
  `Description` varchar(73) DEFAULT NULL,
  `Department` varchar(10) DEFAULT NULL COMMENT 'Deprecated; no longer used',
  `Modality` varchar(8) DEFAULT NULL COMMENT 'Deprecated; no longer used',
  `BodySite` varchar(10) DEFAULT NULL COMMENT 'Deprecated; no longer used',
  `SubSpecialty` varchar(9) DEFAULT NULL COMMENT 'Deprecated; no longer used',
  `Rotation` varchar(9) DEFAULT NULL COMMENT 'Deprecated; no longer used',
  `Section` varchar(6) DEFAULT NULL COMMENT 'i.e. body, chest, MSK, IR, etc.',
  `Type` varchar(6) DEFAULT NULL COMMENT 'Modality, i.e. CT, MR, etc',
  `Notes` varchar(16) DEFAULT NULL  COMMENT 'Special notation further characterizing specific study.',
  PRIMARY KEY (`ExamCode`,`ORG`),
  KEY `Rotation` (`ExamCode`,`Notes`,`Section`,`Type`,`ORG`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `ExamCodeDefinition` WRITE;
/*!40000 ALTER TABLE `ExamCodeDefinition` DISABLE KEYS */;
INSERT INTO `ExamCodeDefinition` VALUES ('1','hosp','RPID2503','XR CHEST 2 VIEWS','dept','UH Chest','CHEST','CR','notes: nil'),('2','hosp','RPID2605','XR PEDIATRIC BABYGRAM','dept','Peds','BABY','BABY','notes: nil');
/*!40000 ALTER TABLE `ExamCodeDefinition` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ExamDiscrepancy`
--

CREATE TABLE IF NOT EXISTS `ExamDiscrepancy` (
  `AccessionNumber` varchar(15) NOT NULL COMMENT 'If two exams are associated to the same report, the PrimaryAccessionNumber is used here.',
  `TraineeID` int(11) NOT NULL,
  `AutoDiscrepancy` varchar(15) NOT NULL COMMENT 'Discrepancy assignment by parsing the attending final report.',
  `AdminDiscrepancy` varchar(15) NOT NULL,
  `CompositeDiscrepancy` varchar(15) NOT NULL DEFAULT 'None' COMMENT 'This is a stored calculated value based on attending macro and admin revision - could be a weighted average, or simply using revision as the new value.',
  `AdminComment` tinytext,
  `TraineeComment` tinytext COMMENT 'Trainee comment regarding the discrepancy assignment.',
  `TraineeMarkAsReviewed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - Not reviewed; 1- Reviewed No issues; 2 - Flag for admin refeview; 3 - Resolved',
  `EDNotify` tinyint(1) DEFAULT NULL COMMENT 'A field to be used when the ED needs to be notified of significant changes made to the report due to discrepancy.',
  PRIMARY KEY (`AccessionNumber`,`TraineeID`),
  KEY `TraineeID` (`TraineeID`),
  KEY `TraineeMarkAsReviewed` (`TraineeMarkAsReviewed`),
  KEY `CompositeDiscrepancy` (`CompositeDiscrepancy`,`EDNotify`),
  KEY `AdminDiscrepancy` (`AdminDiscrepancy`,`EDNotify`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ExamDiscrepancyCounts`
--

CREATE TABLE IF NOT EXISTS `ExamDiscrepancyCounts` (
  `TraineeID` int(11) NOT NULL,
  `Section` varchar(15) NOT NULL,
  `Type` varchar(10) NOT NULL,
  `FinalDiscrepancy` varchar(15) NOT NULL,
  `StartDate` date NOT NULL,
  `Count` int(7) NOT NULL,
  PRIMARY KEY (`Type`,`Section`,`FinalDiscrepancy`,`TraineeID`,`StartDate`),
  KEY `TraineeID` (`TraineeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ExamMeta`
--

CREATE TABLE IF NOT EXISTS `ExamMeta` (
  `InternalID` varchar(30) NOT NULL,
  `AccessionNumber` varchar(15) DEFAULT NULL,
  `PrimaryAccessionNumber` varchar(8) NOT NULL COMMENT 'The AccessionNumber used to store the exam text.',
  `Urgency` varchar(5) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'R - Routine; SE - Stat Exam; SI - Stat Interpretation; SEI - Stat Exam and Interpretation, or whatever convention your institution has',
  `LastName` varchar(35) NOT NULL,
  `FirstName` varchar(35) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `Location` varchar(1) DEFAULT NULL,
  `ExamCode` varchar(16) DEFAULT NULL,
  `RISTraineeID` int(11) DEFAULT NULL,
  `TraineeID` int(11) DEFAULT NULL,
  `AttendingID` int(11) DEFAULT NULL,
  `OrganizationID` int(11) DEFAULT NULL,
  `Organization` varchar(8) NOT NULL,
  `CompletedDTTM` datetime DEFAULT NULL,
  `InquiryDTTM` datetime DEFAULT NULL,
  `DraftDTTM` datetime DEFAULT NULL,
  `PrelimDTTM` datetime DEFAULT NULL,
  `PrelimTAT` mediumint(9) DEFAULT '0' COMMENT 'Complete to Prelim report turnaround time, in seconds',
  `ResidentYear` int(11) DEFAULT NULL,
  PRIMARY KEY (`InternalID`),
  KEY `CompletedDTTM` (`CompletedDTTM`,`ExamCode`,`Organization`),
  KEY `AccessionNumber` (`AccessionNumber`),
  KEY `PrimaryAccessionNumber` (`PrimaryAccessionNumber`),
  KEY `AttendingID` (`AttendingID`),
  KEY `ExamCode` (`ExamCode`,`Organization`),
  KEY `LocationCode` (`Location`,`Urgency`),
  KEY `PrelimTAT` (`PrelimTAT`,`Location`,`Urgency`),
  KEY `TraineeID` (`TraineeID`,`CompletedDTTM`,`ExamCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `ExamMeta` WRITE;
/*!40000 ALTER TABLE `ExamMeta` DISABLE KEYS */;
INSERT INTO `ExamMeta` VALUES ('1','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-01 17:00:00','2014-06-01 17:05:00','2014-06-01 17:10:00','2014-06-01 17:11:02',1),('2','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-02 17:00:00','2014-06-02 17:05:00','2014-06-02 17:10:00','2014-06-02 17:11:02',1),('3','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-02 19:00:00','2014-06-02 19:05:00','2014-06-02 19:10:00','2014-06-02 19:11:02',1),('4','1234','Flintstone','Wilma',120,'RPID2503',1,1,1,'hosp','2014-06-03 10:30:00','2014-06-03 10:35:00','2014-06-03 10:40:00','2014-06-03 19:41:02',1);
/*!40000 ALTER TABLE `ExamMeta` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ExamReportText`
--

CREATE TABLE IF NOT EXISTS `ExamReportText` (
  `AccessionNumber` varchar(15) NOT NULL COMMENT 'If two exams are associated to the same report, the PrimaryAccessionNumber is used here.',
  `PreliminaryReportText` text NOT NULL,
  `FinalReportText` text NOT NULL,
  PRIMARY KEY (`AccessionNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores the report texts for the examinations.';

LOCK TABLES `ExamReportText` WRITE;
/*!40000 ALTER TABLE `ExamReportText` DISABLE KEYS */;
INSERT INTO `ExamReportText` VALUES ('1','Test Report Preliminary text.', 'Test Report Final text.'), ('2','Test Report Preliminary text.', 'Test Report Final text.'), ('3','Test Report Preliminary text.', 'Test Report Final text.'), ('4','Test Report Preliminary text.', 'Test Report Final text.');
/*!40000 ALTER TABLE `ExamReportText` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ExamUserTags`
--

CREATE TABLE IF NOT EXISTS `ExamUserTags` (
  `AccessionNumber` varchar(11) NOT NULL COMMENT 'Correlates with PrimaryAccessionNumber in exammeta for multiple case associations.',
  `TraineeID` int(11) NOT NULL,
  `Tag` varchar(25) NOT NULL,
  PRIMARY KEY (`TraineeID`,`Tag`,`AccessionNumber`),
  KEY `AccessionNumber` (`AccessionNumber`),
  KEY `Tag` (`Tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores tags/folders user can assign to individual cases.';

-- --------------------------------------------------------

--
-- Table structure for table `LoginMember`
--

CREATE TABLE IF NOT EXISTS `LoginMember` (
  `TraineeID` int(11) NOT NULL,
  `Username` varchar(25) NOT NULL,
  `PasswordHash` text NOT NULL,
  PRIMARY KEY (`TraineeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `LoginMember` WRITE;
/*!40000 ALTER TABLE `LoginMember` DISABLE KEYS */;
INSERT INTO `LoginMember` VALUES (1,'fbueler','$2BTThe03q1l2');
/*!40000 ALTER TABLE `LoginMember` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ResidentCounts`
--

CREATE TABLE IF NOT EXISTS `ResidentCounts` (
  `UniqueID` varchar(40) NOT NULL,
  `TraineeID` int(11) NOT NULL,
  `ResidentYear` int(2) NOT NULL,
  `CountDT` date NOT NULL,
  `Section` varchar(6) NOT NULL,
  `Type` varchar(6) NOT NULL,
  `Notes` varchar(16) NOT NULL,
  `Count` int(7) NOT NULL,
  PRIMARY KEY (`UniqueID`),
  KEY `CountDT` (`CountDT`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ResidentIDDefinition`
--

CREATE TABLE IF NOT EXISTS `ResidentIDDefinition` (
  `RISTraineeID` int(11) NOT NULL,
  `TraineeID` int(11) NOT NULL COMMENT 'PS360 Mirror AccountID',
  `FirstName` varchar(35) NOT NULL,
  `MiddleName` varchar(35) NOT NULL DEFAULT '',
  `LastName` varchar(35) NOT NULL,
  `IsCurrentTrainee` varchar(5) NOT NULL DEFAULT 'N',
  `IsResident` binary(1) NOT NULL DEFAULT '1' COMMENT 'Binary value - set to 1 if this trainee is/was a resident',
  `IsFellow` binary(1) NOT NULL DEFAULT '0',
  `Subspecialty` varchar(8) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL COMMENT 'Type of fellowship or specialty residents can be designated here.',
  `StartDate` date NOT NULL,
  `QGendaName` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`TraineeID`),
  KEY `IsCurrentTrainee` (`IsCurrentTrainee`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


LOCK TABLES `ResidentIDDefinition` WRITE;
/*!40000 ALTER TABLE `ResidentIDDefinition` DISABLE KEYS */;
INSERT INTO `ResidentIDDefinition` VALUES (1,'Ferris','D','Bueller','Y','2013-07-01','FBue');
/*!40000 ALTER TABLE `ResidentIDDefinition` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `ResidentRotation`
--

CREATE TABLE IF NOT EXISTS `ResidentRotation` (
  `TraineeID` int(11) NOT NULL,
  `Rotation` varchar(25) NOT NULL,
  `RotationStartDate` date NOT NULL,
  `RotationEndDate` date NOT NULL,
  KEY `TraineeID` (`TraineeID`,`RotationStartDate`,`RotationEndDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ResidentRotationRaw`
--

CREATE TABLE IF NOT EXISTS `ResidentRotationRaw` (
  `UniqueID` varchar(40) NOT NULL,
  `TraineeID` int(11) NOT NULL,
  `Rotation` varchar(25) NOT NULL,
  `RotationStartDate` date NOT NULL,
  `RotationEndDate` date NOT NULL,
  PRIMARY KEY (`UniqueID`),
  KEY `RotationStartDate` (`RotationStartDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

