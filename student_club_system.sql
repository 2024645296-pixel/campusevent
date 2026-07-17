-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2026 at 02:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_club_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `event_name` varchar(150) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` enum('Upcoming','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `poster` varchar(255) DEFAULT NULL,
  `report_status` varchar(20) DEFAULT 'Pending',
  `report_remark` text DEFAULT NULL,
  `report_summary` text DEFAULT NULL,
  `club_name` varchar(100) DEFAULT NULL,
  `event_fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `proposal_id`, `event_name`, `event_date`, `event_time`, `location`, `description`, `budget`, `status`, `created_at`, `poster`, `report_status`, `report_remark`, `report_summary`, `club_name`, `event_fee`) VALUES
(2, 3, 'Blood Donation Campaign', '2026-06-25', '10:00:00', 'Student Centre', 'A collaboration with the National Blood Centre to collect blood donations from students and staff.', 300.00, 'Upcoming', '2026-06-20 10:17:17', '1783068369_Blood Donation Campaign.jpg', 'Pending', NULL, NULL, NULL, 0.00),
(4, 1, 'Tech Talk: AI & Future', '2026-07-15', '08:30:00', 'DKP Hall', 'This program will invite industry experts to share knowledge about AI trends, career opportunities, and future technologies.', 500.00, 'Upcoming', '2026-06-20 14:31:30', 'event_4_1783759368.jpg', 'Pending', NULL, NULL, 'IT Club', 0.00),
(5, 6, 'ROTU Run 2025', '2026-08-19', '07:30:00', 'UiTM Puncak Alam', 'The ROTU Run is a physical fitness programme organized to promote health, endurance, and teamwork among ROTU cadets. The event consists of a running activity conducted within a designated route under the supervision of officers and instructors. Besides improving physical fitness, the programme aims to strengthen discipline, resilience, leadership, and esprit de corps while encouraging an active and healthy lifestyle. It also provides an opportunity for cadets to build stronger relationships, enhance communication, and develop teamwork skills in a supportive military training environment.', 800.00, 'Upcoming', '2026-07-11 10:46:00', 'proposal_4_1783766686.jpg', 'Approved', 'Approved. The proposal is complete and meets the required requirements. The organizing committee may proceed with the programme according to the approved schedule, budget, and university guidelines.', 'The proposal for the ROTU Run Programme has been reviewed and approved by the administrator. The programme meets the required objectives, complies with the university\'s regulations, and demonstrates a well-planned schedule, budget, safety measures, and resource allocation. The event is expected to enhance cadets\' physical fitness, teamwork, discipline, and leadership while promoting a healthy lifestyle. The organizing committee is authorized to proceed with the programme according to the approved proposal and timeline.', 'PALAPES', 0.00),
(6, 8, 'Hiking Fun 2026', '2026-08-08', '06:30:00', 'Ipoh', '-', 500.00, 'Upcoming', '2026-07-16 21:08:41', 'proposal_4_1784236059_882cd04f.jpg', 'Pending', NULL, NULL, 'Sports Club', 75.00),
(7, 7, 'FUN RUN 5KM', '2026-08-08', '06:30:00', 'UiTM Puncak Alam', 'The Fun Run is a recreational running event organized to encourage a healthy and active lifestyle among students and the university community. The programme provides participants with an enjoyable opportunity to engage in physical activity while promoting fitness, teamwork, and social interaction. In addition to improving health awareness, the event aims to strengthen relationships among participants in a fun, safe, and positive environment.', 500.00, 'Upcoming', '2026-07-16 21:16:46', 'proposal_4_1784232139_e4d267c6.jpg', 'Pending', NULL, NULL, 'iREc', 10.00),
(9, 10, 'leadership camp 2026', '2026-08-13', '09:41:00', 'UiTM Puncak Alam', '-', 1500.00, 'Upcoming', '2026-07-16 21:42:42', 'proposal_4_1784238107_5cc107a5.jpg', 'Pending', NULL, NULL, 'Aims', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `program_proposals`
--

CREATE TABLE `program_proposals` (
  `proposal_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_name` varchar(150) NOT NULL,
  `club_name` varchar(100) NOT NULL,
  `person_in_charge` varchar(150) DEFAULT NULL,
  `objective` text NOT NULL,
  `description` text DEFAULT NULL,
  `proposal_date` date NOT NULL,
  `proposal_time` time NOT NULL,
  `location` varchar(150) NOT NULL,
  `expected_participants` int(11) DEFAULT NULL,
  `budget` decimal(10,2) NOT NULL,
  `event_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `admin_remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reject_reason` text DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `official_letter` varchar(255) DEFAULT NULL,
  `proposal_paper` varchar(255) DEFAULT NULL,
  `activity_form` varchar(255) DEFAULT NULL,
  `speaker_profile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_proposals`
--

INSERT INTO `program_proposals` (`proposal_id`, `user_id`, `program_name`, `club_name`, `person_in_charge`, `objective`, `description`, `proposal_date`, `proposal_time`, `location`, `expected_participants`, `budget`, `event_fee`, `status`, `admin_remark`, `created_at`, `reject_reason`, `poster`, `official_letter`, `proposal_paper`, `activity_form`, `speaker_profile`) VALUES
(1, 1, 'Tech Talk: AI & Future', 'IT Club', NULL, 'To increase awareness and knowledge about Artificial Intelligence among students.', 'This program will invite industry experts to share knowledge about AI trends, career opportunities, and future technologies.', '2026-07-15', '08:30:00', 'DKP Hall', NULL, 500.00, 0.00, 'Approved', NULL, '2026-06-20 10:01:17', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Sports Day 2026', 'Sports Club', NULL, 'To promote a healthy lifestyle and strengthen teamwork among students.', 'A sports event involving football, netball, badminton, and various recreational activities.', '2026-06-23', '08:00:00', 'Main Stadium', NULL, 1200.00, 0.00, 'Approved', NULL, '2026-06-20 10:02:45', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, 'Blood Donation Campaign', 'Red Crescent Club', NULL, 'To encourage students to participate in blood donation activities.', 'A collaboration with the National Blood Centre to collect blood donations from students and staff.', '2026-06-25', '10:00:00', 'Student Centre', NULL, 300.00, 0.00, 'Approved', NULL, '2026-06-20 10:03:46', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 4, 'ROTU Run 2025', 'PALAPES', 'Nur Aina Nabila', 'To improve physical fitness and endurance.\r\nTo strengthen teamwork and discipline among cadets.\r\nTo promote a healthy and active lifestyle.\r\nTo build mental resilience and leadership qualities.\r\nTo foster unity and esprit de corps within ROTU.', 'The ROTU Run is a physical fitness programme organized to promote health, endurance, and teamwork among ROTU cadets. The event consists of a running activity conducted within a designated route under the supervision of officers and instructors. Besides improving physical fitness, the programme aims to strengthen discipline, resilience, leadership, and esprit de corps while encouraging an active and healthy lifestyle. It also provides an opportunity for cadets to build stronger relationships, enhance communication, and develop teamwork skills in a supportive military training environment.', '2026-08-19', '07:30:00', 'UiTM Puncak Alam', 200, 800.00, 0.00, 'Approved', NULL, '2026-07-11 10:44:46', NULL, 'proposal_4_1783766686.jpg', NULL, NULL, NULL, NULL),
(7, 4, 'FUN RUN 5KM', 'iREc', 'Afza Aina', 'To promote a healthy lifestyle, encourage physical fitness, and strengthen friendship among participants through a fun and enjoyable running event.', 'The Fun Run is a recreational running event organized to encourage a healthy and active lifestyle among students and the university community. The programme provides participants with an enjoyable opportunity to engage in physical activity while promoting fitness, teamwork, and social interaction. In addition to improving health awareness, the event aims to strengthen relationships among participants in a fun, safe, and positive environment.', '2026-08-08', '06:30:00', 'UiTM Puncak Alam', 250, 500.00, 10.00, 'Approved', NULL, '2026-07-16 20:02:19', NULL, 'proposal_4_1784232139_e4d267c6.jpg', '', '', '', ''),
(8, 4, 'Hiking Fun 2026', 'Sports Club', 'Muhammad Iqbal', 'Hiking for fun with friends and create bonding', '-', '2026-08-08', '06:30:00', 'Ipoh', 50, 500.00, 75.00, 'Approved', NULL, '2026-07-16 21:07:39', NULL, 'proposal_4_1784236059_882cd04f.jpg', '', '', 'activity_form_4_1784236059_3e2b8a95.docx', ''),
(9, 4, 'FUN RUN 5KM', 'iREc', 'Afza Aina', '-', 'good for everyone', '2026-08-08', '06:30:00', 'UiTM Puncak Alam', 150, 500.00, 30.00, 'Approved', NULL, '2026-07-16 21:28:04', NULL, 'proposal_4_1784237284_6d95ac51.jpg', '', '', '', ''),
(10, 4, 'leadership camp 2026', 'Aims', 'Muhammad Iqbal', 'to build leadership', '-', '2026-08-13', '09:41:00', 'UiTM Puncak Alam', 60, 1500.00, 20.00, 'Approved', NULL, '2026-07-16 21:41:47', NULL, 'proposal_4_1784238107_5cc107a5.jpg', '', '', 'activity_form_4_1784238107_f86c8d34.docx', '');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_documents`
--

CREATE TABLE `proposal_documents` (
  `document_id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `matric_no` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `payment_status` enum('Pending','Paid') DEFAULT 'Pending',
  `register_date` datetime DEFAULT current_timestamp(),
  `attendance_status` enum('Registered','Attended','Absent') DEFAULT 'Registered',
  `certificate_status` enum('Not Generated','Generated') NOT NULL DEFAULT 'Not Generated',
  `certificate_number` varchar(100) DEFAULT NULL,
  `certificate_generated_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`registration_id`, `event_id`, `user_id`, `full_name`, `matric_no`, `email`, `phone`, `payment_status`, `register_date`, `attendance_status`, `certificate_status`, `certificate_number`, `certificate_generated_at`, `payment_method`, `receipt`) VALUES
(1, 2, 1, 'MUHAMMD SHAHRUL ABDULLAH', '2024655539', 'shah223@gmail.com', '011-32256831', 'Pending', '2026-06-20 18:20:14', 'Registered', 'Not Generated', NULL, NULL, NULL, NULL),
(2, 4, 2, 'NURKHAIRUNNISA HAYUSZAIMI', '2024654378', '2024645296@student.uitm.edu.my', '0355442000', 'Paid', '2026-07-03 18:29:28', 'Attended', 'Not Generated', NULL, NULL, 'Cash', ''),
(3, 5, 2, 'John Doe', 'A21EC1234', 'john@student.edu.my', '0112345678', 'Paid', '2026-07-11 20:31:47', 'Attended', 'Not Generated', NULL, NULL, 'Cash', 'receipt_2_5_1783773107.png'),
(4, 6, 2, 'John Doe', 'A21EC1234', 'john@student.edu.my', '0112345678', 'Paid', '2026-07-17 05:12:26', 'Registered', 'Not Generated', NULL, NULL, 'Free Event', ''),
(5, 7, 2, 'John Doe', 'A21EC1234', 'john@student.edu.my', '0112345678', '', '2026-07-17 05:44:56', 'Registered', 'Not Generated', NULL, NULL, 'Online Banking', 'receipt_2_7_1784238296.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `matric_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('Admin','Club Leader','Student') NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `matric_no`, `email`, `password`, `phone`, `role`, `faculty`, `created_at`) VALUES
(1, 'Admin User', 'ADMIN001', 'admin@gmail.com', '12345', '0111111111', 'Admin', 'Faculty of Information Management', '2026-06-20 09:34:15'),
(2, 'John Doe', 'A21EC1234', 'john@student.edu.my', '12345', '0112345678', 'Student', 'Information Management', '2026-06-20 10:21:36'),
(4, 'Nur Nisa', 'CLUB001', 'nisaa@student.edu.my', '12345', '0134567890', 'Club Leader', 'Information Management', '2026-06-20 10:21:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `program_proposals`
--
ALTER TABLE `program_proposals`
  ADD PRIMARY KEY (`proposal_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fk_proposal_documents` (`proposal_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matric_no` (`matric_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `program_proposals`
--
ALTER TABLE `program_proposals`
  MODIFY `proposal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `program_proposals` (`proposal_id`);

--
-- Constraints for table `program_proposals`
--
ALTER TABLE `program_proposals`
  ADD CONSTRAINT `program_proposals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD CONSTRAINT `fk_proposal_documents` FOREIGN KEY (`proposal_id`) REFERENCES `program_proposals` (`proposal_id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`),
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
