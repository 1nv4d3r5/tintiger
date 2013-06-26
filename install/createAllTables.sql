-- phpMyAdmin SQL Dump
-- version 2.7.0-pl2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Oct 28, 2009 at 09:02 PM
-- Server version: 5.0.18
-- PHP Version: 5.1.2
-- 
-- Database: `journal`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `comments`
-- 

CREATE TABLE IF NOT EXISTS `comments` (
  `cmtType` char(1) NOT NULL COMMENT 'Guestbook, Comment, Journal cmt',
  `cmtKey` varchar(255) NOT NULL,
  `createDateTime` datetime NOT NULL,
  `cmtSubject` varchar(255) NOT NULL,
  `cmtText` text NOT NULL,
  `cmtName` varchar(255) NOT NULL,
  `cmtEmail` varchar(255) NOT NULL,
  `hideEmail` char(1) NOT NULL,
  `cmtWebsite` varchar(255) NOT NULL,
  `hideWebsite` char(1) NOT NULL,
  `replyNotify` char(1) NOT NULL COMMENT 'notify parent(s) when comment commented on',
  `cmtStatus` varchar(255) NOT NULL,
  `rowID` bigint(20) NOT NULL auto_increment,
  `sid` bigint(20) NOT NULL COMMENT 'story / master rowID',
  `pid` bigint(20) NOT NULL COMMENT 'parent comment rowID',
  PRIMARY KEY  (`rowID`),
  KEY `cmtKey` (`cmtKey`,`createDateTime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `entry`
-- 

CREATE TABLE IF NOT EXISTS `entry` (
  `CreateDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `Entry` text NOT NULL COMMENT 'text of the journal entry',
  `AttachTo` bigint(20) default NULL,
  `UserID` varchar(64) default NULL,
  `ID` bigint(20) NOT NULL auto_increment,
  PRIMARY KEY  (`ID`),
  KEY `CreateDate` (`CreateDate`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `members`
-- 

CREATE TABLE IF NOT EXISTS `members` (
  `rowID` int(11) NOT NULL auto_increment,
  `username` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `password` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `zip` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `boatname` varchar(255) NOT NULL,
  `hailingport` varchar(255) NOT NULL,
  `CreateDate` datetime NOT NULL,
  `mbrStatus` varchar(8) NOT NULL,
  `loginDateTime` datetime default NULL,
  `loginIP` varchar(255) default NULL,
  `lastPageTime` datetime default NULL,
  PRIMARY KEY  (`rowID`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `photos`
-- 

CREATE TABLE IF NOT EXISTS `photos` (
  `id` bigint(20) NOT NULL auto_increment,
  `createDate` date NOT NULL default '0000-00-00',
  `dirName` varchar(255) default NULL character set latin1 collate latin1_general_cs 
        COMMENT `full path back to PhotoDir`,
  `fileName` varchar(255) default NULL character set latin1 collate latin1_general_cs   
        COMMENT `filename and extension, case sensitive`,
  `imageText` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `access`
-- 

CREATE TABLE IF NOT EXISTS `access` (
  accName varchar(255) NOT NULL,
  accTarget varchar(255) NOT NULL,
  accLogin varchar(255) default NULL,
  accView text,
  accEdit text,
  rowID bigint(20) NOT NULL auto_increment,
  CreateDate datetime NOT NULL,
  lastEditDate datetime NOT NULL,
  lastEditUser varchar(255) NOT NULL,
  PRIMARY KEY  (rowID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
