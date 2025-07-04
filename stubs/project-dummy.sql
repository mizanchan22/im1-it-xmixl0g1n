-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table project.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(25) NOT NULL,
  `user_nok` varchar(25) NOT NULL,
  `user_ptj` varchar(10) DEFAULT NULL,
  `user_stesen` varchar(10) DEFAULT NULL,
  `user_kodprogram` varchar(10) DEFAULT NULL,
  `user_password` varchar(255) DEFAULT NULL,
  `user_salt` varchar(25) DEFAULT NULL,
  `user_nama` varchar(200) DEFAULT NULL,
  `user_jawatan` varchar(255) DEFAULT NULL,
  `user_emel` varchar(255) DEFAULT NULL,
  `user_telpejabat` varchar(25) DEFAULT NULL,
  `user_telbimbit` varchar(25) DEFAULT NULL,
  `user_role` varchar(25) DEFAULT NULL,
  `user_kontrak` varchar(25) DEFAULT NULL,
  `user_status` varchar(15) DEFAULT NULL COMMENT 'AKTIF\r\nTIDAK AKTIF',
  `user_signature` varchar(255) DEFAULT NULL,
  `user_date_created` datetime DEFAULT NULL,
  `user_last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`user_id`,`user_nok`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=latin1 COMMENT='Senarai pengguna (pentadbir HQ, pegawai negeri & pentadbir teknikal)';

-- Dumping data for table project.users: ~1 rows (approximately)
INSERT INTO `users` (`id`, `user_id`, `user_nok`, `user_ptj`, `user_stesen`, `user_kodprogram`, `user_password`, `user_salt`, `user_nama`, `user_jawatan`, `user_emel`, `user_telpejabat`, `user_telbimbit`, `user_role`, `user_kontrak`, `user_status`, `user_signature`, `user_date_created`, `user_last_login`) VALUES
	(1, '000000000000', '00000', '0026', '1001', 'IM1', '701d8d7795931c2bd79ca7512aa8381f', '9d7408', 'testing', 'pptm', '', '', '', 'PENTADBIR', 'N', 'AKTIF', '', '0000-00-00 00:00:00', '2025-07-04 07:27:04');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
