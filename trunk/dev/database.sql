-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server Version:               5.5.16 - MySQL Community Server (GPL)
-- Server Betriebssystem:        Win32
-- HeidiSQL Version:             9.1.0.4867
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportiere Struktur von Tabelle mcalc.meta_MetaproductUpdates
CREATE TABLE IF NOT EXISTS `meta_MetaproductUpdates` (
  `idMetaproduct` int(10) unsigned NOT NULL,
  `lastUpdate` datetime NOT NULL,
  PRIMARY KEY (`idMetaproduct`),
  KEY `lastUpdate` (`lastUpdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.meta_ProductPriceUpdates
CREATE TABLE IF NOT EXISTS `meta_ProductPriceUpdates` (
  `idProduct` int(11) unsigned NOT NULL,
  `lastUpdate` datetime NOT NULL,
  PRIMARY KEY (`idProduct`),
  KEY `lastUpdate` (`lastUpdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Article
CREATE TABLE IF NOT EXISTS `mkm_Article` (
  `idArticle` int(11) unsigned NOT NULL,
  `idProduct` int(11) unsigned DEFAULT NULL,
  `idSeller` int(11) unsigned DEFAULT NULL,
  `idLanguage` tinyint(3) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `condition` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `isFoil` tinyint(1) DEFAULT NULL,
  `isSigned` tinyint(1) DEFAULT NULL,
  `isAltered` tinyint(1) DEFAULT NULL,
  `isPlayset` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idArticle`),
  KEY `idProduct` (`idProduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_ArticleComment
CREATE TABLE IF NOT EXISTS `mkm_ArticleComment` (
  `idArticle` int(11) unsigned NOT NULL,
  `comment` text COLLATE utf8_bin,
  PRIMARY KEY (`idArticle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Category
CREATE TABLE IF NOT EXISTS `mkm_Category` (
  `idCategory` int(11) unsigned NOT NULL,
  `categoryName` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Category_x_Product
CREATE TABLE IF NOT EXISTS `mkm_Category_x_Product` (
  `idCategory` int(11) unsigned NOT NULL,
  `idProduct` int(11) unsigned NOT NULL,
  PRIMARY KEY (`idCategory`,`idProduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Language
CREATE TABLE IF NOT EXISTS `mkm_Language` (
  `idLanguage` tinyint(3) unsigned NOT NULL,
  `languageName` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idLanguage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Metaproduct
CREATE TABLE IF NOT EXISTS `mkm_Metaproduct` (
  `idMetaproduct` int(11) unsigned NOT NULL,
  PRIMARY KEY (`idMetaproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_MetaproductName
CREATE TABLE IF NOT EXISTS `mkm_MetaproductName` (
  `idMetaproduct` int(11) unsigned NOT NULL,
  `idLanguage` tinyint(3) unsigned NOT NULL,
  `metaproductName` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idMetaproduct`,`idLanguage`),
  KEY `metaproductName` (`metaproductName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_Product
CREATE TABLE IF NOT EXISTS `mkm_Product` (
  `idProduct` int(11) unsigned NOT NULL,
  `idMetaproduct` int(11) unsigned NOT NULL,
  `image` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `expansion` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `rarity` varchar(15) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idProduct`),
  KEY `idMetaproduct` (`idMetaproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_ProductName
CREATE TABLE IF NOT EXISTS `mkm_ProductName` (
  `idProduct` int(11) unsigned NOT NULL,
  `idLanguage` tinyint(3) unsigned NOT NULL,
  `productName` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`idProduct`,`idLanguage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.mkm_User
CREATE TABLE IF NOT EXISTS `mkm_User` (
  `idUser` int(11) unsigned NOT NULL,
  `username` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `country` varchar(5) COLLATE utf8_bin DEFAULT NULL,
  `isCommercial` tinyint(1) unsigned DEFAULT NULL,
  `riskGroup` tinyint(1) unsigned DEFAULT NULL,
  `reputation` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`idUser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.user_List
CREATE TABLE IF NOT EXISTS `user_List` (
  `idList` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idUser` int(10) unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`idList`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.user_List_x_Metaproduct
CREATE TABLE IF NOT EXISTS `user_List_x_Metaproduct` (
  `idList` int(10) unsigned NOT NULL,
  `idMetaproduct` int(10) unsigned NOT NULL,
  PRIMARY KEY (`idList`,`idMetaproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt


-- Exportiere Struktur von Tabelle mcalc.user_User
CREATE TABLE IF NOT EXISTS `user_User` (
  `idUser` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`idUser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Daten Export vom Benutzer nicht ausgewählt
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
