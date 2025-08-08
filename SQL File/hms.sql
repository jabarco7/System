-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 31 يوليو 2025 الساعة 23:23
-- إصدار الخادم: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hms`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updationDate` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `updationDate`) VALUES
(1, 'admin', '123456', '04-03-2024 11:42:05 AM');

-- --------------------------------------------------------

--
-- بنية الجدول `appointment`
--

CREATE TABLE `appointment` (
  `id` int(11) NOT NULL,
  `doctorSpecialization` varchar(255) DEFAULT NULL,
  `doctorId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `consultancyFees` int(11) DEFAULT NULL,
  `appointmentDate` varchar(255) DEFAULT NULL,
  `appointmentTime` varchar(255) DEFAULT NULL,
  `postingDate` timestamp NULL DEFAULT current_timestamp(),
  `userStatus` int(11) DEFAULT NULL,
  `doctorStatus` int(11) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `appointment`
--

INSERT INTO `appointment` (`id`, `doctorSpecialization`, `doctorId`, `userId`, `consultancyFees`, `appointmentDate`, `appointmentTime`, `postingDate`, `userStatus`, `doctorStatus`, `updationDate`) VALUES
(1, 'ENT', 1, 1, 500, '2024-05-30', '9:15 AM', '2024-05-15 03:42:11', 1, 1, NULL),
(2, 'Endocrinologists', 2, 2, 800, '2024-05-31', '2:45 PM', '2024-05-16 09:08:54', 1, 1, NULL),
(3, 'Obstetrics and Gynecology', 7, 6, 800, '2025-07-30', '3:15 AM', '2025-07-27 00:07:34', 1, 1, NULL),
(4, 'Obstetrics and Gynecology', 7, 6, 800, '2025-07-30', '3:15 AM', '2025-07-27 00:07:34', 1, 1, NULL),
(5, 'Orthopedics', 5, 8, 1200, '2025-07-29', '4:15 AM', '2025-07-27 01:07:20', 0, 1, '2025-07-27 01:07:50'),
(6, 'Orthopedics', 5, 8, 1200, '2025-07-30', '10:15 PM', '2025-07-27 19:14:47', 1, 1, NULL),
(7, 'Orthopedics', 8, 8, 500, '2025-07-31', '10:15 PM', '2025-07-27 19:16:34', 1, 0, '2025-07-27 19:17:51'),
(8, 'Orthopedics', 8, 8, 500, '2025-07-29', '3:15 AM', '2025-07-29 00:14:23', 1, 1, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `specilization` varchar(255) DEFAULT NULL,
  `doctorName` varchar(255) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `docFees` varchar(255) DEFAULT NULL,
  `contactno` bigint(11) DEFAULT NULL,
  `docEmail` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `doctors`
--

INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES
(1, 'ENT', 'Anuj kumar', 'A 123 XYZ Apartment Raj Nagar Ext Ghaziabad', '500', 142536250, 'anujk123@test.com', 'Test@123456\n', '2024-04-10 18:16:52', '2025-07-27 00:10:47'),
(2, 'Endocrinologists', 'Charu Dua', 'X 1212 ABC Apartment Laxmi Nagar New Delhi ', '800', 1231231230, 'charudua12@test.com', 'f925916e2754e5e03f75dd58a5733251', '2024-04-11 01:06:41', '2024-05-14 09:26:28'),
(4, 'Pediatrics', 'Priyanka Sinha', 'A 123 Xyz Aparmtnent Ghaziabad', '700', 74561235, 'p12@t.com', 'f925916e2754e5e03f75dd58a5733251', '2024-05-16 09:12:23', NULL),
(5, 'Orthopedics', 'Vipin Tayagi', 'Yasho Hospital New Delhi', '1200', 95214563210, 'vpint123@gmail.com', 'f925916e2754e5e03f75dd58a5733251', '2024-05-16 09:13:11', NULL),
(6, 'Internal Medicine', 'Dr Romil', 'Max Hospital Vaishali  GZB', '1500', 8563214751, 'drromil12@gmail.com', 'f925916e2754e5e03f75dd58a5733251', '2024-05-16 09:14:11', NULL),
(7, 'Obstetrics and Gynecology', 'Bhavya rathore', 'Shop 12 Indira Puram Ghaziabad', '800', 745621330, 'bhawya12@tt.com', 'f925916e2754e5e03f75dd58a5733251', '2024-05-16 09:15:18', NULL),
(8, 'Orthopedics', 'ش', 'ش', '500', 779047080, 'bdhbdw2@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '2025-07-27 00:18:00', NULL),
(9, 'Orthopedics', 'عمار', 'عمران', '2500', 773394994, 'amar@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '2025-07-28 21:06:53', NULL),
(10, 'Orthopedics', 'محمد', 'اليمن', '2500', 773394994, 'ala@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '2025-07-28 21:27:31', '2025-07-30 00:30:09');

-- --------------------------------------------------------

--
-- بنية الجدول `doctorslog`
--

CREATE TABLE `doctorslog` (
  `id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `doctorslog`
--

INSERT INTO `doctorslog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES
(1, 1, 'anujk123@test.com', 0x3a3a3100000000000000000000000000, '2024-05-16 05:19:33', NULL, 1),
(2, 1, 'anujk123@test.com', 0x3a3a3100000000000000000000000000, '2024-05-16 09:01:03', '16-05-2024 02:37:32 PM', 1),
(3, NULL, '102', 0x3a3a3100000000000000000000000000, '2025-07-25 22:24:08', NULL, 0),
(4, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:07:07', NULL, 0),
(5, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:07:16', NULL, 0),
(6, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:08:15', NULL, 0),
(7, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:19:36', NULL, 0),
(8, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:35:32', NULL, 0),
(9, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:36:08', NULL, 0),
(10, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:40:59', NULL, 0),
(11, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:04:11', NULL, 0),
(12, NULL, 'anujk123@test.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:11:27', NULL, 0),
(13, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:12:25', NULL, 0),
(14, NULL, 'bdhbdw2', 0x3a3a3100000000000000000000000000, '2025-07-27 00:18:32', NULL, 0),
(15, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:18:44', NULL, 1),
(16, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:35:05', NULL, 1),
(17, NULL, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 18:39:09', NULL, 0),
(18, NULL, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 18:39:31', NULL, 0),
(19, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 18:39:49', NULL, 1),
(20, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 19:16:45', NULL, 1),
(21, 9, 'amar@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 21:50:10', NULL, 1),
(22, NULL, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 21:50:59', NULL, 0),
(23, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 21:51:05', NULL, 1),
(24, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 22:30:24', '29-07-2025 06:13:33 AM', 1),
(25, NULL, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 00:43:44', NULL, 0),
(26, NULL, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 00:43:54', NULL, 0),
(27, NULL, 'admin', 0x3a3a3100000000000000000000000000, '2025-07-29 00:44:06', NULL, 0),
(28, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 00:44:19', '30-07-2025 04:25:13 AM', 1),
(29, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 22:55:22', NULL, 1),
(30, 8, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-31 21:11:06', '01-08-2025 02:46:41 AM', 1);

-- --------------------------------------------------------

--
-- بنية الجدول `doctorspecilization`
--

CREATE TABLE `doctorspecilization` (
  `id` int(11) NOT NULL,
  `specilization` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `doctorspecilization`
--

INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES
(1, 'Orthopedics', '2024-04-09 18:09:46', '2024-05-14 09:26:47'),
(2, 'Internal Medicine', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(3, 'Obstetrics and Gynecology', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(4, 'Dermatology', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(5, 'Pediatrics', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(6, 'Radiology', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(7, 'General Surgery', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(8, 'Ophthalmology', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(9, 'Anesthesia', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(10, 'Pathology', '2024-04-09 18:09:46', '2024-05-14 09:26:56'),
(11, '2', '2024-04-09 18:09:46', '2025-07-30 00:16:03');

-- --------------------------------------------------------

--
-- بنية الجدول `tblcontactus`
--

CREATE TABLE `tblcontactus` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contactno` bigint(12) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `AdminRemark` longtext DEFAULT NULL,
  `LastupdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `IsRead` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tblcontactus`
--

INSERT INTO `tblcontactus` (`id`, `fullname`, `email`, `contactno`, `message`, `PostingDate`, `AdminRemark`, `LastupdationDate`, `IsRead`) VALUES
(1, 'Anuj kumar', 'anujk30@test.com', 1425362514, 'This is for testing purposes.   This is for testing purposes.This is for testing purposes.This is for testing purposes.This is for testing purposes.This is for testing purposes.This is for testing purposes.This is for testing purposes.This is for testing purposes.', '2024-04-20 16:52:03', 'ش', '2025-07-29 23:52:27', 1),
(2, 'Anuj kumar', 'ak@gmail.com', 1111122233, 'This is for testing', '2024-04-23 13:13:41', 'Contact the patient', '2024-04-27 13:13:57', 1);

-- --------------------------------------------------------

--
-- بنية الجدول `tblmedicalhistory`
--

CREATE TABLE `tblmedicalhistory` (
  `ID` int(10) NOT NULL,
  `PatientID` int(10) DEFAULT NULL,
  `BloodPressure` varchar(200) DEFAULT NULL,
  `BloodSugar` varchar(200) NOT NULL,
  `Weight` varchar(100) DEFAULT NULL,
  `Temperature` varchar(200) DEFAULT NULL,
  `MedicalPres` longtext DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tblmedicalhistory`
--

INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES
(1, 2, '80/120', '110', '85', '97', 'Dolo,\r\nLevocit 5mg', '2024-05-16 09:07:16'),
(2, 3, '100', '150', '80', '20', 'بندول', '2025-07-28 23:50:56'),
(3, 3, '100', '150', '80', '20', 'لا شيء', '2025-07-28 23:51:51');

-- --------------------------------------------------------

--
-- بنية الجدول `tblpage`
--

CREATE TABLE `tblpage` (
  `ID` int(10) NOT NULL,
  `PageType` varchar(200) DEFAULT NULL,
  `PageTitle` varchar(200) DEFAULT NULL,
  `PageDescription` mediumtext DEFAULT NULL,
  `Email` varchar(120) DEFAULT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `UpdationDate` timestamp NULL DEFAULT current_timestamp(),
  `OpenningTime` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tblpage`
--

INSERT INTO `tblpage` (`ID`, `PageType`, `PageTitle`, `PageDescription`, `Email`, `MobileNumber`, `UpdationDate`, `OpenningTime`) VALUES
(1, 'aboutus', 'About Us', '<ul style=\"padding: 0px; margin-right: 0px; margin-bottom: 1.313em; margin-left: 1.655em;\" times=\"\" new=\"\" roman\";=\"\" font-size:=\"\" 14px;=\"\" text-align:=\"\" center;=\"\" background-color:=\"\" rgb(255,=\"\" 246,=\"\" 246);\"=\"\"><li style=\"text-align: left;\"><br></li></ul>', NULL, NULL, '2020-05-20 07:21:52', NULL),
(2, 'contactus', 'Contact Details', 'D-204, Hole Town South West, Delhi-110096,India', 'info@gmail.com', 1122334455, '2020-05-20 07:24:07', '9 am To 8 Pm');

-- --------------------------------------------------------

--
-- بنية الجدول `tblpatient`
--

CREATE TABLE `tblpatient` (
  `ID` int(10) NOT NULL,
  `Docid` int(10) DEFAULT NULL,
  `PatientName` varchar(200) DEFAULT NULL,
  `PatientContno` bigint(10) DEFAULT NULL,
  `PatientEmail` varchar(200) DEFAULT NULL,
  `PatientGender` varchar(50) DEFAULT NULL,
  `PatientAdd` longtext DEFAULT NULL,
  `PatientAge` int(10) DEFAULT NULL,
  `PatientMedhis` longtext DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tblpatient`
--

INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES
(1, 1, 'Rahul Singyh', 452463210, 'rahul12@gmail.com', 'male', 'NA', 32, 'Fever, Cold', '2024-05-16 05:23:35', NULL),
(2, 1, 'Amit', 4545454545, 'amitk@gmail.com', 'male', 'NA', 45, 'Fever', '2024-05-16 09:01:26', NULL),
(3, 8, 's', 1, 'bdhhbdw2@gmail.com', 'Male', 's', 24, 's', '2025-07-27 00:36:26', '2025-07-27 00:38:47'),
(4, 8, 'عبود', 123, 'amar@gmail.com', 'ذكر', 'عمران', 24, '2', '2025-07-29 00:16:31', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `userlog`
--

CREATE TABLE `userlog` (
  `id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `userlog`
--

INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES
(1, 1, 'johndoe12@test.com', 0x3a3a3100000000000000000000000000, '2024-05-15 03:41:48', NULL, 1),
(2, 2, 'amitk@gmail.com', 0x3a3a3100000000000000000000000000, '2024-05-16 09:08:06', '16-05-2024 02:41:06 PM', 1),
(3, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 00:03:38', NULL, 1),
(4, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 00:12:17', NULL, 1),
(5, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 20:43:36', NULL, 1),
(6, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 20:53:41', NULL, 1),
(7, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 20:53:46', '27-07-2025 02:39:28 AM', 1),
(8, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 21:15:38', '27-07-2025 02:59:04 AM', 1),
(9, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 21:29:13', NULL, 1),
(10, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:14:49', NULL, 1),
(11, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:15:52', NULL, 1),
(12, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:17:06', NULL, 1),
(13, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:18:51', NULL, 1),
(14, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:18:55', NULL, 1),
(15, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:18:58', NULL, 1),
(16, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:20:53', '27-07-2025 04:50:57 AM', 1),
(17, 3, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:29:06', NULL, 1),
(18, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:31:28', NULL, 0),
(19, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-26 23:31:36', NULL, 0),
(20, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:05:49', NULL, 0),
(21, 6, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:06:57', NULL, 1),
(22, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:33:26', NULL, 0),
(23, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:33:33', NULL, 0),
(24, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:33:45', NULL, 0),
(25, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:33:56', NULL, 0),
(26, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:34:01', NULL, 0),
(27, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:34:39', NULL, 0),
(28, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:39:43', NULL, 0),
(29, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 00:41:56', NULL, 1),
(30, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:21:53', NULL, 0),
(31, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:22:08', NULL, 1),
(32, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:23:40', NULL, 1),
(33, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:23:46', NULL, 1),
(34, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:24:09', NULL, 1),
(35, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 01:24:21', NULL, 1),
(36, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 18:41:54', NULL, 0),
(37, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 18:43:18', NULL, 1),
(38, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 19:13:04', NULL, 1),
(39, NULL, 'admin@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 22:07:07', NULL, 0),
(40, NULL, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 22:08:06', NULL, 0),
(41, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 22:08:11', NULL, 1),
(42, 9, 'b@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-27 23:32:16', NULL, 1),
(43, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 00:41:24', NULL, 1),
(44, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-28 21:54:34', NULL, 1),
(45, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 21:59:48', NULL, 1),
(46, 8, 'bdbd@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-29 21:59:52', NULL, 1),
(47, NULL, 'bdhbdw2@gmail.com', 0x3a3a3100000000000000000000000000, '2025-07-31 21:17:22', NULL, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `regDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `regDate`, `updationDate`) VALUES
(1, 'John Doe', 'A 123 ABC Apartment GZB 201017', 'Ghaziabad', 'male', 'johndoe12@test.com', 'f925916e2754e5e03f75dd58a5733251', '2024-04-20 12:13:56', '2024-05-14 09:28:15'),
(2, 'Amit kumar', 'new Delhi india', 'New Delhi', 'male', 'amitk@gmail.com', 'f925916e2754e5e03f75dd58a5733251', '2024-04-21 13:15:32', '2024-05-14 09:28:23'),
(6, 'osa', '??????', '?????', 'female', 'admin@gmail.com', 'Test@123456', '2025-07-27 00:06:45', NULL),
(7, 'b', 'b', 'b', 'female', 'bdhbdw2@gmail.com', '123456', '2025-07-27 00:06:45', NULL),
(8, 'عبود', 'حجه', 'حجه', 'ذكر', 'bdbd@gmail.com', '25f9e794323b453885f5181f1b624d0b', '2025-07-27 00:41:38', '2025-07-28 21:55:11');

--

-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctorslog`
--
ALTER TABLE `doctorslog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctorspecilization`
--
ALTER TABLE `doctorspecilization`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcontactus`
--
ALTER TABLE `tblcontactus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblmedicalhistory`
--
ALTER TABLE `tblmedicalhistory`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblpage`
--
ALTER TABLE `tblpage`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblpatient`
--
ALTER TABLE `tblpatient`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `userlog`
--
ALTER TABLE `userlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctorslog`
--
ALTER TABLE `doctorslog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `doctorspecilization`
--
ALTER TABLE `doctorspecilization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tblcontactus`
--
ALTER TABLE `tblcontactus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblmedicalhistory`
--
ALTER TABLE `tblmedicalhistory`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblpage`
--
ALTER TABLE `tblpage`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblpatient`
--
ALTER TABLE `tblpatient`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `userlog`
--
ALTER TABLE `userlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
