-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql102.infinityfree.com
-- Generation Time: Mar 17, 2026 at 02:15 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41335992_lanbridge_kpi`
--

-- --------------------------------------------------------

--
-- Table structure for table `acad_attendance_records`
--

CREATE TABLE `acad_attendance_records` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_attendance_sessions`
--

CREATE TABLE `acad_attendance_sessions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `total_students` int(11) DEFAULT 0,
  `present_count` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_calendar_events`
--

CREATE TABLE `acad_calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('academic','exam','registration','holiday','graduation','other') NOT NULL DEFAULT 'academic',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `audience` enum('all','staff','students','management') NOT NULL DEFAULT 'all',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_courses`
--

CREATE TABLE `acad_courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `credits` tinyint(4) DEFAULT 3,
  `year_of_study` tinyint(4) DEFAULT 1,
  `semester` tinyint(4) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_dept_reports`
--

CREATE TABLE `acad_dept_reports` (
  `id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `report_month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `total_programmes` int(11) DEFAULT 0,
  `total_courses` int(11) DEFAULT 0,
  `total_staff` int(11) DEFAULT 0,
  `pending_approvals` int(11) DEFAULT 0,
  `exams_conducted` int(11) DEFAULT 0,
  `pending_marks` int(11) DEFAULT 0,
  `at_risk_students` int(11) DEFAULT 0,
  `avg_attendance_rate` decimal(5,2) DEFAULT NULL,
  `syllabus_coverage` decimal(5,2) DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `action_plans` text DEFAULT NULL,
  `training_needs` text DEFAULT NULL,
  `upcoming_events` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','acknowledged') NOT NULL DEFAULT 'draft',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_exam_schedule`
--

CREATE TABLE `acad_exam_schedule` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` enum('mid_term','final','supplementary','practical') NOT NULL DEFAULT 'final',
  `exam_date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `invigilator_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','postponed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_graduation_clearance`
--

CREATE TABLE `acad_graduation_clearance` (
  `id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `graduation_year` year(4) NOT NULL,
  `library_cleared` tinyint(1) DEFAULT 0,
  `finance_cleared` tinyint(1) DEFAULT 0,
  `academic_cleared` tinyint(1) DEFAULT 0,
  `it_cleared` tinyint(1) DEFAULT 0,
  `status` enum('pending','in_progress','cleared','blocked') NOT NULL DEFAULT 'pending',
  `cleared_by` int(11) DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_lecturer_assignments`
--

CREATE TABLE `acad_lecturer_assignments` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `self_assigned` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_by` int(11) DEFAULT NULL,
  `semester` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_marks`
--

CREATE TABLE `acad_marks` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL COMMENT 'Student ID / registration number',
  `student_name` varchar(150) NOT NULL,
  `exam_type` enum('mid_term','final','supplementary','practical','coursework') NOT NULL DEFAULT 'final',
  `marks_obtained` decimal(6,2) DEFAULT NULL,
  `total_marks` decimal(6,2) NOT NULL DEFAULT 100.00,
  `grade` varchar(5) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `moderation_status` enum('pending','moderated','approved','rejected') NOT NULL DEFAULT 'pending',
  `moderated_by` int(11) DEFAULT NULL,
  `moderation_note` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `moderated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_memos`
--

CREATE TABLE `acad_memos` (
  `id` int(11) NOT NULL,
  `sent_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `priority` enum('normal','urgent','confidential') NOT NULL DEFAULT 'normal',
  `audience` enum('all','specific') NOT NULL DEFAULT 'all',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_memo_recipients`
--

CREATE TABLE `acad_memo_recipients` (
  `id` int(11) NOT NULL,
  `memo_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_notices`
--

CREATE TABLE `acad_notices` (
  `id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `category` enum('general','academic','exam','meeting','deadline','event','urgent') NOT NULL DEFAULT 'general',
  `expires_on` date DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_programmes`
--

CREATE TABLE `acad_programmes` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `duration_years` tinyint(4) DEFAULT 3,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_research_log`
--

CREATE TABLE `acad_research_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `type` enum('journal_paper','conference_paper','book','book_chapter','thesis_supervision','conference_attendance','workshop','grant','other') NOT NULL DEFAULT 'journal_paper',
  `title` varchar(500) NOT NULL,
  `authors` varchar(500) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `journal_name` varchar(255) DEFAULT NULL,
  `conference_name` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `status` enum('submitted','under_review','accepted','published','rejected') NOT NULL DEFAULT 'published',
  `is_peer_reviewed` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_student_progression`
--

CREATE TABLE `acad_student_progression` (
  `id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `current_year` tinyint(4) NOT NULL DEFAULT 1,
  `current_semester` tinyint(4) NOT NULL DEFAULT 1,
  `gpa` decimal(4,2) DEFAULT NULL,
  `status` enum('active','probation','suspended','withdrawn','completed') NOT NULL DEFAULT 'active',
  `risk_flag` enum('none','low','medium','high') NOT NULL DEFAULT 'none',
  `risk_reason` text DEFAULT NULL,
  `last_updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_supp_applications`
--

CREATE TABLE `acad_supp_applications` (
  `id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `course_id` int(11) NOT NULL,
  `original_score` decimal(6,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `supporting_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','scheduled','completed') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `exam_time` time DEFAULT NULL,
  `exam_venue` varchar(100) DEFAULT NULL,
  `supp_score` decimal(6,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_syllabus_topics`
--

CREATE TABLE `acad_syllabus_topics` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `topic_number` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `week_target` tinyint(4) DEFAULT NULL COMMENT 'Target week number to complete by',
  `status` enum('not_started','in_progress','completed','skipped') NOT NULL DEFAULT 'not_started',
  `completed_at` date DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acad_timetable`
--

CREATE TABLE `acad_timetable` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '1=Mon, 5=Fri',
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `semester` tinyint(4) DEFAULT 1,
  `year` year(4) NOT NULL,
  `entry_status` varchar(20) NOT NULL DEFAULT 'approved',
  `created_by` int(11) DEFAULT NULL,
  `reject_note` varchar(500) DEFAULT NULL,
  `custom_course_name` varchar(200) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accreditation_records`
--

CREATE TABLE `accreditation_records` (
  `id` int(11) NOT NULL,
  `authority_name` varchar(200) NOT NULL,
  `submission_type` varchar(100) DEFAULT NULL,
  `submission_deadline` date DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Pending',
  `document_path` varchar(300) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `programme` varchar(150) DEFAULT NULL,
  `intake_year` year(4) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'submitted',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_applications`
--

CREATE TABLE `adm_applications` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `nationality` varchar(80) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `programme_applied` varchar(150) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `intake_year` year(4) NOT NULL DEFAULT year(curdate()),
  `intake_semester` tinyint(4) NOT NULL DEFAULT 1,
  `qualification` varchar(150) DEFAULT NULL COMMENT 'Highest qualification held',
  `school_attended` varchar(200) DEFAULT NULL,
  `grade_obtained` varchar(50) DEFAULT NULL,
  `stage` enum('submitted','document_check','scoring','interview','offer_made','enrolled','rejected','withdrawn','deferred') NOT NULL DEFAULT 'submitted',
  `assigned_to` int(11) DEFAULT NULL,
  `source` enum('walk_in','online','referral','agent','school_visit','other') DEFAULT 'online',
  `referral_name` varchar(150) DEFAULT NULL,
  `special_needs` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adm_applications`
--

INSERT INTO `adm_applications` (`id`, `ref_no`, `applicant_name`, `email`, `phone`, `date_of_birth`, `gender`, `national_id`, `nationality`, `address`, `programme_applied`, `programme_id`, `intake_year`, `intake_semester`, `qualification`, `school_attended`, `grade_obtained`, `stage`, `assigned_to`, `source`, `referral_name`, `special_needs`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'APP-20260316-91A318', 'BLESSING KABWE', '', '0776609139', '2003-01-23', 'female', '676598/10/1', 'Zambian', 'LUSAKA', 'REGISTERD NURSING', NULL, 2026, 1, 'GRADE TWELVE CERTIFICATE', 'LANBRIDGECOLLEGE', '5 CREDIT INCULUDING MATHS AND ENGLISH', 'enrolled', 24, 'online', 'BLESSING', NULL, '', 21, '2026-03-16 04:55:04', '2026-03-16 04:55:04'),
(2, 'APP-20260316-9791FE', 'KASEMPA PATIENCE', 'patiencekasempa@728.com', '0770041807', '2005-09-11', 'female', '973201/10/1', 'Zambian', 'LUSAKAP', 'REGISTERD NURSING', NULL, 2026, 1, 'GRADE TWELVE CERTIFICATE', 'LANBRIDGECOLLEGE', '5 CREDIT INCULUDING MATHS AND ENGLISH', 'enrolled', 24, 'online', 'BLESSING', NULL, '', 21, '2026-03-16 05:33:29', '2026-03-16 05:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `adm_documents`
--

CREATE TABLE `adm_documents` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `doc_type` enum('national_id','birth_certificate','grade_12_results','transcript','reference_letter','medical_certificate','passport_photo','other') NOT NULL DEFAULT 'other',
  `filename` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected','missing') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_enrollments`
--

CREATE TABLE `adm_enrollments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `enrollment_date` date NOT NULL,
  `programme` varchar(150) DEFAULT NULL,
  `intake_year` year(4) DEFAULT NULL,
  `intake_semester` tinyint(4) DEFAULT NULL,
  `fee_structure` varchar(100) DEFAULT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `registration_fee_paid` tinyint(1) DEFAULT 0,
  `status` enum('active','deferred','withdrawn','completed') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adm_enrollments`
--

INSERT INTO `adm_enrollments` (`id`, `application_id`, `student_ref`, `enrollment_date`, `programme`, `intake_year`, `intake_semester`, `fee_structure`, `scholarship_id`, `registration_fee_paid`, `status`, `notes`, `enrolled_by`, `created_at`) VALUES
(1, 2, 'STU-20260316-B89AE6', '2026-03-16', 'REGISTERD NURSING', 2026, 1, 'STANDARD TUITION FEE', NULL, 1, 'active', 'ADIMITED FOR JUNE 2026 INTAKE', 21, '2026-03-16 05:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `adm_interviews`
--

CREATE TABLE `adm_interviews` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `venue` varchar(150) DEFAULT NULL,
  `panel_member_ids` text DEFAULT NULL COMMENT 'JSON array of user IDs on the interview panel',
  `interview_score` decimal(5,2) DEFAULT NULL,
  `outcome` enum('pending','pass','fail','no_show','rescheduled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `scheduled_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_offers`
--

CREATE TABLE `adm_offers` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `ref_no` varchar(40) NOT NULL,
  `offer_date` date NOT NULL,
  `programme` varchar(150) DEFAULT NULL,
  `intake_year` year(4) DEFAULT NULL,
  `intake_semester` tinyint(4) DEFAULT NULL,
  `conditions` text DEFAULT NULL COMMENT 'Any conditions attached to the offer',
  `validity_date` date DEFAULT NULL,
  `status` enum('draft','sent','accepted','declined','expired','revoked') NOT NULL DEFAULT 'draft',
  `sent_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `response_note` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_scholarships`
--

CREATE TABLE `adm_scholarships` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('full','partial','merit','bursary','government','other') NOT NULL DEFAULT 'partial',
  `value_zmw` decimal(12,2) DEFAULT NULL,
  `coverage_pct` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of fees covered',
  `available_slots` int(11) NOT NULL DEFAULT 1,
  `filled_slots` int(11) NOT NULL DEFAULT 0,
  `academic_year` year(4) DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_scores`
--

CREATE TABLE `adm_scores` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `scored_by` int(11) DEFAULT NULL,
  `scored_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adm_scoring_criteria`
--

CREATE TABLE `adm_scoring_criteria` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Multiplier for weighted total',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adm_scoring_criteria`
--

INSERT INTO `adm_scoring_criteria` (`id`, `name`, `description`, `max_score`, `weight`, `is_active`, `sort_order`) VALUES
(1, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(2, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(3, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(4, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(5, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(6, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(7, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(8, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(9, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(10, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(11, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(12, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(13, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(14, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(15, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(16, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(17, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(18, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(19, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(20, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(21, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(22, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(23, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(24, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(25, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(26, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(27, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(28, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(29, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(30, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(31, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(32, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(33, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(34, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(35, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(36, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(37, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(38, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(39, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(40, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(41, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(42, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(43, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(44, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(45, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(46, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(47, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(48, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(49, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(50, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(51, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(52, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(53, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(54, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(55, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(56, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(57, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(58, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(59, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(60, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(61, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(62, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(63, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(64, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(65, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(66, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(67, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(68, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(69, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(70, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(71, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(72, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(73, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(74, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(75, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(76, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(77, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(78, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(79, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(80, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(81, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(82, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(83, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(84, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(85, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(86, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(87, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(88, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(89, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(90, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(91, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(92, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(93, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(94, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(95, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(96, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(97, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(98, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(99, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(100, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(101, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(102, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(103, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(104, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(105, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(106, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(107, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(108, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(109, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(110, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(111, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(112, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(113, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(114, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(115, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(116, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(117, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(118, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(119, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(120, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(121, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(122, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(123, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(124, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(125, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(126, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(127, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(128, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(129, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(130, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(131, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(132, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(133, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(134, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(135, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(136, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(137, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(138, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(139, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(140, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(141, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(142, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(143, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(144, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(145, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(146, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(147, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(148, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(149, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(150, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(151, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(152, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(153, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(154, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(155, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(156, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(157, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(158, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(159, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(160, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(161, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(162, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(163, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(164, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(165, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(166, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(167, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(168, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(169, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(170, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(171, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(172, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(173, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(174, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(175, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(176, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(177, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(178, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(179, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(180, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(181, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(182, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(183, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(184, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(185, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(186, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(187, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(188, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(189, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(190, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(191, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(192, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(193, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(194, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(195, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(196, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(197, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(198, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(199, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(200, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(201, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(202, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(203, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(204, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(205, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(206, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(207, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(208, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(209, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(210, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(211, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(212, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(213, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(214, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(215, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(216, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(217, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(218, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(219, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(220, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(221, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(222, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(223, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(224, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(225, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(226, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(227, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(228, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(229, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(230, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(231, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(232, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(233, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(234, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(235, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(236, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(237, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(238, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(239, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(240, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(241, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(242, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(243, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(244, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(245, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(246, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(247, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(248, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(249, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(250, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(251, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(252, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(253, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(254, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(255, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(256, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(257, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(258, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(259, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(260, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(261, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(262, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(263, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(264, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(265, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(266, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(267, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(268, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(269, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(270, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(271, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(272, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(273, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(274, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(275, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(276, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(277, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(278, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(279, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(280, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(281, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(282, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(283, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(284, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(285, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(286, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(287, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(288, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(289, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(290, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(291, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(292, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(293, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(294, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(295, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(296, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(297, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(298, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(299, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(300, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(301, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(302, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(303, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(304, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(305, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(306, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(307, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(308, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(309, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(310, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(311, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(312, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(313, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(314, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(315, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(316, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(317, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(318, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(319, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(320, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(321, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(322, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(323, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(324, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(325, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(326, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(327, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(328, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(329, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(330, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(331, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(332, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(333, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(334, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(335, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(336, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(337, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(338, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(339, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(340, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(341, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(342, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(343, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(344, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(345, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(346, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(347, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(348, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(349, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(350, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(351, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(352, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(353, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(354, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(355, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(356, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(357, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(358, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(359, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(360, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(361, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(362, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(363, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(364, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(365, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(366, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(367, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(368, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(369, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(370, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(371, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(372, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(373, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(374, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(375, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(376, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4),
(377, 'Academic Results', 'Grade 12 or equivalent results', '100.00', '0.50', 1, 1),
(378, 'Entry Examination', 'Institutional entry test score', '100.00', '0.30', 1, 2),
(379, 'Recommendation Letter', 'Quality of reference letters', '10.00', '0.10', 1, 3),
(380, 'Personal Statement', 'Motivation letter / personal statement', '10.00', '0.10', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `ai_insights`
--

CREATE TABLE `ai_insights` (
  `id` int(11) NOT NULL,
  `insight_type` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `confidence_pct` tinyint(4) DEFAULT 0,
  `source_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_engagement`
--

CREATE TABLE `alumni_engagement` (
  `id` int(11) NOT NULL,
  `alumni_name` varchar(150) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `engagement_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_contact_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `audience` enum('all','department') DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `priority` varchar(20) DEFAULT 'normal',
  `category` varchar(40) DEFAULT 'general',
  `reactions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `posted_by`, `title`, `body`, `audience`, `is_active`, `is_pinned`, `created_at`, `updated_at`, `priority`, `category`, `reactions`) VALUES
(1, 13, 'Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'all', 1, 0, '2026-03-11 18:29:42', '2026-03-16 14:17:29', 'normal', 'general', '{\"\\u2764\\ufe0f\":[3,9,36,21],\"\\ud83d\\ude4f\":[36]}');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_attachments`
--

CREATE TABLE `announcement_attachments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(120) DEFAULT '',
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_departments`
--

CREATE TABLE `announcement_departments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_views`
--

INSERT INTO `announcement_views` (`id`, `announcement_id`, `user_id`, `viewed_at`) VALUES
(1, 1, 3, '2026-03-11 11:33:54'),
(3, 1, 28, '2026-03-11 11:40:51'),
(5, 1, 36, '2026-03-11 23:13:10'),
(8, 1, 9, '2026-03-12 01:42:05'),
(10, 1, 14, '2026-03-12 02:22:40'),
(12, 1, 7, '2026-03-12 13:50:05'),
(18, 1, 21, '2026-03-16 07:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(80) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(3, 3, 'REPORT_APPROVED', 'reports', 1, NULL, NULL, '127.0.0.1', NULL, '2026-03-07 21:03:17'),
(8, 3, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 21:53:27'),
(9, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 21:58:12'),
(12, 2, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 21:59:48'),
(13, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 22:01:58'),
(20, 3, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 23:22:22'),
(21, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 23:23:13'),
(28, 7, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 23:58:59'),
(29, 7, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-07 23:59:15'),
(30, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:01:31'),
(31, 7, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:03:28'),
(32, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:08:47'),
(33, 7, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:09:10'),
(34, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:10:52'),
(35, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:11:05'),
(39, 7, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:21:10'),
(40, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:21:49'),
(41, 7, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:22:26'),
(42, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 00:22:31'),
(47, 2, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 05:15:52'),
(48, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 10:43:57'),
(49, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 11:00:52'),
(50, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 11:20:56'),
(51, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 12:03:59'),
(52, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 12:34:13'),
(53, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 12:35:02'),
(54, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 12:58:48'),
(55, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 13:08:58'),
(56, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 13:09:11'),
(57, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 13:27:45'),
(59, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 13:31:02'),
(61, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 13:31:47'),
(62, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 13:47:37'),
(63, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 13:55:01'),
(64, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:08:52'),
(65, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:09:39'),
(66, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:10:07'),
(67, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:10:44'),
(70, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 14:19:56'),
(71, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:34:48'),
(72, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:40:47'),
(73, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 14:53:41'),
(74, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 14:54:58'),
(75, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 14:55:04'),
(76, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 14:55:22'),
(81, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 14:58:04'),
(82, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 15:01:34'),
(83, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 15:01:57'),
(84, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 15:02:07'),
(86, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 15:04:02'),
(89, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 15:38:10'),
(91, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 16:44:15'),
(92, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 17:06:18'),
(94, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:09:42'),
(95, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:09:51'),
(96, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:10:23'),
(97, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:15:31'),
(98, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:17:00'),
(99, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:17:15'),
(100, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:20:28'),
(104, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:31:22'),
(105, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:31:56'),
(106, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:32:32'),
(107, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:34:14'),
(110, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:37:06'),
(111, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:37:18'),
(113, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:37:53'),
(114, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:38:16'),
(115, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:38:31'),
(116, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:38:41'),
(117, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:39:14'),
(118, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:39:23'),
(119, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:39:33'),
(120, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 17:42:16'),
(121, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:09:27'),
(122, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:09:45'),
(123, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:10:06'),
(124, 9, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:10:57'),
(125, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 18:12:30'),
(126, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:57:34'),
(127, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:58:28'),
(128, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 18:59:54'),
(129, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 19:00:18'),
(130, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 19:00:33'),
(131, 14, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-08 19:01:10'),
(132, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 23:55:40'),
(133, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-08 23:57:14'),
(134, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:00:00'),
(135, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:00:04'),
(136, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:00:36'),
(137, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:00:41'),
(138, 14, 'USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:13:13'),
(139, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 00:13:46'),
(142, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:33:59'),
(145, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 00:46:23'),
(147, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 00:55:06'),
(148, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 02:25:41'),
(149, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 02:26:24'),
(150, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 02:33:52'),
(151, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 02:33:57'),
(152, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 03:57:25'),
(153, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 03:57:30'),
(154, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 03:58:21'),
(155, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 03:58:42'),
(156, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 03:59:26'),
(157, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 03:59:56'),
(158, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:08:47'),
(159, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:09:05'),
(160, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:43:29'),
(161, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:43:41'),
(162, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:46:15'),
(163, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 04:46:25'),
(164, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:56:10'),
(165, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:56:13'),
(166, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:57:55'),
(167, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:58:05'),
(168, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:59:08'),
(169, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 05:59:38'),
(170, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:00:35'),
(171, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:00:46'),
(172, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 06:01:16'),
(173, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 06:01:21'),
(174, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:36:14'),
(177, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:39:05'),
(178, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:40:43'),
(179, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 06:40:54'),
(180, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 07:03:59'),
(181, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 07:04:04'),
(182, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 07:13:53'),
(183, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 07:13:57'),
(184, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 07:32:40'),
(185, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.38', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 07:32:53'),
(186, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.204', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 14:36:24'),
(187, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.204', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-09 16:12:17'),
(188, 13, 'LOGIN', NULL, NULL, NULL, NULL, '154.117.182.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-09 18:21:34'),
(189, 13, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '154.117.182.250', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-09 18:22:24'),
(190, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 18:40:33'),
(191, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 18:52:24'),
(192, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 19:08:31'),
(193, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-09 19:08:37'),
(194, 13, 'PASSWORD_RESET_REQUEST', NULL, NULL, NULL, NULL, '154.117.182.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 20:41:06'),
(195, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 03:56:29'),
(196, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 03:56:48'),
(197, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 03:58:07'),
(198, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-10 03:58:39'),
(201, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:00:35'),
(204, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:21:51'),
(205, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:22:45'),
(209, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:24:24'),
(212, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:33:17'),
(213, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:34:41'),
(217, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 04:36:26'),
(219, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 04:41:21'),
(220, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 04:59:42'),
(221, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 04:59:56'),
(222, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 05:00:22'),
(223, 7, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-10 06:11:11'),
(224, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:22:14'),
(225, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:22:18'),
(226, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:22:33'),
(227, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:52:09'),
(228, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:52:17'),
(229, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:52:31'),
(230, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:52:41'),
(231, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 08:57:17'),
(235, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:10:56'),
(236, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:11:09'),
(237, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:11:22'),
(238, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:11:57'),
(239, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.198', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 09:12:26'),
(240, 21, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.198', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 09:13:03'),
(241, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:28:49'),
(242, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.255.214', 'Mozilla/5.0 (Linux; U; Android 8.1.0; en-us; CPH1803 Build/OPM1.171019.026) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.134 Mobile Safari/537.36 OppoBrowser/15.5.1.10', '2026-03-10 09:32:04'),
(243, 22, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; U; Android 8.1.0; en-us; CPH1803 Build/OPM1.171019.026) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.134 Mobile Safari/537.36 OppoBrowser/15.5.1.10', '2026-03-10 09:39:33'),
(244, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 09:39:36'),
(245, 23, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.86.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 09:40:52'),
(246, 23, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '41.216.86.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 09:42:02'),
(247, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 09:43:22'),
(248, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 09:43:28'),
(249, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 10:04:01'),
(250, 24, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.117.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-03-10 10:06:36'),
(251, 24, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '41.223.117.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 10:07:40'),
(252, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 10:19:48'),
(253, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 10:20:12'),
(254, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 10:32:37'),
(255, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 10:33:28'),
(256, 25, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 10:36:16'),
(257, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 10:44:47'),
(258, 26, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 10:50:15'),
(259, 26, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 10:54:28'),
(260, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 11:15:28'),
(261, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 11:24:09'),
(262, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 11:29:46'),
(263, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 11:41:01'),
(264, 27, 'LOGIN', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-10 11:42:39'),
(265, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-10 11:43:41'),
(266, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-10 11:43:59'),
(267, 27, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-10 11:44:27'),
(268, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 11:47:06'),
(269, 27, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-10 11:47:46'),
(270, 27, 'LOGIN', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 11:49:48'),
(271, 22, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.117.46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 12:18:58'),
(272, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 12:30:13'),
(273, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.12', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 12:30:21'),
(274, 13, 'LOGIN', NULL, NULL, NULL, NULL, '154.117.182.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 12:44:54'),
(275, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 12:48:04'),
(276, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 12:50:34'),
(277, 28, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 12:53:37'),
(278, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 12:55:44'),
(279, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 12:56:30'),
(280, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:01:25'),
(281, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:03:20'),
(282, 29, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-10 13:04:00'),
(283, 23, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.18', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 13:04:06'),
(284, 13, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '154.117.182.250', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 13:04:12'),
(285, 29, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-10 13:08:49'),
(286, 27, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:17:50'),
(287, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:22:18'),
(288, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:24:51'),
(289, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 13:25:09'),
(290, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-10 13:28:52'),
(291, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:33:05'),
(292, 7, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:33:12'),
(293, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 13:34:34'),
(294, 30, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 13:35:55'),
(295, 30, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '41.216.82.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 13:37:09'),
(296, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 13:39:57'),
(297, 29, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-10 13:44:50'),
(298, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '197.212.134.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 13:45:57'),
(299, 30, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 13:52:20'),
(300, 24, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.86.46', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 14:00:49'),
(301, 24, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.86.46', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 14:01:04'),
(302, 24, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.86.46', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 14:02:58'),
(303, 28, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 14:08:51'),
(304, 21, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.57.81.140', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 14:15:29'),
(305, 27, 'LOGIN', NULL, NULL, NULL, NULL, '102.212.181.43', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-10 14:38:11'),
(306, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 15:01:46'),
(307, 7, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 15:01:53'),
(308, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '45.215.249.92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 15:05:35'),
(309, 31, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.182', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 15:11:24'),
(310, 31, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '45.215.237.182', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 15:14:27'),
(311, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 15:21:22'),
(312, 27, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:15:53'),
(313, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:16:06'),
(314, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:06'),
(315, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:21'),
(316, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:37');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(317, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:41'),
(318, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:53'),
(319, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 16:19:58'),
(320, 24, 'LOGOUT', NULL, NULL, NULL, NULL, '102.145.114.154', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 16:29:36'),
(321, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 16:48:31'),
(322, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 16:48:36'),
(323, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 16:50:31'),
(324, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 16:52:33'),
(325, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 17:13:21'),
(326, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 17:13:51'),
(327, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.21', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 18:24:46'),
(328, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.21', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-10 18:24:54'),
(329, 22, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.97', 'Mozilla/5.0 (Linux; U; Android 8.1.0; en-us; CPH1803 Build/OPM1.171019.026) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.134 Mobile Safari/537.36 OppoBrowser/15.5.1.10', '2026-03-10 18:25:52'),
(330, 22, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.97', 'Mozilla/5.0 (Linux; U; Android 8.1.0; en-us; CPH1803 Build/OPM1.171019.026) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.134 Mobile Safari/537.36 OppoBrowser/15.5.1.10', '2026-03-10 18:25:52'),
(331, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.97', 'Mozilla/5.0 (Linux; U; Android 8.1.0; en-us; CPH1803 Build/OPM1.171019.026) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.134 Mobile Safari/537.36 OppoBrowser/15.5.1.10', '2026-03-10 18:26:32'),
(332, 31, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.77', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-10 18:32:35'),
(333, 29, 'LOGOUT', NULL, NULL, NULL, NULL, '102.147.42.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-10 18:45:12'),
(334, 29, 'LOGIN', NULL, NULL, NULL, NULL, '102.147.42.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-10 18:45:54'),
(335, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 18:46:11'),
(336, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 18:46:11'),
(337, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 18:46:11'),
(338, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.95.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 18:54:10'),
(339, 13, 'LOGIN', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:01:00'),
(340, 13, 'KPI_REJECTED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:03:10'),
(341, 13, 'KPI_REJECTED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:04:22'),
(342, 13, 'KPI_REJECTED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:04:27'),
(343, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:05:34'),
(344, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:05:40'),
(345, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:07:20'),
(346, 13, 'KPI_REJECTED', NULL, NULL, NULL, NULL, '154.117.164.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.24 Mobile/15E148 Safari/604.1', '2026-03-10 19:07:53'),
(347, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-10 20:11:14'),
(348, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 21:07:18'),
(349, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 21:07:33'),
(350, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 21:18:04'),
(351, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 21:18:11'),
(352, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-10 21:20:22'),
(355, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 04:19:20'),
(356, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 04:19:25'),
(357, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 04:20:14'),
(358, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 04:20:24'),
(359, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 04:25:08'),
(360, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 04:29:39'),
(361, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 04:29:54'),
(362, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 04:30:20'),
(365, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 05:14:35'),
(368, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 06:34:59'),
(369, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 06:51:16'),
(370, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 06:52:04'),
(371, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.72', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 06:55:16'),
(372, 22, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 07:51:34'),
(373, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-11 08:10:23'),
(374, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-11 08:10:37'),
(375, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 10:12:17'),
(376, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 10:12:24'),
(377, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 10:29:26'),
(378, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 10:49:46'),
(379, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 10:52:17'),
(380, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 11:00:54'),
(381, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 11:10:04'),
(382, 29, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 11:12:11'),
(383, 29, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 11:12:17'),
(384, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '102.212.183.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:13:26'),
(385, 30, 'LOGIN', NULL, NULL, NULL, NULL, '102.212.183.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:13:55'),
(386, 23, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.86.45', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:15:34'),
(387, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:18:38'),
(388, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-11 11:20:52'),
(389, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.118.39', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 11:20:54'),
(390, 23, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.86.45', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:25:41'),
(391, 24, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.171', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:35:19'),
(392, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-11 11:35:34'),
(393, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:37:24'),
(394, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 11:48:53'),
(395, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 11:49:00'),
(396, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 11:51:42'),
(397, 7, 'IT_USER_DEACTIVATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 11:53:13'),
(398, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '197.212.127.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:53:23'),
(399, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 11:53:34'),
(400, 34, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.149', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:53:38'),
(401, 34, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '45.215.237.149', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 11:57:38'),
(402, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:02:10'),
(403, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.149', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 12:02:56'),
(404, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:03:37'),
(406, 35, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.149', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 12:04:37'),
(407, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 12:04:41'),
(409, 35, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '102.145.3.251', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 12:07:02'),
(411, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:11:49'),
(412, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:14:31'),
(413, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 12:17:04'),
(414, 36, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '45.215.236.121', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 12:18:16'),
(415, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:29:38'),
(418, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:30:30'),
(419, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 12:31:43'),
(420, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 12:32:00'),
(424, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 13:03:30'),
(428, 27, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.86.42', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-11 13:26:28'),
(430, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 13:29:43'),
(431, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 13:37:45'),
(432, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 13:39:04'),
(433, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 13:43:46'),
(434, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 13:52:06'),
(435, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 13:53:43'),
(436, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 13:53:52'),
(437, 28, 'PASSWORD_RESET_REQUEST', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 13:55:46'),
(438, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 13:58:11'),
(439, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 13:58:49'),
(440, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:01:57'),
(441, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 14:02:08'),
(442, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:02:31'),
(443, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 14:04:19'),
(444, 27, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:05:06'),
(445, 27, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:06:44'),
(446, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:07:49'),
(447, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '102.212.183.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:08:14'),
(448, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:08:18'),
(449, 30, 'LOGIN', NULL, NULL, NULL, NULL, '102.212.183.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:08:31'),
(450, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 14:10:41'),
(451, 27, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:12:09'),
(452, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:12:24'),
(453, 3, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:12:44'),
(454, 27, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.86.42', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0.1 Mobile/15E148 Safari/604.1', '2026-03-11 14:14:20'),
(455, 29, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 14:16:24'),
(456, 29, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 14:16:29'),
(457, 30, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '102.212.183.41', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:20:51'),
(458, 3, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 14:21:21'),
(459, 29, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 14:23:46'),
(460, 24, 'LOGOUT', NULL, NULL, NULL, NULL, '102.149.131.249', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:28:46'),
(461, 24, 'LOGIN', NULL, NULL, NULL, NULL, '102.149.131.249', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:29:15'),
(462, 28, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 14:31:43'),
(463, 21, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.58.129.112', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-11 14:41:18'),
(464, 24, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '102.149.131.249', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 14:47:01'),
(465, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 14:54:42'),
(468, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.231', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 14:55:46'),
(469, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 14:57:30'),
(470, 25, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.117.41', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 15:06:41'),
(471, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.161', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:09:19'),
(472, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.223.117.41', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 15:10:00'),
(473, 35, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:14:01'),
(474, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:15:16'),
(475, 34, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:15:29'),
(476, 34, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:18:21'),
(477, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 15:33:08'),
(478, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:52:40'),
(479, 35, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 15:53:12'),
(480, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 16:27:34'),
(481, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 16:27:43'),
(482, 29, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 16:38:33'),
(483, 29, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-11 16:38:38'),
(484, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 16:41:49'),
(485, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 16:42:00'),
(486, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 16:49:16'),
(487, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 16:49:22'),
(488, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 16:57:55'),
(489, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 16:58:09'),
(490, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 16:58:23'),
(491, 35, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.27', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 16:59:56'),
(492, 13, 'PASSWORD_RESET_REQUEST', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 17:49:59'),
(493, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 17:54:51'),
(494, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Linux; Android 13; SM-G998U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Mobile Safari/537.36', '2026-03-11 17:55:12'),
(495, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 17:59:23'),
(496, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 17:59:42'),
(497, 7, 'IT_USER_UPDATED', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 18:02:14'),
(498, 7, 'IT_USER_UPDATED', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 18:03:54'),
(499, 7, 'IT_USER_UPDATED', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 18:05:32'),
(500, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 18:05:52'),
(501, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 18:19:10'),
(502, 13, 'LOGIN', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:21:22'),
(503, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 18:21:33'),
(504, 13, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:23:39'),
(505, 13, 'LOGIN', NULL, NULL, NULL, NULL, '105.234.174.234', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 18:25:34'),
(506, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-11 18:26:34'),
(507, 13, 'ANNOUNCEMENT_POSTED', NULL, NULL, NULL, NULL, '105.234.174.234', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-11 18:29:42'),
(508, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:32:14'),
(509, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:33:23'),
(510, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.23', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:33:35'),
(511, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.23', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:33:44'),
(512, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:34:41'),
(513, 13, 'KPI_REJECTED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:38:40'),
(514, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:40:15'),
(515, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.116.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:40:24'),
(516, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:43:41'),
(517, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:44:18'),
(518, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:45:03'),
(519, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:45:54'),
(520, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:46:32'),
(521, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:47:01'),
(522, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.27', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:47:42'),
(523, 34, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.27', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:47:50'),
(524, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:48:40'),
(525, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.16', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:48:47'),
(526, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-11 18:48:47'),
(527, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:48:50'),
(528, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:49:56'),
(529, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:50:55'),
(530, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:51:45'),
(531, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 18:52:33'),
(532, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(533, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(534, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(535, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(536, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(537, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(538, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(539, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(540, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(541, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(542, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(543, 13, 'KPI_APPROVED', NULL, NULL, NULL, NULL, '153.67.82.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:29:29'),
(544, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 04:37:26'),
(545, 36, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 04:39:05'),
(546, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 04:44:02'),
(547, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 04:44:07'),
(548, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 06:03:42'),
(549, 36, 'LOGIN', NULL, NULL, NULL, NULL, '165.57.81.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 06:03:49'),
(550, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 06:32:00'),
(551, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 06:49:37'),
(552, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 06:49:54'),
(553, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 06:51:01'),
(554, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 06:51:40'),
(555, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '197.212.67.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 07:06:14'),
(556, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 07:57:16'),
(557, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 07:57:22'),
(558, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 08:27:01'),
(559, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 08:27:12'),
(560, 36, 'HOLIDAY_DELETED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 08:37:54'),
(561, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 08:38:54'),
(562, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 08:39:13'),
(563, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 08:58:51'),
(564, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 08:59:11'),
(565, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 08:59:48'),
(566, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:00:03'),
(567, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:00:13'),
(568, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:00:24'),
(569, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:00:34'),
(570, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:00:56'),
(571, 14, 'HOLIDAY_ADDED', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 09:01:06'),
(572, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 09:03:44'),
(573, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 09:03:48'),
(574, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:12:51'),
(575, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:12:56'),
(576, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:18:17'),
(577, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:18:28'),
(578, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:19:07'),
(579, 2, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:19:33'),
(580, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:24:18'),
(581, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:24:30'),
(582, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:31:22'),
(583, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:31:31'),
(584, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:33:25'),
(585, 2, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:33:36');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(586, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:35:28'),
(587, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:35:37'),
(588, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:39:33'),
(589, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:39:40'),
(590, 13, 'LOGOUT', NULL, NULL, NULL, NULL, '105.234.179.44', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-12 09:49:46'),
(591, 13, 'LOGIN', NULL, NULL, NULL, NULL, '105.234.179.44', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-12 09:51:33'),
(592, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:54:07'),
(593, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 09:54:15'),
(594, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 09:58:02'),
(595, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 09:58:07'),
(596, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:02:00'),
(597, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:02:07'),
(598, 9, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:12:15'),
(599, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:12:53'),
(600, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:13:01'),
(601, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 10:34:47'),
(602, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 10:35:00'),
(603, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 10:55:25'),
(604, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 10:56:17'),
(605, 3, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 10:58:20'),
(606, 14, 'MGMT_REPORT_REVIEW', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:59:07'),
(607, 14, 'MGMT_REPORT_REVIEW', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 10:59:29'),
(608, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 11:10:39'),
(609, 2, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 11:11:11'),
(610, 2, 'DAILY_REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 11:29:03'),
(611, 14, 'ATTACHMENT_DOWNLOAD', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 11:35:13'),
(612, 14, 'DAILY_REPORT_REVIEW', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 11:36:22'),
(613, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 12:09:35'),
(614, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 12:09:40'),
(615, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 15:13:51'),
(616, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 16:08:12'),
(617, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:37:09'),
(618, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:37:33'),
(619, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:39:51'),
(620, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:39:58'),
(621, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:40:13'),
(622, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 18:42:21'),
(623, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 20:18:03'),
(624, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 20:18:34'),
(625, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 20:22:32'),
(626, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 20:22:36'),
(627, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 20:29:28'),
(628, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 20:30:14'),
(631, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 20:34:46'),
(632, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 20:35:21'),
(633, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-12 20:36:57'),
(634, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.224.138', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 21:10:46'),
(635, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 21:12:51'),
(636, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 21:36:08'),
(637, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 21:36:15'),
(638, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 21:41:51'),
(639, 3, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-12 21:41:58'),
(640, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 21:44:31'),
(642, 26, 'LOGIN', NULL, NULL, NULL, NULL, '102.150.18.107', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-12 23:37:42'),
(643, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 03:23:00'),
(645, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 03:59:42'),
(646, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 03:59:49'),
(647, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:25:08'),
(648, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:25:17'),
(653, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:41:52'),
(654, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:46:06'),
(655, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:46:16'),
(656, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:47:53'),
(657, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 04:47:59'),
(658, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 05:03:02'),
(662, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 05:05:05'),
(663, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 05:06:02'),
(667, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.75', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 06:10:33'),
(668, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.75', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 06:10:42'),
(669, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.8', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-13 10:30:53'),
(670, 34, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 10:32:39'),
(671, 21, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.56.186.194', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-13 10:37:06'),
(672, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 11:40:29'),
(673, 30, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 11:47:49'),
(674, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.230', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:20:07'),
(675, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.230', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:20:19'),
(676, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.230', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:27:25'),
(678, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:32:22'),
(679, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:35:32'),
(680, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:35:45'),
(681, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.230', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:43:00'),
(682, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.230', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 12:49:24'),
(683, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.25', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-13 13:09:25'),
(684, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.255.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 13:16:07'),
(685, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 13:24:18'),
(686, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 13:25:17'),
(687, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 13:25:27'),
(689, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 13:29:36'),
(690, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 13:36:31'),
(691, 28, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.178', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 13:59:18'),
(692, 28, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.178', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 14:07:45'),
(693, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:11:51'),
(694, 34, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:17:19'),
(695, 34, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:18:23'),
(696, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.190', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 14:23:32'),
(698, 34, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:26:11'),
(699, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:32:19'),
(700, 35, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:32:50'),
(701, 35, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:38:04'),
(702, 35, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 14:50:11'),
(703, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 15:22:00'),
(704, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 15:22:11'),
(705, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.59', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 16:11:52'),
(706, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.59', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 16:12:05'),
(707, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 19:51:44'),
(708, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 20:54:21'),
(709, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 20:54:28'),
(710, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 20:54:33'),
(711, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 20:56:30'),
(712, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.101', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 20:58:57'),
(713, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 21:08:36'),
(714, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 21:09:55'),
(720, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.66.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 06:58:10'),
(723, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.135', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 08:12:34'),
(726, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.84', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 11:56:20'),
(727, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.84', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 11:56:36'),
(728, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 13:03:01'),
(729, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.26', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 13:03:08'),
(730, 29, 'LOGIN', NULL, NULL, NULL, NULL, '102.149.227.44', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-14 13:43:41'),
(731, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 17:31:00'),
(732, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:29:37'),
(733, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:29:47'),
(734, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:32:21'),
(735, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:32:31'),
(736, 7, 'IT_USER_UPDATED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:34:43'),
(737, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.236.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:48:07'),
(738, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:48:34'),
(739, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.223.116.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 12:23:59'),
(740, 26, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.254', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 12:24:18'),
(741, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.254', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 12:56:11'),
(742, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '216.234.213.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 15:44:00'),
(743, 7, 'LOGIN', NULL, NULL, NULL, NULL, '216.234.213.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 15:44:10'),
(744, 7, 'IT_SOFTWARE_ADDED', NULL, NULL, NULL, NULL, '216.234.213.22', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 15:46:26'),
(745, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.255.74', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 20:07:13'),
(747, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:46:33'),
(748, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:46:56'),
(749, 7, 'IT_USER_DELETED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:47:47'),
(750, 7, 'IT_USER_CREATED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:50:31'),
(751, 43, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 20:51:30'),
(752, 43, 'PASSWORD_CHANGE', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 20:51:50'),
(753, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:58:17'),
(754, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:58:25'),
(755, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:58:57'),
(756, 7, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:59:16'),
(757, 7, 'IT_SOFTWARE_UPDATED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 21:01:20'),
(758, 43, 'SOH_COURSE_UPDATED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 21:13:52'),
(759, 43, 'SOH_COURSE_ADDED', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 21:14:25'),
(760, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 21:36:42'),
(761, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 21:38:02'),
(762, 9, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 21:38:09'),
(763, 43, 'LOGOUT', NULL, NULL, NULL, NULL, '165.58.129.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 05:13:29'),
(764, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:16:58'),
(765, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:17:08'),
(766, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 09:28:42'),
(767, 14, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.14', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 09:48:14'),
(768, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.14', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 09:48:36'),
(769, 9, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.14', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 09:50:11'),
(770, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:58:34'),
(771, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:58:40'),
(772, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 10:14:34'),
(773, 28, 'LOGOUT', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:23:55'),
(774, 22, 'LOGIN', NULL, NULL, NULL, NULL, '102.147.124.167', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:57:32'),
(775, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/382.0.794785026 Mobile/15E148 Safari/604.1', '2026-03-16 11:01:47'),
(776, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/382.0.794785026 Mobile/15E148 Safari/604.1', '2026-03-16 11:01:50'),
(777, 22, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:28:44'),
(778, 22, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:28:44'),
(779, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-16 11:40:42'),
(780, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 11:43:59'),
(781, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:47:40'),
(782, 22, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:47:41'),
(783, 21, 'ADM_APPLICATION_ADDED', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 11:55:04'),
(784, 35, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.39', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:03:53'),
(785, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.39', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:04:03'),
(786, 34, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.39', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:04:07'),
(787, 26, 'LOGIN', NULL, NULL, NULL, NULL, '197.212.172.113', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:04:27'),
(788, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 12:14:21'),
(789, 9, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.14', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 12:15:53'),
(790, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 12:22:36'),
(791, 25, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/382.0.794785026 Mobile/15E148 Safari/604.1', '2026-03-16 12:24:34'),
(792, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 12:28:16'),
(793, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '197.212.172.113', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:29:53'),
(794, 24, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.106', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:32:56'),
(795, 21, 'ADM_APPLICATION_ADDED', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 12:33:29'),
(796, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '197.212.172.113', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:39:35'),
(797, 21, 'ADM_ENROLLED', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 12:42:03'),
(798, 26, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '197.212.172.113', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:47:02'),
(799, 28, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 12:48:15'),
(800, 36, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.162', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 13:00:14'),
(801, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.255.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2026-03-16 13:01:13'),
(802, 29, 'LOGIN', NULL, NULL, NULL, NULL, '102.145.94.217', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-16 13:07:43'),
(803, 29, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '102.145.94.217', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-16 13:12:07'),
(804, 36, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.236.64', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:27:34'),
(805, 24, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.106', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:35:38'),
(806, 24, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.106', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:35:44'),
(807, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 13:47:13'),
(808, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 13:51:14'),
(809, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 13:51:14'),
(810, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 13:51:31'),
(811, 25, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 13:54:39'),
(812, 26, 'LOGOUT', NULL, NULL, NULL, NULL, '197.212.172.113', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:55:40'),
(813, 21, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-16 14:01:02'),
(814, 25, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 14:03:59'),
(815, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.180', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:11:17'),
(816, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.180', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:11:23'),
(817, 35, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.39', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 14:15:16'),
(818, 21, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '165.57.81.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 14:16:11'),
(819, 36, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 14:34:35'),
(820, 35, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.39', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 14:36:15'),
(821, 7, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 14:44:44'),
(822, 24, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.249.218', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:52:13'),
(823, 7, 'IT_PASSWORD_RESET', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 14:52:38'),
(824, 30, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.180', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:52:42'),
(825, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:56:40'),
(826, 34, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:56:46'),
(827, 24, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.249.218', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:58:57'),
(828, 34, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:59:21'),
(829, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 15:09:06'),
(830, 7, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 15:16:10'),
(831, 34, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.249.143', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:19:57'),
(832, 7, 'IT_USER_UPDATED', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 15:20:10'),
(833, 2, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 15:21:09'),
(834, 23, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.240', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:25:53'),
(835, 25, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 15:29:22'),
(836, 28, 'LOGIN', NULL, NULL, NULL, NULL, '41.216.82.30', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:29:52'),
(837, 23, 'REPORT_SUBMIT', NULL, NULL, NULL, NULL, '45.215.237.240', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:30:54'),
(838, 25, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 15:33:11'),
(839, 21, 'LOGIN', NULL, NULL, NULL, NULL, '165.58.129.181', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:03'),
(840, 34, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:15'),
(841, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:19'),
(842, 35, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:19'),
(843, 35, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:37'),
(844, 2, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:50:28'),
(845, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.224.250', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 18:33:01'),
(846, 24, 'LOGOUT', NULL, NULL, NULL, NULL, '102.147.220.14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 19:48:29'),
(847, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.191', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 20:03:28'),
(848, 30, 'LOGOUT', NULL, NULL, NULL, NULL, '45.215.237.191', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 20:03:28'),
(849, 30, 'LOGIN', NULL, NULL, NULL, NULL, '45.215.237.191', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 20:03:42'),
(850, 7, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.66.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 20:11:28'),
(851, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.66.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 20:11:51'),
(852, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 04:22:15'),
(853, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 05:24:36'),
(854, 14, 'LOGOUT', NULL, NULL, NULL, NULL, '165.56.186.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 05:50:03'),
(855, 14, 'LOGIN', NULL, NULL, NULL, NULL, '165.56.186.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 06:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `ca_activity_logs`
--

CREATE TABLE `ca_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(60) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ca_events`
--

CREATE TABLE `ca_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_type` varchar(80) NOT NULL DEFAULT 'Conference',
  `event_date` date NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stakeholders_invited` text DEFAULT NULL,
  `budget_allocated` decimal(12,2) DEFAULT NULL,
  `report_document` varchar(300) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Planned',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_ceo_reports`
--

CREATE TABLE `daily_ceo_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_title` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `tasks_done` text DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_size` int(11) DEFAULT NULL,
  `ceo_reply` text DEFAULT NULL,
  `report_date` date NOT NULL,
  `body` text DEFAULT NULL,
  `status` enum('pending','read','acknowledged','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_ceo_reports`
--

INSERT INTO `daily_ceo_reports` (`id`, `user_id`, `report_title`, `summary`, `tasks_done`, `issues`, `additional_notes`, `attachment_name`, `attachment_path`, `attachment_size`, `ceo_reply`, `report_date`, `body`, `status`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'TESTING TESTING TESTING TESTING TESTING TESTING', 'TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', 'TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', 'TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', 'TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', 'logo.png', 'daily_2_1773314942.png', 318262, 'TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', '2026-03-12', NULL, 'acknowledged', NULL, '2026-03-12 04:36:22', '2026-03-12 11:29:03', '2026-03-12 11:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `departmental_budget`
--

CREATE TABLE `departmental_budget` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `head_name` varchar(150) DEFAULT NULL,
  `head_email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `created_at`, `head_name`, `head_email`, `phone`, `location`, `budget`, `is_active`) VALUES
(1, 'Academic Affairs', 'ACAD', 'Academic programs, curriculum, faculty coordination, timetables, exams, and graduation', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(2, 'Administration & HR', 'ADMIN', 'Human resources, staff contracts, appraisals, recruitment, attendance, and leave management', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(3, 'Finance', 'FIN', 'Financial management, budgeting, payroll, invoices, petty cash, bank reconciliation, and procurement', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(4, 'Information Technology', 'IT', 'IT infrastructure, network devices, software, assets, helpdesk tickets, and user management', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(5, 'Student Affairs', 'STU', 'Student welfare, activities, and support services', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(6, 'Library', 'LIB', 'Library resources, e-library, research support, and information services', '2026-03-07 21:03:14', NULL, NULL, NULL, NULL, NULL, 1),
(7, 'Admissions', 'ADM', 'Student applications, interviews, scoring, offers, enrollment, and scholarships', '2026-03-07 23:12:01', NULL, NULL, NULL, NULL, NULL, 1),
(8, 'Registry', 'REG', 'Student records, programmes, results, transcripts, and academic reports', '2026-03-07 23:12:01', NULL, NULL, NULL, NULL, NULL, 1),
(9, 'Human Resources', 'HR', NULL, '2026-03-07 23:12:01', NULL, NULL, NULL, NULL, NULL, 1),
(10, 'Marketing', 'MKT', 'Marketing campaigns, leads, events, materials, and external reports', '2026-03-07 23:12:01', NULL, NULL, NULL, NULL, NULL, 1),
(11, 'Corporate Affairs', 'CA', 'Partnerships, accreditation, internships, sponsorships, media & PR, and alumni engagement', '2026-03-07 23:12:01', NULL, NULL, NULL, NULL, NULL, 1),
(21, 'Executive Office', 'EXEC', 'CEO, Vice Principal, Principal — top-level management and institutional oversight', '2026-03-08 00:09:31', NULL, NULL, NULL, NULL, NULL, 1),
(22, 'Nursing & Health Department', 'NRS', 'Campus health clinic, patient visits, referrals, medical inventory, and immunizations', '2026-03-08 00:09:31', NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `elearning_materials`
--

CREATE TABLE `elearning_materials` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `material_type` enum('pdf','video','link','document','presentation','notes') DEFAULT 'pdf',
  `file_path` varchar(500) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `elearning_sessions`
--

CREATE TABLE `elearning_sessions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `lecturer_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `session_type` enum('live','recorded','hybrid') DEFAULT 'live',
  `platform` varchar(100) DEFAULT 'Zoom',
  `meeting_link` varchar(500) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `duration_mins` int(11) DEFAULT 60,
  `status` enum('scheduled','live','completed','cancelled') DEFAULT 'scheduled',
  `recording_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `elibrary_access_log`
--

CREATE TABLE `elibrary_access_log` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `elibrary_resources`
--

CREATE TABLE `elibrary_resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` enum('ebook','journal','research','reference','textbook','magazine','other') DEFAULT 'ebook',
  `subject` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `year_published` year(4) DEFAULT NULL,
  `access_level` enum('all','lecturers','students','staff') DEFAULT 'all',
  `is_active` tinyint(4) DEFAULT 1,
  `uploaded_by` int(11) NOT NULL,
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(180) NOT NULL,
  `to_name` varchar(150) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_queue`
--

INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `subject`, `body`, `status`, `attempts`, `sent_at`, `created_at`) VALUES
(1, 'musumali22@gmail.com', 'Chief', 'Password Reset — Lanbridge KPI', 'Hello Chief,\n\nClick the link below to reset your password (valid for 1 hour):\n\nhttps://lanbridgecollegesystem.ct.ws/reset_password.php?token=d71a4ddf283e5b0c9cc09b5435e4b460975494d059f30b67d2153e1f686a7371\n\nIf you did not request this, please ignore this email.\n\nLanbridge College KPI System', 'pending', 0, NULL, '2026-03-09 20:41:06'),
(2, 'kapalashas@yahoo.com', 'Simon', 'Password Reset — Lanbridge KPI', 'Hello Simon,\n\nClick the link below to reset your password (valid for 1 hour):\n\nhttps://lanbridgecollegesystem.ct.ws/reset_password.php?token=7a113feb21b685fec563188666e7dfc7e6672221b78eea40dde5433a7e3c167f\n\nIf you did not request this, please ignore this email.\n\nLanbridge College KPI System', 'pending', 0, NULL, '2026-03-11 13:55:46'),
(3, 'musumali22@gmail.com', 'Chief', 'Password Reset — Lanbridge KPI', 'Hello Chief,\n\nClick the link below to reset your password (valid for 1 hour):\n\nhttps://lanbridgecollegesystem.ct.ws/reset_password.php?token=0155d3bb19fbb8d68f7aadd4f732ecf75638e63da8b80491dd38fe6ac657cf1e\n\nIf you did not request this, please ignore this email.\n\nLanbridge College KPI System', 'pending', 0, NULL, '2026-03-11 17:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `expenditure`
--

CREATE TABLE `expenditure` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_logs`
--

CREATE TABLE `financial_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `fin_bank_accounts`
--

CREATE TABLE `fin_bank_accounts` (
  `id` int(11) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'ZMW',
  `opening_balance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(16,2) NOT NULL DEFAULT 0.00,
  `account_type` enum('current','savings','fixed_deposit','trust','project') NOT NULL DEFAULT 'current',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_bank_entries`
--

CREATE TABLE `fin_bank_entries` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `value_date` date DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(16,2) DEFAULT NULL,
  `entry_type` enum('deposit','withdrawal','transfer_in','transfer_out','bank_charge','interest','correction','other') NOT NULL DEFAULT 'deposit',
  `is_reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `reconciled_by` int(11) DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `source` enum('manual','import','transfer','payroll','fee') NOT NULL DEFAULT 'manual',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_bank_reconciliations`
--

CREATE TABLE `fin_bank_reconciliations` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `period_month` char(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `statement_balance` decimal(16,2) NOT NULL,
  `book_balance` decimal(16,2) NOT NULL,
  `difference` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','completed','approved') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_invoices`
--

CREATE TABLE `fin_invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(40) NOT NULL,
  `invoice_type` enum('student_fee','service','vendor','other') NOT NULL DEFAULT 'student_fee',
  `client_name` varchar(200) NOT NULL,
  `client_ref` varchar(100) DEFAULT NULL COMMENT 'Student ID, vendor code, etc.',
  `client_email` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(5) NOT NULL DEFAULT 'ZMW',
  `status` enum('draft','issued','partially_paid','paid','overdue','cancelled','written_off') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_invoice_items`
--

CREATE TABLE `fin_invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_invoice_payments`
--

CREATE TABLE `fin_invoice_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `paid_date` date NOT NULL,
  `method` enum('cash','bank_transfer','cheque','mobile_money','card','other') NOT NULL DEFAULT 'cash',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_petty_cash_funds`
--

CREATE TABLE `fin_petty_cash_funds` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `custodian_id` int(11) DEFAULT NULL,
  `float_limit` decimal(12,2) NOT NULL DEFAULT 500.00,
  `current_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `replenish_threshold` decimal(12,2) DEFAULT 100.00,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_petty_cash_transactions`
--

CREATE TABLE `fin_petty_cash_transactions` (
  `id` int(11) NOT NULL,
  `fund_id` int(11) NOT NULL,
  `ref_no` varchar(40) NOT NULL,
  `type` enum('disbursement','replenishment','adjustment','opening') NOT NULL DEFAULT 'disbursement',
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) DEFAULT NULL,
  `payee` varchar(150) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `receipt_no` varchar(80) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fraud_flags`
--

CREATE TABLE `fraud_flags` (
  `id` int(11) NOT NULL,
  `flag_type` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `ai_confidence` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_applicants`
--

CREATE TABLE `hr_applicants` (
  `id` int(11) NOT NULL,
  `recruitment_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `applied_date` date NOT NULL,
  `cv_filename` varchar(255) DEFAULT NULL,
  `stage` enum('applied','shortlisted','interviewed','offered','accepted','rejected','withdrawn') NOT NULL DEFAULT 'applied',
  `interview_date` date DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_appraisals`
--

CREATE TABLE `hr_appraisals` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `period` varchar(20) NOT NULL COMMENT 'e.g. 2024-Q1 or 2024-H1',
  `appraiser_id` int(11) DEFAULT NULL,
  `kra_score` decimal(5,2) DEFAULT NULL COMMENT 'Key Result Areas score',
  `competency_score` decimal(5,2) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `rating` enum('outstanding','exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `staff_comments` text DEFAULT NULL,
  `status` enum('draft','submitted','acknowledged','finalised') NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `finalised_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_assets`
--

CREATE TABLE `hr_assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(40) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('laptop','desktop','phone','vehicle','furniture','equipment','other') NOT NULL DEFAULT 'equipment',
  `serial_no` varchar(100) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `condition_on_issue` enum('new','good','fair','poor') NOT NULL DEFAULT 'good',
  `status` enum('available','assigned','maintenance','disposed') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_attendance`
--

CREATE TABLE `hr_attendance` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','on_leave','public_holiday') NOT NULL DEFAULT 'present',
  `note` varchar(200) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_contracts`
--

CREATE TABLE `hr_contracts` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `contract_ref` varchar(40) NOT NULL,
  `contract_type` enum('permanent','fixed_term','casual','probation','renewal') NOT NULL DEFAULT 'fixed_term',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `probation_end` date DEFAULT NULL,
  `status` enum('active','expired','terminated','renewed') NOT NULL DEFAULT 'active',
  `document_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_requests`
--

CREATE TABLE `hr_leave_requests` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_types`
--

CREATE TABLE `hr_leave_types` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `days_allowed` int(11) NOT NULL DEFAULT 14,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_types`
--

INSERT INTO `hr_leave_types` (`id`, `name`, `days_allowed`, `is_paid`, `requires_approval`) VALUES
(1, 'Annual Leave', 24, 1, 1),
(2, 'Sick Leave', 30, 1, 1),
(3, 'Maternity Leave', 84, 1, 1),
(4, 'Paternity Leave', 5, 1, 1),
(5, 'Compassionate Leave', 5, 1, 1),
(6, 'Study Leave', 10, 0, 1),
(7, 'Unpaid Leave', 30, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `hr_recruitment`
--

CREATE TABLE `hr_recruitment` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `job_title` varchar(150) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `vacancies` tinyint(4) NOT NULL DEFAULT 1,
  `employment_type` enum('permanent','contract','part_time','intern','consultant') NOT NULL DEFAULT 'permanent',
  `closing_date` date DEFAULT NULL,
  `status` enum('draft','open','shortlisting','interviewing','offered','filled','cancelled') NOT NULL DEFAULT 'draft',
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(80) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_staff`
--

CREATE TABLE `hr_staff` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `job_grade` varchar(20) DEFAULT NULL,
  `employment_type` enum('permanent','contract','part_time','intern','consultant') NOT NULL DEFAULT 'permanent',
  `date_joined` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `nrc_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT NULL,
  `status` enum('active','inactive','suspended','terminated') NOT NULL DEFAULT 'active',
  `linked_user_id` int(11) DEFAULT NULL COMMENT 'Corresponding users.id if staff has a system account',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internship_companies`
--

CREATE TABLE `internship_companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `available_slots` int(11) NOT NULL DEFAULT 0,
  `linked_partnership_id` int(11) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internship_placements`
--

CREATE TABLE `internship_placements` (
  `id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `student_id_ref` varchar(50) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `placement_start` date DEFAULT NULL,
  `placement_end` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Pending',
  `evaluation_document` varchar(300) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_assets`
--

CREATE TABLE `it_assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(40) NOT NULL,
  `asset_type` enum('laptop','desktop','printer','server','switch','router','projector','phone','tablet','ups','scanner','camera','other') NOT NULL DEFAULT 'other',
  `make` varchar(80) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `condition_status` enum('new','good','fair','poor','under_repair','decommissioned') NOT NULL DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_helpdesk_chat`
--

CREATE TABLE `it_helpdesk_chat` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_it_staff` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_network_devices`
--

CREATE TABLE `it_network_devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(150) NOT NULL,
  `device_type` enum('router','switch','access_point','firewall','server','nas','voip','modem','repeater','other') NOT NULL DEFAULT 'other',
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(20) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `make` varchar(80) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(60) DEFAULT NULL,
  `status` enum('online','offline','maintenance','decommissioned') NOT NULL DEFAULT 'online',
  `last_seen` datetime DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `managed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_network_log`
--

CREATE TABLE `it_network_log` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `log_type` enum('uptime','downtime','maintenance','firmware_update','config_change','incident','note') NOT NULL DEFAULT 'note',
  `description` text DEFAULT NULL,
  `logged_by` int(11) DEFAULT NULL,
  `logged_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_software`
--

CREATE TABLE `it_software` (
  `id` int(11) NOT NULL,
  `software_name` varchar(200) NOT NULL,
  `vendor` varchar(150) DEFAULT NULL,
  `category` enum('os','office','security','accounting','erp','communication','design','development','database','other') NOT NULL DEFAULT 'other',
  `licence_type` enum('perpetual','subscription','open_source','freeware','trial','volume','oem') NOT NULL DEFAULT 'subscription',
  `licence_key` varchar(300) DEFAULT NULL,
  `seats` int(11) DEFAULT 1,
  `seats_used` int(11) DEFAULT 0,
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cost_zmw` decimal(10,2) DEFAULT NULL,
  `renewal_cost_zmw` decimal(10,2) DEFAULT NULL,
  `vendor_contact` varchar(200) DEFAULT NULL,
  `assigned_to_dept` varchar(150) DEFAULT NULL,
  `status` enum('active','expired','expiring_soon','cancelled','pending_renewal') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `it_software`
--

INSERT INTO `it_software` (`id`, `software_name`, `vendor`, `category`, `licence_type`, `licence_key`, `seats`, `seats_used`, `purchase_date`, `expiry_date`, `cost_zmw`, `renewal_cost_zmw`, `vendor_contact`, `assigned_to_dept`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Lanbridge College system saver', 'HOSTINGER', 'erp', 'subscription', '029753-28562-214-25232-325-255252', 1, 1, '2026-03-06', '2027-03-05', '3200.00', '1300.00', 'Linux Web Saver', 'All', 'active', '', 7, '2026-03-15 08:46:26', '2026-03-15 14:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `it_tickets`
--

CREATE TABLE `it_tickets` (
  `id` int(11) NOT NULL,
  `ticket_no` varchar(20) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `sla_deadline` datetime DEFAULT NULL,
  `status` enum('open','in_progress','pending_user','resolved','closed','cancelled') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `satisfaction_rating` tinyint(4) DEFAULT NULL,
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_ticket_activity_log`
--

CREATE TABLE `it_ticket_activity_log` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_ticket_comments`
--

CREATE TABLE `it_ticket_comments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_attachments`
--

CREATE TABLE `kpi_attachments` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT 'application/octet-stream',
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kpi_attachments`
--

INSERT INTO `kpi_attachments` (`id`, `submission_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at`) VALUES
(1, 20, 'dairy report for 10 march.pdf', 'kpi_20_69b024928b50c.pdf', 'application/pdf', 199450, '2026-03-10 14:02:58'),
(2, 46, 'KASEMPA PATIENCE.pdf', 'kpi_46_69b17f0e61934.pdf', 'application/pdf', 299326, '2026-03-11 14:41:18'),
(3, 47, 'MANGEMENT DAILY WORK LOG.pdf', 'kpi_47_69b18065bfa7b.pdf', 'application/pdf', 206904, '2026-03-11 14:47:01'),
(4, 55, 'RESEARCH TASK- ISC 111.pdf', 'kpi_55_69b402ad4151e.pdf', 'application/pdf', 45131, '2026-03-13 12:27:25'),
(5, 56, 'Research Task- Farm management 2.pdf', 'kpi_56_69b40654ec69a.pdf', 'application/pdf', 70639, '2026-03-13 12:43:00'),
(6, 57, 'Research Task- AGRICULTURAL RESEARCH METHODS.pdf', 'kpi_57_69b407d4a80d9.pdf', 'application/pdf', 43870, '2026-03-13 12:49:24'),
(7, 80, 'TIREZA CHISANGA.pdf', 'kpi_80_69b810ab7cda2.pdf', 'application/pdf', 285847, '2026-03-16 14:16:11'),
(8, 83, 'Erick Ngoma work activity.pdf', 'kpi_83_69b81ab0e00ab.pdf', 'application/pdf', 206304, '2026-03-16 14:58:57');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_categories`
--

CREATE TABLE `kpi_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_global` tinyint(1) DEFAULT 0,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `weight` decimal(5,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kpi_categories`
--

INSERT INTO `kpi_categories` (`id`, `name`, `description`, `department_id`, `is_global`, `max_score`, `weight`, `created_at`) VALUES
(1, 'Teaching & Instruction', 'Lesson delivery, student engagement, curriculum coverage', 1, 0, '100.00', '1.50', '2026-03-07 21:03:15'),
(2, 'Student Assessment', 'Marking, grading, feedback to students', 1, 0, '100.00', '1.20', '2026-03-07 21:03:15'),
(3, 'Curriculum Development', 'Lesson planning, curriculum updates, resource creation', 1, 0, '100.00', '1.00', '2026-03-07 21:03:15'),
(4, 'Administrative Tasks', 'Filing, reporting, meetings, correspondence', NULL, 1, '100.00', '0.80', '2026-03-07 21:03:15'),
(5, 'IT Support & Maintenance', 'System maintenance, user support, infrastructure', 4, 0, '100.00', '1.00', '2026-03-07 21:03:15'),
(6, 'Student Affairs Activities', 'Student counseling, events, welfare activities', 5, 0, '100.00', '1.00', '2026-03-07 21:03:15'),
(7, 'Library Services', 'Cataloging, research assistance, resource management', 6, 0, '100.00', '1.00', '2026-03-07 21:03:15'),
(8, 'Professional Development', 'Training, workshops, seminars attended or delivered', NULL, 1, '100.00', '0.70', '2026-03-07 21:03:15'),
(9, 'Community & Outreach', 'Community engagement, external partnerships', NULL, 1, '100.00', '0.60', '2026-03-07 21:03:15'),
(10, 'Financial Operations', 'Budget processing, financial records, reconciliation', 3, 0, '100.00', '1.00', '2026-03-07 21:03:15'),
(11, 'Teaching Quality', 'Lecture delivery, student feedback, punctuality', NULL, 0, '100.00', '1.50', '2026-03-07 23:13:37'),
(12, 'Research & Publications', 'Papers published, conferences attended', NULL, 0, '100.00', '1.20', '2026-03-07 23:13:37'),
(13, 'Student Support', 'Office hours, student queries resolved', NULL, 0, '100.00', '1.00', '2026-03-07 23:13:37'),
(14, 'Administrative Duties', 'Committee work, reports submitted on time', NULL, 0, '100.00', '1.00', '2026-03-07 23:13:37'),
(15, 'Community Engagement', 'Outreach, mentorship, industry linkages', NULL, 0, '100.00', '0.80', '2026-03-07 23:13:37'),
(16, 'Staff Development', 'Training attended, certifications earned', NULL, 0, '100.00', '1.00', '2026-03-07 23:13:37'),
(17, 'Finance Compliance', 'Budget adherence, procurement compliance', NULL, 0, '100.00', '1.20', '2026-03-07 23:13:37'),
(18, 'Customer Service', 'Responsiveness to enquiries, complaints resolved', NULL, 0, '100.00', '1.00', '2026-03-07 23:13:37'),
(19, 'Agroforestry', NULL, 1, 0, '100.00', '1.00', '2026-03-10 11:24:09'),
(20, 'Rural Sociology and extention', NULL, 1, 0, '100.00', '1.00', '2026-03-10 11:29:46'),
(21, 'Sustainable Agriculture', NULL, 1, 0, '100.00', '1.00', '2026-03-10 13:28:52'),
(22, 'Lecturing', NULL, 1, 0, '100.00', '1.00', '2026-03-10 13:44:50'),
(23, 'Had a meeting with marketing and admission', NULL, 7, 0, '100.00', '1.00', '2026-03-10 14:15:29'),
(24, 'Aquaculture', NULL, 1, 0, '100.00', '1.00', '2026-03-11 11:20:54'),
(25, 'Soil science 2 (irrigation and conversation)', NULL, 1, 0, '100.00', '1.00', '2026-03-11 13:43:46'),
(26, 'Animal production I (RUMINANT PRODUCTION)', NULL, 1, 0, '100.00', '1.00', '2026-03-11 13:52:06'),
(27, 'Integrated Science', NULL, 1, 0, '100.00', '1.00', '2026-03-11 14:01:57'),
(28, 'Introduction to Entrepreneurship', NULL, 1, 0, '100.00', '1.00', '2026-03-11 14:08:18'),
(29, 'Teaching / Academic Delivery', NULL, 4, 0, '100.00', '1.00', '2026-03-11 14:21:21'),
(30, 'Marketing and reception', NULL, 10, 0, '100.00', '1.00', '2026-03-11 14:47:01'),
(31, 'Field and practical work (Crop production )', NULL, 1, 0, '100.00', '1.00', '2026-03-11 15:09:19'),
(32, 'Field work(practical)', NULL, 1, 0, '100.00', '1.00', '2026-03-11 15:10:00'),
(33, 'Had an interview with some new students', NULL, 7, 0, '100.00', '1.00', '2026-03-13 10:37:06'),
(34, 'Farm Management II', NULL, 1, 0, '100.00', '1.00', '2026-03-13 12:43:00'),
(35, 'AGRICULTURAL RESEARCH METHODS (RES 222)', NULL, 1, 0, '100.00', '1.00', '2026-03-13 12:49:24'),
(36, 'Daily Combined KPI', NULL, 4, 1, '100.00', '1.00', '2026-03-13 13:09:25'),
(37, 'General Works', NULL, 1, 0, '100.00', '1.00', '2026-03-13 14:26:11'),
(38, 'Farm Management I', NULL, 1, 0, '100.00', '1.00', '2026-03-16 12:29:53'),
(39, 'Farm Engineering', NULL, 1, 0, '100.00', '1.00', '2026-03-16 12:47:02'),
(40, 'Crop production', NULL, 1, 0, '100.00', '1.00', '2026-03-16 13:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_submissions`
--

CREATE TABLE `kpi_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `custom_category` varchar(150) DEFAULT NULL,
  `submission_date` date NOT NULL,
  `task_description` text NOT NULL,
  `quantity_completed` decimal(10,2) DEFAULT 0.00,
  `quality_score` decimal(5,2) DEFAULT NULL,
  `time_spent_hours` decimal(5,2) DEFAULT NULL,
  `supporting_notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','revision_requested') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kpi_submissions`
--

INSERT INTO `kpi_submissions` (`id`, `user_id`, `category_id`, `custom_category`, `submission_date`, `task_description`, `quantity_completed`, `quality_score`, `time_spent_hours`, `supporting_notes`, `status`, `reviewed_by`, `reviewed_at`, `reviewer_notes`, `is_locked`, `created_at`, `updated_at`) VALUES
(10, 25, 19, NULL, '2026-03-10', 'Presentations:\r\nIntroduction to agro forestry, benefits, challenges, limitations, importantance and types of agro forestry systems around the word', NULL, NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 11:24:09', '2026-03-11 19:29:29'),
(11, 25, 20, NULL, '2026-03-10', 'Definition of operational terms.\r\n-defination of operational terms\r\n-development of rural sociology\r\n-scope and importance or rural sociology \r\n- oral questioning', NULL, NULL, '1.30', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 11:29:46', '2026-03-11 19:29:29'),
(12, 22, 1, NULL, '2026-03-10', 'presentations on Disasters under the following \r\nEarthquake\r\nfalls', '1.00', NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 12:55:44', '2026-03-11 19:29:29'),
(14, 23, 1, NULL, '2026-03-10', 'Taught Anatomy and Physiology on \r\n1. Epithelial tissue stating the types of epithelial tissue broadly classified as simple and stratified epithelium.', '1.00', NULL, '2.00', '', 'rejected', 13, NULL, 'Time spent appears inaccurate for the tasks described.', 0, '2026-03-10 13:04:06', '2026-03-10 19:04:27'),
(16, 25, 21, NULL, '2026-03-10', '-Basic operational terms\r\n-Goals of sustainable agriculture \r\n-importance of sustainable agriculture \r\n-key principles of sustainable agriculture', NULL, NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 13:28:52', '2026-03-11 19:29:29'),
(17, 29, 22, NULL, '2026-03-10', 'Taught \r\n1. Nursing student first session 08hrs with 2,2\r\nMed-surg \r\n2. Class of 3,1 psychiatry & mental health in nursing on ADHD (attention deficit hyper reactivity disorder)\r\n3. Philosophy of education \r\nOn communication and the existence of God \r\nEach class was 2hrs each', '3.00', NULL, '6.00', 'Nill', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 13:44:50', '2026-03-11 19:29:29'),
(18, 26, 2, NULL, '2026-03-10', '1. Plant Breeding and technology Quiz test 1\r\n2. Farm management 1 Quiz test 1\r\n3. Enteprenuership and development quiz test 1\r\n4. Animal Health and welfare. \r\n-introduction \r\na) Relationship between health and productivity\r\nb) Causes of Diseases', '4.00', NULL, '6.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-10 13:45:57', '2026-03-11 19:29:29'),
(19, 30, 22, NULL, '2026-03-10', 'Had two classes at 08:00 to 10:00hrs with 3,1 paediatric and  Child Health nursing (Introduction to Leukemia, predisposing factors and types of leukemia).\r\nAt 10:30 to 12:30  with 2,1 had Leadership,Management and Governance (types of leadership, leadership style and characteristics of a good leader).', '2.00', '80.00', '4.00', '', 'approved', 13, NULL, '', 0, '2026-03-10 13:52:20', '2026-03-10 19:07:20'),
(20, 24, 4, NULL, '2026-03-10', 'Marketing and administration activity', '6.00', NULL, '2.00', '', 'rejected', 13, NULL, 'Time spent appears inaccurate for the tasks described.', 0, '2026-03-10 14:02:58', '2026-03-10 19:07:53'),
(21, 28, 1, NULL, '2026-03-10', 'Taught students what matrices are and the types of matrices. Furthermore 100% of the students were able to perform addition and subtraction of matrices up to the order of (4*4).\r\nThey were able to state the conditions that satisfy the addition and subtraction in each entry.', '1.00', '95.00', '1.00', '', 'approved', 13, NULL, '', 0, '2026-03-10 14:08:51', '2026-03-10 19:05:40'),
(22, 21, 23, NULL, '2026-03-10', 'We evaluated and come up with new admissions for 2026.\r\nHelp with assembling tailoring sowing machines', '3.00', NULL, '8.50', '', 'rejected', 13, NULL, 'Time spent appears inaccurate for the tasks described.', 0, '2026-03-10 14:15:29', '2026-03-10 19:03:10'),
(23, 25, 22, NULL, '2026-03-11', 'Course:Post harvest \r\nTime: 08:00-09:30\r\nObjectives of post-harvest management\r\n-	Types of post-harvest losses(quantitative and qualitative)\r\nUnit 2: Causes of Post-Harvest Losses \r\n-	Mechanical damage during harvesting and handling\r\n-     Pest and disease attacks\r\n-     Poor harvesting methods\r\nActivity (group discussion and lecture method)', NULL, NULL, '1.30', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:00:54', '2026-03-11 19:29:29'),
(24, 25, 21, NULL, '2026-03-11', 'Course:Sustainable Agriculture \r\nTime: 11:00-1230\r\n•	History and development of sustainable agriculture\r\n	•	Importance of sustainable agriculture\r\n	•	Goals and objectives of sustainable agriculture\r\nUnit 2: Principles of Sustainable Agriculture\r\n	•	Soil conservation\r\n	•	Efficient use of natural resources\r\n(Method: lecture method,note writing )', NULL, NULL, '1.30', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:10:04', '2026-03-11 19:29:29'),
(25, 25, 24, NULL, '2026-03-11', 'Aquaculture \r\n09:30-11:00\r\n-      Freshwater aquaculture\r\n-	Marine aquaculture (mariculture)\r\n-	Brackish water aquaculture\r\nFish farming systems.\r\nExtensive aquaculture systems\r\n-	Semi-intensive aquaculture systems\r\n-	Intensive aquaculture systems\r\n-	Integrated aquaculture systems', NULL, NULL, '1.30', 'Student will require practice field operations to boost interest in the aquaculture and also to help with conceptualizing of ideas and boost understanding.', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:20:54', '2026-03-11 19:29:29'),
(26, 23, 1, NULL, '2026-03-11', 'Classroom teaching in anatomy and physiology on connective, muscle and nervous tissue', '1.00', NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:25:41', '2026-03-11 19:29:29'),
(27, 22, 1, NULL, '2026-03-11', 'course: fundamental of Nursing\r\ntopic: Health assessing status\r\nhistory talking .\r\n components of health assessing.\r\nwith the the first years (1,1).  from 10:3O to 12:30', '1.00', NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:37:24', '2026-03-11 19:29:29'),
(28, 22, 22, NULL, '2026-03-11', 'course :PEDIATRICIAN AND CHILD HEALTH NURSING\r\ntopic: ASPHYXIA NEONATORUM\r\nwith second years ,from 14:00 to 16:00', '1.00', NULL, '2.00', '', 'approved', 13, NULL, 'Reviewed in bulk', 0, '2026-03-11 11:53:23', '2026-03-11 19:29:29'),
(29, 3, 9, NULL, '2026-03-11', 'Checked the college email system and fixed some issues with messages not delivering. Also updated the spam filter settings.', '3.00', '80.00', '1.70', 'Email is working fine now. Delivery problems resolved.', 'approved', 13, NULL, '', 0, '2026-03-12 00:15:43', '2026-03-11 18:32:14'),
(38, 26, 25, NULL, '2026-03-11', 'Soil conservation \r\nImportance \r\nMethods 9f soil conservation \r\nDrainage, importance, signs and methods to drain the soil', '1.00', '65.00', '1.50', '', 'approved', 13, NULL, '', 0, '2026-03-11 13:43:46', '2026-03-11 18:52:33'),
(39, 26, 26, NULL, '2026-03-11', 'Beef production; \r\nManagement; oestrus cycle, when to breed, implication of early breeding of heifer\r\nReplacement of heifer\r\nManagement of beef cows\r\nManaging the calf\r\nColostrum, importance and it\'s substitutes\r\nResearch assignment\r\nDescribe and briefly explain the types of methods, importance  of the following as a routine management in need production  \r\n(1) Castration  \r\n(2)Dehorning', '2.00', '80.00', '1.50', '', 'approved', 13, NULL, '', 0, '2026-03-11 13:52:06', '2026-03-11 18:51:45'),
(40, 26, 27, NULL, '2026-03-11', 'CELL ORGANISATION \r\ncells, tissues, Organs and systems\r\nSystems\r\n1. Cardiovascular system \r\nThe external and internal structure of the heart; parts and function\r\nThe blood vessels\r\n- The differences between oxygenated and deoxygenated blood \r\n- Blood flow of oxygenated and deoxygenated blood\r\nOrgans involved \r\nContents of blood', '2.00', '80.00', '1.50', 'Assignment \r\nDescribe a) Systoles.  b) Diastole c) pacemaker.   d) heartbeat.     e) pulse.', 'approved', 13, NULL, '', 0, '2026-03-11 14:01:57', '2026-03-11 18:50:55'),
(41, 26, 28, NULL, '2026-03-11', '1. Enteprises, Types of enteprises, \r\n2. SWOT analysis \r\n3. Business planning: contents of a Business plan', '1.00', '80.00', '1.50', 'Assignment \r\n1. Prepare a value addition business plan if any choice of atleast 3 pages of printed pages.\r\nDate of submission; Friday, 13th March, 2026\r\nDue time: 12:30 hours', 'approved', 13, NULL, '', 0, '2026-03-11 14:08:18', '2026-03-11 18:49:56'),
(42, 30, 22, NULL, '2026-03-11', 'At 08:00 to 10:00hrs, I had Leadership, Management and Governance with the 3,1.Topic Quality Improvement,steps in quality improvement and Quality assessment,defined performance audit and Nursing Audit..Introduced a new topic Trade union definition and the types of trade unions.\r\nAt 14:00 to 16:00hrs,we had Paediatric and Child Health Nursing with the 3,1;Topic covered finishing leukemia and Introduced  Expanded Immunization Program in children,did the the definition of terms and cover partly of the strategies used in Immunization Program..', '2.00', '95.00', '4.00', 'Recommended Students to study on the types of immunoglobulins to aid them in easy understanding of the immunization schedule as we will proceed in the next session.', 'approved', 13, NULL, '', 0, '2026-03-11 14:20:51', '2026-03-11 18:48:50'),
(43, 3, 29, NULL, '2026-03-11', 'Today I conducted four classes: two with Diploma in Computer Science students and two with Certificate in Computer Studies students. The courses taught included Computer Application Packages, Web Development (HTML Basics), and Introduction to Computer Science.\r\n\r\nThe classes went well, and students actively participated in discussions and practical explanations. Most students demonstrated a good understanding of the concepts covered, especially during the examples and demonstrations.', '4.00', '95.00', '6.00', 'Challenges:\r\nSome courses require the use of a projector for better demonstrations, especially when teaching practical or visual topics such as web development and computer application packages.\r\n\r\nOverall, the classes were productive and the students responded positively to the lessons.', 'approved', 13, NULL, '', 0, '2026-03-11 14:21:21', '2026-03-11 18:47:01'),
(44, 29, 22, NULL, '2026-03-11', '11th March 2026\r\nClasses taught, \r\n2,2\r\n2,1 \r\nMed-surg on conditions affecting blood. Anemia from introduction to management.  \r\nAfter break \r\nClass of 3,1 \r\nIrh on hypertensive disorders specifically pre-eclampsia', '2.00', '80.00', '4.00', '', 'approved', 13, NULL, '', 0, '2026-03-11 14:23:46', '2026-03-11 18:46:32'),
(45, 28, 1, NULL, '2026-03-11', '- Taught students (Primary education) what Arithmetic operations is and their properties. Primarily, today we just looked at addition and its properties i.e. commutative and associative.\r\nMore than 85% of students understood the concept, only less 15% had difficulties with integers.\r\n- Taught students (Dip. General Agri) how use APA referencing style when answering an assignment.', '2.00', '95.00', '2.00', '', 'approved', 13, NULL, '', 0, '2026-03-11 14:31:43', '2026-03-11 18:45:54'),
(46, 21, 4, NULL, '2026-03-11', 'I created new acceptance letters,and recorded a new student for cdf under nursing', '3.00', '95.00', '8.00', '', 'approved', 13, NULL, '', 0, '2026-03-11 14:41:18', '2026-03-11 18:45:03'),
(47, 24, 30, NULL, '2026-03-11', 'Marketing and receptionist activities', '5.00', '65.00', NULL, '', 'approved', 13, NULL, '', 0, '2026-03-11 14:47:01', '2026-03-11 18:44:18'),
(48, 26, 31, NULL, '2026-03-11', 'Cultivation\r\nLand and Seedbed preparation \r\n1. Clearing of a portion of land\r\n2. Primary tillage (Digging)\r\n3. Types of bed (i) Sunken seed bed (ii) Raised seed bed', '3.00', '80.00', '1.50', '', 'approved', 13, NULL, '', 0, '2026-03-11 15:09:19', '2026-03-11 18:43:41'),
(49, 25, 32, NULL, '2026-03-11', 'Agricultural field work.\r\nTime :14:00 -17:00\r\nPractical work…\r\nDemerit: insufficient tools to on the ground.\r\nObj: the student are learning how to make beds,be practical and innovative.', NULL, NULL, '4.00', '', 'rejected', 13, NULL, 'kindly refer to your HOD for procurement of tools', 0, '2026-03-11 15:10:00', '2026-03-11 18:38:40'),
(50, 35, 22, NULL, '2026-03-11', 'DAILY KPI REPORT\r\nDate: 11th March 2026\r\nInstitution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\nSummary of Activities\r\nIRH Nursing Department\r\nA lesson was conducted with IRH Nursing students on the Second Stage of Labour. The class focused on understanding the process that occurs during this stage and the important role health personnel play in ensuring a safe delivery. Students followed the lesson well and participated in the discussion.\r\nPrimary and Secondary Teaching Department\r\nUnder the Teaching programme, the lesson focused on Teaching Methods used in both primary and secondary education. Different strategies that teachers can use to deliver lessons effectively and involve learners in the classroom were discussed.\r\nNursing 2.1\r\nA lecture was also conducted for Nursing 2.1 students on Sensation and Perception. The lesson explained how people receive and interpret information through their senses. Students were encouraged to share examples and take part in the discussion to better understand the topic.\r\nConclusion\r\nAll the planned lessons for the day were conducted successfully, and students showed good participation during the sessions.\r\nSubmitted by:\r\nMs Banda', '3.00', '80.00', '5.30', '', 'approved', 13, NULL, '', 0, '2026-03-11 15:14:01', '2026-03-11 18:34:41'),
(51, 34, 22, NULL, '2026-03-11', 'Institution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\nSummary of Activities\r\nBusiness Administration Department\r\nA lecture was conducted with the Business Administration students where the topic Economics was discussed. The session focused on helping students understand key economic concepts related to their programme. Students participated actively and contributed to the discussion during the lesson.\r\nPrimary Teaching Department\r\nIn the Primary Teaching programme, the lesson continued on English Methods. The class focused on different approaches and techniques used when teaching English at the primary school level. Students followed the lesson well and participated in the learning activities.\r\nSecondary Teaching Department\r\nStudents under Secondary Teaching were given a quiz test in Guidance and Counselling. The purpose of the quiz was to assess their understanding of the concepts that had been covered in previous lessons.\r\nConclusion\r\nAll the planned lessons and assessments for the day were conducted successfully, and students showed good participation throughout the sessions.\r\nSubmitted by:\r\nMr Nchenesi Michael', '3.00', '95.00', '4.30', '', 'approved', 13, NULL, '', 0, '2026-03-11 15:18:21', '2026-03-11 18:33:23'),
(53, 21, 33, NULL, '2026-03-13', 'I made fils for 1,1 nursing students and submitted them to the H.O.D', '5.00', NULL, '6.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 10:37:06', '2026-03-13 10:37:06'),
(54, 30, 22, NULL, '2026-03-13', 'At 08:00 to 10:00 hours,I had class with 3,1 Leadership Management and Governance;Trade Union, was able to Outline the topic objectives,Defined trade union,Explained the types of trade union, Bargaining process and Dead lock.Notes to be shared before the end of today.', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 11:47:49', '2026-03-13 11:47:49'),
(55, 26, 27, NULL, '2026-03-13', 'Lanbridge college \r\nAgriculture Science Department\r\nRESEARCH TASK\r\nCOURSE: Integrated Science (ISC 111)\r\nTOPIC; Blood Physiology & Disorders\r\nResearch Task: Profiles of Anemia, Leukemia, and Hemophilia.\r\nKey Question: Which specific blood components (RBCs, WBCs, or Platelets) are affected by these disorders?\r\nObjective: To link physiological structures to medical conditions.', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 12:27:25', '2026-03-13 12:27:25'),
(56, 26, 34, NULL, '2026-03-13', 'Lanbridge college \r\nAgriculture Science Departments \r\nResearch task\r\nFarm Management II: Cash Flow Analysis\r\nResearch Task: Investigate the \"Timing Gap\" in seasonal farming.\r\nKey Question:\r\n1.	 Why can a farm be \"profitable\" or make money on paper but have a \"negative cash flow\" during the planting season?\r\n\r\n2.	A farmer in Chilanga begins the month of October with a starting cash balance of K2,500 in their Airtel money account. During the month, the following transactions occur:\r\nCash Sales of Maize: K4,000\r\nPurchase of Fertilizer (Cash): K3,000\r\nPayment for Hired Labour: K1,200\r\nSale of an old plough (Cash received): K800\r\nPersonal family expenses (Withdrawal): K500\r\na)	Calculate the Total Cash Inflow for October.\r\nb)	Calculate the Total Cash Outflow for October.\r\nc)	Determine the Ending Cash Balance for the month\r\nObjective: To understand the difference between income (sales) and liquidity (available cash).', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 12:43:00', '2026-03-13 12:43:00'),
(57, 26, 35, NULL, '2026-03-13', 'COURSE; Agriculture Research Methods (RES 22): Identifying Variables\r\nRESEARCH TASK: Defining Independent, Dependent, and Controlled variables.\r\nKey Question: In an experiment testing fertilizer on crop yield, which variable is being manipulated and which is being measured?\r\nObjective: To master the foundational steps of scientific agricultural experimentation', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 12:49:24', '2026-03-13 12:49:24'),
(58, 3, 5, NULL, '2026-03-13', 'DAILY KPI REPORT\r\nDate: 13 March 2026\r\nInstitution: Lanbridge College\r\nSubmitted By: IT Department Head / Lecturer\r\n\r\n=======================================\r\n1. IT ADMINISTRATION ACTIVITIES\r\n=======================================\r\n* System Development    : Finalised and deployed an update to the notification system ensuring all alert notification are sent to the system users include direct action links.\r\n* System Infrastructure        : Updated system-wide password policy settings to enforce stronger passwords and a 90-day expiry for all staff accounts.\r\n\r\n=======================================', '3.00', NULL, '5.50', 'IT Head system maintenance responsibilities.', 'pending', NULL, NULL, NULL, 0, '2026-03-13 23:50:25', '2026-03-13 13:39:20'),
(59, 22, 22, NULL, '2026-03-13', 'course: public health  nursing\r\nintake: first year\r\ntime:08:00 to 10:00\r\ntopic: sanitation', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 13:24:18', '2026-03-13 13:24:18'),
(63, 22, 1, NULL, '2026-03-13', 'course: public Health Nursing\r\nIntake: first year\r\nTime:08:00 to 10:00\r\nTopic: sanitation', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 13:36:31', '2026-03-13 13:36:31'),
(66, 28, 1, NULL, '2026-03-13', 'Gave the students (Dip. General Agric) an assignment online to Redo as they did not follow the rules of APA referencing style  in the previous assignments, since the last lecture with them was based on how to use APA referencing style.', '1.00', NULL, '0.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 14:07:45', '2026-03-13 14:07:45'),
(67, 34, 22, NULL, '2026-03-13', 'Date: 13th March 2026\r\nInstitution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\nKey Performance Indicator (KPI) Report\r\nProgramme: Business Administration\r\nCourse: Business Law\r\nSummary of the Lesson\r\nToday, a lesson was conducted with Business Administration students focusing on Contract Law. The main objective of the lesson was to help students understand the meaning of a contract and the different types of contracts that exist in business and legal practice.\r\nDuring the session, students were guided through the basic concept of a contract and how agreements between two or more parties become legally binding. The lesson also covered the different types of contracts, including express contracts, implied contracts, bilateral contracts, unilateral contracts, and valid and void contracts.\r\nStudents actively participated in the lesson by giving examples of contracts they encounter in everyday life, such as buying goods, employment agreements, and service agreements. This helped them relate the theory to practical situations.\r\nPerformance Outcome\r\nThe lesson was interactive and students demonstrated a good level of understanding of the topic. Most students were able to identify and explain the different types of contracts and how they apply in business activities.\r\nOverview \r\nThe lesson was successfully conducted and the objectives were achieved. Future lessons will focus on the elements of a valid contract and possible remedies in case of breach of contract', '1.00', NULL, '1.30', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 14:18:23', '2026-03-13 14:18:23'),
(68, 34, 37, NULL, '2026-03-13', 'Date: 13th March 2026\r\nInstitution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\n\r\nGeneral Works\r\nGeneral maintenance activities were carried out around the college premises. During this process, 8 bulbs and 5 bulb holders were installed in various areas of the institution to improve lighting and create a better environment for both students and staff.\r\nDespite this progress, it was noted that some sections of the college still require additional lighting. To fully address this issue, there is a need for an additional 14 bulbs and 5 bulb holders.\r\nOverall, the planned academic and maintenance activities for the day were successfully carried out. With the provision of the remaining bulbs and holders, the lighting situation around the college will be further improved, creating a more conducive learning environment.', '2.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 14:26:11', '2026-03-13 14:26:11'),
(69, 35, 4, NULL, '2026-03-13', 'Date: 13th March 2026\r\nInstitution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\nSubject: Meeting with China Geo Engineering Corporation\r\nToday, I visited China Geo Engineering Corporation to conclude discussions concerning the Memorandum of Understanding (MOU) between the company and Lanbridge College. The main purpose of the meeting was to establish a partnership that will enable students from different programmes at the college to undertake their industrial attachments with the company.\r\nDuring the meeting, several important issues relating to student support during the attachment period were discussed. The company agreed to receive Lanbridge College students for industrial attachment and also confirmed that they will provide accommodation (boarding facilities) for the students during their stay.\r\nHowever, the matter regarding the provision of allowances to the students is still under consideration by the company’s management. They indicated that they will continue internal discussions on the matter and will communicate their final position at a later stage.\r\nOverall, the meeting was positive and productive, and it represents a good step towards strengthening collaboration between the college and the industry for the benefit of our students.\r\nConclusion\r\nThe engagement with China Geo Engineering Corporation was successful. Further feedback from the company is expected regarding the issue of student allowances.', '1.00', NULL, '1.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 14:38:04', '2026-03-13 14:38:04'),
(70, 35, 22, NULL, '2026-03-13', 'Date: 13th March 2026\r\nDepartment: Nursing 1,1\r\nInstitution: Lanbridge College\r\nSubmitted To: The Chief Executive Officer (CEO)\r\nKPI Report\r\nToday, as part of our academic activities, students were assigned a task on the topic “Learning.” The main goal of this assignment is to help students gain a better understanding of what learning is and appreciate its importance in their training and professional development.\r\nStudents were asked to cover the following areas in their work:\r\nDefinition of learning\r\nTypes of learning\r\nClassification of learning\r\nImportance of learning\r\nThe assignment is to be submitted on Monday at 10:00 hours. Students were encouraged to conduct thorough research and provide clear and detailed explanations for each section.\r\nOverall \r\nThe assignment was successfully issued, and students acknowledged both the instructions and the submission deadline. This activity is expected to strengthen their comprehension of the learning process and its practical relevance in their academic programme.', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-13 14:50:11', '2026-03-13 14:50:11'),
(71, 22, 22, NULL, '2026-03-16', 'course: fundamental of nursing\r\nintake :  first year\r\ntime:08:00 to 10:00\r\ntopic: clinical examination\r\n\r\n\r\ncourse: pediatric and child health nursing\r\nintake: second years\r\ntime:10:30 to 12:30\r\ntopic: Asphyxia neonatorum  (continued)', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 11:28:44', '2026-03-16 11:28:44'),
(72, 26, 38, NULL, '2026-03-16', 'TOPIC: PRODUCTION FUNCTIONS/ECONOMICS\r\n(a)Types of production function\r\n(b) Measures of INPUTS\r\n(c)types of product relations\r\n2. Law of Diminishing Returns/Variable proportions \r\n(a) categories of LDR', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 12:29:53', '2026-03-16 12:29:53'),
(73, 26, 34, NULL, '2026-03-16', 'TOPIC: COST BENEFIT ANALYSIS \r\nSubtopic: Costs\r\na) Categories of costs\r\n(i) Explicit cost (ii) Implicit cost\r\nb) Cost Function\r\nc) Types of costs (i) Total Cost; TFC, TVC, TC, \r\n(ii) Average cost ; AFC, AVC, ATC, etc.\r\n(iii) Marginal cost\r\nImportance of Cost', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 12:39:35', '2026-03-16 12:39:35'),
(74, 26, 39, NULL, '2026-03-16', 'TOPIC:  IC ENGINES \r\nENGINE SYSTEMS\r\n1. Fuel Supply system\r\n2. Cooling system a) water-cooled IC engines \r\na) Air-cooled IC engines\r\n3. Ignition System i) ignition by Spark ii) Spark by heat compression', '1.00', NULL, '1.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 12:47:02', '2026-03-16 12:47:02'),
(75, 28, 1, NULL, '2026-03-16', '- Taught Mathematics (Matrices) to students doing secondary Diploma on how to multiply two matrices and with a scalar. The lecture was to successful as at least 80% of the students performed very well and less than 20% didn\'t. As such, further explanation was given to the students accompanied with a task.\r\n- Students doing Dip. General Agri were submitting the assignment they were given last week and they did so.\r\n- Students doing a course in business mathematics didn\'t have a lecture today as they as they were having test.', '3.00', NULL, '4.50', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 12:48:15', '2026-03-16 12:48:15'),
(76, 3, 36, NULL, '2026-03-16', 'DAILY KPI REPORT\nDate: 16 March 2026\nInstitution: Lanbridge College\nSubmitted By: IT Department Head / Lecturer\n\n=======================================\n1. TEACHING & LEARNING ACTIVITIES\n=======================================\n* Classes Taught   : 3 classes\n* Programmes       : Diploma in Computer Science and Diploma in Information Technology\n* Courses Covered  : Cloud Storage Solutions, Remote Work Tools, and Collaborative Software Platforms\n* Student Outcomes : Students explored collaborative tools practically and demonstrated the ability to share and co-edit documents online.\n\n=======================================\n2. IT ADMINISTRATION ACTIVITIES\n=======================================\n* System Development    : Implemented improvements to the KPI submission module and tested the changes with sample data.\n* Maintenance & Support : Reset staff passwords, reconfigured a network printer, and updated antivirus definitions on lab machines.\n* Infrastructure        : Monitored the student portal uptime throughout the day and confirmed stable connectivity for all users.\n\n=======================================\nCHALLENGES\n=======================================\n* Cloud-based activities depend heavily on stable internet access which is not always guaranteed during lesson time.', '3.00', NULL, '5.20', 'Combined daily report covering both Lecturer and IT Head responsibilities.', 'pending', NULL, NULL, NULL, 0, '2026-03-16 22:11:15', '2026-03-16 13:00:13'),
(77, 29, 22, NULL, '2026-03-16', '16march \r\nClass taught \r\n3,3 mental health on CONDITIONS affecting cognitive aspect\r\n2,2 \r\nMed-surg \r\nManagement of a patient with Aneamia', '2.00', NULL, '4.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 13:12:07', '2026-03-16 13:12:07'),
(78, 25, 24, NULL, '2026-03-16', 'Topic:\r\n-integrated fish farming \r\n-importance\r\n-Relationship (between agriculture businesses)', NULL, NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 13:51:14', '2026-03-16 13:51:14'),
(79, 25, 40, NULL, '2026-03-16', 'SEED AND PLANTING MATERIALS \r\n-Defination\r\n-improved seeds\r\n-hybrid seeds\r\n-certified seeds\r\n-vegetative seeds\r\n-local seeds', NULL, NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 13:54:39', '2026-03-16 13:54:39'),
(80, 21, 4, NULL, '2026-03-16', '=had a meeting with marketing dipartment ,we discused  and come  up with marketing stratege,\r\n=we also made a budget for june/july 2026\r\n=i made two adimissions', '3.00', NULL, '8.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 14:16:11', '2026-03-16 14:16:11'),
(81, 35, 22, NULL, '2026-03-16', 'Date: 16th March 2026\r\nSubject: Report on Today’s Teaching activity \r\nGood afternoon sir\r\nI hereby submit the report on the academic activities conducted today, 16th March 2026.\r\nUnder the nursing department .topic covered was third stage of labor with the 2,1. The lesson focused on nursing management of  third stage of labour and  did   cesarian section with 3,1.lesson focused on the classification and indications.osh topic covered was environmental management tools and techniques . biology secondary teaching and lesson forced on classification of living organisms.secondary teaching English,topic covered was language skill development, and students actively participated in discussions to enhance their understanding of the subject matter.\r\nAll lessons were successfully conducted, and students responded positively during the assessments.', '4.00', NULL, '6.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 14:36:15', '2026-03-16 14:36:15'),
(82, 30, 22, NULL, '2026-03-16', 'At 08:00 to 10:00hrs had Psychiatry and mental health with 2,1 Topic covered Schizophrenia definition, predisposing factors and sign and symptoms.\r\nAt 10:30 to 12:30 hours had Paediatric and Child Nursing with 3,1 Expanded Immunization Program in Zambia, definition of terms, Strategies used in EPI in Zambia,Explained New Immunization Schedule in Zambia and the equipment to use on outreach.\r\nAt 14:00 to 16:00hrs \r\nHad class with 1,1 Nutrition in Nursing Topic covered Dietetics definition of terms, Types of Diets, Nutrition fro people with HIV/AIDS TB and Counselling For people with HIV/AIDS.', '3.00', NULL, '6.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 14:52:42', '2026-03-16 14:52:42'),
(83, 24, 30, NULL, '2026-03-16', 'Today I attended to clients, assisted with admissions, responded to inquiries, and worked on marketing activities to help promote the college. I also helped with office duties at the reception and supported the administration office where needed.', '7.00', NULL, '4.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 14:58:57', '2026-03-16 14:58:57'),
(84, 34, 22, NULL, '2026-03-16', 'Date: 16th March 2026\r\nInstitution: Lanbridge College\r\nDepartment: Primary Teaching\r\nSubmitted To: The Chief Executive Officer (CEO)\r\n\r\nGuidance and Counselling – Morning Session\r\nThe morning session was conducted with students under the Primary Teaching programme in the Guidance and Counselling course. During this lesson, the class continued studying the types of counselling. The discussion helped students understand the different approaches counsellors can use when assisting individuals with various personal and social concerns.\r\nPart of the lesson also touched on LGBTQ-related counselling, where students were guided on the importance of professionalism, ethical conduct, and respect when dealing with sensitive matters in counselling. Emphasis was placed on the need for counsellors to maintain confidentiality, show empathy, and treat every individual without discrimination. Students actively participated in the discussion and showed interest in the topic.\r\nAfternoon Session – English Lesson\r\nIn the afternoon, a lesson was conducted in English with the Primary Teaching students. The focus of the lesson was on Teaching Methods. Students were introduced to the core ideas behind different teaching methods and how these methods are applied in the classroom.\r\nThe discussion also covered the strengths and limitations of various teaching methods, helping students understand that each method has advantages and challenges depending on the learning environment and the needs of learners.\r\nOverall, the day’s lessons were conducted successfully. Students participated well in both sessions and demonstrated a good level of understanding of the concepts that were covered.', '2.00', NULL, '3.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 14:59:21', '2026-03-16 14:59:21'),
(85, 23, 1, NULL, '2026-03-16', '10:30-12:30\r\nMicrobiology\r\nCovered the following specific objectives\r\n1. Iisted the types of specimen\r\n2. Discussed the Growth, reproduction and phases of bacterial growth\r\n3. Outlined the elements and environmental factors necessary for bacterial growth', '1.00', NULL, '2.00', '', 'pending', NULL, NULL, NULL, 0, '2026-03-16 15:30:54', '2026-03-16 15:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_targets`
--

CREATE TABLE `kpi_targets` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `period` varchar(10) NOT NULL,
  `target_reports` int(11) NOT NULL DEFAULT 0,
  `target_kpis` int(11) NOT NULL DEFAULT 0,
  `target_approval_rate` decimal(5,2) NOT NULL DEFAULT 80.00,
  `set_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_academic_reports`
--

CREATE TABLE `lect_academic_reports` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `report_type` enum('end_of_semester','course_reflection','academic_challenge','resource_need','other') DEFAULT 'end_of_semester',
  `semester` varchar(10) DEFAULT NULL,
  `academic_year` varchar(10) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `recommendations` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('draft','submitted','acknowledged','actioned') DEFAULT 'draft',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `hod_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lect_academic_reports`
--

INSERT INTO `lect_academic_reports` (`id`, `lecturer_id`, `course_id`, `report_type`, `semester`, `academic_year`, `title`, `content`, `recommendations`, `priority`, `status`, `acknowledged_by`, `acknowledged_at`, `hod_note`, `created_at`, `updated_at`) VALUES
(1, 9, NULL, 'end_of_semester', '1', '2026', 'Testing', 'Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing', 'Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing  Testing Testing Testing Testing Testing Testing Testing', 'medium', 'acknowledged', 14, '2026-03-12 05:18:40', 'testing testing testing testing testing testing', '2026-03-12 05:10:54', '2026-03-12 05:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `lect_announcements`
--

CREATE TABLE `lect_announcements` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `audience` enum('all_courses','specific_course') DEFAULT 'specific_course',
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_assessments`
--

CREATE TABLE `lect_assessments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `assessment_type` enum('assignment','test','quiz','practical','mid_term','final_exam','ca') DEFAULT 'assignment',
  `total_marks` decimal(6,2) NOT NULL DEFAULT 100.00,
  `weight_percent` decimal(5,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `submission_deadline` date DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('draft','published','grading','submitted','locked') DEFAULT 'draft',
  `hod_approved` tinyint(1) DEFAULT 0,
  `hod_approved_by` int(11) DEFAULT NULL,
  `hod_approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_attendance_records`
--

CREATE TABLE `lect_attendance_records` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `note` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_attendance_sessions`
--

CREATE TABLE `lect_attendance_sessions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_type` enum('lecture','tutorial','practical','exam') DEFAULT 'lecture',
  `topic` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_consultation_slots`
--

CREATE TABLE `lect_consultation_slots` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `notes` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_leave_requests`
--

CREATE TABLE `lect_leave_requests` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','maternity','paternity','conference','research','unpaid','other') DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `needs_cover` tinyint(1) DEFAULT 1,
  `substitute_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_stored` varchar(255) DEFAULT NULL,
  `hod_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `hod_reviewed_by` int(11) DEFAULT NULL,
  `hod_reviewed_at` datetime DEFAULT NULL,
  `hod_note` text DEFAULT NULL,
  `final_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `final_reviewed_by` int(11) DEFAULT NULL,
  `final_reviewed_at` datetime DEFAULT NULL,
  `final_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_lecture_log`
--

CREATE TABLE `lect_lecture_log` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `custom_course_name` varchar(200) DEFAULT NULL,
  `lecturer_id` int(11) NOT NULL,
  `lecture_date` date NOT NULL,
  `topic_covered` varchar(300) NOT NULL,
  `duration_mins` int(11) DEFAULT 60,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `timetable_slot_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_marks`
--

CREATE TABLE `lect_marks` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `marks_obtained` decimal(6,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_materials`
--

CREATE TABLE `lect_materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `custom_course_name` varchar(200) DEFAULT NULL,
  `lecturer_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `material_type` enum('syllabus','lecture_notes','slides','assignment','reference','other') DEFAULT 'lecture_notes',
  `file_name` varchar(255) DEFAULT NULL,
  `stored_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_visible_to_students` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_research_log`
--

CREATE TABLE `lect_research_log` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `entry_type` enum('publication','conference','workshop','cpd','seminar','other') DEFAULT 'cpd',
  `title` varchar(300) NOT NULL,
  `institution` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cpd_hours` decimal(5,1) DEFAULT 0.0,
  `event_date` date DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_stored` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_student_queries`
--

CREATE TABLE `lect_student_queries` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `student_ref` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `query_text` text NOT NULL,
  `response` text DEFAULT NULL,
  `status` enum('open','responded','closed') DEFAULT 'open',
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lect_timetable_changes`
--

CREATE TABLE `lect_timetable_changes` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `requested_date` date DEFAULT NULL,
  `requested_time_start` time DEFAULT NULL,
  `requested_time_end` time DEFAULT NULL,
  `requested_venue` varchar(100) DEFAULT NULL,
  `substitute_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `success`, `user_agent`, `attempt_time`) VALUES
(6, 'emmanuelchikunda@gmail.com', '165.56.66.225', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 20:54:21'),
(11, 'kasondebeatrice25@gmail.com', '45.215.236.101', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 20:58:57'),
(14, 'mary@gmail.com', '165.56.66.225', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 21:08:36'),
(31, 'benjamin@gmail.com', '165.56.66.196', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 05:56:41'),
(32, 'benjamin@gmail.com', '165.56.66.196', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 05:56:48'),
(33, 'nursing@gmail.com', '165.56.66.196', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 06:00:35'),
(34, 'nursing@gmail.com', '165.56.66.196', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 06:39:08'),
(35, 'emmanuelchikunda@gmail.com', '165.56.66.196', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 06:58:10'),
(36, 'nursing@gmail.com', '165.56.66.196', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 07:02:08'),
(37, 'lucksondaka419@gmail.com', '45.215.236.84', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 11:56:36'),
(38, 'kasondebeatrice25@gmail.com', '45.215.236.26', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 13:03:08'),
(39, 'zgambomathews87@gmail.com', '102.149.227.44', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-14 13:43:41'),
(40, 'mary@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 17:31:00'),
(41, 'ceo@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:29:47'),
(42, 'emmanuelchikunda@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:32:31'),
(43, 'kasondebeatrice25@gmail.com', '45.215.236.9', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 19:48:34'),
(44, 'kapalashas@yahoo.com', '41.223.116.246', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 12:23:59'),
(45, 'lucksondaka419@gmail.com', '45.215.249.254', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 12:24:18'),
(46, 'emmanuelchikunda@gmail.com', '216.234.213.22', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 15:44:10'),
(47, 'kasondebeatrice25@gmail.com', '45.215.255.74', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 20:07:13'),
(48, 'nursing@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 20:20:28'),
(49, 'emmanuelchikunda@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:46:33'),
(50, 'ilungaleonardo@yahoo.com', '165.58.129.65', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 20:51:30'),
(51, 'ceo@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:58:25'),
(52, 'emmanuelchikunda@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-15 20:59:16'),
(53, 'mary@gmail.com', '165.58.129.65', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-15 21:38:09'),
(54, 'kapalashas@yahoo.com', '41.216.82.30', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:17:08'),
(55, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 09:28:42'),
(56, 'ceo@gmail.com', '45.215.249.14', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 09:48:14'),
(57, 'mary@gmail.com', '45.215.249.14', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.7 Mobile/15E148 Safari/604.1', '2026-03-16 09:50:11'),
(58, 'kapalashas@yahoo.com', '41.216.82.30', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 09:58:40'),
(59, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 10:14:34'),
(60, 'bensamba64@gmail.com', '165.56.186.216', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 10:23:16'),
(61, 'bensamba64@gmail.com', '165.56.186.216', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 10:23:16'),
(62, 'bensamba64@gmail.com', '165.56.186.216', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 10:23:21'),
(63, 'esamapaze@gmail.com', '102.147.124.167', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:56:22'),
(64, 'esamapaze@gmail.com', '102.147.124.167', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:56:23'),
(65, 'esamapaze@gmail.com', '102.147.124.167', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:56:23'),
(66, 'esamapaze@gmail.com', '102.147.124.167', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 10:57:32'),
(67, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/382.0.794785026 Mobile/15E148 Safari/604.1', '2026-03-16 11:01:47'),
(68, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/382.0.794785026 Mobile/15E148 Safari/604.1', '2026-03-16 11:01:50'),
(69, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:29:43'),
(70, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:29:43'),
(71, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:29:44'),
(72, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:10'),
(73, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:11'),
(74, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:31'),
(75, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:32'),
(76, 'esamapaze2gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:32'),
(77, 'esamapaze@gmail.com', '45.215.237.234', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:30:57'),
(78, 'bensamba64@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-16 11:40:42'),
(79, 'esamapaze@gmail', '45.215.237.240', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:43:43'),
(80, 'bensamba64@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 11:43:59'),
(81, 'esamapaze@gmail .com', '45.215.237.240', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:44:40'),
(82, 'esamapaze@gmail .com', '45.215.237.240', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:44:40'),
(83, 'esamapaze@gmail', '45.215.237.240', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:46:40'),
(84, 'esamapaze@gmail.com', '45.215.237.240', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:47:40'),
(85, 'esamapaze@gmail.com', '45.215.237.240', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 11:47:41'),
(86, 'marybanda1997@gmail.com', '45.215.237.39', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:03:53'),
(87, 'macusmicheal@gmail.com', '45.215.237.39', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:04:07'),
(88, 'lucksondaka419@gmail.com', '197.212.172.113', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:04:27'),
(89, 'bensamba64@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 12:22:36'),
(90, 'kapalashas@yahoo.com', '41.216.82.30', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 12:28:16'),
(91, 'erickngoma97@gmail.com', '45.215.249.106', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 12:32:56'),
(92, 'vmfune98@gmail.com', '165.58.129.162', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 12:59:58'),
(93, 'vmfune98@gmail.com', '165.58.129.162', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 13:00:14'),
(94, 'vmfune98@gmail.com', '45.215.255.248', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2026-03-16 13:01:13'),
(95, 'zgambomathews87@gmail.com', '102.145.94.217', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-03-16 13:07:43'),
(96, 'vmfune98@gmail.com', '45.215.236.64', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:27:34'),
(97, 'erickngoma97@gmail.com', '45.215.249.106', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 13:35:44'),
(98, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 13:47:13'),
(99, 'bensamba64@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 13:51:31'),
(100, 'jacksonulaya97@gmail.com', '165.56.186.216', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 14:03:59'),
(101, 'kasondebeatrice25@gmail.com', '45.215.237.180', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:11:23'),
(102, 'marybanda1997@gmail.com', '45.215.237.39', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 14:15:16'),
(103, 'emmanuelchikunda@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 14:44:44'),
(104, 'erickngoma97@gmail.com', '45.215.249.218', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:52:13'),
(105, 'macusmicheal@gmail.com', '45.215.237.11', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 14:56:46'),
(106, 'emmanuelchikunda@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 15:16:10'),
(107, 'dominicmukwe@gmail.com', '45.215.237.7', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 15:20:52'),
(108, 'dominicmukwe@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 15:21:09'),
(109, 'beverlynwitika@gmail.com', '45.215.237.240', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:25:53'),
(110, 'kapalashas@yahoo.com', '41.216.82.30', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:29:52'),
(111, 'jacksonulaya97@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-03-16 15:33:11'),
(112, 'bensamba64@gmail.com', '165.58.129.181', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:03'),
(113, 'macusmicheal@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:15'),
(114, 'marybanda1997@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-16 15:38:37'),
(115, 'dominicmukwe@gmail.com', '45.215.237.7', 0, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:50:12'),
(116, 'dominicmukwe@gmail.com', '45.215.237.7', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 15:50:28'),
(117, 'kasondebeatrice25@gmail.com', '45.215.237.191', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-16 20:03:42'),
(118, 'ceo@gmail.com', '165.56.66.56', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-16 20:11:51'),
(119, 'ceo@gmail.com', '165.56.186.182', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 05:24:36'),
(120, 'ceo@gmail.com', '165.56.186.182', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 06:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `management_reports`
--

CREATE TABLE `management_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `report_month` varchar(7) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `status` enum('pending','reviewed','acknowledged','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_records`
--

CREATE TABLE `media_records` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `media_type` varchar(80) NOT NULL DEFAULT 'News Article',
  `media_house` varchar(150) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `attachment` varchar(300) DEFAULT NULL,
  `engagement_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_campaigns`
--

CREATE TABLE `mkt_campaigns` (
  `id` int(11) NOT NULL,
  `campaign_name` varchar(200) NOT NULL,
  `campaign_type` enum('social_media','radio','tv','print','email','sms','school_visit','open_day','billboard','online_ads','event','other') NOT NULL DEFAULT 'social_media',
  `objective` enum('brand_awareness','lead_generation','enrollment','retention','other') NOT NULL DEFAULT 'lead_generation',
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `budget_zmw` decimal(12,2) DEFAULT NULL,
  `spent_zmw` decimal(12,2) NOT NULL DEFAULT 0.00,
  `target_leads` int(11) DEFAULT NULL,
  `actual_leads` int(11) NOT NULL DEFAULT 0,
  `target_audience` varchar(200) DEFAULT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of channel strings'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_events`
--

CREATE TABLE `mkt_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_type` enum('open_day','school_visit','exhibition','webinar','community_outreach','graduation','other') NOT NULL DEFAULT 'open_day',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target_audience` varchar(150) DEFAULT NULL,
  `expected_attendance` int(11) DEFAULT NULL,
  `actual_attendance` int(11) DEFAULT NULL,
  `budget_zmw` decimal(12,2) DEFAULT NULL,
  `spent_zmw` decimal(12,2) NOT NULL DEFAULT 0.00,
  `leads_generated` int(11) NOT NULL DEFAULT 0,
  `status` enum('planned','active','completed','cancelled','postponed') NOT NULL DEFAULT 'planned',
  `notes` text DEFAULT NULL,
  `organiser_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_event_attendees`
--

CREATE TABLE `mkt_event_attendees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL COMMENT 'Set if attendee is linked to a lead record',
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `school` varchar(150) DEFAULT NULL,
  `programme_interest` varchar(150) DEFAULT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `converted_to_lead` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_leads`
--

CREATE TABLE `mkt_leads` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` enum('male','female','other','unknown') NOT NULL DEFAULT 'unknown',
  `date_of_birth` date DEFAULT NULL,
  `school_attended` varchar(150) DEFAULT NULL,
  `programme_interest` varchar(150) DEFAULT NULL,
  `intake_year` year(4) DEFAULT NULL,
  `intake_semester` tinyint(4) NOT NULL DEFAULT 1,
  `source` enum('walk_in','online_form','social_media','referral','school_visit','open_day','phone_call','agent','email','other') NOT NULL DEFAULT 'walk_in',
  `referral_name` varchar(150) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `stage` enum('new','contacted','interested','applied','enrolled','lost','disqualified') NOT NULL DEFAULT 'new',
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `next_follow_up` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_lead_followups`
--

CREATE TABLE `mkt_lead_followups` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `follow_up_date` date NOT NULL,
  `method` enum('call','email','sms','walk_in','whatsapp','social','other') NOT NULL DEFAULT 'call',
  `outcome` enum('no_answer','interested','not_interested','applied','will_apply','needs_info','other') NOT NULL DEFAULT 'no_answer',
  `notes` text DEFAULT NULL,
  `next_action` text DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `done_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mkt_materials`
--

CREATE TABLE `mkt_materials` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `material_type` enum('brochure','flyer','poster','social_post','email_template','video_link','presentation','press_release','banner','other') NOT NULL DEFAULT 'flyer',
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL COMMENT 'Uploaded file path or URL',
  `external_url` varchar(500) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `tags` varchar(300) DEFAULT NULL COMMENT 'Comma-separated tags',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `msg_conversations`
--

CREATE TABLE `msg_conversations` (
  `id` int(11) NOT NULL,
  `type` enum('direct','group') DEFAULT 'direct',
  `name` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msg_conversations`
--

INSERT INTO `msg_conversations` (`id`, `type`, `name`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'direct', NULL, 1, '2026-03-08 08:37:16', '2026-03-08 10:13:55'),
(2, 'direct', NULL, 21, '2026-03-10 02:22:45', '2026-03-10 02:22:45'),
(3, 'direct', NULL, 3, '2026-03-12 14:42:17', '2026-03-12 14:43:27');

-- --------------------------------------------------------

--
-- Table structure for table `msg_messages`
--

CREATE TABLE `msg_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_stored` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_mime` varchar(120) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msg_messages`
--

INSERT INTO `msg_messages` (`id`, `conversation_id`, `sender_id`, `body`, `file_name`, `file_stored`, `file_size`, `file_mime`, `created_at`, `is_deleted`) VALUES
(1, 1, 1, 'hello. this is demo message', 'immanuel.jpg', 'msg_69ad97c95cdd83.08298007.jpg', 15068, 'image/jpeg', '2026-03-08 08:37:44', 0),
(2, 1, 7, 'Hello', NULL, NULL, NULL, NULL, '2026-03-08 10:06:04', 0),
(3, 1, 7, 'Hello', NULL, NULL, NULL, NULL, '2026-03-08 10:11:47', 0),
(4, 1, 1, 'hey', NULL, NULL, NULL, NULL, '2026-03-08 10:12:31', 0),
(5, 1, 1, 'hello', NULL, NULL, NULL, NULL, '2026-03-08 10:12:48', 0),
(6, 1, 7, '', 'PNG image.png', 'msg_69adae54865dd1.59668884.png', 77894, 'image/png', '2026-03-08 10:13:55', 1),
(7, 3, 3, 'hello', NULL, NULL, NULL, NULL, '2026-03-12 14:42:23', 0),
(8, 3, 7, 'Yes', NULL, NULL, NULL, NULL, '2026-03-12 14:43:09', 0),
(9, 3, 3, 'hello', NULL, NULL, NULL, NULL, '2026-03-12 14:43:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `msg_participants`
--

CREATE TABLE `msg_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime DEFAULT current_timestamp(),
  `last_read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msg_participants`
--

INSERT INTO `msg_participants` (`id`, `conversation_id`, `user_id`, `joined_at`, `last_read_at`) VALUES
(1, 1, 1, '2026-03-08 08:37:16', '2026-03-08 10:30:46'),
(2, 1, 7, '2026-03-08 08:37:16', '2026-03-11 09:29:11'),
(3, 2, 21, '2026-03-10 02:22:45', '2026-03-10 02:23:00'),
(4, 2, 7, '2026-03-10 02:22:45', '2026-03-11 09:28:34'),
(5, 3, 3, '2026-03-12 14:42:17', '2026-03-12 16:24:04'),
(6, 3, 7, '2026-03-12 14:42:17', '2026-03-12 14:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `department_id` int(11) DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`, `priority`, `department_id`, `action_url`, `expires_at`) VALUES
(5, 3, 'New Submission', 'Jane Mwanza has submitted a new daily report pending your review.', 'info', 1, '/kpi/portals/head_approvals.php', '2026-03-07 21:03:16', 'medium', NULL, NULL, NULL),
(6, 3, 'New Submission', 'Moses Banda has submitted a new KPI report pending your review.', 'info', 1, '/kpi/portals/head_approvals.php', '2026-03-07 21:03:16', 'medium', NULL, NULL, NULL),
(8, 2, 'Pending Reviews', 'There are 4 reports pending review in the system.', 'warning', 1, '/kpi/portals/vp_approvals.php', '2026-03-07 21:03:16', 'medium', NULL, NULL, NULL),
(10, 7, 'Welcome to Lanbridge KPI', 'Your account has been created. Please log in and change your password.', 'info', 1, 'http://localhost/lcms/login.php', '2026-03-07 23:58:36', 'medium', NULL, NULL, NULL),
(12, 7, '💬 Chief Executive', '📎 immanuel.jpg', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=1', '2026-03-08 15:37:44', 'medium', NULL, NULL, NULL),
(15, 7, '💬 Chief Executive', 'hey', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=1', '2026-03-08 17:12:31', 'medium', NULL, NULL, NULL),
(16, 7, '💬 Chief Executive', 'hello', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=1', '2026-03-08 17:12:48', 'medium', NULL, NULL, NULL),
(18, 9, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-08 17:15:31', 'medium', NULL, NULL, NULL),
(20, 13, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-08 17:42:16', 'medium', NULL, NULL, NULL),
(21, 14, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-08 18:59:54', 'medium', NULL, NULL, NULL),
(23, 7, 'Low Submission Alert', 'IT Head has missed 3+ consecutive weekdays without submitting a report.', 'warning', 1, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-09 07:31:51', 'medium', NULL, NULL, NULL),
(24, 14, 'Low Submission Alert', 'Mary Banda has missed 3+ consecutive weekdays without submitting a report.', 'warning', 1, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-09 07:34:27', 'medium', NULL, NULL, NULL),
(25, 14, 'Low Submission Alert', 'testing principal has missed 3+ consecutive weekdays without submitting a report.', 'warning', 1, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-09 07:34:27', 'medium', NULL, NULL, NULL),
(26, 14, 'Low Submission Alert', 'Vice Principal has missed 3+ consecutive weekdays without submitting a report.', 'warning', 1, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-09 07:34:27', 'medium', NULL, NULL, NULL),
(31, 21, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 09:11:57', 'medium', NULL, NULL, NULL),
(32, 22, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 09:28:49', 'medium', NULL, NULL, NULL),
(33, 23, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 09:39:36', 'medium', NULL, NULL, NULL),
(34, 24, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 10:04:01', 'medium', NULL, NULL, NULL),
(35, 25, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 10:32:37', 'medium', NULL, NULL, NULL),
(36, 26, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 10:44:47', 'medium', NULL, NULL, NULL),
(37, 27, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 11:41:01', 'medium', NULL, NULL, NULL),
(38, 28, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 12:48:04', 'medium', NULL, NULL, NULL),
(39, 29, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 12:56:30', 'medium', NULL, NULL, NULL),
(40, 30, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 13:34:34', 'medium', NULL, NULL, NULL),
(41, 31, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-10 15:05:35', 'medium', NULL, NULL, NULL),
(42, 21, 'KPI Rejected', 'Your KPI submission was rejected. Reason: Time spent appears inaccurate for the tasks described.', 'danger', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:03:10', 'medium', NULL, NULL, NULL),
(43, 23, 'KPI Rejected', 'Your KPI submission was rejected. Reason: Time spent appears inaccurate for the tasks described.', 'danger', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:04:22', 'medium', NULL, NULL, NULL),
(44, 23, 'KPI Rejected', 'Your KPI submission was rejected. Reason: Time spent appears inaccurate for the tasks described.', 'danger', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:04:27', 'medium', NULL, NULL, NULL),
(45, 28, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:05:34', 'medium', NULL, NULL, NULL),
(46, 28, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:05:40', 'medium', NULL, NULL, NULL),
(47, 30, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:07:20', 'medium', NULL, NULL, NULL),
(48, 24, 'KPI Rejected', 'Your KPI submission was rejected. Reason: Time spent appears inaccurate for the tasks described.', 'danger', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-10 19:07:53', 'medium', NULL, NULL, NULL),
(51, 34, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-11 11:51:42', 'medium', NULL, NULL, NULL),
(52, 35, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-11 12:02:10', 'medium', NULL, NULL, NULL),
(53, 36, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-11 12:14:31', 'medium', NULL, NULL, NULL),
(56, 3, 'New KPI Submission', 'IT Head submitted a KPI report.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php?tab=kpi', '2026-03-11 14:21:21', 'medium', NULL, NULL, NULL),
(57, 2, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(58, 3, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(59, 7, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(60, 9, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(61, 14, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(62, 21, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(63, 22, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(64, 23, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(65, 24, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(67, 26, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(68, 27, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(69, 28, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(70, 29, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(71, 30, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(72, 31, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(74, 34, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(75, 35, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(76, 36, '📢 Youth Week', 'Happy Youth Day Message\r\n\r\nI would like to wish all our supporting staff, members of staff, and senior management a Happy Youth Day.\r\n\r\nAs we commemorate this important day, let us remain dedicated, productive, and committed to our shared goals. Together, through teamwork and hard work, we will continue to achieve excellence and move our institution forward.', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/announcements.php', '2026-03-11 18:29:42', 'medium', NULL, NULL, NULL),
(79, 3, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:32:14', 'medium', NULL, NULL, NULL),
(80, 34, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:33:23', 'medium', NULL, NULL, NULL),
(81, 35, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:34:41', 'medium', NULL, NULL, NULL),
(82, 25, 'KPI Rejected', 'Your KPI submission was rejected. Reason: kindly refer to your HOD for procurement of tools', 'danger', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:38:40', 'medium', NULL, NULL, NULL),
(83, 26, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:43:41', 'medium', NULL, NULL, NULL),
(84, 24, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:44:18', 'medium', NULL, NULL, NULL),
(85, 21, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:45:03', 'medium', NULL, NULL, NULL),
(86, 28, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:45:54', 'medium', NULL, NULL, NULL),
(87, 29, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:46:32', 'medium', NULL, NULL, NULL),
(88, 3, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:47:01', 'medium', NULL, NULL, NULL),
(89, 30, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:48:50', 'medium', NULL, NULL, NULL),
(90, 26, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:49:56', 'medium', NULL, NULL, NULL),
(91, 26, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:50:55', 'medium', NULL, NULL, NULL),
(92, 26, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:51:45', 'medium', NULL, NULL, NULL),
(93, 26, 'KPI Approved', 'Your KPI submission has been approved!', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 18:52:33', 'medium', NULL, NULL, NULL),
(94, 22, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(95, 22, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(96, 23, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(97, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(98, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(99, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(100, 26, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(101, 29, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(102, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(103, 22, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(104, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(105, 25, 'KPI Approved', 'Your KPI submission has been approved.', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/my_submissions.php', '2026-03-11 19:29:29', 'medium', NULL, NULL, NULL),
(106, 13, 'Monthly Report Submitted', 'Mary Banda (Lecturer) submitted their monthly report for March 2026.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-12 10:12:15', 'medium', NULL, NULL, NULL),
(107, 3, 'New Monthly Report', 'IT Head submitted their monthly report.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-12 10:58:20', 'medium', NULL, NULL, NULL),
(108, 13, 'Monthly Report Submitted', 'IT Head (Department Head) submitted their monthly report for March 2026.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/head_approvals.php', '2026-03-12 10:58:20', 'medium', NULL, NULL, NULL),
(109, 3, '✅ Monthly Report Reviewed', 'CEO Testing has reviewed your March 2026 monthly report.', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/submit_report.php', '2026-03-12 10:59:07', 'medium', NULL, NULL, NULL),
(110, 9, '✅ Monthly Report Reviewed', 'CEO Testing has reviewed your March 2026 monthly report. Note: TESTING TESTING TESTING', 'success', 0, 'https://lanbridgecollegesystem.ct.ws/portals/submit_report.php', '2026-03-12 10:59:29', 'medium', NULL, NULL, NULL),
(111, 13, '📋 Daily Report: TESTING TESTING TESTING TESTING TESTING TESTING', 'Vice Principal (Vice Principal) has submitted a daily report: TESTING TESTING TESTING TESTING TESTING TESTING', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/view_ceo_inbox.php?id=1', '2026-03-12 11:29:03', 'medium', NULL, NULL, NULL),
(112, 2, '✅ Daily Report Acknowledged', 'CEO has acknowledged your daily report: TESTING TESTING TESTING TESTING TESTING TESTING — Reply: TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING TESTING', 'success', 1, 'https://lanbridgecollegesystem.ct.ws/portals/view_daily_reports.php', '2026-03-12 11:36:22', 'medium', NULL, NULL, NULL),
(113, 9, 'Report Acknowledged', 'Your report \"Testing\" has been Acknowledged.', 'info', 0, NULL, '2026-03-12 12:18:40', 'medium', NULL, NULL, NULL),
(115, 7, '💬 IT Head', 'hello', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=3', '2026-03-12 21:42:23', 'medium', NULL, NULL, NULL),
(116, 3, '💬 emmanuel chikunda', 'Yes', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=3', '2026-03-12 21:43:09', 'medium', NULL, NULL, NULL),
(117, 7, '💬 IT Head', 'hello', 'info', 1, 'https://lanbridgecollegesystem.ct.ws/portals/messages.php?conv=3', '2026-03-12 21:43:27', 'medium', NULL, NULL, NULL),
(120, 43, 'Welcome to Lanbridge KPI', 'Your account has been created by IT. Please log in and change your password.', 'info', 0, 'https://lanbridgecollegesystem.ct.ws/login.php', '2026-03-15 20:50:31', 'medium', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `partnerships`
--

CREATE TABLE `partnerships` (
  `id` int(11) NOT NULL,
  `organization_name` varchar(200) NOT NULL,
  `organization_type` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Active',
  `linked_programs` text DEFAULT NULL COMMENT 'Programmes covered by this partnership',
  `benefits` text DEFAULT NULL,
  `document_path` varchar(300) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_history`
--

INSERT INTO `password_history` (`id`, `user_id`, `password_hash`, `created_at`) VALUES
(2, 7, '$2y$12$sKoXWoOy6wGOU05woKsWZeyfWlI5jd0uVAQFvbzBoXfPfmWK9n.OK', '2026-03-07 23:59:15'),
(5, 9, '$2y$12$lqnvRjC2qnoqtyM84UGUpuOW1J/Z2afEFFK5.mLnwp6uXeJCa1YIa', '2026-03-08 18:10:57'),
(6, 14, '$2y$12$703uPtTz02xJ7o1FrreYzOomM5MDKos4u2BV9iZZYAJrYaVNA6Qq6', '2026-03-08 19:01:10'),
(8, 13, '$2y$12$xmPPYIMMdnYGn3Z1Zt89suyqwvQwgziXVJXKjYfM4wnh8xJFjw9/e', '2026-03-09 18:22:24'),
(13, 21, '$2y$12$zh65sCpaCeKEg5ysNbrDtemOlVEDb2W7Xw/xjCR.2tmmqnFbXYlXa', '2026-03-10 09:13:03'),
(14, 22, '$2y$12$tktJj/f2ROYmt9D2hP7fN.lwfej/D54XXfCdFXAibaOZNgJ9pRqwS', '2026-03-10 09:39:33'),
(15, 23, '$2y$12$bpkWT9spZHYXfnJyFNlIMeAN6SRL4baO0sFfDJPESJcwBYmESTNMi', '2026-03-10 09:42:02'),
(16, 24, '$2y$12$lt.uxfbgZjshA3j.wchxsea9cTWPBJRTIcBnFAQGnVrhQ74zauVFW', '2026-03-10 10:07:40'),
(17, 25, '$2y$12$7BzzXGDpbwor0XlH1N/btO9riFDSzg1x3bdR0FYskAm.W/DkV0Qje', '2026-03-10 10:36:16'),
(18, 26, '$2y$12$CpFiHhTQZctS8Z8wxxC5G.w4iSzl3GOpNeMUDcFloea1htsjn3gEO', '2026-03-10 10:54:28'),
(19, 27, '$2y$12$aTRx3nndvl/gJj8ku2CUieQdG4A4EVroFkmVOzsU06b1x6fpKbLxO', '2026-03-10 11:44:27'),
(20, 27, '$2y$12$mpwhl3uDyqpLPNPlIC5N1.OUzs5BvYgI4mYCeMowqa3QmH56Dct1m', '2026-03-10 11:47:46'),
(21, 28, '$2y$12$uIcfugDwzNEmyYgc7fbY7Oj5s.8UbU.Sgp1Y2VQiHKz0RUsvo4rPu', '2026-03-10 12:53:37'),
(22, 13, '$2y$12$3w1S7KfIz73AvPjhswG91.mMW/QiJRm8eYXHGKnWPLs22hEogvnxe', '2026-03-10 13:04:12'),
(23, 29, '$2y$12$UzOhL2HzXQVgi5qp0sKdeOJEdDDMkJaUK4EgJ858YzY776NG8xzBS', '2026-03-10 13:08:49'),
(24, 30, '$2y$12$hgBEYQU282uLEzHG6GU5peWioQs8U63Oi5paVj.9Ef06zVqfVNBmO', '2026-03-10 13:37:09'),
(25, 31, '$2y$12$Lq4b2hpVbMJRjXWL0Z0GtOAsFFpIKt9nZFCGd21D18UtNtjuUqMZG', '2026-03-10 15:14:27'),
(28, 34, '$2y$12$BjVYo6RP2GTxSY3l9HOzFeeoqtaAU.oWlfImPWbZnMdy/nSTgjYSK', '2026-03-11 11:57:38'),
(29, 35, '$2y$12$2YH9lxX2GjjJKmmqdv8vb.Vg0FLhXERxMLtqMTfzQiXyGEebYSBti', '2026-03-11 12:07:02'),
(30, 36, '$2y$12$eZ0yMEze6QtYIB86rfP89ulxCK7Im347fn8HYd/YZdA0ZTLIxODj.', '2026-03-11 12:18:16'),
(33, 27, '$2y$12$fFP6JKBWtVfpQn8vgrXt9e4nxK5icGjoMtz2glHbrQnW6yQjyRYp2', '2026-03-11 14:06:44'),
(34, 3, '$2y$12$MnTUWFaD2aeUPzEG.BJioucM4dt3Af0JcEZQNPt8IuqhNKqd5blBC', '2026-03-11 14:12:44'),
(35, 13, '$2y$12$AeOg6rvK7ZaWj0c9cZ/WnOUJf3o5J6caVOsjkwcHm9BSeERkVsLte', '2026-03-11 18:23:39'),
(39, 43, '$2y$12$Vdc8Fo/cxdoDGpmCN7lXyerKFcagkzl07ALfWpwtdYfh//BL1vWfi', '2026-03-15 20:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pay_period` char(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_ref` varchar(80) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('draft','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pay_period` char(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'paid',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_scorecards`
--

CREATE TABLE `performance_scorecards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `period` varchar(20) NOT NULL,
  `report_score` decimal(5,2) DEFAULT 0.00,
  `kpi_score` decimal(5,2) DEFAULT 0.00,
  `approval_rate` decimal(5,2) DEFAULT 0.00,
  `consistency_score` decimal(5,2) DEFAULT 0.00,
  `overall_score` decimal(5,2) DEFAULT 0.00,
  `rank_in_dept` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE `procurement_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(40) NOT NULL,
  `requesting_dept` int(11) NOT NULL,
  `requesting_user` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT NULL,
  `estimated_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `vendor_name` varchar(150) DEFAULT NULL,
  `vendor_contact` varchar(150) DEFAULT NULL,
  `urgency` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('draft','submitted','finance_review','approved','rejected','ordered','received') NOT NULL DEFAULT 'submitted',
  `finance_notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

CREATE TABLE `public_holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('public','closure','religious','other') NOT NULL DEFAULT 'public',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `public_holidays`
--

INSERT INTO `public_holidays` (`id`, `holiday_date`, `name`, `type`, `is_active`, `created_by`, `created_at`) VALUES
(2, '2025-03-12', 'Youth Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(3, '2025-03-08', 'Women\'s Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(4, '2025-04-18', 'Good Friday', 'public', 1, NULL, '2026-03-08 01:12:16'),
(5, '2025-04-19', 'Holy Saturday', 'public', 1, NULL, '2026-03-08 01:12:16'),
(6, '2025-04-21', 'Easter Monday', 'public', 1, NULL, '2026-03-08 01:12:16'),
(7, '2025-05-01', 'Labour Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(8, '2025-05-25', 'Africa Freedom Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(9, '2025-07-07', 'Heroes Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(10, '2025-07-08', 'Unity Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(11, '2025-08-04', 'Farmers Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(12, '2025-10-18', 'National Day of Prayer', 'public', 1, NULL, '2026-03-08 01:12:16'),
(13, '2025-10-24', 'Independence Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(14, '2025-12-25', 'Christmas Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(15, '2025-12-26', 'Boxing Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(16, '2026-01-01', 'New Year\'s Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(17, '2026-03-12', 'Youth Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(18, '2026-03-08', 'Women\'s Day', 'public', 1, NULL, '2026-03-08 01:12:16'),
(19, '2026-04-28', 'African Freedom Day', 'public', 1, 14, '2026-03-12 01:58:51'),
(20, '2026-05-01', 'Workers\' Day', 'public', 1, 14, '2026-03-12 01:59:11'),
(21, '2026-05-25', 'Africa Day', 'public', 1, 14, '2026-03-12 01:59:48'),
(22, '2026-07-07', 'Heroes\' Day', 'public', 1, 14, '2026-03-12 02:00:03'),
(23, '2026-07-08', 'Unity Day', 'public', 1, 14, '2026-03-12 02:00:13'),
(24, '2026-08-04', 'Farmers\' Day', 'public', 1, 14, '2026-03-12 02:00:24'),
(25, '2026-10-24', 'Independence Day', 'public', 1, 14, '2026-03-12 02:00:34'),
(26, '2026-12-25', 'Christmas Day', 'public', 1, 14, '2026-03-12 02:00:56'),
(27, '2026-12-26', 'Boxing Day', 'public', 1, 14, '2026-03-12 02:01:06');

-- --------------------------------------------------------

--
-- Table structure for table `reg_modules`
--

CREATE TABLE `reg_modules` (
  `id` int(11) NOT NULL,
  `module_code` varchar(20) NOT NULL,
  `module_name` varchar(200) NOT NULL,
  `programme_id` int(11) DEFAULT NULL COMMENT 'FK to reg_programmes',
  `year_of_study` tinyint(4) NOT NULL DEFAULT 1,
  `semester` tinyint(4) NOT NULL DEFAULT 1,
  `credits` tinyint(4) NOT NULL DEFAULT 3,
  `lecture_hours` tinyint(4) DEFAULT NULL,
  `practical_hours` tinyint(4) DEFAULT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reg_programmes`
--

CREATE TABLE `reg_programmes` (
  `id` int(11) NOT NULL,
  `programme_code` varchar(20) NOT NULL,
  `programme_name` varchar(200) NOT NULL,
  `award_type` enum('certificate','diploma','higher_diploma','degree','postgrad_diploma','masters','phd','short_course','other') NOT NULL DEFAULT 'diploma',
  `department` varchar(100) DEFAULT NULL,
  `duration_years` decimal(3,1) NOT NULL DEFAULT 2.0,
  `semesters_total` tinyint(4) NOT NULL DEFAULT 4,
  `min_credits` int(11) DEFAULT NULL COMMENT 'Minimum credits required to graduate',
  `description` text DEFAULT NULL,
  `entry_requirements` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reg_programmes`
--

INSERT INTO `reg_programmes` (`id`, `programme_code`, `programme_name`, `award_type`, `department`, `duration_years`, `semesters_total`, `min_credits`, `description`, `entry_requirements`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'DIT', 'Diploma in Information Technology', 'diploma', 'School of ICT', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(2, 'DBAM', 'Diploma in Business Administration & Management', 'diploma', 'School of Business', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(3, 'DBA', 'Diploma in Business Administration', 'diploma', 'School of Business', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(4, 'DNM', 'Diploma in Nursing & Midwifery', 'diploma', 'School of Health', '3.0', 6, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(5, 'DHRM', 'Diploma in Human Resource Management', 'diploma', 'School of Business', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(6, 'DPRM', 'Diploma in Public Relations & Marketing', 'diploma', 'School of Business', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08'),
(7, 'DAM', 'Diploma in Accounting & Management', 'diploma', 'School of Business', '2.0', 4, NULL, NULL, NULL, 1, 1, '2026-03-08 07:59:08', '2026-03-08 07:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `reg_results`
--

CREATE TABLE `reg_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `academic_year` year(4) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `ca_mark` decimal(5,2) DEFAULT NULL COMMENT 'Continuous assessment mark',
  `exam_mark` decimal(5,2) DEFAULT NULL COMMENT 'Final exam mark',
  `total_mark` decimal(5,2) DEFAULT NULL COMMENT 'Auto-calculated or manually entered total',
  `grade` varchar(5) DEFAULT NULL COMMENT 'A, B, C, D, F, I, W, etc.',
  `grade_points` decimal(3,2) DEFAULT NULL COMMENT 'GPA points e.g. 4.0, 3.7',
  `result_status` enum('pass','fail','incomplete','withheld','deferred','withdrawn') NOT NULL DEFAULT 'pass',
  `attempt` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Attempt number for resit tracking',
  `entered_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reg_status_changes`
--

CREATE TABLE `reg_status_changes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `old_status` varchar(40) DEFAULT NULL,
  `new_status` varchar(40) NOT NULL,
  `reason` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reg_students`
--

CREATE TABLE `reg_students` (
  `id` int(11) NOT NULL,
  `student_no` varchar(30) NOT NULL COMMENT 'Official number e.g. 2025/001',
  `enrollment_id` int(11) DEFAULT NULL COMMENT 'FK to adm_enrollments if enrolled via Admissions module',
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','unknown') NOT NULL DEFAULT 'unknown',
  `nationality` varchar(80) NOT NULL DEFAULT 'Zambian',
  `nrc_number` varchar(30) DEFAULT NULL COMMENT 'National Registration Card number',
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `next_of_kin` varchar(150) DEFAULT NULL,
  `nok_phone` varchar(30) DEFAULT NULL,
  `nok_relationship` varchar(60) DEFAULT NULL,
  `programme_id` int(11) DEFAULT NULL COMMENT 'FK to reg_programmes',
  `programme_name` varchar(150) DEFAULT NULL,
  `intake_year` year(4) NOT NULL,
  `intake_semester` tinyint(4) NOT NULL DEFAULT 1,
  `current_year` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Current year of study',
  `current_semester` tinyint(4) NOT NULL DEFAULT 1,
  `academic_status` enum('active','deferred','suspended','withdrawn','completed','graduated','expelled') NOT NULL DEFAULT 'active',
  `sponsor` varchar(150) DEFAULT NULL COMMENT 'Self / Government / Employer / Bursary',
  `special_needs` text DEFAULT NULL,
  `photo_path` varchar(300) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `registered_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reg_transcript_requests`
--

CREATE TABLE `reg_transcript_requests` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('official_transcript','attestation_letter','enrollment_letter','completion_letter','clearance_letter','result_slip','other') NOT NULL DEFAULT 'official_transcript',
  `purpose` text DEFAULT NULL,
  `addressee` varchar(200) DEFAULT NULL COMMENT 'Institution or employer the document is addressed to',
  `copies` tinyint(4) NOT NULL DEFAULT 1,
  `delivery_method` enum('collect','email','courier','post') NOT NULL DEFAULT 'collect',
  `delivery_address` text DEFAULT NULL,
  `status` enum('pending','processing','ready','collected','sent','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `fee_zmw` decimal(8,2) DEFAULT NULL,
  `fee_paid` tinyint(1) NOT NULL DEFAULT 0,
  `receipt_no` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL COMMENT 'Staff user ID if submitted on behalf of student',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `tasks_completed` text NOT NULL,
  `key_metrics` varchar(255) DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `tomorrow_plan` text DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_comment` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `level` int(11) NOT NULL COMMENT '1=CEO, 2=VP, 3=DeptHead, 4=Staff',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `level`, `description`) VALUES
(1, 'CEO', 'ceo', 1, 'Chief Executive Officer — Full system access'),
(2, 'Vice Principal', 'vice_principal', 2, 'Vice Principal — Academic monitoring and approvals'),
(3, 'Department Head', 'dept_head', 3, 'Head of Department'),
(4, 'Staff Member', 'staff', 6, 'General Staff Member'),
(5, 'Vice Principal', 'vp', 90, NULL),
(6, 'Principal', 'principal', 2, 'Principal — Academic oversight'),
(7, 'Academic Head', 'academic_head', 80, NULL),
(8, 'Head of Department', 'head', 70, NULL),
(9, 'Registry Officer', 'registry', 60, NULL),
(10, 'Finance Officer', 'finance', 60, NULL),
(11, 'Admissions Officer', 'admissions', 60, NULL),
(12, 'IT Officer', 'it', 60, NULL),
(13, 'HR / Admin Officer', 'admin', 60, NULL),
(14, 'Marketing Officer', 'marketing', 60, NULL),
(15, 'Corporate Affairs', 'corporate', 3, NULL),
(16, 'Student Affairs', 'student_affairs', 60, NULL),
(17, 'Senior Lecturer ', 'nursing', 60, NULL),
(18, 'Lecturer', 'lecturer', 6, 'Lecturer / Teaching / Academic Delivery'),
(19, 'Student', 'student', 10, NULL),
(22, 'Finance Admin', 'finance_admin', 3, 'Finance Administrator'),
(23, 'Finance Officer', 'finance_officer', 4, 'Finance Officer'),
(24, 'Bursar', 'bursar', 3, 'Bursar — Financial management'),
(25, 'Auditor', 'auditor', 3, 'Internal Auditor'),
(26, 'IT Admin', 'it_admin', 3, 'IT Administrator'),
(27, 'IT Officer', 'it_officer', 4, 'IT Officer'),
(29, 'Corporate Director', 'corporate_director', 2, 'Corporate Affairs Director'),
(30, 'Registrar', 'registrar', 4, 'Registry Officer'),
(31, 'Registry Officer', 'registry_officer', 4, 'Registry Officer'),
(32, 'Marketing Manager', 'marketing_manager', 4, 'Marketing Manager'),
(33, 'Marketing Officer', 'marketing_officer', 4, 'Marketing Officer'),
(34, 'Admissions Officer', 'admissions_officer', 4, 'Admissions Officer'),
(35, 'Nursing Officer', 'nursing_officer', 4, 'Nursing / Health Officer'),
(36, 'Dean of Students', 'dean_of_students', 5, 'Dean of Students'),
(37, 'Student Affairs Manager', 'student_affairs_manager', 5, 'Student Affairs Manager'),
(38, 'Student Affairs Officer', 'student_affairs_officer', 5, 'Student Affairs Officer'),
(39, 'Counsellor', 'counsellor', 5, 'Student Counsellor'),
(52, 'Corporate Affairs Officer', 'corp_affairs', 4, NULL),
(53, 'Senior Lecurer Nursing ', 'senior_lecurer_nursing ', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_clubs`
--

CREATE TABLE `sa_clubs` (
  `id` int(11) NOT NULL,
  `club_name` varchar(150) NOT NULL,
  `club_type` enum('academic','sports','cultural','religious','community','professional','arts','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `patron_id` int(11) DEFAULT NULL COMMENT 'Staff patron user ID',
  `president_name` varchar(150) DEFAULT NULL,
  `president_phone` varchar(30) DEFAULT NULL,
  `meeting_schedule` varchar(200) DEFAULT NULL,
  `meeting_venue` varchar(150) DEFAULT NULL,
  `membership_fee` decimal(8,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `established_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_club_members`
--

CREATE TABLE `sa_club_members` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(160) DEFAULT NULL,
  `student_no` varchar(30) DEFAULT NULL,
  `role` enum('member','president','vice_president','secretary','treasurer','committee','patron') NOT NULL DEFAULT 'member',
  `joined_date` date DEFAULT NULL,
  `fee_paid` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_counselling_sessions`
--

CREATE TABLE `sa_counselling_sessions` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(160) NOT NULL,
  `student_no` varchar(30) DEFAULT NULL,
  `session_type` enum('academic','career','personal','mental_health','grief','financial','relationship','crisis','group','other') NOT NULL DEFAULT 'personal',
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `duration_mins` int(11) NOT NULL DEFAULT 60,
  `counsellor_id` int(11) DEFAULT NULL,
  `mode` enum('in_person','phone','online') NOT NULL DEFAULT 'in_person',
  `presenting_issue` text DEFAULT NULL,
  `session_notes` text DEFAULT NULL COMMENT 'Confidential — visible to counsellors only',
  `action_plan` text DEFAULT NULL,
  `next_session` date DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show','referred') NOT NULL DEFAULT 'scheduled',
  `is_confidential` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_discipline_cases`
--

CREATE TABLE `sa_discipline_cases` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(160) NOT NULL,
  `student_no` varchar(30) DEFAULT NULL,
  `offence_type` enum('academic_dishonesty','misconduct','property_damage','substance_abuse','harassment','assault','absenteeism','dress_code','social_media','other') NOT NULL DEFAULT 'other',
  `offence_date` date DEFAULT NULL,
  `description` text NOT NULL,
  `witnesses` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `severity` enum('minor','moderate','serious','gross') NOT NULL DEFAULT 'minor',
  `status` enum('reported','under_investigation','hearing_scheduled','hearing_done','appealed','closed','dismissed') NOT NULL DEFAULT 'reported',
  `hearing_date` datetime DEFAULT NULL,
  `outcome` enum('warning','written_warning','fine','community_service','suspension','expulsion','dismissed','no_action') DEFAULT NULL,
  `outcome_details` text DEFAULT NULL,
  `fine_amount` decimal(8,2) DEFAULT NULL,
  `suspension_days` int(11) DEFAULT NULL,
  `appeal_filed` tinyint(1) NOT NULL DEFAULT 0,
  `appeal_outcome` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_events`
--

CREATE TABLE `sa_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_type` enum('orientation','graduation','sports','cultural','seminar','trip','fundraiser','competition','community','other') NOT NULL DEFAULT 'other',
  `club_id` int(11) DEFAULT NULL COMMENT 'Set if organised by a specific club',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expected_attendance` int(11) DEFAULT NULL,
  `actual_attendance` int(11) DEFAULT NULL,
  `budget_zmw` decimal(10,2) DEFAULT NULL,
  `spent_zmw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  `organiser_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_welfare_cases`
--

CREATE TABLE `sa_welfare_cases` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'References reg_students.id',
  `student_name` varchar(160) NOT NULL,
  `student_no` varchar(30) DEFAULT NULL,
  `case_type` enum('financial_hardship','medical','bereavement','accommodation','food_insecurity','domestic','mental_health','disability','other') NOT NULL DEFAULT 'other',
  `description` text NOT NULL,
  `urgency` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','referred','resolved','closed') NOT NULL DEFAULT 'open',
  `support_given` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_config`
--

CREATE TABLE `security_config` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_config`
--

INSERT INTO `security_config` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'password_min_length', '8', 'Minimum password character length', '2026-03-07 21:03:17'),
(2, 'require_uppercase', '1', 'Require at least one uppercase letter', '2026-03-07 21:03:17'),
(3, 'require_lowercase', '1', 'Require at least one lowercase letter', '2026-03-07 21:03:17'),
(4, 'require_numbers', '1', 'Require at least one number', '2026-03-07 21:03:17'),
(5, 'require_special', '1', 'Require at least one special character', '2026-03-07 21:03:17'),
(6, 'password_expiry_days', '90', 'Days before password expires', '2026-03-07 21:03:17'),
(7, 'password_history_count', '5', 'Number of previous passwords that cannot be reused', '2026-03-07 21:03:17'),
(8, 'max_login_attempts', '5', 'Max failed logins before lockout', '2026-03-07 21:03:17'),
(9, 'lockout_duration_mins', '30', 'Lockout duration in minutes', '2026-03-07 21:03:17'),
(10, 'session_timeout_mins', '120', 'Session inactivity timeout in minutes', '2026-03-07 21:03:17'),
(11, 'lockout_duration', '900', 'Lockout duration in seconds (15 minutes)', '2026-03-07 23:10:58'),
(12, 'attempt_window', '3600', 'Rolling window in seconds to count attempts', '2026-03-07 23:10:58'),
(17, 'session_timeout', '7200', NULL, '2026-03-08 14:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `soh_assessments`
--

CREATE TABLE `soh_assessments` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL COMMENT 'Originating visit if applicable',
  `referral_date` date NOT NULL,
  `referred_to` varchar(200) NOT NULL COMMENT 'Hospital / clinic / specialist name',
  `specialty` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `urgency` enum('routine','urgent','emergency') NOT NULL DEFAULT 'routine',
  `referral_letter` text DEFAULT NULL,
  `status` enum('pending','attended','report_received','lost_to_followup','cancelled') NOT NULL DEFAULT 'pending',
  `attended_date` date DEFAULT NULL,
  `outcome_notes` text DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_courses`
--

CREATE TABLE `soh_courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `programme` varchar(100) DEFAULT NULL COMMENT 'e.g. Diploma in Nursing, BSc Nursing',
  `department` varchar(100) DEFAULT NULL COMMENT 'e.g. Nursing, Public Health, Clinical Medicine',
  `year_level` tinyint(4) DEFAULT NULL COMMENT '1, 2, 3, 4',
  `semester` enum('1','2','3') DEFAULT '1',
  `academic_year` varchar(20) DEFAULT NULL,
  `credits` tinyint(4) DEFAULT 3,
  `max_students` smallint(6) DEFAULT 40,
  `lecturer_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soh_courses`
--

INSERT INTO `soh_courses` (`id`, `course_code`, `course_name`, `programme`, `department`, `year_level`, `semester`, `academic_year`, `credits`, `max_students`, `lecturer_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'NRS101', 'Fundamentals of Nursing', 'Diploma in Nursing', 'Nursing', 1, '1', '2025/2026', 3, 45, NULL, 1, '2026-03-13 09:34:55', '2026-03-13 09:34:55'),
(2, 'NRS201', 'Medical-Surgical Nursing', 'Bachelor of Nursing Sciences', 'Nursing', 2, '1', '2025/2026', 4, 40, NULL, 1, '2026-03-13 09:34:55', '2026-03-13 09:34:55'),
(3, 'NRS301', 'Community Health Nursing', 'Bachelor of Nursing Sciences', 'Nursing', 3, '1', '2025/2026', 3, 35, NULL, 1, '2026-03-13 09:34:55', '2026-03-13 09:34:55'),
(6, 'MDW2018', 'Principles of Midwifery', 'Diploma in Midwifery', 'Nursing', 1, '2', '2026/2027', 3, 40, 43, 1, '2026-03-15 14:14:25', '2026-03-15 14:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `soh_course_records`
--

CREATE TABLE `soh_course_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `dose_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `administered_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `administered_by` int(11) DEFAULT NULL,
  `site` varchar(50) DEFAULT NULL COMMENT 'e.g. left arm, right deltoid',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_enrollments`
--

CREATE TABLE `soh_enrollments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `student_number` varchar(30) DEFAULT NULL,
  `programme` varchar(100) DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` enum('active','withdrawn','completed','deferred') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_lectures`
--

CREATE TABLE `soh_lectures` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecture_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `topic` varchar(200) DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `lecture_type` enum('lecture','practical','tutorial','seminar','online') DEFAULT 'lecture',
  `status` enum('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `attendance_taken` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_materials`
--

CREATE TABLE `soh_materials` (
  `id` int(11) NOT NULL,
  `item_code` varchar(40) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` enum('medication','dressing','equipment','consumable','vaccine','other') NOT NULL DEFAULT 'medication',
  `unit` varchar(30) NOT NULL DEFAULT 'units' COMMENT 'tablets, vials, boxes, pairs, etc.',
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT 10.00,
  `max_stock` decimal(10,2) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `location` varchar(80) DEFAULT NULL COMMENT 'Storage location / shelf reference',
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soh_materials`
--

INSERT INTO `soh_materials` (`id`, `item_code`, `item_name`, `category`, `unit`, `current_stock`, `reorder_level`, `max_stock`, `unit_cost`, `expiry_date`, `supplier`, `location`, `requires_prescription`, `is_active`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PARA500', 'Paracetamol 500mg', 'medication', 'tablets', '200.00', '50.00', '500.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(2, 'IBUP400', 'Ibuprofen 400mg', 'medication', 'tablets', '100.00', '30.00', '300.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(3, 'AMOX500', 'Amoxicillin 500mg', 'medication', 'capsules', '60.00', '20.00', '200.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(4, 'ORS001', 'ORS Sachets', 'medication', 'sachets', '50.00', '20.00', '100.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(5, 'GLOVES-M', 'Exam Gloves (Medium)', 'consumable', 'pairs', '100.00', '30.00', '200.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(6, 'BANDAGE-S', 'Crepe Bandage 7.5cm', 'dressing', 'rolls', '30.00', '10.00', '80.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(7, 'COTTON-100', 'Cotton Wool 100g', 'dressing', 'rolls', '20.00', '10.00', '50.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(8, 'THERMOM', 'Digital Thermometer', 'equipment', 'units', '5.00', '2.00', '10.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18'),
(9, 'BP-CUFF', 'BP Cuff / Sphygmomanometer', 'equipment', 'units', '3.00', '1.00', '5.00', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-08 07:57:18', '2026-03-08 07:57:18');

-- --------------------------------------------------------

--
-- Table structure for table `soh_material_transactions`
--

CREATE TABLE `soh_material_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `txn_type` enum('stock_in','dispensed','expired','damaged','adjustment','opening') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'Visit ref, supplier invoice, etc.',
  `patient_id` int(11) DEFAULT NULL COMMENT 'Set when dispensed to a patient',
  `batch_no` varchar(60) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_research`
--

CREATE TABLE `soh_research` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `student_number` varchar(30) DEFAULT NULL,
  `programme` varchar(100) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `research_type` enum('dissertation','thesis','project','case_study','research_paper') DEFAULT 'project',
  `status` enum('proposed','approved','in_progress','submitted','reviewed','completed') DEFAULT 'proposed',
  `start_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `supervisor_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_sessions`
--

CREATE TABLE `soh_sessions` (
  `id` int(11) NOT NULL,
  `visit_ref` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `chief_complaint` text NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Body temperature in Celsius',
  `blood_pressure` varchar(20) DEFAULT NULL COMMENT 'e.g. 120/80',
  `pulse_rate` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Beats per minute',
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `oxygen_saturation` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'SpO2 percentage',
  `diagnosis` text DEFAULT NULL,
  `icd_code` varchar(20) DEFAULT NULL COMMENT 'ICD-10 code (optional)',
  `treatment_given` text DEFAULT NULL,
  `medications_dispensed` text DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `sick_note_issued` tinyint(1) NOT NULL DEFAULT 0,
  `sick_note_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `sick_note_from` date DEFAULT NULL,
  `sick_note_to` date DEFAULT NULL,
  `referred_out` tinyint(1) NOT NULL DEFAULT 0,
  `disposition` enum('treated_discharged','admitted','referred','observation','follow_up','deceased') NOT NULL DEFAULT 'treated_discharged',
  `attended_by` int(11) DEFAULT NULL COMMENT 'Nurse or clinician user ID',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soh_students`
--

CREATE TABLE `soh_students` (
  `id` int(11) NOT NULL,
  `patient_type` enum('student','staff','visitor') NOT NULL DEFAULT 'student',
  `full_name` varchar(150) NOT NULL,
  `id_number` varchar(60) DEFAULT NULL COMMENT 'Student / Staff ID',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','unknown') NOT NULL DEFAULT 'unknown',
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','unknown') NOT NULL DEFAULT 'unknown',
  `programme` varchar(150) DEFAULT NULL COMMENT 'Programme name for students',
  `department` varchar(100) DEFAULT NULL COMMENT 'Department for staff',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `emergency_contact` varchar(150) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `special_notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `registered_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soh_students`
--

INSERT INTO `soh_students` (`id`, `patient_type`, `full_name`, `id_number`, `date_of_birth`, `gender`, `blood_group`, `programme`, `department`, `phone`, `email`, `emergency_contact`, `emergency_phone`, `allergies`, `chronic_conditions`, `special_notes`, `is_active`, `registered_by`, `created_at`, `updated_at`) VALUES
(1, 'student', 'testing demo', 'soh-219932', '2000-12-09', 'male', 'unknown', 'Diploma Registered Nursing', '', '+26077681282', 'lcs.demo@gmail.com', 'demo demo', '+260123456789', 'demo demo', 'demo demo', 'demo demo demo', 1, 42, '2026-03-13 23:13:20', '2026-03-13 23:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `sponsor_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `sponsorship_type` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT NULL,
  `agreement_document` varchar(300) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_requests`
--

CREATE TABLE `staff_leave_requests` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `staff_role` varchar(60) DEFAULT NULL,
  `dept_code` varchar(20) DEFAULT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `leave_type` enum('annual','sick','maternity','paternity','study','conference','unpaid','other') DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) DEFAULT 1,
  `reason` text NOT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_stored` varchar(255) DEFAULT NULL,
  `hod_status` enum('pending','approved','rejected','na') DEFAULT 'pending',
  `hod_reviewed_by` int(11) DEFAULT NULL,
  `hod_reviewed_at` datetime DEFAULT NULL,
  `hod_note` text DEFAULT NULL,
  `final_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `final_reviewed_by` int(11) DEFAULT NULL,
  `final_reviewed_at` datetime DEFAULT NULL,
  `final_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL DEFAULT '',
  `student_id` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `academic_year` year(4) DEFAULT NULL,
  `status` enum('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `programme` varchar(150) DEFAULT NULL,
  `semester` tinyint(4) DEFAULT 1,
  `fee_type` varchar(80) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Lanbridge College Management System', 'System name shown in header', '2026-03-09 02:44:07'),
(2, 'site_url', 'https://lanbridgecollegesystem.ct.ws/', 'Base URL of the system', '2026-03-09 02:44:07'),
(3, 'timezone', 'Africa/Lusaka', 'System timezone', '2026-03-07 21:03:18'),
(4, 'report_deadline_hour', '17', 'Hour (24h) by which daily reports must be submitted', '2026-03-07 21:03:18'),
(5, 'academic_year', '2025/2026', 'Current academic year', '2026-03-07 23:12:16'),
(6, 'maintenance_mode', '0', '1=maintenance mode on, 0=off', '2026-03-07 21:03:18'),
(7, 'college_name', 'Lanbridge College', 'Full legal name of the institution', '2026-03-07 23:12:16'),
(8, 'semester', '1', 'Current semester (1 or 2)', '2026-03-07 23:12:16'),
(9, 'currency', 'ZMW', 'Default currency code', '2026-03-07 23:12:16'),
(10, 'max_file_upload_mb', '10', 'Max file upload size in MB', '2026-03-07 23:12:16'),
(11, 'site_email', 'info@lanbridgecollegezambia.com', 'Primary contact email', '2026-03-07 23:12:16'),
(14, 'sla_critical_hours', '2', 'SLA deadline hours for critical IT tickets', '2026-03-07 23:34:05'),
(15, 'sla_high_hours', '8', 'SLA deadline hours for high priority tickets', '2026-03-07 23:34:05'),
(16, 'sla_medium_hours', '24', 'SLA deadline hours for medium priority tickets', '2026-03-07 23:34:05'),
(17, 'sla_low_hours', '72', 'SLA deadline hours for low priority tickets', '2026-03-07 23:34:05'),
(18, 'finance_fiscal_year', '2025', 'Current fiscal year', '2026-03-07 23:34:05'),
(19, 'poll_interval_ms', '30000', 'Real-time polling interval in milliseconds', '2026-03-07 23:34:05'),
(20, 'ai_insights_enabled', '1', 'Enable AI-style pattern detection', '2026-03-07 23:34:05'),
(21, 'fraud_threshold_days', '3', 'Flag users with identical reports for N days', '2026-03-07 23:34:05'),
(22, 'weekend_submissions', '0', 'Block weekend submissions (0=block, 1=allow)', '2026-03-07 23:34:05'),
(23, 'scorecard_auto_generate', '1', 'Auto-generate monthly performance scorecards', '2026-03-07 23:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requesting_user_id` int(11) NOT NULL,
  `requesting_dept_id` int(11) DEFAULT NULL,
  `assigned_dept_id` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `deadline` date DEFAULT NULL,
  `status` enum('open','in_progress','pending_approval','completed','cancelled','pending','overdue') NOT NULL DEFAULT 'open',
  `completion_note` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `type` enum('income','expense','transfer','refund') NOT NULL DEFAULT 'expense',
  `reference_no` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `transaction_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_no` varchar(100) DEFAULT NULL,
  `student_fee_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_login_attempt` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dept_code` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `push_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `password_hash`, `role_id`, `department_id`, `supervisor_id`, `phone`, `position`, `join_date`, `profile_photo`, `is_active`, `last_login`, `password_changed_at`, `password_reset_token`, `password_reset_expires`, `login_attempts`, `last_login_attempt`, `locked_until`, `force_password_change`, `created_at`, `updated_at`, `dept_code`, `avatar`, `push_token`) VALUES
(2, 'LC-002', 'Vice', 'Principal', 'dominicmukwe@gmail.com', '$2y$12$Nbk/I.Fw/nDz2/YjVE58V.mpDdnf9hLC9//tqyOoWyrJZ2pkkgtPm', 2, 1, NULL, '+260975212456', 'Vice Principal', '2020-01-01', NULL, 1, '2026-03-16 15:50:28', NULL, NULL, NULL, 0, NULL, NULL, 0, '2026-03-07 21:03:15', '2026-03-16 15:50:47', 'ACAD', NULL, 'ab0a37cd-a248-4132-ab39-37285f9077aa'),
(3, 'LC-003', 'Emmanuel', 'Chikunda', 'it@lanbridgecollegezambia.com', '$2y$12$MnTUWFaD2aeUPzEG.BJioucM4dt3Af0JcEZQNPt8IuqhNKqd5blBC', 3, 4, NULL, '+260972737979', 'IT Department Head', '2020-01-01', NULL, 1, '2026-03-12 21:41:58', '2026-03-11 14:12:44', NULL, NULL, 0, NULL, NULL, 0, '2026-03-07 21:03:15', '2026-03-14 19:34:43', 'IT', NULL, 'f4292452-88dd-4353-9f5f-55638395d5a5'),
(7, 'lc8866', 'emmanuel', 'chikunda', 'emmanuelchikunda@gmail.com', '$2y$12$sKoXWoOy6wGOU05woKsWZeyfWlI5jd0uVAQFvbzBoXfPfmWK9n.OK', 26, 4, NULL, '', '', '2026-03-08', NULL, 1, '2026-03-16 15:16:10', '2026-03-07 23:59:15', NULL, NULL, 0, NULL, NULL, 0, '2026-03-07 23:58:36', '2026-03-16 15:16:10', 'IT', NULL, 'f4292452-88dd-4353-9f5f-55638395d5a5'),
(9, 'Lcs123', 'Mary', 'Banda', 'mary@gmail.com', '$2y$12$lqnvRjC2qnoqtyM84UGUpuOW1J/Z2afEFFK5.mLnwp6uXeJCa1YIa', 18, 1, NULL, '', '', '2026-03-08', NULL, 1, '2026-03-16 09:50:11', '2026-03-08 18:10:57', NULL, NULL, 0, NULL, NULL, 0, '2026-03-08 17:15:31', '2026-03-16 09:50:11', 'ACAD', NULL, 'e72dffcf-2f13-4ac1-8d9f-70fed29a528d'),
(13, 'LC-001', 'CEO', 'Musumali', 'musumali22@gmail.com', '$2y$12$AeOg6rvK7ZaWj0c9cZ/WnOUJf3o5J6caVOsjkwcHm9BSeERkVsLte', 1, 2, NULL, '+260 96 9777305', 'Chief executive', '2026-03-08', NULL, 1, '2026-03-12 09:51:33', '2026-03-11 18:23:39', '0155d3bb19fbb8d68f7aadd4f732ecf75638e63da8b80491dd38fe6ac657cf1e', '2026-03-11 20:49:59', 0, NULL, NULL, 0, '2026-03-08 17:42:16', '2026-03-12 09:51:33', 'ADMIN', NULL, NULL),
(14, 'LC-456', 'CEO', 'Testing', 'ceo@gmail.com', '$2y$12$703uPtTz02xJ7o1FrreYzOomM5MDKos4u2BV9iZZYAJrYaVNA6Qq6', 1, 1, NULL, '', '', '2026-03-08', NULL, 1, '2026-03-17 06:03:54', '2026-03-08 19:01:10', NULL, NULL, 0, NULL, NULL, 0, '2026-03-08 18:59:54', '2026-03-17 06:03:54', 'ACAD', NULL, 'f4292452-88dd-4353-9f5f-55638395d5a5'),
(21, 'LC-007', 'Benjamin', 'Samba', 'bensamba64@gmail.com', '$2y$12$zh65sCpaCeKEg5ysNbrDtemOlVEDb2W7Xw/xjCR.2tmmqnFbXYlXa', 34, 7, NULL, '+26077681282', 'Admission Officer', '2026-03-10', NULL, 1, '2026-03-16 15:38:03', '2026-03-10 09:13:03', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 09:11:57', '2026-03-16 15:42:55', 'ADM', 'avatar_21_69b824a6abfa3.jpg', '9a43599a-3b9c-4beb-bed0-1269a39134f8'),
(22, 'LC-006', 'Emeldah', 'Samapaze', 'esamapaze@gmail.com', '$2y$12$tktJj/f2ROYmt9D2hP7fN.lwfej/D54XXfCdFXAibaOZNgJ9pRqwS', 18, 1, NULL, '+26069364535', 'Tutor', '2026-03-10', NULL, 1, '2026-03-16 11:47:41', '2026-03-10 09:39:33', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 09:28:49', '2026-03-16 11:47:41', 'ACAD', NULL, '44fa6c3e-2147-44b3-b256-bcc7c753e3db'),
(23, 'LC-008', 'Beverly', 'Witika', 'beverlynwitika@gmail.com', '$2y$12$bpkWT9spZHYXfnJyFNlIMeAN6SRL4baO0sFfDJPESJcwBYmESTNMi', 18, 1, NULL, '+260973531471', 'Tutor', '2026-03-10', NULL, 1, '2026-03-16 15:25:53', '2026-03-10 09:42:02', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 09:39:36', '2026-03-16 15:25:53', 'ACAD', NULL, NULL),
(24, 'LC-009', 'Erick', 'Ngoma', 'erickngoma97@gmail.com', '$2y$12$lt.uxfbgZjshA3j.wchxsea9cTWPBJRTIcBnFAQGnVrhQ74zauVFW', 33, 10, NULL, '+0979885438', 'Marketing Officer', '2026-03-10', NULL, 1, '2026-03-16 14:52:13', '2026-03-10 10:07:40', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 10:04:01', '2026-03-16 14:52:13', 'MKT', NULL, NULL),
(25, 'LC-045', 'jackson', 'Ulaya', 'jacksonulaya97@gmail.com', '$2y$12$7BzzXGDpbwor0XlH1N/btO9riFDSzg1x3bdR0FYskAm.W/DkV0Qje', 18, 1, NULL, '+260974414222', 'Lecturer - Agriculture', '2026-03-10', NULL, 1, '2026-03-16 15:33:11', '2026-03-10 10:36:16', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 10:32:37', '2026-03-16 15:47:46', 'ACAD', 'avatar_25_69b8248d1e6a9.jpeg', '2ce652d4-f997-45d0-b56f-8f1e2e823bbe'),
(26, 'LC-010', 'Lackson', 'Daka', 'lucksondaka419@gmail.com', '$2y$12$CpFiHhTQZctS8Z8wxxC5G.w4iSzl3GOpNeMUDcFloea1htsjn3gEO', 18, 1, NULL, '+260974796488', 'Lecturer - Agriculture', '2026-03-10', NULL, 1, '2026-03-16 12:04:27', '2026-03-10 10:54:28', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 10:44:47', '2026-03-16 12:04:27', 'ACAD', NULL, NULL),
(27, 'LC-011', 'Mandona', 'Njapau', 'mandonanjapau@gmail.com', '$2y$12$fFP6JKBWtVfpQn8vgrXt9e4nxK5icGjoMtz2glHbrQnW6yQjyRYp2', 23, 3, NULL, '+260778580778', 'Finance Officer', '2026-03-10', NULL, 1, '2026-03-11 14:14:20', '2026-03-11 14:06:44', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 11:41:01', '2026-03-11 14:14:20', 'FIN', NULL, NULL),
(28, 'LC-012', 'Simon', 'Kapalasha', 'kapalashas@yahoo.com', '$2y$12$uIcfugDwzNEmyYgc7fbY7Oj5s.8UbU.Sgp1Y2VQiHKz0RUsvo4rPu', 18, 1, NULL, '+260961824904', 'Lecturer - Education', '2026-03-10', NULL, 1, '2026-03-16 15:29:52', '2026-03-10 12:53:37', '7a113feb21b685fec563188666e7dfc7e6672221b78eea40dde5433a7e3c167f', '2026-03-11 16:55:45', 0, NULL, NULL, 0, '2026-03-10 12:48:04', '2026-03-16 15:30:32', 'ACAD', 'avatar_28_69b822185ab1c.jpg', NULL),
(29, 'LC-013', 'Mathews', 'Zgambo', 'zgambomathews87@gmail.com', '$2y$12$UzOhL2HzXQVgi5qp0sKdeOJEdDDMkJaUK4EgJ858YzY776NG8xzBS', 18, 1, NULL, '+260974005920', 'Lecturer - Nursing', '2026-03-10', NULL, 1, '2026-03-16 13:07:43', '2026-03-10 13:08:49', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 12:56:30', '2026-03-16 13:07:43', 'ACAD', 'avatar_29_69b018b90c44e.jpg', '171fbcb3-edd1-4049-bebd-76321adb61a1'),
(30, 'LC-014', 'Beatrice', 'Kasonde', 'kasondebeatrice25@gmail.com', '$2y$12$hgBEYQU282uLEzHG6GU5peWioQs8U63Oi5paVj.9Ef06zVqfVNBmO', 18, 1, NULL, '+0260970510730', 'Lecturer - Nursing', '2026-03-10', NULL, 1, '2026-03-16 20:03:42', '2026-03-10 13:37:09', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 13:34:34', '2026-03-16 20:03:42', 'ACAD', NULL, NULL),
(31, 'LC--015', 'Emmanuel', 'Tembo', 'principalphoenixreserchinstit@gmail.com', '$2y$12$Lq4b2hpVbMJRjXWL0Z0GtOAsFFpIKt9nZFCGd21D18UtNtjuUqMZG', 6, 2, NULL, '+260966275799', 'Principal', '2026-03-10', NULL, 1, '2026-03-10 15:11:24', '2026-03-10 15:14:27', NULL, NULL, 0, NULL, NULL, 0, '2026-03-10 15:05:35', '2026-03-10 15:14:27', 'ADMIN', NULL, NULL),
(34, 'LC-016', 'Marcus', 'Nchenesi', 'macusmicheal@gmail.com', '$2y$12$BjVYo6RP2GTxSY3l9HOzFeeoqtaAU.oWlfImPWbZnMdy/nSTgjYSK', 18, 1, NULL, '+260977229696', 'Lecturer - Busniess', '2026-03-11', NULL, 1, '2026-03-16 15:38:15', '2026-03-11 11:57:38', NULL, NULL, 0, NULL, NULL, 0, '2026-03-11 11:51:42', '2026-03-16 15:43:11', 'ACAD', 'avatar_34_69b8250ebd0cc.jpg', 'a0ba16c4-3d54-4ab8-8a88-fa09c5e5f0f6'),
(35, 'LC-017', 'Mary', 'Banda', 'marybanda1997@gmail.com', '$2y$12$2YH9lxX2GjjJKmmqdv8vb.Vg0FLhXERxMLtqMTfzQiXyGEebYSBti', 18, 1, NULL, '+260978795346', 'Lecturer - Nursing/Education', '2026-03-11', NULL, 1, '2026-03-16 15:38:37', '2026-03-11 12:07:02', NULL, NULL, 0, NULL, NULL, 0, '2026-03-11 12:02:10', '2026-03-16 15:39:54', 'ACAD', 'avatar_35_69b8244a410fc.jpg', '1c38977c-d317-4c99-8e9f-3b3446ebc218'),
(36, 'LC-020', 'Vincent', 'Mfune', 'vmfune98@gmail.com', '$2y$12$eZ0yMEze6QtYIB86rfP89ulxCK7Im347fn8HYd/YZdA0ZTLIxODj.', 15, 11, NULL, '+260979337327', 'Corporate Director', '2026-03-11', NULL, 1, '2026-03-16 13:27:34', '2026-03-11 12:18:16', NULL, NULL, 0, NULL, NULL, 0, '2026-03-11 12:14:31', '2026-03-16 13:28:04', 'CA', NULL, '41f8a0b7-d995-4af0-b430-42ed387074c3'),
(43, 'LC-021', 'Lenard', 'M Ilunga', 'ilungaleonardo@yahoo.com', '$2y$12$VbNa/2J3cmhvprWe8EVj/.WO.KRUzg64L0RUO2duMM1dm.VtASTpe', 17, 22, NULL, '+260 979 686 785', 'Senior Lecturer - Nursing', '2026-03-15', NULL, 1, '2026-03-15 20:51:30', '2026-03-15 20:51:50', NULL, NULL, 0, NULL, NULL, 1, '2026-03-15 20:50:31', '2026-03-16 14:52:38', 'NRS', NULL, '4eb916c3-0a4b-4c93-bc49-c2d3688222b4');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `record_table` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`, `department_id`, `old_value`, `new_value`, `record_table`, `record_id`) VALUES
(3, 3, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-07 21:53:27', NULL, NULL, NULL, NULL, NULL),
(4, 3, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-07 21:58:12', NULL, NULL, NULL, NULL, NULL),
(7, 2, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-07 21:59:48', NULL, NULL, NULL, NULL, NULL),
(8, 2, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-07 22:01:58', NULL, NULL, NULL, NULL, NULL),
(15, 3, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-07 23:22:22', NULL, NULL, NULL, NULL, NULL),
(16, 3, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-07 23:23:13', NULL, NULL, NULL, NULL, NULL),
(23, 7, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-07 23:58:59', NULL, NULL, NULL, NULL, NULL),
(24, 7, 'PASSWORD_CHANGE', 'Password updated successfully', '127.0.0.1', '2026-03-07 23:59:15', NULL, NULL, NULL, NULL, NULL),
(25, 7, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-08 00:01:31', NULL, NULL, NULL, NULL, NULL),
(26, 7, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-08 00:03:28', NULL, NULL, NULL, NULL, NULL),
(27, 7, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-08 00:08:47', NULL, NULL, NULL, NULL, NULL),
(28, 7, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-08 00:09:10', NULL, NULL, NULL, NULL, NULL),
(29, 7, 'IT_USER_CREATED', 'IT created user: tembo@gmail.com', '127.0.0.1', '2026-03-08 00:10:52', NULL, NULL, NULL, NULL, NULL),
(30, 7, 'LOGOUT', 'User logged out', '127.0.0.1', '2026-03-08 00:11:05', NULL, NULL, NULL, NULL, NULL),
(34, 7, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-08 00:21:10', NULL, NULL, NULL, NULL, NULL),
(35, 7, 'LOGOUT', 'Session ended: logout', '127.0.0.1', '2026-03-08 00:21:49', NULL, NULL, NULL, NULL, NULL),
(36, 7, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-08 00:22:26', NULL, NULL, NULL, NULL, NULL),
(37, 7, 'LOGOUT', 'Session ended: logout', '127.0.0.1', '2026-03-08 00:22:31', NULL, NULL, NULL, NULL, NULL),
(42, 2, 'LOGIN', 'User logged in from 127.0.0.1', '127.0.0.1', '2026-03-08 05:15:52', NULL, NULL, NULL, NULL, NULL),
(43, 2, 'LOGOUT', 'Session ended: logout', '127.0.0.1', '2026-03-08 10:43:57', NULL, NULL, NULL, NULL, NULL),
(44, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 11:00:52', NULL, NULL, NULL, NULL, NULL),
(45, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 11:20:56', NULL, NULL, NULL, NULL, NULL),
(46, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 12:03:59', NULL, NULL, NULL, NULL, NULL),
(47, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 12:34:13', NULL, NULL, NULL, NULL, NULL),
(48, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 12:35:02', NULL, NULL, NULL, NULL, NULL),
(49, 3, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 12:58:48', NULL, NULL, NULL, NULL, NULL),
(50, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 13:08:58', NULL, NULL, NULL, NULL, NULL),
(51, 3, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 13:09:11', NULL, NULL, NULL, NULL, NULL),
(52, 3, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 13:27:45', NULL, NULL, NULL, NULL, NULL),
(54, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 13:31:02', NULL, NULL, NULL, NULL, NULL),
(56, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 13:31:47', NULL, NULL, NULL, NULL, NULL),
(57, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 13:47:37', NULL, NULL, NULL, NULL, NULL),
(58, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 13:55:01', NULL, NULL, NULL, NULL, NULL),
(59, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:08:52', NULL, NULL, NULL, NULL, NULL),
(60, 3, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 14:09:39', NULL, NULL, NULL, NULL, NULL),
(61, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:10:07', NULL, NULL, NULL, NULL, NULL),
(62, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 14:10:44', NULL, NULL, NULL, NULL, NULL),
(65, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:19:56', NULL, NULL, NULL, NULL, NULL),
(66, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:34:48', NULL, NULL, NULL, NULL, NULL),
(67, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:40:47', NULL, NULL, NULL, NULL, NULL),
(68, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 14:53:41', NULL, NULL, NULL, NULL, NULL),
(69, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 14:54:58', NULL, NULL, NULL, NULL, NULL),
(70, 3, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:55:04', NULL, NULL, NULL, NULL, NULL),
(71, 3, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 14:55:22', NULL, NULL, NULL, NULL, NULL),
(76, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 14:58:04', NULL, NULL, NULL, NULL, NULL),
(77, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 15:01:34', NULL, NULL, NULL, NULL, NULL),
(78, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 15:01:57', NULL, NULL, NULL, NULL, NULL),
(79, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 15:02:07', NULL, NULL, NULL, NULL, NULL),
(81, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 15:04:02', NULL, NULL, NULL, NULL, NULL),
(84, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 15:38:10', NULL, NULL, NULL, NULL, NULL),
(86, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 16:44:15', NULL, NULL, NULL, NULL, NULL),
(87, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 17:06:18', NULL, NULL, NULL, NULL, NULL),
(89, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 5', '165.58.129.38', '2026-03-08 17:09:42', NULL, NULL, NULL, NULL, NULL),
(90, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 5', '165.58.129.38', '2026-03-08 17:09:51', NULL, NULL, NULL, NULL, NULL),
(91, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 8', '165.58.129.38', '2026-03-08 17:10:23', NULL, NULL, NULL, NULL, NULL),
(92, 7, 'IT_USER_CREATED', 'IT created user: mary@gmail.com', '165.58.129.38', '2026-03-08 17:15:31', NULL, NULL, NULL, NULL, NULL),
(93, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 17:17:00', NULL, NULL, NULL, NULL, NULL),
(94, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 17:17:15', NULL, NULL, NULL, NULL, NULL),
(95, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 17:20:28', NULL, NULL, NULL, NULL, NULL),
(99, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 17:31:22', NULL, NULL, NULL, NULL, NULL),
(100, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 1', '165.58.129.38', '2026-03-08 17:31:56', NULL, NULL, NULL, NULL, NULL),
(101, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 8', '165.58.129.38', '2026-03-08 17:32:32', NULL, NULL, NULL, NULL, NULL),
(102, 7, 'IT_USER_CREATED', 'IT created user: musumali@gmail.com', '165.58.129.38', '2026-03-08 17:34:14', NULL, NULL, NULL, NULL, NULL),
(105, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 17:37:06', NULL, NULL, NULL, NULL, NULL),
(106, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 17:37:18', NULL, NULL, NULL, NULL, NULL),
(108, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 1', '165.58.129.38', '2026-03-08 17:37:53', NULL, NULL, NULL, NULL, NULL),
(109, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 12', '165.58.129.38', '2026-03-08 17:38:16', NULL, NULL, NULL, NULL, NULL),
(110, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 6', '165.58.129.38', '2026-03-08 17:38:31', NULL, NULL, NULL, NULL, NULL),
(111, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 4', '165.58.129.38', '2026-03-08 17:38:41', NULL, NULL, NULL, NULL, NULL),
(112, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 12', '165.58.129.38', '2026-03-08 17:39:14', NULL, NULL, NULL, NULL, NULL),
(113, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 6', '165.58.129.38', '2026-03-08 17:39:23', NULL, NULL, NULL, NULL, NULL),
(114, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 4', '165.58.129.38', '2026-03-08 17:39:33', NULL, NULL, NULL, NULL, NULL),
(115, 7, 'IT_USER_CREATED', 'IT created user: musumali22@gmail.com', '165.58.129.38', '2026-03-08 17:42:16', NULL, NULL, NULL, NULL, NULL),
(116, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 9', '165.58.129.38', '2026-03-08 18:09:27', NULL, NULL, NULL, NULL, NULL),
(117, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 18:09:45', NULL, NULL, NULL, NULL, NULL),
(118, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 18:10:06', NULL, NULL, NULL, NULL, NULL),
(119, 9, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.38', '2026-03-08 18:10:57', NULL, NULL, NULL, NULL, NULL),
(120, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 18:12:30', NULL, NULL, NULL, NULL, NULL),
(121, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 18:57:34', NULL, NULL, NULL, NULL, NULL),
(122, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 18:58:28', NULL, NULL, NULL, NULL, NULL),
(123, 7, 'IT_USER_CREATED', 'IT created user: ceo@gmail.com', '165.58.129.38', '2026-03-08 18:59:54', NULL, NULL, NULL, NULL, NULL),
(124, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 19:00:18', NULL, NULL, NULL, NULL, NULL),
(125, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 19:00:33', NULL, NULL, NULL, NULL, NULL),
(126, 14, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.38', '2026-03-08 19:01:10', NULL, NULL, NULL, NULL, NULL),
(127, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-08 23:55:40', NULL, NULL, NULL, NULL, NULL),
(128, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-08 23:57:14', NULL, NULL, NULL, NULL, NULL),
(129, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 00:00:00', NULL, NULL, NULL, NULL, NULL),
(130, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 00:00:04', NULL, NULL, NULL, NULL, NULL),
(131, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 00:00:36', NULL, NULL, NULL, NULL, NULL),
(132, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 00:00:41', NULL, NULL, NULL, NULL, NULL),
(133, 14, 'USER_CREATED', 'Created user: principal@gmail.com', '165.58.129.38', '2026-03-09 00:13:13', NULL, NULL, NULL, NULL, NULL),
(134, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 00:13:46', NULL, NULL, NULL, NULL, NULL),
(137, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 00:33:59', NULL, NULL, NULL, NULL, NULL),
(140, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 00:46:23', NULL, NULL, NULL, NULL, NULL),
(142, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 00:55:06', NULL, NULL, NULL, NULL, NULL),
(143, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 02:25:41', NULL, NULL, NULL, NULL, NULL),
(144, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 02:26:24', NULL, NULL, NULL, NULL, NULL),
(145, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 02:33:52', NULL, NULL, NULL, NULL, NULL),
(146, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 02:33:57', NULL, NULL, NULL, NULL, NULL),
(147, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 03:57:25', NULL, NULL, NULL, NULL, NULL),
(148, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 03:57:30', NULL, NULL, NULL, NULL, NULL),
(149, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 03:58:21', NULL, NULL, NULL, NULL, NULL),
(150, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 03:58:41', NULL, NULL, NULL, NULL, NULL),
(151, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 03:59:26', NULL, NULL, NULL, NULL, NULL),
(152, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 03:59:56', NULL, NULL, NULL, NULL, NULL),
(153, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 04:08:47', NULL, NULL, NULL, NULL, NULL),
(154, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 04:09:05', NULL, NULL, NULL, NULL, NULL),
(155, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 04:43:29', NULL, NULL, NULL, NULL, NULL),
(156, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 04:43:41', NULL, NULL, NULL, NULL, NULL),
(157, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 04:46:15', NULL, NULL, NULL, NULL, NULL),
(158, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 04:46:25', NULL, NULL, NULL, NULL, NULL),
(159, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 05:56:10', NULL, NULL, NULL, NULL, NULL),
(160, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 05:56:13', NULL, NULL, NULL, NULL, NULL),
(161, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 05:57:55', NULL, NULL, NULL, NULL, NULL),
(162, 9, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 05:58:05', NULL, NULL, NULL, NULL, NULL),
(163, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 05:59:08', NULL, NULL, NULL, NULL, NULL),
(164, 2, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 05:59:38', NULL, NULL, NULL, NULL, NULL),
(165, 9, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 06:00:35', NULL, NULL, NULL, NULL, NULL),
(166, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 06:00:46', NULL, NULL, NULL, NULL, NULL),
(167, 2, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 06:01:16', NULL, NULL, NULL, NULL, NULL),
(168, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 06:01:21', NULL, NULL, NULL, NULL, NULL),
(169, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 06:36:14', NULL, NULL, NULL, NULL, NULL),
(172, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 06:39:05', NULL, NULL, NULL, NULL, NULL),
(173, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 06:40:43', NULL, NULL, NULL, NULL, NULL),
(174, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 06:40:54', NULL, NULL, NULL, NULL, NULL),
(175, 14, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 07:03:59', NULL, NULL, NULL, NULL, NULL),
(176, 7, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 07:04:04', NULL, NULL, NULL, NULL, NULL),
(177, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 07:13:53', NULL, NULL, NULL, NULL, NULL),
(178, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 07:13:57', NULL, NULL, NULL, NULL, NULL),
(179, 7, 'LOGOUT', 'User logged out', '165.58.129.38', '2026-03-09 07:32:40', NULL, NULL, NULL, NULL, NULL),
(180, 14, 'LOGIN', 'User logged in from 165.58.129.38', '165.58.129.38', '2026-03-09 07:32:53', NULL, NULL, NULL, NULL, NULL),
(181, 14, 'LOGOUT', 'User logged out', '165.58.129.204', '2026-03-09 14:36:24', NULL, NULL, NULL, NULL, NULL),
(182, 14, 'LOGIN', 'User logged in from 165.58.129.204', '165.58.129.204', '2026-03-09 16:12:17', NULL, NULL, NULL, NULL, NULL),
(183, 13, 'LOGIN', 'User logged in from 154.117.182.250', '154.117.182.250', '2026-03-09 18:21:34', NULL, NULL, NULL, NULL, NULL),
(184, 13, 'PASSWORD_CHANGE', 'Password updated successfully', '154.117.182.250', '2026-03-09 18:22:24', NULL, NULL, NULL, NULL, NULL),
(185, 14, 'LOGOUT', 'User logged out', '165.58.129.204', '2026-03-09 18:40:33', NULL, NULL, NULL, NULL, NULL),
(186, 14, 'LOGIN', 'User logged in from 165.58.129.204', '165.58.129.204', '2026-03-09 18:52:24', NULL, NULL, NULL, NULL, NULL),
(187, 14, 'LOGOUT', 'User logged out', '165.58.129.40', '2026-03-09 19:08:31', NULL, NULL, NULL, NULL, NULL),
(188, 7, 'LOGIN', 'User logged in from 165.58.129.40', '165.58.129.40', '2026-03-09 19:08:37', NULL, NULL, NULL, NULL, NULL),
(189, 13, 'PASSWORD_RESET_REQUEST', 'Reset requested for musumali22@gmail.com', '154.117.182.250', '2026-03-09 20:41:06', NULL, NULL, NULL, NULL, NULL),
(190, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 03:56:29', NULL, NULL, NULL, NULL, NULL),
(191, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 03:56:48', NULL, NULL, NULL, NULL, NULL),
(192, 7, 'IT_USER_CREATED', 'IT created user: benjamin@gmail.com', '165.58.129.72', '2026-03-10 03:58:07', NULL, NULL, NULL, NULL, NULL),
(193, 14, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 03:58:39', NULL, NULL, NULL, NULL, NULL),
(196, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 04:00:35', NULL, NULL, NULL, NULL, NULL),
(199, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 04:21:51', NULL, NULL, NULL, NULL, NULL),
(200, 7, 'IT_USER_CREATED', 'IT created user: marketing@gmail.com', '165.58.129.72', '2026-03-10 04:22:45', NULL, NULL, NULL, NULL, NULL),
(204, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 04:24:24', NULL, NULL, NULL, NULL, NULL),
(207, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 04:33:17', NULL, NULL, NULL, NULL, NULL),
(208, 7, 'IT_USER_CREATED', 'IT created user: corp@gmail.com', '165.58.129.72', '2026-03-10 04:34:41', NULL, NULL, NULL, NULL, NULL),
(212, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 04:36:26', NULL, NULL, NULL, NULL, NULL),
(214, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 04:41:21', NULL, NULL, NULL, NULL, NULL),
(215, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 18', '165.58.129.72', '2026-03-10 04:59:42', NULL, NULL, NULL, NULL, NULL),
(216, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 18', '165.58.129.72', '2026-03-10 04:59:56', NULL, NULL, NULL, NULL, NULL),
(218, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 05:00:22', NULL, NULL, NULL, NULL, NULL),
(220, 7, 'LOGIN', 'User logged in from 45.215.249.92', '45.215.249.92', '2026-03-10 06:11:11', NULL, NULL, NULL, NULL, NULL),
(221, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 08:22:14', NULL, NULL, NULL, NULL, NULL),
(222, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 08:22:18', NULL, NULL, NULL, NULL, NULL),
(223, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 13', '165.58.129.72', '2026-03-10 08:22:33', NULL, NULL, NULL, NULL, NULL),
(224, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 17', '165.58.129.72', '2026-03-10 08:52:09', NULL, NULL, NULL, NULL, NULL),
(225, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 17', '165.58.129.72', '2026-03-10 08:52:17', NULL, NULL, NULL, NULL, NULL),
(226, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 16', '165.58.129.72', '2026-03-10 08:52:31', NULL, NULL, NULL, NULL, NULL),
(227, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 16', '165.58.129.72', '2026-03-10 08:52:41', NULL, NULL, NULL, NULL, NULL),
(228, 7, 'IT_USER_CREATED', 'IT created user: bensamba64@gmail.com', '165.58.129.72', '2026-03-10 08:57:17', NULL, NULL, NULL, NULL, NULL),
(232, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 20', '165.58.129.72', '2026-03-10 09:10:56', NULL, NULL, NULL, NULL, NULL),
(233, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 20', '165.58.129.72', '2026-03-10 09:11:09', NULL, NULL, NULL, NULL, NULL),
(234, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 20', '165.58.129.72', '2026-03-10 09:11:22', NULL, NULL, NULL, NULL, NULL),
(235, 7, 'IT_USER_CREATED', 'IT created user: bensamba64@gmail.com', '165.58.129.72', '2026-03-10 09:11:57', NULL, NULL, NULL, NULL, NULL),
(236, 21, 'LOGIN', 'User logged in from 165.58.129.198', '165.58.129.198', '2026-03-10 09:12:26', NULL, NULL, NULL, NULL, NULL),
(237, 21, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.198', '2026-03-10 09:13:03', NULL, NULL, NULL, NULL, NULL),
(238, 7, 'IT_USER_CREATED', 'IT created user: esamapaze@gmail.com', '165.58.129.72', '2026-03-10 09:28:49', NULL, NULL, NULL, NULL, NULL),
(239, 22, 'LOGIN', 'User logged in from 45.215.255.214', '45.215.255.214', '2026-03-10 09:32:04', NULL, NULL, NULL, NULL, NULL),
(240, 22, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.72', '2026-03-10 09:39:33', NULL, NULL, NULL, NULL, NULL),
(241, 7, 'IT_USER_CREATED', 'IT created user: beverlynwitika@gmail.com', '165.58.129.72', '2026-03-10 09:39:36', NULL, NULL, NULL, NULL, NULL),
(242, 23, 'LOGIN', 'User logged in from 41.216.86.41', '41.216.86.41', '2026-03-10 09:40:52', NULL, NULL, NULL, NULL, NULL),
(243, 23, 'PASSWORD_CHANGE', 'Password updated successfully', '41.216.86.41', '2026-03-10 09:42:02', NULL, NULL, NULL, NULL, NULL),
(244, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 09:43:22', NULL, NULL, NULL, NULL, NULL),
(245, 9, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 09:43:28', NULL, NULL, NULL, NULL, NULL),
(246, 7, 'IT_USER_CREATED', 'IT created user: erickngoma97@gmail.com', '165.58.129.72', '2026-03-10 10:04:01', NULL, NULL, NULL, NULL, NULL),
(247, 24, 'LOGIN', 'User logged in from 41.223.117.41', '41.223.117.41', '2026-03-10 10:06:36', NULL, NULL, NULL, NULL, NULL),
(248, 24, 'PASSWORD_CHANGE', 'Password updated successfully', '41.223.117.41', '2026-03-10 10:07:40', NULL, NULL, NULL, NULL, NULL),
(249, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 10:19:48', NULL, NULL, NULL, NULL, NULL),
(250, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 10:20:12', NULL, NULL, NULL, NULL, NULL),
(251, 7, 'IT_USER_CREATED', 'IT created user: jacksonulaya97@gmail.com', '165.58.129.72', '2026-03-10 10:32:37', NULL, NULL, NULL, NULL, NULL),
(252, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-10 10:33:28', NULL, NULL, NULL, NULL, NULL),
(253, 25, 'PASSWORD_CHANGE', 'Password updated successfully', '41.223.118.39', '2026-03-10 10:36:16', NULL, NULL, NULL, NULL, NULL),
(254, 7, 'IT_USER_CREATED', 'IT created user: lucksondaka419@gmail.com', '165.58.129.72', '2026-03-10 10:44:47', NULL, NULL, NULL, NULL, NULL),
(255, 26, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 10:50:15', NULL, NULL, NULL, NULL, NULL),
(256, 26, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.72', '2026-03-10 10:54:28', NULL, NULL, NULL, NULL, NULL),
(257, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-10 11:15:28', NULL, NULL, NULL, NULL, NULL),
(258, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 19', '41.223.118.39', '2026-03-10 11:24:09', NULL, NULL, NULL, NULL, NULL),
(259, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 20', '41.223.118.39', '2026-03-10 11:29:46', NULL, NULL, NULL, NULL, NULL),
(260, 7, 'IT_USER_CREATED', 'IT created user: mandonanjapau@gmail.com', '102.212.181.43', '2026-03-10 11:41:01', NULL, NULL, NULL, NULL, NULL),
(261, 27, 'LOGIN', 'User logged in from 102.212.181.43', '102.212.181.43', '2026-03-10 11:42:39', NULL, NULL, NULL, NULL, NULL),
(262, 7, 'LOGOUT', 'User logged out', '45.215.249.92', '2026-03-10 11:43:41', NULL, NULL, NULL, NULL, NULL),
(263, 14, 'LOGIN', 'User logged in from 45.215.249.92', '45.215.249.92', '2026-03-10 11:43:59', NULL, NULL, NULL, NULL, NULL),
(264, 27, 'PASSWORD_CHANGE', 'Password updated successfully', '102.212.181.43', '2026-03-10 11:44:27', NULL, NULL, NULL, NULL, NULL),
(265, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 27', '102.212.181.43', '2026-03-10 11:47:06', NULL, NULL, NULL, NULL, NULL),
(266, 27, 'PASSWORD_CHANGE', 'Password updated successfully', '102.212.181.43', '2026-03-10 11:47:46', NULL, NULL, NULL, NULL, NULL),
(267, 27, 'LOGIN', 'User logged in from 102.212.181.43', '102.212.181.43', '2026-03-10 11:49:48', NULL, NULL, NULL, NULL, NULL),
(268, 22, 'LOGIN', 'User logged in from 41.223.117.46', '41.223.117.46', '2026-03-10 12:18:58', NULL, NULL, NULL, NULL, NULL),
(269, 21, 'LOGOUT', 'User logged out', '165.56.186.12', '2026-03-10 12:30:13', NULL, NULL, NULL, NULL, NULL),
(270, 21, 'LOGIN', 'User logged in from 165.56.186.12', '165.56.186.12', '2026-03-10 12:30:21', NULL, NULL, NULL, NULL, NULL),
(271, 13, 'LOGIN', 'User logged in from 154.117.182.250', '154.117.182.250', '2026-03-10 12:44:54', NULL, NULL, NULL, NULL, NULL),
(272, 7, 'IT_USER_CREATED', 'IT created user: kapalashas@yahoo.com', '165.58.129.72', '2026-03-10 12:48:04', NULL, NULL, NULL, NULL, NULL),
(273, 28, 'LOGIN', 'User logged in from 41.216.95.237', '41.216.95.237', '2026-03-10 12:50:34', NULL, NULL, NULL, NULL, NULL),
(274, 28, 'PASSWORD_CHANGE', 'Password updated successfully', '41.216.95.237', '2026-03-10 12:53:37', NULL, NULL, NULL, NULL, NULL),
(275, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.216.82.17', '2026-03-10 12:55:44', NULL, NULL, NULL, NULL, NULL),
(276, 7, 'IT_USER_CREATED', 'IT created user: zgambomathews87@gmail.com', '165.58.129.72', '2026-03-10 12:56:30', NULL, NULL, NULL, NULL, NULL),
(277, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 13', '165.58.129.72', '2026-03-10 13:01:25', NULL, NULL, NULL, NULL, NULL),
(278, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 29', '165.58.129.72', '2026-03-10 13:03:20', NULL, NULL, NULL, NULL, NULL),
(279, 29, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 13:04:00', NULL, NULL, NULL, NULL, NULL),
(280, 23, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.216.82.18', '2026-03-10 13:04:06', NULL, NULL, NULL, NULL, NULL),
(281, 13, 'PASSWORD_CHANGE', 'Password updated successfully', '154.117.182.250', '2026-03-10 13:04:12', NULL, NULL, NULL, NULL, NULL),
(282, 29, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.72', '2026-03-10 13:08:49', NULL, NULL, NULL, NULL, NULL),
(283, 27, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 13:17:50', NULL, NULL, NULL, NULL, NULL),
(284, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 13:22:18', NULL, NULL, NULL, NULL, NULL),
(285, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 13:24:51', NULL, NULL, NULL, NULL, NULL),
(286, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-10 13:25:09', NULL, NULL, NULL, NULL, NULL),
(287, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 21', '41.223.118.39', '2026-03-10 13:28:52', NULL, NULL, NULL, NULL, NULL),
(288, 14, 'LOGOUT', 'User logged out', '45.215.249.92', '2026-03-10 13:33:05', NULL, NULL, NULL, NULL, NULL),
(289, 7, 'LOGIN', 'User logged in from 45.215.249.92', '45.215.249.92', '2026-03-10 13:33:12', NULL, NULL, NULL, NULL, NULL),
(290, 7, 'IT_USER_CREATED', 'IT created user: kasondebeatrice25@gmail.com', '45.215.249.92', '2026-03-10 13:34:34', NULL, NULL, NULL, NULL, NULL),
(291, 30, 'LOGIN', 'User logged in from 41.216.82.22', '41.216.82.22', '2026-03-10 13:35:55', NULL, NULL, NULL, NULL, NULL),
(292, 30, 'PASSWORD_CHANGE', 'Password updated successfully', '41.216.82.22', '2026-03-10 13:37:09', NULL, NULL, NULL, NULL, NULL),
(293, 28, 'LOGIN', 'User logged in from 41.216.95.237', '41.216.95.237', '2026-03-10 13:39:57', NULL, NULL, NULL, NULL, NULL),
(294, 29, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '165.58.129.72', '2026-03-10 13:44:50', NULL, NULL, NULL, NULL, NULL),
(295, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 2', '197.212.134.29', '2026-03-10 13:45:57', NULL, NULL, NULL, NULL, NULL),
(296, 30, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '41.216.82.22', '2026-03-10 13:52:20', NULL, NULL, NULL, NULL, NULL),
(297, 24, 'LOGOUT', 'User logged out', '41.216.86.46', '2026-03-10 14:00:49', NULL, NULL, NULL, NULL, NULL),
(298, 24, 'LOGIN', 'User logged in from 41.216.86.46', '41.216.86.46', '2026-03-10 14:01:04', NULL, NULL, NULL, NULL, NULL),
(299, 24, 'REPORT_SUBMIT', 'KPI submission for category ID 4', '41.216.86.46', '2026-03-10 14:02:58', NULL, NULL, NULL, NULL, NULL),
(300, 28, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.216.95.237', '2026-03-10 14:08:51', NULL, NULL, NULL, NULL, NULL),
(301, 21, 'REPORT_SUBMIT', 'KPI submission for category ID 23', '165.57.81.140', '2026-03-10 14:15:29', NULL, NULL, NULL, NULL, NULL),
(302, 27, 'LOGIN', 'User logged in from 102.212.181.43', '102.212.181.43', '2026-03-10 14:38:11', NULL, NULL, NULL, NULL, NULL),
(303, 9, 'LOGOUT', 'User logged out', '45.215.249.92', '2026-03-10 15:01:46', NULL, NULL, NULL, NULL, NULL),
(304, 7, 'LOGIN', 'User logged in from 45.215.249.92', '45.215.249.92', '2026-03-10 15:01:53', NULL, NULL, NULL, NULL, NULL),
(305, 7, 'IT_USER_CREATED', 'IT created user: principalphoenixreserchinstit@gmail.com', '45.215.249.92', '2026-03-10 15:05:35', NULL, NULL, NULL, NULL, NULL),
(306, 31, 'LOGIN', 'User logged in from 45.215.237.182', '45.215.237.182', '2026-03-10 15:11:24', NULL, NULL, NULL, NULL, NULL),
(307, 31, 'PASSWORD_CHANGE', 'Password updated successfully', '45.215.237.182', '2026-03-10 15:14:27', NULL, NULL, NULL, NULL, NULL),
(308, 28, 'LOGOUT', 'User logged out', '41.216.95.237', '2026-03-10 15:21:22', NULL, NULL, NULL, NULL, NULL),
(309, 27, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 16:15:53', NULL, NULL, NULL, NULL, NULL),
(310, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:16:06', NULL, NULL, NULL, NULL, NULL),
(311, 14, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 16:19:06', NULL, NULL, NULL, NULL, NULL),
(312, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:19:21', NULL, NULL, NULL, NULL, NULL),
(313, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 16:19:37', NULL, NULL, NULL, NULL, NULL),
(314, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:19:41', NULL, NULL, NULL, NULL, NULL),
(315, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 16:19:53', NULL, NULL, NULL, NULL, NULL),
(316, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:19:58', NULL, NULL, NULL, NULL, NULL),
(317, 24, 'LOGOUT', 'User logged out', '102.145.114.154', '2026-03-10 16:29:36', NULL, NULL, NULL, NULL, NULL),
(318, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 16:48:31', NULL, NULL, NULL, NULL, NULL),
(319, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:48:36', NULL, NULL, NULL, NULL, NULL),
(320, 9, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:50:31', NULL, NULL, NULL, NULL, NULL),
(321, 9, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 16:52:33', NULL, NULL, NULL, NULL, NULL),
(322, 9, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 17:13:21', NULL, NULL, NULL, NULL, NULL),
(323, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 17:13:51', NULL, NULL, NULL, NULL, NULL),
(324, 21, 'LOGOUT', 'User logged out', '165.58.129.21', '2026-03-10 18:24:46', NULL, NULL, NULL, NULL, NULL),
(325, 21, 'LOGIN', 'User logged in from 165.58.129.21', '165.58.129.21', '2026-03-10 18:24:54', NULL, NULL, NULL, NULL, NULL),
(326, 22, 'LOGOUT', 'User logged out', '45.215.237.97', '2026-03-10 18:25:52', NULL, NULL, NULL, NULL, NULL),
(327, 22, 'LOGOUT', 'User logged out', '45.215.237.97', '2026-03-10 18:25:52', NULL, NULL, NULL, NULL, NULL),
(328, 22, 'LOGIN', 'User logged in from 45.215.237.97', '45.215.237.97', '2026-03-10 18:26:32', NULL, NULL, NULL, NULL, NULL),
(329, 31, 'LOGOUT', 'User logged out', '45.215.236.77', '2026-03-10 18:32:35', NULL, NULL, NULL, NULL, NULL),
(330, 29, 'LOGOUT', 'User logged out', '102.147.42.194', '2026-03-10 18:45:12', NULL, NULL, NULL, NULL, NULL),
(331, 29, 'LOGIN', 'User logged in from 102.147.42.194', '102.147.42.194', '2026-03-10 18:45:54', NULL, NULL, NULL, NULL, NULL),
(332, 28, 'LOGOUT', 'User logged out', '41.216.95.237', '2026-03-10 18:46:11', NULL, NULL, NULL, NULL, NULL),
(333, 28, 'LOGOUT', 'User logged out', '41.216.95.237', '2026-03-10 18:46:11', NULL, NULL, NULL, NULL, NULL),
(334, 28, 'LOGOUT', 'User logged out', '41.216.95.237', '2026-03-10 18:46:11', NULL, NULL, NULL, NULL, NULL),
(335, 28, 'LOGIN', 'User logged in from 41.216.95.237', '41.216.95.237', '2026-03-10 18:54:10', NULL, NULL, NULL, NULL, NULL),
(336, 13, 'LOGIN', 'User logged in from 154.117.164.154', '154.117.164.154', '2026-03-10 19:01:00', NULL, NULL, NULL, NULL, NULL),
(337, 13, 'KPI_REJECTED', 'KPI ID 22 rejected', '154.117.164.154', '2026-03-10 19:03:10', NULL, NULL, NULL, NULL, NULL),
(338, 13, 'KPI_REJECTED', 'KPI ID 14 rejected', '154.117.164.154', '2026-03-10 19:04:22', NULL, NULL, NULL, NULL, NULL),
(339, 13, 'KPI_REJECTED', 'KPI ID 14 rejected', '154.117.164.154', '2026-03-10 19:04:27', NULL, NULL, NULL, NULL, NULL),
(340, 13, 'KPI_APPROVED', 'KPI ID 21 approved', '154.117.164.154', '2026-03-10 19:05:34', NULL, NULL, NULL, NULL, NULL),
(341, 13, 'KPI_APPROVED', 'KPI ID 21 approved', '154.117.164.154', '2026-03-10 19:05:40', NULL, NULL, NULL, NULL, NULL),
(342, 13, 'KPI_APPROVED', 'KPI ID 19 approved', '154.117.164.154', '2026-03-10 19:07:20', NULL, NULL, NULL, NULL, NULL),
(343, 13, 'KPI_REJECTED', 'KPI ID 20 rejected', '154.117.164.154', '2026-03-10 19:07:53', NULL, NULL, NULL, NULL, NULL),
(344, 14, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 20:11:14', NULL, NULL, NULL, NULL, NULL),
(345, 14, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 21:07:18', NULL, NULL, NULL, NULL, NULL),
(346, 9, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 21:07:33', NULL, NULL, NULL, NULL, NULL),
(347, 9, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-10 21:18:04', NULL, NULL, NULL, NULL, NULL),
(348, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-10 21:18:11', NULL, NULL, NULL, NULL, NULL),
(349, 7, 'IT_USER_CREATED', 'IT created user: corp@gmail.com', '165.58.129.72', '2026-03-10 21:20:22', NULL, NULL, NULL, NULL, NULL),
(352, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-11 04:19:20', NULL, NULL, NULL, NULL, NULL),
(353, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-11 04:19:25', NULL, NULL, NULL, NULL, NULL),
(354, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 32', '165.58.129.72', '2026-03-11 04:20:14', NULL, NULL, NULL, NULL, NULL),
(355, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 32', '165.58.129.72', '2026-03-11 04:20:24', NULL, NULL, NULL, NULL, NULL),
(356, 7, 'IT_USER_CREATED', 'IT created user: corptesting@gmail.com', '165.58.129.72', '2026-03-11 04:25:08', NULL, NULL, NULL, NULL, NULL),
(358, 7, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-11 04:29:39', NULL, NULL, NULL, NULL, NULL),
(359, 7, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-11 04:29:54', NULL, NULL, NULL, NULL, NULL),
(360, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 33', '165.58.129.72', '2026-03-11 04:30:20', NULL, NULL, NULL, NULL, NULL),
(363, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-11 05:14:35', NULL, NULL, NULL, NULL, NULL),
(366, 9, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-11 06:34:59', NULL, NULL, NULL, NULL, NULL),
(367, 9, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-11 06:51:16', NULL, NULL, NULL, NULL, NULL),
(368, 14, 'LOGIN', 'User logged in from 165.58.129.72', '165.58.129.72', '2026-03-11 06:52:04', NULL, NULL, NULL, NULL, NULL),
(369, 14, 'LOGOUT', 'User logged out', '165.58.129.72', '2026-03-11 06:55:16', NULL, NULL, NULL, NULL, NULL),
(370, 22, 'LOGOUT', 'User logged out', '45.215.237.151', '2026-03-11 07:51:34', NULL, NULL, NULL, NULL, NULL),
(371, 21, 'LOGOUT', 'User logged out', '165.58.129.225', '2026-03-11 08:10:23', NULL, NULL, NULL, NULL, NULL),
(372, 21, 'LOGIN', 'User logged in from 165.58.129.225', '165.58.129.225', '2026-03-11 08:10:37', NULL, NULL, NULL, NULL, NULL),
(373, 7, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 10:12:17', NULL, NULL, NULL, NULL, NULL),
(374, 9, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 10:12:24', NULL, NULL, NULL, NULL, NULL),
(375, 9, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 10:29:26', NULL, NULL, NULL, NULL, NULL),
(376, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-11 10:49:46', NULL, NULL, NULL, NULL, NULL),
(377, 25, 'LOGIN', 'User logged in from 41.223.118.39', '41.223.118.39', '2026-03-11 10:52:17', NULL, NULL, NULL, NULL, NULL),
(378, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '41.223.118.39', '2026-03-11 11:00:54', NULL, NULL, NULL, NULL, NULL),
(379, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 21', '41.223.118.39', '2026-03-11 11:10:04', NULL, NULL, NULL, NULL, NULL),
(380, 29, 'LOGOUT', 'User logged out', '45.215.237.194', '2026-03-11 11:12:11', NULL, NULL, NULL, NULL, NULL),
(381, 29, 'LOGIN', 'User logged in from 45.215.237.194', '45.215.237.194', '2026-03-11 11:12:17', NULL, NULL, NULL, NULL, NULL),
(382, 30, 'LOGOUT', 'User logged out', '102.212.183.41', '2026-03-11 11:13:26', NULL, NULL, NULL, NULL, NULL),
(383, 30, 'LOGIN', 'User logged in from 102.212.183.41', '102.212.183.41', '2026-03-11 11:13:55', NULL, NULL, NULL, NULL, NULL),
(384, 23, 'LOGIN', 'User logged in from 41.216.86.45', '41.216.86.45', '2026-03-11 11:15:34', NULL, NULL, NULL, NULL, NULL),
(385, 22, 'LOGIN', 'User logged in from 45.215.237.240', '45.215.237.240', '2026-03-11 11:18:38', NULL, NULL, NULL, NULL, NULL),
(386, 21, 'LOGOUT', 'User logged out', '165.58.129.190', '2026-03-11 11:20:52', NULL, NULL, NULL, NULL, NULL),
(387, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 24', '41.223.118.39', '2026-03-11 11:20:54', NULL, NULL, NULL, NULL, NULL),
(388, 23, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.216.86.45', '2026-03-11 11:25:41', NULL, NULL, NULL, NULL, NULL),
(389, 24, 'LOGIN', 'User logged in from 45.215.236.171', '45.215.236.171', '2026-03-11 11:35:19', NULL, NULL, NULL, NULL, NULL),
(390, 21, 'LOGIN', 'User logged in from 165.58.129.190', '165.58.129.190', '2026-03-11 11:35:34', NULL, NULL, NULL, NULL, NULL),
(391, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '45.215.236.55', '2026-03-11 11:37:24', NULL, NULL, NULL, NULL, NULL),
(392, 9, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 11:48:53', NULL, NULL, NULL, NULL, NULL),
(393, 7, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 11:49:00', NULL, NULL, NULL, NULL, NULL),
(394, 7, 'IT_USER_CREATED', 'IT created user: macusmicheal@gmail.com', '165.58.129.231', '2026-03-11 11:51:42', NULL, NULL, NULL, NULL, NULL),
(395, 7, 'IT_USER_DEACTIVATED', 'IT toggled user ID 15', '165.58.129.231', '2026-03-11 11:53:13', NULL, NULL, NULL, NULL, NULL),
(396, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '197.212.127.9', '2026-03-11 11:53:23', NULL, NULL, NULL, NULL, NULL),
(397, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 15', '165.58.129.231', '2026-03-11 11:53:34', NULL, NULL, NULL, NULL, NULL),
(398, 34, 'LOGIN', 'User logged in from 45.215.237.149', '45.215.237.149', '2026-03-11 11:53:38', NULL, NULL, NULL, NULL, NULL),
(399, 34, 'PASSWORD_CHANGE', 'Password updated successfully', '45.215.237.149', '2026-03-11 11:57:38', NULL, NULL, NULL, NULL, NULL),
(400, 7, 'IT_USER_CREATED', 'IT created user: marybanda1997@gmail.com', '165.58.129.231', '2026-03-11 12:02:10', NULL, NULL, NULL, NULL, NULL),
(401, 34, 'LOGOUT', 'User logged out', '45.215.237.149', '2026-03-11 12:02:56', NULL, NULL, NULL, NULL, NULL),
(402, 7, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 12:03:37', NULL, NULL, NULL, NULL, NULL),
(404, 35, 'LOGIN', 'User logged in from 45.215.237.149', '45.215.237.149', '2026-03-11 12:04:37', NULL, NULL, NULL, NULL, NULL),
(405, 9, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 12:04:41', NULL, NULL, NULL, NULL, NULL),
(407, 35, 'PASSWORD_CHANGE', 'Password updated successfully', '102.145.3.251', '2026-03-11 12:07:02', NULL, NULL, NULL, NULL, NULL),
(409, 7, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 12:11:49', NULL, NULL, NULL, NULL, NULL),
(410, 7, 'IT_USER_CREATED', 'IT created user: vmfune98@gmail.com', '165.58.129.231', '2026-03-11 12:14:31', NULL, NULL, NULL, NULL, NULL),
(411, 36, 'LOGIN', 'User logged in from 45.215.236.121', '45.215.236.121', '2026-03-11 12:17:04', NULL, NULL, NULL, NULL, NULL),
(412, 36, 'PASSWORD_CHANGE', 'Password updated successfully', '45.215.236.121', '2026-03-11 12:18:16', NULL, NULL, NULL, NULL, NULL),
(413, 7, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 12:29:38', NULL, NULL, NULL, NULL, NULL),
(416, 7, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 12:30:30', NULL, NULL, NULL, NULL, NULL),
(417, 7, 'IT_USER_CREATED', 'IT created user: testing@gmail.com', '165.58.129.231', '2026-03-11 12:31:43', NULL, NULL, NULL, NULL, NULL),
(418, 7, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 12:32:00', NULL, NULL, NULL, NULL, NULL),
(422, 7, 'IT_USER_CREATED', 'IT created user: kelvin@gmail.com', '165.58.129.231', '2026-03-11 13:03:30', NULL, NULL, NULL, NULL, NULL),
(426, 27, 'LOGIN', 'User logged in from 41.216.86.42', '41.216.86.42', '2026-03-11 13:26:28', NULL, NULL, NULL, NULL, NULL),
(428, 9, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 13:29:43', NULL, NULL, NULL, NULL, NULL),
(429, 14, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 13:37:45', NULL, NULL, NULL, NULL, NULL),
(430, 26, 'LOGIN', 'User logged in from 45.215.236.161', '45.215.236.161', '2026-03-11 13:39:04', NULL, NULL, NULL, NULL, NULL),
(431, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 25', '45.215.236.161', '2026-03-11 13:43:46', NULL, NULL, NULL, NULL, NULL),
(432, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 26', '45.215.236.161', '2026-03-11 13:52:06', NULL, NULL, NULL, NULL, NULL),
(433, 14, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 13:53:43', NULL, NULL, NULL, NULL, NULL),
(434, 28, 'LOGOUT', 'User logged out', '41.223.116.241', '2026-03-11 13:53:52', NULL, NULL, NULL, NULL, NULL),
(435, 28, 'PASSWORD_RESET_REQUEST', 'Reset requested for kapalashas@yahoo.com', '41.223.116.241', '2026-03-11 13:55:46', NULL, NULL, NULL, NULL, NULL),
(436, 28, 'LOGIN', 'User logged in from 41.223.116.241', '41.223.116.241', '2026-03-11 13:58:11', NULL, NULL, NULL, NULL, NULL),
(437, 28, 'LOGOUT', 'User logged out', '41.223.116.241', '2026-03-11 13:58:49', NULL, NULL, NULL, NULL, NULL),
(438, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 27', '45.215.236.161', '2026-03-11 14:01:57', NULL, NULL, NULL, NULL, NULL),
(439, 28, 'LOGIN', 'User logged in from 41.223.116.241', '41.223.116.241', '2026-03-11 14:02:08', NULL, NULL, NULL, NULL, NULL),
(440, 3, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 14:02:31', NULL, NULL, NULL, NULL, NULL),
(441, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 27', '165.58.129.231', '2026-03-11 14:04:19', NULL, NULL, NULL, NULL, NULL),
(442, 27, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 14:05:06', NULL, NULL, NULL, NULL, NULL),
(443, 27, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.231', '2026-03-11 14:06:44', NULL, NULL, NULL, NULL, NULL),
(444, 28, 'LOGIN', 'User logged in from 41.223.116.241', '41.223.116.241', '2026-03-11 14:07:49', NULL, NULL, NULL, NULL, NULL),
(445, 30, 'LOGOUT', 'User logged out', '102.212.183.41', '2026-03-11 14:08:14', NULL, NULL, NULL, NULL, NULL),
(446, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 28', '45.215.236.161', '2026-03-11 14:08:18', NULL, NULL, NULL, NULL, NULL),
(447, 30, 'LOGIN', 'User logged in from 102.212.183.41', '102.212.183.41', '2026-03-11 14:08:31', NULL, NULL, NULL, NULL, NULL),
(448, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 3', '165.58.129.231', '2026-03-11 14:10:41', NULL, NULL, NULL, NULL, NULL),
(449, 27, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 14:12:09', NULL, NULL, NULL, NULL, NULL),
(450, 3, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 14:12:24', NULL, NULL, NULL, NULL, NULL),
(451, 3, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.231', '2026-03-11 14:12:44', NULL, NULL, NULL, NULL, NULL),
(452, 27, 'LOGIN', 'User logged in from 41.216.86.42', '41.216.86.42', '2026-03-11 14:14:20', NULL, NULL, NULL, NULL, NULL),
(453, 29, 'LOGOUT', 'User logged out', '45.215.237.194', '2026-03-11 14:16:24', NULL, NULL, NULL, NULL, NULL),
(454, 29, 'LOGIN', 'User logged in from 45.215.237.194', '45.215.237.194', '2026-03-11 14:16:29', NULL, NULL, NULL, NULL, NULL),
(455, 30, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '102.212.183.41', '2026-03-11 14:20:51', NULL, NULL, NULL, NULL, NULL),
(456, 3, 'REPORT_SUBMIT', 'KPI submission for category ID 29', '165.58.129.231', '2026-03-11 14:21:21', NULL, NULL, NULL, NULL, NULL),
(457, 29, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.194', '2026-03-11 14:23:46', NULL, NULL, NULL, NULL, NULL),
(458, 24, 'LOGOUT', 'User logged out', '102.149.131.249', '2026-03-11 14:28:46', NULL, NULL, NULL, NULL, NULL),
(459, 24, 'LOGIN', 'User logged in from 102.149.131.249', '102.149.131.249', '2026-03-11 14:29:15', NULL, NULL, NULL, NULL, NULL),
(460, 28, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.223.116.241', '2026-03-11 14:31:43', NULL, NULL, NULL, NULL, NULL),
(461, 21, 'REPORT_SUBMIT', 'KPI submission for category ID 4', '165.58.129.112', '2026-03-11 14:41:18', NULL, NULL, NULL, NULL, NULL),
(462, 24, 'REPORT_SUBMIT', 'KPI submission for category ID 30', '102.149.131.249', '2026-03-11 14:47:01', NULL, NULL, NULL, NULL, NULL),
(463, 9, 'LOGOUT', 'User logged out', '165.58.129.231', '2026-03-11 14:54:42', NULL, NULL, NULL, NULL, NULL),
(466, 14, 'LOGIN', 'User logged in from 165.58.129.231', '165.58.129.231', '2026-03-11 14:55:46', NULL, NULL, NULL, NULL, NULL),
(467, 14, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 14:57:30', NULL, NULL, NULL, NULL, NULL),
(468, 25, 'LOGIN', 'User logged in from 41.223.117.41', '41.223.117.41', '2026-03-11 15:06:41', NULL, NULL, NULL, NULL, NULL),
(469, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 31', '45.215.236.161', '2026-03-11 15:09:19', NULL, NULL, NULL, NULL, NULL),
(470, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 32', '41.223.117.41', '2026-03-11 15:10:00', NULL, NULL, NULL, NULL, NULL),
(471, 35, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.236.143', '2026-03-11 15:14:01', NULL, NULL, NULL, NULL, NULL),
(472, 35, 'LOGOUT', 'User logged out', '45.215.236.143', '2026-03-11 15:15:16', NULL, NULL, NULL, NULL, NULL),
(473, 34, 'LOGIN', 'User logged in from 45.215.236.143', '45.215.236.143', '2026-03-11 15:15:29', NULL, NULL, NULL, NULL, NULL),
(474, 34, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.236.143', '2026-03-11 15:18:21', NULL, NULL, NULL, NULL, NULL),
(475, 7, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 15:33:08', NULL, NULL, NULL, NULL, NULL),
(476, 34, 'LOGOUT', 'User logged out', '45.215.236.143', '2026-03-11 15:52:40', NULL, NULL, NULL, NULL, NULL),
(477, 35, 'LOGIN', 'User logged in from 45.215.236.143', '45.215.236.143', '2026-03-11 15:53:12', NULL, NULL, NULL, NULL, NULL),
(478, 7, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 16:27:34', NULL, NULL, NULL, NULL, NULL),
(479, 7, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 16:27:43', NULL, NULL, NULL, NULL, NULL),
(480, 29, 'LOGOUT', 'User logged out', '45.215.237.194', '2026-03-11 16:38:33', NULL, NULL, NULL, NULL, NULL),
(481, 29, 'LOGIN', 'User logged in from 45.215.237.194', '45.215.237.194', '2026-03-11 16:38:38', NULL, NULL, NULL, NULL, NULL),
(482, 3, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 16:41:49', NULL, NULL, NULL, NULL, NULL),
(483, 14, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 16:42:00', NULL, NULL, NULL, NULL, NULL),
(484, 7, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 16:49:16', NULL, NULL, NULL, NULL, NULL),
(485, 14, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 16:49:22', NULL, NULL, NULL, NULL, NULL),
(486, 3, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 16:57:55', NULL, NULL, NULL, NULL, NULL),
(487, 14, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 16:58:09', NULL, NULL, NULL, NULL, NULL),
(488, 3, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 16:58:23', NULL, NULL, NULL, NULL, NULL),
(489, 35, 'LOGIN', 'User logged in from 41.216.82.27', '41.216.82.27', '2026-03-11 16:59:56', NULL, NULL, NULL, NULL, NULL),
(490, 13, 'PASSWORD_RESET_REQUEST', 'Reset requested for musumali22@gmail.com', '153.67.82.245', '2026-03-11 17:49:59', NULL, NULL, NULL, NULL, NULL),
(491, 7, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 17:54:51', NULL, NULL, NULL, NULL, NULL),
(492, 14, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 17:55:12', NULL, NULL, NULL, NULL, NULL),
(493, 14, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 17:59:23', NULL, NULL, NULL, NULL, NULL),
(494, 7, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 17:59:42', NULL, NULL, NULL, NULL, NULL),
(495, 7, 'IT_USER_UPDATED', 'IT updated user ID: 13', '165.57.81.87', '2026-03-11 18:02:14', NULL, NULL, NULL, NULL, NULL),
(496, 7, 'IT_USER_UPDATED', 'IT updated user ID: 13', '165.57.81.87', '2026-03-11 18:03:54', NULL, NULL, NULL, NULL, NULL),
(497, 7, 'IT_USER_UPDATED', 'IT updated user ID: 13', '165.57.81.87', '2026-03-11 18:05:32', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`, `department_id`, `old_value`, `new_value`, `record_table`, `record_id`) VALUES
(498, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 13', '165.57.81.87', '2026-03-11 18:05:52', NULL, NULL, NULL, NULL, NULL),
(499, 14, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 18:19:10', NULL, NULL, NULL, NULL, NULL),
(500, 13, 'LOGIN', 'User logged in from 153.67.82.245', '153.67.82.245', '2026-03-11 18:21:22', NULL, NULL, NULL, NULL, NULL),
(501, 3, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-11 18:21:33', NULL, NULL, NULL, NULL, NULL),
(502, 13, 'PASSWORD_CHANGE', 'Password updated successfully', '153.67.82.245', '2026-03-11 18:23:39', NULL, NULL, NULL, NULL, NULL),
(503, 13, 'LOGIN', 'User logged in from 105.234.174.234', '105.234.174.234', '2026-03-11 18:25:34', NULL, NULL, NULL, NULL, NULL),
(504, 3, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-11 18:26:34', NULL, NULL, NULL, NULL, NULL),
(505, 13, 'ANNOUNCEMENT_POSTED', 'Announcement: Youth Week | Recipients: 22', '105.234.174.234', '2026-03-11 18:29:42', NULL, NULL, NULL, NULL, NULL),
(506, 13, 'KPI_APPROVED', 'KPI ID 29 approved', '153.67.82.245', '2026-03-11 18:32:14', NULL, NULL, NULL, NULL, NULL),
(507, 13, 'KPI_APPROVED', 'KPI ID 51 approved', '153.67.82.245', '2026-03-11 18:33:23', NULL, NULL, NULL, NULL, NULL),
(508, 30, 'LOGOUT', 'User logged out', '45.215.237.23', '2026-03-11 18:33:35', NULL, NULL, NULL, NULL, NULL),
(509, 30, 'LOGIN', 'User logged in from 45.215.237.23', '45.215.237.23', '2026-03-11 18:33:44', NULL, NULL, NULL, NULL, NULL),
(510, 13, 'KPI_APPROVED', 'KPI ID 50 approved', '153.67.82.245', '2026-03-11 18:34:41', NULL, NULL, NULL, NULL, NULL),
(511, 13, 'KPI_REJECTED', 'KPI ID 49 rejected', '153.67.82.245', '2026-03-11 18:38:40', NULL, NULL, NULL, NULL, NULL),
(512, 28, 'LOGOUT', 'User logged out', '41.223.116.241', '2026-03-11 18:40:15', NULL, NULL, NULL, NULL, NULL),
(513, 28, 'LOGIN', 'User logged in from 41.223.116.241', '41.223.116.241', '2026-03-11 18:40:24', NULL, NULL, NULL, NULL, NULL),
(514, 13, 'KPI_APPROVED', 'KPI ID 48 approved', '153.67.82.245', '2026-03-11 18:43:41', NULL, NULL, NULL, NULL, NULL),
(515, 13, 'KPI_APPROVED', 'KPI ID 47 approved', '153.67.82.245', '2026-03-11 18:44:18', NULL, NULL, NULL, NULL, NULL),
(516, 13, 'KPI_APPROVED', 'KPI ID 46 approved', '153.67.82.245', '2026-03-11 18:45:03', NULL, NULL, NULL, NULL, NULL),
(517, 13, 'KPI_APPROVED', 'KPI ID 45 approved', '153.67.82.245', '2026-03-11 18:45:54', NULL, NULL, NULL, NULL, NULL),
(518, 13, 'KPI_APPROVED', 'KPI ID 44 approved', '153.67.82.245', '2026-03-11 18:46:32', NULL, NULL, NULL, NULL, NULL),
(519, 13, 'KPI_APPROVED', 'KPI ID 43 approved', '153.67.82.245', '2026-03-11 18:47:01', NULL, NULL, NULL, NULL, NULL),
(520, 35, 'LOGOUT', 'User logged out', '41.216.82.27', '2026-03-11 18:47:42', NULL, NULL, NULL, NULL, NULL),
(521, 34, 'LOGIN', 'User logged in from 41.216.82.27', '41.216.82.27', '2026-03-11 18:47:50', NULL, NULL, NULL, NULL, NULL),
(522, 26, 'LOGOUT', 'User logged out', '45.215.236.1', '2026-03-11 18:48:40', NULL, NULL, NULL, NULL, NULL),
(523, 26, 'LOGIN', 'User logged in from 45.215.236.16', '45.215.236.16', '2026-03-11 18:48:47', NULL, NULL, NULL, NULL, NULL),
(524, 26, 'LOGIN', 'User logged in from 45.215.236.10', '45.215.236.10', '2026-03-11 18:48:47', NULL, NULL, NULL, NULL, NULL),
(525, 13, 'KPI_APPROVED', 'KPI ID 42 approved', '153.67.82.245', '2026-03-11 18:48:50', NULL, NULL, NULL, NULL, NULL),
(526, 13, 'KPI_APPROVED', 'KPI ID 41 approved', '153.67.82.245', '2026-03-11 18:49:56', NULL, NULL, NULL, NULL, NULL),
(527, 13, 'KPI_APPROVED', 'KPI ID 40 approved', '153.67.82.245', '2026-03-11 18:50:55', NULL, NULL, NULL, NULL, NULL),
(528, 13, 'KPI_APPROVED', 'KPI ID 39 approved', '153.67.82.245', '2026-03-11 18:51:45', NULL, NULL, NULL, NULL, NULL),
(529, 13, 'KPI_APPROVED', 'KPI ID 38 approved', '153.67.82.245', '2026-03-11 18:52:33', NULL, NULL, NULL, NULL, NULL),
(530, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 28', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(531, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 27', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(532, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 26', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(533, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 25', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(534, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 24', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(535, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 23', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(536, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 18', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(537, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 17', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(538, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 16', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(539, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 12', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(540, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 11', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(541, 13, 'KPI_APPROVED', 'Bulk approved KPI ID 10', '153.67.82.245', '2026-03-11 19:29:29', NULL, NULL, NULL, NULL, NULL),
(542, 14, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-12 04:37:26', NULL, NULL, NULL, NULL, NULL),
(543, 36, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-12 04:39:05', NULL, NULL, NULL, NULL, NULL),
(544, 7, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-12 04:44:02', NULL, NULL, NULL, NULL, NULL),
(545, 7, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-12 04:44:07', NULL, NULL, NULL, NULL, NULL),
(546, 36, 'LOGOUT', 'User logged out', '165.57.81.87', '2026-03-12 06:03:42', NULL, NULL, NULL, NULL, NULL),
(547, 36, 'LOGIN', 'User logged in from 165.57.81.87', '165.57.81.87', '2026-03-12 06:03:49', NULL, NULL, NULL, NULL, NULL),
(548, 36, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 06:32:00', NULL, NULL, NULL, NULL, NULL),
(549, 36, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 06:49:37', NULL, NULL, NULL, NULL, NULL),
(550, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 06:49:54', NULL, NULL, NULL, NULL, NULL),
(551, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 06:51:01', NULL, NULL, NULL, NULL, NULL),
(552, 36, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 06:51:40', NULL, NULL, NULL, NULL, NULL),
(553, 35, 'LOGOUT', 'User logged out', '197.212.67.114', '2026-03-12 07:06:14', NULL, NULL, NULL, NULL, NULL),
(554, 36, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 07:57:16', NULL, NULL, NULL, NULL, NULL),
(555, 36, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 07:57:22', NULL, NULL, NULL, NULL, NULL),
(556, 36, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 08:27:01', NULL, NULL, NULL, NULL, NULL),
(557, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 08:27:12', NULL, NULL, NULL, NULL, NULL),
(558, 36, 'HOLIDAY_DELETED', 'Deleted: New Year\'s Day on 2025-01-01', '45.215.237.51', '2026-03-12 08:37:54', NULL, NULL, NULL, NULL, NULL),
(559, 36, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 08:38:54', NULL, NULL, NULL, NULL, NULL),
(560, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 08:39:13', NULL, NULL, NULL, NULL, NULL),
(561, 14, 'HOLIDAY_ADDED', 'Added holiday: African Freedom Day on 2026-04-28', '45.215.237.51', '2026-03-12 08:58:51', NULL, NULL, NULL, NULL, NULL),
(562, 14, 'HOLIDAY_ADDED', 'Added holiday: Workers\' Day on 2026-05-01', '45.215.237.51', '2026-03-12 08:59:11', NULL, NULL, NULL, NULL, NULL),
(563, 14, 'HOLIDAY_ADDED', 'Added holiday: Africa Day on 2026-05-25', '45.215.237.51', '2026-03-12 08:59:48', NULL, NULL, NULL, NULL, NULL),
(564, 14, 'HOLIDAY_ADDED', 'Added holiday: Heroes\' Day on 2026-07-07', '45.215.237.51', '2026-03-12 09:00:03', NULL, NULL, NULL, NULL, NULL),
(565, 14, 'HOLIDAY_ADDED', 'Added holiday: Unity Day on 2026-07-08', '45.215.237.51', '2026-03-12 09:00:13', NULL, NULL, NULL, NULL, NULL),
(566, 14, 'HOLIDAY_ADDED', 'Added holiday: Farmers\' Day on 2026-08-04', '45.215.237.51', '2026-03-12 09:00:24', NULL, NULL, NULL, NULL, NULL),
(567, 14, 'HOLIDAY_ADDED', 'Added holiday: Independence Day on 2026-10-24', '45.215.237.51', '2026-03-12 09:00:34', NULL, NULL, NULL, NULL, NULL),
(568, 14, 'HOLIDAY_ADDED', 'Added holiday: Christmas Day on 2026-12-25', '45.215.237.51', '2026-03-12 09:00:56', NULL, NULL, NULL, NULL, NULL),
(569, 14, 'HOLIDAY_ADDED', 'Added holiday: Boxing Day on 2026-12-26', '45.215.237.51', '2026-03-12 09:01:06', NULL, NULL, NULL, NULL, NULL),
(570, 9, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:03:44', NULL, NULL, NULL, NULL, NULL),
(571, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:03:48', NULL, NULL, NULL, NULL, NULL),
(572, 7, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:12:51', NULL, NULL, NULL, NULL, NULL),
(573, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:12:56', NULL, NULL, NULL, NULL, NULL),
(574, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:18:17', NULL, NULL, NULL, NULL, NULL),
(575, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:18:28', NULL, NULL, NULL, NULL, NULL),
(576, 9, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:19:07', NULL, NULL, NULL, NULL, NULL),
(577, 2, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:19:33', NULL, NULL, NULL, NULL, NULL),
(578, 2, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:24:18', NULL, NULL, NULL, NULL, NULL),
(579, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:24:30', NULL, NULL, NULL, NULL, NULL),
(580, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:31:22', NULL, NULL, NULL, NULL, NULL),
(581, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:31:31', NULL, NULL, NULL, NULL, NULL),
(582, 9, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:33:25', NULL, NULL, NULL, NULL, NULL),
(583, 2, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:33:36', NULL, NULL, NULL, NULL, NULL),
(584, 2, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:35:28', NULL, NULL, NULL, NULL, NULL),
(585, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:35:37', NULL, NULL, NULL, NULL, NULL),
(586, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:39:33', NULL, NULL, NULL, NULL, NULL),
(587, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:39:40', NULL, NULL, NULL, NULL, NULL),
(588, 13, 'LOGOUT', 'User logged out', '105.234.179.44', '2026-03-12 09:49:46', NULL, NULL, NULL, NULL, NULL),
(589, 13, 'LOGIN', 'User logged in from 105.234.179.44', '105.234.179.44', '2026-03-12 09:51:33', NULL, NULL, NULL, NULL, NULL),
(590, 9, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:54:07', NULL, NULL, NULL, NULL, NULL),
(591, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:54:15', NULL, NULL, NULL, NULL, NULL),
(592, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 09:58:02', NULL, NULL, NULL, NULL, NULL),
(593, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 09:58:07', NULL, NULL, NULL, NULL, NULL),
(594, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 10:02:00', NULL, NULL, NULL, NULL, NULL),
(595, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 10:02:07', NULL, NULL, NULL, NULL, NULL),
(596, 9, 'REPORT_SUBMIT', 'Monthly report submitted for March 2026', '45.215.237.51', '2026-03-12 10:12:15', NULL, NULL, NULL, NULL, NULL),
(597, 9, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 10:12:53', NULL, NULL, NULL, NULL, NULL),
(598, 14, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 10:13:01', NULL, NULL, NULL, NULL, NULL),
(599, 14, 'LOGOUT', 'User logged out', '45.215.237.51', '2026-03-12 10:34:47', NULL, NULL, NULL, NULL, NULL),
(600, 9, 'LOGIN', 'User logged in from 45.215.237.51', '45.215.237.51', '2026-03-12 10:35:00', NULL, NULL, NULL, NULL, NULL),
(601, 9, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 10:55:25', NULL, NULL, NULL, NULL, NULL),
(602, 3, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 10:56:17', NULL, NULL, NULL, NULL, NULL),
(603, 3, 'REPORT_SUBMIT', 'Monthly report submitted for March 2026', '165.58.129.42', '2026-03-12 10:58:20', NULL, NULL, NULL, NULL, NULL),
(604, 14, 'MGMT_REPORT_REVIEW', 'Report #6 approved', '165.58.129.42', '2026-03-12 10:59:07', NULL, NULL, NULL, NULL, NULL),
(605, 14, 'MGMT_REPORT_REVIEW', 'Report #5 approved', '165.58.129.42', '2026-03-12 10:59:29', NULL, NULL, NULL, NULL, NULL),
(606, 3, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 11:10:39', NULL, NULL, NULL, NULL, NULL),
(607, 2, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 11:11:11', NULL, NULL, NULL, NULL, NULL),
(608, 2, 'DAILY_REPORT_SUBMIT', 'Daily CEO Report ID 1 submitted: TESTING TESTING TESTING TESTING TESTING TESTING', '165.58.129.42', '2026-03-12 11:29:03', NULL, NULL, NULL, NULL, NULL),
(609, 14, 'ATTACHMENT_DOWNLOAD', 'Downloaded attachment for daily_ceo_report #1: logo.png', '165.58.129.42', '2026-03-12 11:35:13', NULL, NULL, NULL, NULL, NULL),
(610, 14, 'DAILY_REPORT_REVIEW', 'Daily Report ID 1 marked as acknowledged', '165.58.129.42', '2026-03-12 11:36:22', NULL, NULL, NULL, NULL, NULL),
(611, 3, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 12:09:35', NULL, NULL, NULL, NULL, NULL),
(612, 9, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 12:09:40', NULL, NULL, NULL, NULL, NULL),
(613, 9, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 15:13:51', NULL, NULL, NULL, NULL, NULL),
(614, 14, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 16:08:12', NULL, NULL, NULL, NULL, NULL),
(615, 14, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 18:37:09', NULL, NULL, NULL, NULL, NULL),
(616, 14, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 18:37:33', NULL, NULL, NULL, NULL, NULL),
(617, 14, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 18:39:51', NULL, NULL, NULL, NULL, NULL),
(618, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 18:39:58', NULL, NULL, NULL, NULL, NULL),
(619, 7, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 18:40:13', NULL, NULL, NULL, NULL, NULL),
(620, 3, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 18:42:21', NULL, NULL, NULL, NULL, NULL),
(621, 9, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 20:18:03', NULL, NULL, NULL, NULL, NULL),
(622, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 20:18:34', NULL, NULL, NULL, NULL, NULL),
(623, 3, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 20:22:32', NULL, NULL, NULL, NULL, NULL),
(624, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 20:22:36', NULL, NULL, NULL, NULL, NULL),
(625, 14, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 20:29:28', NULL, NULL, NULL, NULL, NULL),
(626, 7, 'IT_USER_CREATED', 'IT created user: affairs@gmail.com', '165.58.129.42', '2026-03-12 20:30:14', NULL, NULL, NULL, NULL, NULL),
(629, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 38', '165.58.129.42', '2026-03-12 20:34:46', NULL, NULL, NULL, NULL, NULL),
(630, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 33', '165.58.129.42', '2026-03-12 20:35:21', NULL, NULL, NULL, NULL, NULL),
(631, 9, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 20:36:57', NULL, NULL, NULL, NULL, NULL),
(632, 9, 'LOGIN', 'User logged in from 45.215.224.138', '45.215.224.138', '2026-03-12 21:10:46', NULL, NULL, NULL, NULL, NULL),
(633, 9, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 21:12:51', NULL, NULL, NULL, NULL, NULL),
(634, 7, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 21:36:08', NULL, NULL, NULL, NULL, NULL),
(635, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 21:36:15', NULL, NULL, NULL, NULL, NULL),
(636, 7, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 21:41:51', NULL, NULL, NULL, NULL, NULL),
(637, 3, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-12 21:41:58', NULL, NULL, NULL, NULL, NULL),
(638, 7, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-12 21:44:31', NULL, NULL, NULL, NULL, NULL),
(640, 26, 'LOGIN', 'User logged in from 102.150.18.107', '102.150.18.107', '2026-03-12 23:37:42', NULL, NULL, NULL, NULL, NULL),
(641, 2, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-13 03:23:00', NULL, NULL, NULL, NULL, NULL),
(643, 3, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-13 03:59:42', NULL, NULL, NULL, NULL, NULL),
(644, 14, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-13 03:59:49', NULL, NULL, NULL, NULL, NULL),
(645, 14, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-13 04:25:08', NULL, NULL, NULL, NULL, NULL),
(646, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-13 04:25:17', NULL, NULL, NULL, NULL, NULL),
(651, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 37', '165.58.129.42', '2026-03-13 04:41:52', NULL, NULL, NULL, NULL, NULL),
(652, 7, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-13 04:46:06', NULL, NULL, NULL, NULL, NULL),
(653, 14, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-13 04:46:16', NULL, NULL, NULL, NULL, NULL),
(654, 14, 'LOGOUT', 'User logged out', '165.58.129.42', '2026-03-13 04:47:53', NULL, NULL, NULL, NULL, NULL),
(655, 7, 'LOGIN', 'User logged in from 165.58.129.42', '165.58.129.42', '2026-03-13 04:47:59', NULL, NULL, NULL, NULL, NULL),
(656, 7, 'IT_USER_CREATED', 'IT created user: nursing@gmail.com', '165.58.129.42', '2026-03-13 05:03:02', NULL, NULL, NULL, NULL, NULL),
(660, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 41', '165.58.129.42', '2026-03-13 05:05:05', NULL, NULL, NULL, NULL, NULL),
(661, 7, 'IT_USER_CREATED', 'IT created user: nursing@gmail.com', '165.58.129.42', '2026-03-13 05:06:02', NULL, NULL, NULL, NULL, NULL),
(665, 26, 'LOGOUT', 'User logged out', '45.215.236.75', '2026-03-13 06:10:33', NULL, NULL, NULL, NULL, NULL),
(666, 26, 'LOGIN', 'User logged in from 45.215.236.75', '45.215.236.75', '2026-03-13 06:10:42', NULL, NULL, NULL, NULL, NULL),
(667, 21, 'LOGIN', 'User logged in from 165.58.129.8', '165.58.129.8', '2026-03-13 10:30:53', NULL, NULL, NULL, NULL, NULL),
(668, 34, 'LOGIN', 'User logged in from 41.216.82.26', '41.216.82.26', '2026-03-13 10:32:39', NULL, NULL, NULL, NULL, NULL),
(669, 21, 'REPORT_SUBMIT', 'KPI submission for category ID 33', '165.56.186.194', '2026-03-13 10:37:06', NULL, NULL, NULL, NULL, NULL),
(670, 30, 'LOGIN', 'User logged in from 45.215.236.101', '45.215.236.101', '2026-03-13 11:40:29', NULL, NULL, NULL, NULL, NULL),
(671, 30, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.236.101', '2026-03-13 11:47:49', NULL, NULL, NULL, NULL, NULL),
(672, 26, 'LOGOUT', 'User logged out', '45.215.237.230', '2026-03-13 12:20:07', NULL, NULL, NULL, NULL, NULL),
(673, 26, 'LOGIN', 'User logged in from 45.215.237.230', '45.215.237.230', '2026-03-13 12:20:19', NULL, NULL, NULL, NULL, NULL),
(674, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 27', '45.215.237.230', '2026-03-13 12:27:25', NULL, NULL, NULL, NULL, NULL),
(676, 14, 'LOGIN', 'User logged in from 165.58.129.190', '165.58.129.190', '2026-03-13 12:32:22', NULL, NULL, NULL, NULL, NULL),
(677, 14, 'LOGOUT', 'User logged out', '165.58.129.190', '2026-03-13 12:35:32', NULL, NULL, NULL, NULL, NULL),
(678, 7, 'LOGIN', 'User logged in from 165.58.129.190', '165.58.129.190', '2026-03-13 12:35:45', NULL, NULL, NULL, NULL, NULL),
(679, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 34', '45.215.237.230', '2026-03-13 12:43:00', NULL, NULL, NULL, NULL, NULL),
(680, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 35', '45.215.237.230', '2026-03-13 12:49:24', NULL, NULL, NULL, NULL, NULL),
(681, 21, 'LOGOUT', 'User logged out', '165.56.186.25', '2026-03-13 13:09:25', NULL, NULL, NULL, NULL, NULL),
(682, 22, 'LOGIN', 'User logged in from 45.215.255.199', '45.215.255.199', '2026-03-13 13:16:07', NULL, NULL, NULL, NULL, NULL),
(683, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.58', '2026-03-13 13:24:18', NULL, NULL, NULL, NULL, NULL),
(684, 7, 'LOGOUT', 'User logged out', '165.58.129.190', '2026-03-13 13:25:17', NULL, NULL, NULL, NULL, NULL),
(685, 7, 'LOGIN', 'User logged in from 165.58.129.190', '165.58.129.190', '2026-03-13 13:25:27', NULL, NULL, NULL, NULL, NULL),
(687, 14, 'LOGIN', 'User logged in from 165.58.129.190', '165.58.129.190', '2026-03-13 13:29:36', NULL, NULL, NULL, NULL, NULL),
(688, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '45.215.237.58', '2026-03-13 13:36:31', NULL, NULL, NULL, NULL, NULL),
(689, 28, 'LOGIN', 'User logged in from 45.215.237.178', '45.215.237.178', '2026-03-13 13:59:18', NULL, NULL, NULL, NULL, NULL),
(690, 28, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '45.215.237.178', '2026-03-13 14:07:45', NULL, NULL, NULL, NULL, NULL),
(691, 34, 'LOGOUT', 'User logged out', '41.216.82.26', '2026-03-13 14:11:51', NULL, NULL, NULL, NULL, NULL),
(692, 34, 'LOGIN', 'User logged in from 41.216.82.26', '41.216.82.26', '2026-03-13 14:17:19', NULL, NULL, NULL, NULL, NULL),
(693, 34, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '41.216.82.26', '2026-03-13 14:18:23', NULL, NULL, NULL, NULL, NULL),
(694, 14, 'LOGOUT', 'User logged out', '165.58.129.190', '2026-03-13 14:23:32', NULL, NULL, NULL, NULL, NULL),
(696, 34, 'REPORT_SUBMIT', 'KPI submission for category ID 37', '41.216.82.26', '2026-03-13 14:26:11', NULL, NULL, NULL, NULL, NULL),
(697, 34, 'LOGOUT', 'User logged out', '41.216.82.26', '2026-03-13 14:32:19', NULL, NULL, NULL, NULL, NULL),
(698, 35, 'LOGIN', 'User logged in from 41.216.82.26', '41.216.82.26', '2026-03-13 14:32:50', NULL, NULL, NULL, NULL, NULL),
(699, 35, 'REPORT_SUBMIT', 'KPI submission for category ID 4', '41.216.82.26', '2026-03-13 14:38:04', NULL, NULL, NULL, NULL, NULL),
(700, 35, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '41.216.82.26', '2026-03-13 14:50:11', NULL, NULL, NULL, NULL, NULL),
(701, 30, 'LOGOUT', 'User logged out', '45.215.236.101', '2026-03-13 15:22:00', NULL, NULL, NULL, NULL, NULL),
(702, 30, 'LOGIN', 'User logged in from 45.215.236.101', '45.215.236.101', '2026-03-13 15:22:11', NULL, NULL, NULL, NULL, NULL),
(703, 26, 'LOGOUT', 'User logged out', '45.215.236.59', '2026-03-13 16:11:52', NULL, NULL, NULL, NULL, NULL),
(704, 26, 'LOGIN', 'User logged in from 45.215.236.59', '45.215.236.59', '2026-03-13 16:12:05', NULL, NULL, NULL, NULL, NULL),
(705, 7, 'LOGOUT', 'User logged out', '165.56.66.225', '2026-03-13 19:51:44', NULL, NULL, NULL, NULL, NULL),
(706, 7, 'LOGIN', 'User logged in from 165.56.66.225', '165.56.66.225', '2026-03-13 20:54:21', NULL, NULL, NULL, NULL, NULL),
(707, 30, 'LOGOUT', 'User logged out', '45.215.236.101', '2026-03-13 20:54:28', NULL, NULL, NULL, NULL, NULL),
(708, 7, 'LOGOUT', 'User logged out', '165.56.66.225', '2026-03-13 20:54:33', NULL, NULL, NULL, NULL, NULL),
(709, 7, 'LOGOUT', 'User logged out', '165.56.66.225', '2026-03-13 20:56:30', NULL, NULL, NULL, NULL, NULL),
(710, 30, 'LOGIN', 'User logged in from 45.215.236.101', '45.215.236.101', '2026-03-13 20:58:57', NULL, NULL, NULL, NULL, NULL),
(711, 9, 'LOGIN', 'User logged in from 165.56.66.225', '165.56.66.225', '2026-03-13 21:08:36', NULL, NULL, NULL, NULL, NULL),
(712, 9, 'LOGOUT', 'User logged out', '165.56.66.225', '2026-03-13 21:09:55', NULL, NULL, NULL, NULL, NULL),
(718, 7, 'LOGIN', 'User logged in from 165.56.66.196', '165.56.66.196', '2026-03-14 06:58:10', NULL, NULL, NULL, NULL, NULL),
(721, 7, 'LOGOUT', 'User logged out', '165.58.129.135', '2026-03-14 08:12:34', NULL, NULL, NULL, NULL, NULL),
(724, 26, 'LOGOUT', 'User logged out', '45.215.236.84', '2026-03-14 11:56:20', NULL, NULL, NULL, NULL, NULL),
(725, 26, 'LOGIN', 'User logged in from 45.215.236.84', '45.215.236.84', '2026-03-14 11:56:36', NULL, NULL, NULL, NULL, NULL),
(726, 30, 'LOGOUT', 'User logged out', '45.215.236.26', '2026-03-14 13:03:01', NULL, NULL, NULL, NULL, NULL),
(727, 30, 'LOGIN', 'User logged in from 45.215.236.26', '45.215.236.26', '2026-03-14 13:03:08', NULL, NULL, NULL, NULL, NULL),
(728, 29, 'LOGIN', 'User logged in from 102.149.227.44', '102.149.227.44', '2026-03-14 13:43:41', NULL, NULL, NULL, NULL, NULL),
(729, 9, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-14 17:31:00', NULL, NULL, NULL, NULL, NULL),
(730, 9, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-14 19:29:37', NULL, NULL, NULL, NULL, NULL),
(731, 14, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-14 19:29:47', NULL, NULL, NULL, NULL, NULL),
(732, 14, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-14 19:32:21', NULL, NULL, NULL, NULL, NULL),
(733, 7, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-14 19:32:31', NULL, NULL, NULL, NULL, NULL),
(734, 7, 'IT_USER_UPDATED', 'IT updated user ID: 3', '165.58.129.65', '2026-03-14 19:34:43', NULL, NULL, NULL, NULL, NULL),
(735, 30, 'LOGOUT', 'User logged out', '45.215.236.7', '2026-03-14 19:48:07', NULL, NULL, NULL, NULL, NULL),
(736, 30, 'LOGIN', 'User logged in from 45.215.236.9', '45.215.236.9', '2026-03-14 19:48:34', NULL, NULL, NULL, NULL, NULL),
(737, 28, 'LOGIN', 'User logged in from 41.223.116.246', '41.223.116.246', '2026-03-15 12:23:59', NULL, NULL, NULL, NULL, NULL),
(738, 26, 'LOGIN', 'User logged in from 45.215.249.254', '45.215.249.254', '2026-03-15 12:24:18', NULL, NULL, NULL, NULL, NULL),
(739, 26, 'LOGOUT', 'User logged out', '45.215.249.254', '2026-03-15 12:56:11', NULL, NULL, NULL, NULL, NULL),
(740, 7, 'LOGOUT', 'User logged out', '216.234.213.22', '2026-03-15 15:44:00', NULL, NULL, NULL, NULL, NULL),
(741, 7, 'LOGIN', 'User logged in from 216.234.213.22', '216.234.213.22', '2026-03-15 15:44:10', NULL, NULL, NULL, NULL, NULL),
(742, 7, 'IT_SOFTWARE_ADDED', 'Software added: Lanbridge College system saver (HOSTINGER)', '216.234.213.22', '2026-03-15 15:46:26', NULL, NULL, NULL, NULL, NULL),
(743, 30, 'LOGIN', 'User logged in from 45.215.255.74', '45.215.255.74', '2026-03-15 20:07:13', NULL, NULL, NULL, NULL, NULL),
(745, 7, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-15 20:46:33', NULL, NULL, NULL, NULL, NULL),
(746, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 42', '165.58.129.65', '2026-03-15 20:46:56', NULL, NULL, NULL, NULL, NULL),
(747, 7, 'IT_USER_DELETED', 'Permanently deleted user ID 40', '165.58.129.65', '2026-03-15 20:47:47', NULL, NULL, NULL, NULL, NULL),
(748, 7, 'IT_USER_CREATED', 'IT created user: ilungaleonardo@yahoo.com', '165.58.129.65', '2026-03-15 20:50:31', NULL, NULL, NULL, NULL, NULL),
(750, 43, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-15 20:51:30', NULL, NULL, NULL, NULL, NULL),
(751, 43, 'PASSWORD_CHANGE', 'Password updated successfully', '165.58.129.65', '2026-03-15 20:51:50', NULL, NULL, NULL, NULL, NULL),
(752, 7, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-15 20:58:17', NULL, NULL, NULL, NULL, NULL),
(753, 14, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-15 20:58:25', NULL, NULL, NULL, NULL, NULL),
(754, 14, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-15 20:58:57', NULL, NULL, NULL, NULL, NULL),
(755, 7, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-15 20:59:16', NULL, NULL, NULL, NULL, NULL),
(756, 7, 'IT_SOFTWARE_UPDATED', 'Software ID 1 updated: Lanbridge College system saver', '165.58.129.65', '2026-03-15 21:01:20', NULL, NULL, NULL, NULL, NULL),
(757, 43, 'SOH_COURSE_UPDATED', 'Course ID 5 updated: MDW201', '165.58.129.65', '2026-03-15 21:13:52', NULL, NULL, NULL, NULL, NULL),
(758, 43, 'SOH_COURSE_ADDED', 'Course added: MDW2018 — Principles of Midwifery', '165.58.129.65', '2026-03-15 21:14:25', NULL, NULL, NULL, NULL, NULL),
(759, 7, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-15 21:36:42', NULL, NULL, NULL, NULL, NULL),
(760, 7, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-15 21:38:02', NULL, NULL, NULL, NULL, NULL),
(761, 9, 'LOGIN', 'User logged in from 165.58.129.65', '165.58.129.65', '2026-03-15 21:38:09', NULL, NULL, NULL, NULL, NULL),
(762, 43, 'LOGOUT', 'User logged out', '165.58.129.65', '2026-03-16 05:13:29', NULL, NULL, NULL, NULL, NULL),
(763, 28, 'LOGOUT', 'User logged out', '41.216.82.30', '2026-03-16 09:16:58', NULL, NULL, NULL, NULL, NULL),
(764, 28, 'LOGIN', 'User logged in from 41.216.82.30', '41.216.82.30', '2026-03-16 09:17:08', NULL, NULL, NULL, NULL, NULL),
(765, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 09:28:42', NULL, NULL, NULL, NULL, NULL),
(766, 14, 'LOGIN', 'User logged in from 45.215.249.14', '45.215.249.14', '2026-03-16 09:48:14', NULL, NULL, NULL, NULL, NULL),
(767, 14, 'LOGOUT', 'User logged out', '45.215.249.14', '2026-03-16 09:48:36', NULL, NULL, NULL, NULL, NULL),
(768, 9, 'LOGIN', 'User logged in from 45.215.249.14', '45.215.249.14', '2026-03-16 09:50:11', NULL, NULL, NULL, NULL, NULL),
(769, 28, 'LOGOUT', 'User logged out', '41.216.82.30', '2026-03-16 09:58:34', NULL, NULL, NULL, NULL, NULL),
(770, 28, 'LOGIN', 'User logged in from 41.216.82.30', '41.216.82.30', '2026-03-16 09:58:40', NULL, NULL, NULL, NULL, NULL),
(771, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 10:14:34', NULL, NULL, NULL, NULL, NULL),
(772, 28, 'LOGOUT', 'User logged out', '41.216.82.30', '2026-03-16 10:23:55', NULL, NULL, NULL, NULL, NULL),
(773, 22, 'LOGIN', 'User logged in from 102.147.124.167', '102.147.124.167', '2026-03-16 10:57:32', NULL, NULL, NULL, NULL, NULL),
(774, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 11:01:47', NULL, NULL, NULL, NULL, NULL),
(775, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 11:01:50', NULL, NULL, NULL, NULL, NULL),
(776, 22, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.234', '2026-03-16 11:28:44', NULL, NULL, NULL, NULL, NULL),
(777, 22, 'LOGOUT', 'User logged out', '45.215.237.234', '2026-03-16 11:28:44', NULL, NULL, NULL, NULL, NULL),
(778, 21, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 11:40:42', NULL, NULL, NULL, NULL, NULL),
(779, 21, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 11:43:59', NULL, NULL, NULL, NULL, NULL),
(780, 22, 'LOGIN', 'User logged in from 45.215.237.240', '45.215.237.240', '2026-03-16 11:47:40', NULL, NULL, NULL, NULL, NULL),
(781, 22, 'LOGIN', 'User logged in from 45.215.237.240', '45.215.237.240', '2026-03-16 11:47:41', NULL, NULL, NULL, NULL, NULL),
(782, 21, 'ADM_APPLICATION_ADDED', 'Application APP-20260316-91A318: BLESSING KABWE → REGISTERD NURSING', '165.56.186.216', '2026-03-16 11:55:04', NULL, NULL, NULL, NULL, NULL),
(783, 35, 'LOGIN', 'User logged in from 45.215.237.39', '45.215.237.39', '2026-03-16 12:03:53', NULL, NULL, NULL, NULL, NULL),
(784, 35, 'LOGOUT', 'User logged out', '45.215.237.39', '2026-03-16 12:04:03', NULL, NULL, NULL, NULL, NULL),
(785, 34, 'LOGIN', 'User logged in from 45.215.237.39', '45.215.237.39', '2026-03-16 12:04:07', NULL, NULL, NULL, NULL, NULL),
(786, 26, 'LOGIN', 'User logged in from 197.212.172.113', '197.212.172.113', '2026-03-16 12:04:27', NULL, NULL, NULL, NULL, NULL),
(787, 21, 'LOGOUT', 'User logged out', '165.56.186.216', '2026-03-16 12:14:21', NULL, NULL, NULL, NULL, NULL),
(788, 9, 'LOGOUT', 'User logged out', '45.215.249.14', '2026-03-16 12:15:53', NULL, NULL, NULL, NULL, NULL),
(789, 21, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 12:22:36', NULL, NULL, NULL, NULL, NULL),
(790, 25, 'LOGOUT', 'User logged out', '165.56.186.216', '2026-03-16 12:24:34', NULL, NULL, NULL, NULL, NULL),
(791, 28, 'LOGIN', 'User logged in from 41.216.82.30', '41.216.82.30', '2026-03-16 12:28:16', NULL, NULL, NULL, NULL, NULL),
(792, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 38', '197.212.172.113', '2026-03-16 12:29:53', NULL, NULL, NULL, NULL, NULL),
(793, 24, 'LOGIN', 'User logged in from 45.215.249.106', '45.215.249.106', '2026-03-16 12:32:56', NULL, NULL, NULL, NULL, NULL),
(794, 21, 'ADM_APPLICATION_ADDED', 'Application APP-20260316-9791FE: KASEMPA PATIENCE → REGISTERD NURSING', '165.56.186.216', '2026-03-16 12:33:29', NULL, NULL, NULL, NULL, NULL),
(795, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 34', '197.212.172.113', '2026-03-16 12:39:35', NULL, NULL, NULL, NULL, NULL),
(796, 21, 'ADM_ENROLLED', 'Student STU-20260316-B89AE6 enrolled from application ID 2', '165.56.186.216', '2026-03-16 12:42:03', NULL, NULL, NULL, NULL, NULL),
(797, 26, 'REPORT_SUBMIT', 'KPI submission for category ID 39', '197.212.172.113', '2026-03-16 12:47:02', NULL, NULL, NULL, NULL, NULL),
(798, 28, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '41.216.82.30', '2026-03-16 12:48:15', NULL, NULL, NULL, NULL, NULL),
(799, 36, 'LOGIN', 'User logged in from 165.58.129.162', '165.58.129.162', '2026-03-16 13:00:14', NULL, NULL, NULL, NULL, NULL),
(800, 36, 'LOGIN', 'User logged in from 45.215.255.248', '45.215.255.248', '2026-03-16 13:01:13', NULL, NULL, NULL, NULL, NULL),
(801, 29, 'LOGIN', 'User logged in from 102.145.94.217', '102.145.94.217', '2026-03-16 13:07:43', NULL, NULL, NULL, NULL, NULL),
(802, 29, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '102.145.94.217', '2026-03-16 13:12:07', NULL, NULL, NULL, NULL, NULL),
(803, 36, 'LOGIN', 'User logged in from 45.215.236.64', '45.215.236.64', '2026-03-16 13:27:34', NULL, NULL, NULL, NULL, NULL),
(804, 24, 'LOGOUT', 'User logged out', '45.215.249.106', '2026-03-16 13:35:38', NULL, NULL, NULL, NULL, NULL),
(805, 24, 'LOGIN', 'User logged in from 45.215.249.106', '45.215.249.106', '2026-03-16 13:35:44', NULL, NULL, NULL, NULL, NULL),
(806, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 13:47:13', NULL, NULL, NULL, NULL, NULL),
(807, 21, 'LOGOUT', 'User logged out', '165.56.186.216', '2026-03-16 13:51:14', NULL, NULL, NULL, NULL, NULL),
(808, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 24', '165.56.186.216', '2026-03-16 13:51:14', NULL, NULL, NULL, NULL, NULL),
(809, 21, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 13:51:31', NULL, NULL, NULL, NULL, NULL),
(810, 25, 'REPORT_SUBMIT', 'KPI submission for category ID 40', '165.56.186.216', '2026-03-16 13:54:39', NULL, NULL, NULL, NULL, NULL),
(811, 26, 'LOGOUT', 'User logged out', '197.212.172.113', '2026-03-16 13:55:40', NULL, NULL, NULL, NULL, NULL),
(812, 21, 'LOGOUT', 'User logged out', '165.56.186.216', '2026-03-16 14:01:02', NULL, NULL, NULL, NULL, NULL),
(813, 25, 'LOGIN', 'User logged in from 165.56.186.216', '165.56.186.216', '2026-03-16 14:03:59', NULL, NULL, NULL, NULL, NULL),
(814, 30, 'LOGOUT', 'User logged out', '45.215.237.180', '2026-03-16 14:11:17', NULL, NULL, NULL, NULL, NULL),
(815, 30, 'LOGIN', 'User logged in from 45.215.237.180', '45.215.237.180', '2026-03-16 14:11:23', NULL, NULL, NULL, NULL, NULL),
(816, 35, 'LOGIN', 'User logged in from 45.215.237.39', '45.215.237.39', '2026-03-16 14:15:16', NULL, NULL, NULL, NULL, NULL),
(817, 21, 'REPORT_SUBMIT', 'KPI submission for category ID 4', '165.57.81.112', '2026-03-16 14:16:11', NULL, NULL, NULL, NULL, NULL),
(818, 36, 'LOGOUT', 'User logged out', '165.56.66.235', '2026-03-16 14:34:35', NULL, NULL, NULL, NULL, NULL),
(819, 35, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.39', '2026-03-16 14:36:15', NULL, NULL, NULL, NULL, NULL),
(820, 7, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 14:44:44', NULL, NULL, NULL, NULL, NULL),
(821, 24, 'LOGIN', 'User logged in from 45.215.249.218', '45.215.249.218', '2026-03-16 14:52:13', NULL, NULL, NULL, NULL, NULL),
(822, 7, 'IT_PASSWORD_RESET', 'IT reset password for user ID 43', '45.215.237.7', '2026-03-16 14:52:38', NULL, NULL, NULL, NULL, NULL),
(823, 30, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.180', '2026-03-16 14:52:42', NULL, NULL, NULL, NULL, NULL),
(824, 34, 'LOGOUT', 'User logged out', '45.215.237.11', '2026-03-16 14:56:40', NULL, NULL, NULL, NULL, NULL),
(825, 34, 'LOGIN', 'User logged in from 45.215.237.11', '45.215.237.11', '2026-03-16 14:56:46', NULL, NULL, NULL, NULL, NULL),
(826, 24, 'REPORT_SUBMIT', 'KPI submission for category ID 30', '45.215.249.218', '2026-03-16 14:58:57', NULL, NULL, NULL, NULL, NULL),
(827, 34, 'REPORT_SUBMIT', 'KPI submission for category ID 22', '45.215.237.11', '2026-03-16 14:59:21', NULL, NULL, NULL, NULL, NULL),
(828, 7, 'LOGOUT', 'User logged out', '45.215.237.7', '2026-03-16 15:09:06', NULL, NULL, NULL, NULL, NULL),
(829, 7, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:16:10', NULL, NULL, NULL, NULL, NULL),
(830, 34, 'LOGOUT', 'User logged out', '45.215.249.143', '2026-03-16 15:19:57', NULL, NULL, NULL, NULL, NULL),
(831, 7, 'IT_USER_UPDATED', 'IT updated user ID: 2', '45.215.237.7', '2026-03-16 15:20:10', NULL, NULL, NULL, NULL, NULL),
(832, 2, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:21:09', NULL, NULL, NULL, NULL, NULL),
(833, 23, 'LOGIN', 'User logged in from 45.215.237.240', '45.215.237.240', '2026-03-16 15:25:53', NULL, NULL, NULL, NULL, NULL),
(834, 25, 'LOGOUT', 'User logged out', '45.215.237.7', '2026-03-16 15:29:22', NULL, NULL, NULL, NULL, NULL),
(835, 28, 'LOGIN', 'User logged in from 41.216.82.30', '41.216.82.30', '2026-03-16 15:29:52', NULL, NULL, NULL, NULL, NULL),
(836, 23, 'REPORT_SUBMIT', 'KPI submission for category ID 1', '45.215.237.240', '2026-03-16 15:30:54', NULL, NULL, NULL, NULL, NULL),
(837, 25, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:33:11', NULL, NULL, NULL, NULL, NULL),
(838, 21, 'LOGIN', 'User logged in from 165.58.129.181', '165.58.129.181', '2026-03-16 15:38:03', NULL, NULL, NULL, NULL, NULL),
(839, 34, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:38:15', NULL, NULL, NULL, NULL, NULL),
(840, 35, 'LOGOUT', 'User logged out', '45.215.237.7', '2026-03-16 15:38:19', NULL, NULL, NULL, NULL, NULL),
(841, 35, 'LOGOUT', 'User logged out', '45.215.237.7', '2026-03-16 15:38:19', NULL, NULL, NULL, NULL, NULL),
(842, 35, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:38:37', NULL, NULL, NULL, NULL, NULL),
(843, 2, 'LOGIN', 'User logged in from 45.215.237.7', '45.215.237.7', '2026-03-16 15:50:28', NULL, NULL, NULL, NULL, NULL),
(844, 2, 'LOGOUT', 'User logged out', '45.215.224.250', '2026-03-16 18:33:01', NULL, NULL, NULL, NULL, NULL),
(845, 24, 'LOGOUT', 'User logged out', '102.147.220.14', '2026-03-16 19:48:29', NULL, NULL, NULL, NULL, NULL),
(846, 30, 'LOGOUT', 'User logged out', '45.215.237.191', '2026-03-16 20:03:28', NULL, NULL, NULL, NULL, NULL),
(847, 30, 'LOGOUT', 'User logged out', '45.215.237.191', '2026-03-16 20:03:28', NULL, NULL, NULL, NULL, NULL),
(848, 30, 'LOGIN', 'User logged in from 45.215.237.191', '45.215.237.191', '2026-03-16 20:03:42', NULL, NULL, NULL, NULL, NULL),
(849, 7, 'LOGOUT', 'User logged out', '165.56.66.56', '2026-03-16 20:11:28', NULL, NULL, NULL, NULL, NULL),
(850, 14, 'LOGIN', 'User logged in from 165.56.66.56', '165.56.66.56', '2026-03-16 20:11:51', NULL, NULL, NULL, NULL, NULL),
(851, 14, 'LOGOUT', 'User logged out', '165.56.186.182', '2026-03-17 04:22:15', NULL, NULL, NULL, NULL, NULL),
(852, 14, 'LOGIN', 'User logged in from 165.56.186.182', '165.56.186.182', '2026-03-17 05:24:36', NULL, NULL, NULL, NULL, NULL),
(853, 14, 'LOGOUT', 'User logged out', '165.56.186.182', '2026-03-17 05:50:03', NULL, NULL, NULL, NULL, NULL),
(854, 14, 'LOGIN', 'User logged in from 165.56.186.182', '165.56.186.182', '2026-03-17 06:03:54', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acad_attendance_records`
--
ALTER TABLE `acad_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_student` (`session_id`,`student_ref`);

--
-- Indexes for table `acad_attendance_sessions`
--
ALTER TABLE `acad_attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `acad_calendar_events`
--
ALTER TABLE `acad_calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `acad_courses`
--
ALTER TABLE `acad_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `programme_id` (`programme_id`);

--
-- Indexes for table `acad_dept_reports`
--
ALTER TABLE `acad_dept_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dept_month` (`department_id`,`report_month`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `acad_exam_schedule`
--
ALTER TABLE `acad_exam_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `invigilator_id` (`invigilator_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `acad_graduation_clearance`
--
ALTER TABLE `acad_graduation_clearance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programme_id` (`programme_id`),
  ADD KEY `cleared_by` (`cleared_by`);

--
-- Indexes for table `acad_lecturer_assignments`
--
ALTER TABLE `acad_lecturer_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`lecturer_id`,`course_id`,`year`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `acad_marks`
--
ALTER TABLE `acad_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `moderated_by` (`moderated_by`);

--
-- Indexes for table `acad_memos`
--
ALTER TABLE `acad_memos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sent_by` (`sent_by`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `acad_memo_recipients`
--
ALTER TABLE `acad_memo_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_memo_user` (`memo_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `acad_notices`
--
ALTER TABLE `acad_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `acad_programmes`
--
ALTER TABLE `acad_programmes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `acad_research_log`
--
ALTER TABLE `acad_research_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `acad_student_progression`
--
ALTER TABLE `acad_student_progression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programme_id` (`programme_id`),
  ADD KEY `last_updated_by` (`last_updated_by`);

--
-- Indexes for table `acad_supp_applications`
--
ALTER TABLE `acad_supp_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `acad_syllabus_topics`
--
ALTER TABLE `acad_syllabus_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `acad_timetable`
--
ALTER TABLE `acad_timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `accreditation_records`
--
ALTER TABLE `accreditation_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`);

--
-- Indexes for table `adm_applications`
--
ALTER TABLE `adm_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `adm_documents`
--
ALTER TABLE `adm_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `adm_enrollments`
--
ALTER TABLE `adm_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD UNIQUE KEY `student_ref` (`student_ref`),
  ADD KEY `scholarship_id` (`scholarship_id`),
  ADD KEY `enrolled_by` (`enrolled_by`);

--
-- Indexes for table `adm_interviews`
--
ALTER TABLE `adm_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `scheduled_by` (`scheduled_by`);

--
-- Indexes for table `adm_offers`
--
ALTER TABLE `adm_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `adm_scholarships`
--
ALTER TABLE `adm_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `adm_scores`
--
ALTER TABLE `adm_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_app_criteria` (`application_id`,`criteria_id`),
  ADD KEY `criteria_id` (`criteria_id`),
  ADD KEY `scored_by` (`scored_by`);

--
-- Indexes for table `adm_scoring_criteria`
--
ALTER TABLE `adm_scoring_criteria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alumni_engagement`
--
ALTER TABLE `alumni_engagement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ann_dept` (`announcement_id`,`department_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ann_user` (`announcement_id`,`user_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `ca_activity_logs`
--
ALTER TABLE `ca_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ca_events`
--
ALTER TABLE `ca_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `daily_ceo_reports`
--
ALTER TABLE `daily_ceo_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`report_date`);

--
-- Indexes for table `departmental_budget`
--
ALTER TABLE `departmental_budget`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_year` (`department_id`,`fiscal_year`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `elearning_materials`
--
ALTER TABLE `elearning_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `elearning_sessions`
--
ALTER TABLE `elearning_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `elibrary_access_log`
--
ALTER TABLE `elibrary_access_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `elibrary_resources`
--
ALTER TABLE `elibrary_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenditure`
--
ALTER TABLE `expenditure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `fin_bank_accounts`
--
ALTER TABLE `fin_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fin_bank_entries`
--
ALTER TABLE `fin_bank_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `reconciled_by` (`reconciled_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fin_bank_reconciliations`
--
ALTER TABLE `fin_bank_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_acct_period` (`account_id`,`period_month`),
  ADD KEY `prepared_by` (`prepared_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `fin_invoices`
--
ALTER TABLE `fin_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `fin_invoice_items`
--
ALTER TABLE `fin_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `fin_invoice_payments`
--
ALTER TABLE `fin_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `fin_petty_cash_funds`
--
ALTER TABLE `fin_petty_cash_funds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `custodian_id` (`custodian_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fin_petty_cash_transactions`
--
ALTER TABLE `fin_petty_cash_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `fund_id` (`fund_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `fraud_flags`
--
ALTER TABLE `fraud_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `hr_applicants`
--
ALTER TABLE `hr_applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recruitment_id` (`recruitment_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `hr_appraisals`
--
ALTER TABLE `hr_appraisals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `appraiser_id` (`appraiser_id`);

--
-- Indexes for table `hr_assets`
--
ALTER TABLE `hr_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_date` (`staff_id`,`date`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `hr_contracts`
--
ALTER TABLE `hr_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_ref` (`contract_ref`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `hr_leave_types`
--
ALTER TABLE `hr_leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `hr_recruitment`
--
ALTER TABLE `hr_recruitment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `hr_staff`
--
ALTER TABLE `hr_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `linked_user_id` (`linked_user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `internship_companies`
--
ALTER TABLE `internship_companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `linked_partnership_id` (`linked_partnership_id`);

--
-- Indexes for table `internship_placements`
--
ALTER TABLE `internship_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `it_assets`
--
ALTER TABLE `it_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `it_helpdesk_chat`
--
ALTER TABLE `it_helpdesk_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `it_network_devices`
--
ALTER TABLE `it_network_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `managed_by` (`managed_by`);

--
-- Indexes for table `it_network_log`
--
ALTER TABLE `it_network_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `logged_by` (`logged_by`);

--
-- Indexes for table `it_software`
--
ALTER TABLE `it_software`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `it_tickets`
--
ALTER TABLE `it_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `it_ticket_activity_log`
--
ALTER TABLE `it_ticket_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `it_ticket_comments`
--
ALTER TABLE `it_ticket_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`);

--
-- Indexes for table `kpi_categories`
--
ALTER TABLE `kpi_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `kpi_submissions`
--
ALTER TABLE `kpi_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_submission` (`user_id`,`category_id`,`submission_date`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kt_dept_period` (`department_id`,`period`),
  ADD KEY `set_by` (`set_by`);

--
-- Indexes for table `lect_academic_reports`
--
ALTER TABLE `lect_academic_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `acknowledged_by` (`acknowledged_by`);

--
-- Indexes for table `lect_announcements`
--
ALTER TABLE `lect_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lect_assessments`
--
ALTER TABLE `lect_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `hod_approved_by` (`hod_approved_by`);

--
-- Indexes for table `lect_attendance_records`
--
ALTER TABLE `lect_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `lect_attendance_sessions`
--
ALTER TABLE `lect_attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session` (`course_id`,`lecturer_id`,`session_date`,`session_type`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lect_consultation_slots`
--
ALTER TABLE `lect_consultation_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lect_leave_requests`
--
ALTER TABLE `lect_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `substitute_id` (`substitute_id`),
  ADD KEY `hod_reviewed_by` (`hod_reviewed_by`),
  ADD KEY `final_reviewed_by` (`final_reviewed_by`);

--
-- Indexes for table `lect_lecture_log`
--
ALTER TABLE `lect_lecture_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lect_marks`
--
ALTER TABLE `lect_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mark` (`assessment_id`,`student_ref`);

--
-- Indexes for table `lect_materials`
--
ALTER TABLE `lect_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lect_research_log`
--
ALTER TABLE `lect_research_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lect_student_queries`
--
ALTER TABLE `lect_student_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lect_timetable_changes`
--
ALTER TABLE `lect_timetable_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_time` (`attempt_time`);

--
-- Indexes for table `management_reports`
--
ALTER TABLE `management_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `media_records`
--
ALTER TABLE `media_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `mkt_events`
--
ALTER TABLE `mkt_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organiser_id` (`organiser_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `mkt_event_attendees`
--
ALTER TABLE `mkt_event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `mkt_leads`
--
ALTER TABLE `mkt_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `mkt_lead_followups`
--
ALTER TABLE `mkt_lead_followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `done_by` (`done_by`);

--
-- Indexes for table `mkt_materials`
--
ALTER TABLE `mkt_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `msg_conversations`
--
ALTER TABLE `msg_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_at` (`updated_at`);

--
-- Indexes for table `msg_messages`
--
ALTER TABLE `msg_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`,`created_at`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `msg_participants`
--
ALTER TABLE `msg_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conv_user` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_period` (`user_id`,`pay_period`),
  ADD KEY `prepared_by` (`prepared_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `performance_scorecards`
--
ALTER TABLE `performance_scorecards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_period` (`user_id`,`period`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `requesting_dept` (`requesting_dept`),
  ADD KEY `requesting_user` (`requesting_user`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_holiday_date` (`holiday_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `reg_modules`
--
ALTER TABLE `reg_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_code` (`module_code`),
  ADD KEY `programme_id` (`programme_id`);

--
-- Indexes for table `reg_programmes`
--
ALTER TABLE `reg_programmes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `programme_code` (`programme_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `reg_results`
--
ALTER TABLE `reg_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`module_id`,`academic_year`,`semester`,`attempt`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `entered_by` (`entered_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `reg_status_changes`
--
ALTER TABLE `reg_status_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `reg_students`
--
ALTER TABLE `reg_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_no` (`student_no`),
  ADD KEY `programme_id` (`programme_id`),
  ADD KEY `registered_by` (`registered_by`);

--
-- Indexes for table `reg_transcript_requests`
--
ALTER TABLE `reg_transcript_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_report` (`user_id`,`report_date`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `sa_clubs`
--
ALTER TABLE `sa_clubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patron_id` (`patron_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sa_club_members`
--
ALTER TABLE `sa_club_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_membership` (`club_id`,`student_id`);

--
-- Indexes for table `sa_counselling_sessions`
--
ALTER TABLE `sa_counselling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `counsellor_id` (`counsellor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sa_discipline_cases`
--
ALTER TABLE `sa_discipline_cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sa_events`
--
ALTER TABLE `sa_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `organiser_id` (`organiser_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sa_welfare_cases`
--
ALTER TABLE `sa_welfare_cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `security_config`
--
ALTER TABLE `security_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `soh_assessments`
--
ALTER TABLE `soh_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `referred_by` (`referred_by`);

--
-- Indexes for table `soh_courses`
--
ALTER TABLE `soh_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `soh_course_records`
--
ALTER TABLE `soh_course_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `administered_by` (`administered_by`);

--
-- Indexes for table `soh_enrollments`
--
ALTER TABLE `soh_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `soh_lectures`
--
ALTER TABLE `soh_lectures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `soh_materials`
--
ALTER TABLE `soh_materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `soh_material_transactions`
--
ALTER TABLE `soh_material_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `soh_research`
--
ALTER TABLE `soh_research`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `soh_sessions`
--
ALTER TABLE `soh_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `visit_ref` (`visit_ref`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `attended_by` (`attended_by`);

--
-- Indexes for table `soh_students`
--
ALTER TABLE `soh_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registered_by` (`registered_by`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `hod_reviewed_by` (`hod_reviewed_by`),
  ADD KEY `final_reviewed_by` (`final_reviewed_by`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requesting_user_id` (`requesting_user_id`),
  ADD KEY `requesting_dept_id` (`requesting_dept_id`),
  ADD KEY `assigned_dept_id` (`assigned_dept_id`),
  ADD KEY `assigned_user_id` (`assigned_user_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `fk_users_dept` (`department_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acad_attendance_records`
--
ALTER TABLE `acad_attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_attendance_sessions`
--
ALTER TABLE `acad_attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_calendar_events`
--
ALTER TABLE `acad_calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_courses`
--
ALTER TABLE `acad_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_dept_reports`
--
ALTER TABLE `acad_dept_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_exam_schedule`
--
ALTER TABLE `acad_exam_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_graduation_clearance`
--
ALTER TABLE `acad_graduation_clearance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_lecturer_assignments`
--
ALTER TABLE `acad_lecturer_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_marks`
--
ALTER TABLE `acad_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_memos`
--
ALTER TABLE `acad_memos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_memo_recipients`
--
ALTER TABLE `acad_memo_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_notices`
--
ALTER TABLE `acad_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_programmes`
--
ALTER TABLE `acad_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_research_log`
--
ALTER TABLE `acad_research_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_student_progression`
--
ALTER TABLE `acad_student_progression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_supp_applications`
--
ALTER TABLE `acad_supp_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_syllabus_topics`
--
ALTER TABLE `acad_syllabus_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acad_timetable`
--
ALTER TABLE `acad_timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `accreditation_records`
--
ALTER TABLE `accreditation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_applications`
--
ALTER TABLE `adm_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `adm_documents`
--
ALTER TABLE `adm_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_enrollments`
--
ALTER TABLE `adm_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adm_interviews`
--
ALTER TABLE `adm_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_offers`
--
ALTER TABLE `adm_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_scholarships`
--
ALTER TABLE `adm_scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_scores`
--
ALTER TABLE `adm_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adm_scoring_criteria`
--
ALTER TABLE `adm_scoring_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=381;

--
-- AUTO_INCREMENT for table `ai_insights`
--
ALTER TABLE `ai_insights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumni_engagement`
--
ALTER TABLE `alumni_engagement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ca_activity_logs`
--
ALTER TABLE `ca_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ca_events`
--
ALTER TABLE `ca_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_ceo_reports`
--
ALTER TABLE `daily_ceo_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departmental_budget`
--
ALTER TABLE `departmental_budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `elearning_materials`
--
ALTER TABLE `elearning_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `elearning_sessions`
--
ALTER TABLE `elearning_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `elibrary_access_log`
--
ALTER TABLE `elibrary_access_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `elibrary_resources`
--
ALTER TABLE `elibrary_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenditure`
--
ALTER TABLE `expenditure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_audit_logs`
--
ALTER TABLE `financial_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_bank_accounts`
--
ALTER TABLE `fin_bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_bank_entries`
--
ALTER TABLE `fin_bank_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_bank_reconciliations`
--
ALTER TABLE `fin_bank_reconciliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_invoices`
--
ALTER TABLE `fin_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_invoice_items`
--
ALTER TABLE `fin_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_invoice_payments`
--
ALTER TABLE `fin_invoice_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_petty_cash_funds`
--
ALTER TABLE `fin_petty_cash_funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_petty_cash_transactions`
--
ALTER TABLE `fin_petty_cash_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fraud_flags`
--
ALTER TABLE `fraud_flags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_applicants`
--
ALTER TABLE `hr_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_appraisals`
--
ALTER TABLE `hr_appraisals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_assets`
--
ALTER TABLE `hr_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_contracts`
--
ALTER TABLE `hr_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_leave_types`
--
ALTER TABLE `hr_leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `hr_recruitment`
--
ALTER TABLE `hr_recruitment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_staff`
--
ALTER TABLE `hr_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internship_companies`
--
ALTER TABLE `internship_companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internship_placements`
--
ALTER TABLE `internship_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_assets`
--
ALTER TABLE `it_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_helpdesk_chat`
--
ALTER TABLE `it_helpdesk_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_network_devices`
--
ALTER TABLE `it_network_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_network_log`
--
ALTER TABLE `it_network_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_software`
--
ALTER TABLE `it_software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `it_tickets`
--
ALTER TABLE `it_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_ticket_activity_log`
--
ALTER TABLE `it_ticket_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_ticket_comments`
--
ALTER TABLE `it_ticket_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kpi_categories`
--
ALTER TABLE `kpi_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `kpi_submissions`
--
ALTER TABLE `kpi_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_academic_reports`
--
ALTER TABLE `lect_academic_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lect_announcements`
--
ALTER TABLE `lect_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_assessments`
--
ALTER TABLE `lect_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_attendance_records`
--
ALTER TABLE `lect_attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_attendance_sessions`
--
ALTER TABLE `lect_attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_consultation_slots`
--
ALTER TABLE `lect_consultation_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_leave_requests`
--
ALTER TABLE `lect_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_lecture_log`
--
ALTER TABLE `lect_lecture_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_marks`
--
ALTER TABLE `lect_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_materials`
--
ALTER TABLE `lect_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_research_log`
--
ALTER TABLE `lect_research_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_student_queries`
--
ALTER TABLE `lect_student_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lect_timetable_changes`
--
ALTER TABLE `lect_timetable_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `management_reports`
--
ALTER TABLE `management_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_records`
--
ALTER TABLE `media_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_campaigns`
--
ALTER TABLE `mkt_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_events`
--
ALTER TABLE `mkt_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_event_attendees`
--
ALTER TABLE `mkt_event_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_leads`
--
ALTER TABLE `mkt_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_lead_followups`
--
ALTER TABLE `mkt_lead_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mkt_materials`
--
ALTER TABLE `mkt_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `msg_conversations`
--
ALTER TABLE `msg_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `msg_messages`
--
ALTER TABLE `msg_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `msg_participants`
--
ALTER TABLE `msg_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `partnerships`
--
ALTER TABLE `partnerships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_scorecards`
--
ALTER TABLE `performance_scorecards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_holidays`
--
ALTER TABLE `public_holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `reg_modules`
--
ALTER TABLE `reg_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reg_programmes`
--
ALTER TABLE `reg_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reg_results`
--
ALTER TABLE `reg_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reg_status_changes`
--
ALTER TABLE `reg_status_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reg_students`
--
ALTER TABLE `reg_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reg_transcript_requests`
--
ALTER TABLE `reg_transcript_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `sa_clubs`
--
ALTER TABLE `sa_clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_club_members`
--
ALTER TABLE `sa_club_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_counselling_sessions`
--
ALTER TABLE `sa_counselling_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_discipline_cases`
--
ALTER TABLE `sa_discipline_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_events`
--
ALTER TABLE `sa_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_welfare_cases`
--
ALTER TABLE `sa_welfare_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_config`
--
ALTER TABLE `security_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `soh_assessments`
--
ALTER TABLE `soh_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_courses`
--
ALTER TABLE `soh_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `soh_course_records`
--
ALTER TABLE `soh_course_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_enrollments`
--
ALTER TABLE `soh_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_lectures`
--
ALTER TABLE `soh_lectures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_materials`
--
ALTER TABLE `soh_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `soh_material_transactions`
--
ALTER TABLE `soh_material_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_research`
--
ALTER TABLE `soh_research`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_sessions`
--
ALTER TABLE `soh_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soh_students`
--
ALTER TABLE `soh_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=855;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acad_attendance_records`
--
ALTER TABLE `acad_attendance_records`
  ADD CONSTRAINT `acad_attendance_records_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `acad_attendance_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_attendance_sessions`
--
ALTER TABLE `acad_attendance_sessions`
  ADD CONSTRAINT `acad_attendance_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_attendance_sessions_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_calendar_events`
--
ALTER TABLE `acad_calendar_events`
  ADD CONSTRAINT `acad_calendar_events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_courses`
--
ALTER TABLE `acad_courses`
  ADD CONSTRAINT `acad_courses_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `acad_programmes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_dept_reports`
--
ALTER TABLE `acad_dept_reports`
  ADD CONSTRAINT `acad_dept_reports_ibfk_1` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_dept_reports_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_exam_schedule`
--
ALTER TABLE `acad_exam_schedule`
  ADD CONSTRAINT `acad_exam_schedule_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_exam_schedule_ibfk_2` FOREIGN KEY (`invigilator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `acad_exam_schedule_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_graduation_clearance`
--
ALTER TABLE `acad_graduation_clearance`
  ADD CONSTRAINT `acad_graduation_clearance_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `acad_programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `acad_graduation_clearance_ibfk_2` FOREIGN KEY (`cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_lecturer_assignments`
--
ALTER TABLE `acad_lecturer_assignments`
  ADD CONSTRAINT `acad_lecturer_assignments_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_lecturer_assignments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_marks`
--
ALTER TABLE `acad_marks`
  ADD CONSTRAINT `acad_marks_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_marks_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `acad_marks_ibfk_3` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_memos`
--
ALTER TABLE `acad_memos`
  ADD CONSTRAINT `acad_memos_ibfk_1` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_memos_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_memo_recipients`
--
ALTER TABLE `acad_memo_recipients`
  ADD CONSTRAINT `acad_memo_recipients_ibfk_1` FOREIGN KEY (`memo_id`) REFERENCES `acad_memos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_memo_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_notices`
--
ALTER TABLE `acad_notices`
  ADD CONSTRAINT `acad_notices_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_notices_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_programmes`
--
ALTER TABLE `acad_programmes`
  ADD CONSTRAINT `acad_programmes_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_research_log`
--
ALTER TABLE `acad_research_log`
  ADD CONSTRAINT `acad_research_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_research_log_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `acad_student_progression`
--
ALTER TABLE `acad_student_progression`
  ADD CONSTRAINT `acad_student_progression_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `acad_programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `acad_student_progression_ibfk_2` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_supp_applications`
--
ALTER TABLE `acad_supp_applications`
  ADD CONSTRAINT `acad_supp_applications_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_supp_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_syllabus_topics`
--
ALTER TABLE `acad_syllabus_topics`
  ADD CONSTRAINT `acad_syllabus_topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_syllabus_topics_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `acad_timetable`
--
ALTER TABLE `acad_timetable`
  ADD CONSTRAINT `acad_timetable_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acad_timetable_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `accreditation_records`
--
ALTER TABLE `accreditation_records`
  ADD CONSTRAINT `accreditation_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `adm_applications`
--
ALTER TABLE `adm_applications`
  ADD CONSTRAINT `adm_applications_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adm_applications_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_documents`
--
ALTER TABLE `adm_documents`
  ADD CONSTRAINT `adm_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adm_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_enrollments`
--
ALTER TABLE `adm_enrollments`
  ADD CONSTRAINT `adm_enrollments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adm_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_enrollments_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `adm_scholarships` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adm_enrollments_ibfk_3` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_interviews`
--
ALTER TABLE `adm_interviews`
  ADD CONSTRAINT `adm_interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adm_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_interviews_ibfk_2` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_offers`
--
ALTER TABLE `adm_offers`
  ADD CONSTRAINT `adm_offers_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adm_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_offers_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_scholarships`
--
ALTER TABLE `adm_scholarships`
  ADD CONSTRAINT `adm_scholarships_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adm_scores`
--
ALTER TABLE `adm_scores`
  ADD CONSTRAINT `adm_scores_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adm_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_scores_ibfk_2` FOREIGN KEY (`criteria_id`) REFERENCES `adm_scoring_criteria` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adm_scores_ibfk_3` FOREIGN KEY (`scored_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alumni_engagement`
--
ALTER TABLE `alumni_engagement`
  ADD CONSTRAINT `alumni_engagement_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD CONSTRAINT `announcement_departments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ca_activity_logs`
--
ALTER TABLE `ca_activity_logs`
  ADD CONSTRAINT `ca_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ca_events`
--
ALTER TABLE `ca_events`
  ADD CONSTRAINT `ca_events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_ceo_reports`
--
ALTER TABLE `daily_ceo_reports`
  ADD CONSTRAINT `daily_ceo_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departmental_budget`
--
ALTER TABLE `departmental_budget`
  ADD CONSTRAINT `departmental_budget_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elearning_materials`
--
ALTER TABLE `elearning_materials`
  ADD CONSTRAINT `elearning_materials_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `elearning_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `elearning_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `elearning_sessions`
--
ALTER TABLE `elearning_sessions`
  ADD CONSTRAINT `elearning_sessions_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elibrary_resources`
--
ALTER TABLE `elibrary_resources`
  ADD CONSTRAINT `elibrary_resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenditure`
--
ALTER TABLE `expenditure`
  ADD CONSTRAINT `expenditure_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fin_bank_accounts`
--
ALTER TABLE `fin_bank_accounts`
  ADD CONSTRAINT `fin_bank_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_bank_entries`
--
ALTER TABLE `fin_bank_entries`
  ADD CONSTRAINT `fin_bank_entries_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `fin_bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_bank_entries_ibfk_2` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fin_bank_entries_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_bank_reconciliations`
--
ALTER TABLE `fin_bank_reconciliations`
  ADD CONSTRAINT `fin_bank_reconciliations_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `fin_bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_bank_reconciliations_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fin_bank_reconciliations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_invoices`
--
ALTER TABLE `fin_invoices`
  ADD CONSTRAINT `fin_invoices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fin_invoices_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_invoice_items`
--
ALTER TABLE `fin_invoice_items`
  ADD CONSTRAINT `fin_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `fin_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fin_invoice_payments`
--
ALTER TABLE `fin_invoice_payments`
  ADD CONSTRAINT `fin_invoice_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `fin_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_invoice_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_petty_cash_funds`
--
ALTER TABLE `fin_petty_cash_funds`
  ADD CONSTRAINT `fin_petty_cash_funds_ibfk_1` FOREIGN KEY (`custodian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fin_petty_cash_funds_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fin_petty_cash_transactions`
--
ALTER TABLE `fin_petty_cash_transactions`
  ADD CONSTRAINT `fin_petty_cash_transactions_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `fin_petty_cash_funds` (`id`),
  ADD CONSTRAINT `fin_petty_cash_transactions_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fin_petty_cash_transactions_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fraud_flags`
--
ALTER TABLE `fraud_flags`
  ADD CONSTRAINT `fraud_flags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fraud_flags_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fraud_flags_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_applicants`
--
ALTER TABLE `hr_applicants`
  ADD CONSTRAINT `hr_applicants_ibfk_1` FOREIGN KEY (`recruitment_id`) REFERENCES `hr_recruitment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_applicants_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_appraisals`
--
ALTER TABLE `hr_appraisals`
  ADD CONSTRAINT `hr_appraisals_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_appraisals_ibfk_2` FOREIGN KEY (`appraiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_assets`
--
ALTER TABLE `hr_assets`
  ADD CONSTRAINT `hr_assets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `hr_staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hr_assets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  ADD CONSTRAINT `hr_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_contracts`
--
ALTER TABLE `hr_contracts`
  ADD CONSTRAINT `hr_contracts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_contracts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD CONSTRAINT `hr_leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`),
  ADD CONSTRAINT `hr_leave_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_recruitment`
--
ALTER TABLE `hr_recruitment`
  ADD CONSTRAINT `hr_recruitment_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_staff`
--
ALTER TABLE `hr_staff`
  ADD CONSTRAINT `hr_staff_ibfk_1` FOREIGN KEY (`linked_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hr_staff_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `internship_companies`
--
ALTER TABLE `internship_companies`
  ADD CONSTRAINT `internship_companies_ibfk_1` FOREIGN KEY (`linked_partnership_id`) REFERENCES `partnerships` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `internship_placements`
--
ALTER TABLE `internship_placements`
  ADD CONSTRAINT `internship_placements_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `internship_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internship_placements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_assets`
--
ALTER TABLE `it_assets`
  ADD CONSTRAINT `it_assets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_network_devices`
--
ALTER TABLE `it_network_devices`
  ADD CONSTRAINT `it_network_devices_ibfk_1` FOREIGN KEY (`managed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_network_log`
--
ALTER TABLE `it_network_log`
  ADD CONSTRAINT `it_network_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `it_network_devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `it_network_log_ibfk_2` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_software`
--
ALTER TABLE `it_software`
  ADD CONSTRAINT `it_software_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_tickets`
--
ALTER TABLE `it_tickets`
  ADD CONSTRAINT `it_tickets_ibfk_1` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `it_tickets_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `it_tickets_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_ticket_activity_log`
--
ALTER TABLE `it_ticket_activity_log`
  ADD CONSTRAINT `it_ticket_activity_log_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `it_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `it_ticket_activity_log_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_ticket_comments`
--
ALTER TABLE `it_ticket_comments`
  ADD CONSTRAINT `it_ticket_comments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `it_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `it_ticket_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  ADD CONSTRAINT `kpi_attachments_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `kpi_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_categories`
--
ALTER TABLE `kpi_categories`
  ADD CONSTRAINT `kpi_categories_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_submissions`
--
ALTER TABLE `kpi_submissions`
  ADD CONSTRAINT `kpi_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_submissions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `kpi_categories` (`id`),
  ADD CONSTRAINT `kpi_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  ADD CONSTRAINT `kpi_targets_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `kpi_targets_ibfk_2` FOREIGN KEY (`set_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `lect_academic_reports`
--
ALTER TABLE `lect_academic_reports`
  ADD CONSTRAINT `lect_academic_reports_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_academic_reports_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lect_academic_reports_ibfk_3` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lect_announcements`
--
ALTER TABLE `lect_announcements`
  ADD CONSTRAINT `lect_announcements_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_announcements_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lect_assessments`
--
ALTER TABLE `lect_assessments`
  ADD CONSTRAINT `lect_assessments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_assessments_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_assessments_ibfk_3` FOREIGN KEY (`hod_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lect_attendance_records`
--
ALTER TABLE `lect_attendance_records`
  ADD CONSTRAINT `lect_attendance_records_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `lect_attendance_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_attendance_sessions`
--
ALTER TABLE `lect_attendance_sessions`
  ADD CONSTRAINT `lect_attendance_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_attendance_sessions_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_consultation_slots`
--
ALTER TABLE `lect_consultation_slots`
  ADD CONSTRAINT `lect_consultation_slots_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_leave_requests`
--
ALTER TABLE `lect_leave_requests`
  ADD CONSTRAINT `lect_leave_requests_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_leave_requests_ibfk_2` FOREIGN KEY (`substitute_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lect_leave_requests_ibfk_3` FOREIGN KEY (`hod_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lect_leave_requests_ibfk_4` FOREIGN KEY (`final_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lect_lecture_log`
--
ALTER TABLE `lect_lecture_log`
  ADD CONSTRAINT `lect_lecture_log_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_lecture_log_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_marks`
--
ALTER TABLE `lect_marks`
  ADD CONSTRAINT `lect_marks_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `lect_assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_materials`
--
ALTER TABLE `lect_materials`
  ADD CONSTRAINT `lect_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_materials_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_research_log`
--
ALTER TABLE `lect_research_log`
  ADD CONSTRAINT `lect_research_log_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lect_student_queries`
--
ALTER TABLE `lect_student_queries`
  ADD CONSTRAINT `lect_student_queries_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_student_queries_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `acad_courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lect_timetable_changes`
--
ALTER TABLE `lect_timetable_changes`
  ADD CONSTRAINT `lect_timetable_changes_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lect_timetable_changes_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `management_reports`
--
ALTER TABLE `management_reports`
  ADD CONSTRAINT `management_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `management_reports_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_records`
--
ALTER TABLE `media_records`
  ADD CONSTRAINT `media_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mkt_events`
--
ALTER TABLE `mkt_events`
  ADD CONSTRAINT `mkt_events_ibfk_1` FOREIGN KEY (`organiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mkt_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mkt_event_attendees`
--
ALTER TABLE `mkt_event_attendees`
  ADD CONSTRAINT `mkt_event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `mkt_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mkt_event_attendees_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `mkt_leads` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mkt_leads`
--
ALTER TABLE `mkt_leads`
  ADD CONSTRAINT `mkt_leads_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `mkt_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mkt_leads_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `mkt_events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mkt_leads_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mkt_leads_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mkt_lead_followups`
--
ALTER TABLE `mkt_lead_followups`
  ADD CONSTRAINT `mkt_lead_followups_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `mkt_leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mkt_lead_followups_ibfk_2` FOREIGN KEY (`done_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mkt_materials`
--
ALTER TABLE `mkt_materials`
  ADD CONSTRAINT `mkt_materials_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `mkt_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mkt_materials_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD CONSTRAINT `partnerships_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_scorecards`
--
ALTER TABLE `performance_scorecards`
  ADD CONSTRAINT `performance_scorecards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_scorecards_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD CONSTRAINT `procurement_requests_ibfk_1` FOREIGN KEY (`requesting_dept`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `procurement_requests_ibfk_2` FOREIGN KEY (`requesting_user`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `procurement_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD CONSTRAINT `public_holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_modules`
--
ALTER TABLE `reg_modules`
  ADD CONSTRAINT `reg_modules_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `reg_programmes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_programmes`
--
ALTER TABLE `reg_programmes`
  ADD CONSTRAINT `reg_programmes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_results`
--
ALTER TABLE `reg_results`
  ADD CONSTRAINT `reg_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `reg_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reg_results_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `reg_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reg_results_ibfk_3` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reg_results_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_status_changes`
--
ALTER TABLE `reg_status_changes`
  ADD CONSTRAINT `reg_status_changes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `reg_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reg_status_changes_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_students`
--
ALTER TABLE `reg_students`
  ADD CONSTRAINT `reg_students_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `reg_programmes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reg_students_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reg_transcript_requests`
--
ALTER TABLE `reg_transcript_requests`
  ADD CONSTRAINT `reg_transcript_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `reg_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reg_transcript_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reg_transcript_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_clubs`
--
ALTER TABLE `sa_clubs`
  ADD CONSTRAINT `sa_clubs_ibfk_1` FOREIGN KEY (`patron_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_clubs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_club_members`
--
ALTER TABLE `sa_club_members`
  ADD CONSTRAINT `sa_club_members_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `sa_clubs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sa_counselling_sessions`
--
ALTER TABLE `sa_counselling_sessions`
  ADD CONSTRAINT `sa_counselling_sessions_ibfk_1` FOREIGN KEY (`counsellor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_counselling_sessions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_discipline_cases`
--
ALTER TABLE `sa_discipline_cases`
  ADD CONSTRAINT `sa_discipline_cases_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_discipline_cases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_events`
--
ALTER TABLE `sa_events`
  ADD CONSTRAINT `sa_events_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `sa_clubs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_events_ibfk_2` FOREIGN KEY (`organiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_events_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_welfare_cases`
--
ALTER TABLE `sa_welfare_cases`
  ADD CONSTRAINT `sa_welfare_cases_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sa_welfare_cases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_assessments`
--
ALTER TABLE `soh_assessments`
  ADD CONSTRAINT `soh_assessments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `soh_students` (`id`),
  ADD CONSTRAINT `soh_assessments_ibfk_2` FOREIGN KEY (`visit_id`) REFERENCES `soh_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `soh_assessments_ibfk_3` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_courses`
--
ALTER TABLE `soh_courses`
  ADD CONSTRAINT `soh_courses_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_course_records`
--
ALTER TABLE `soh_course_records`
  ADD CONSTRAINT `soh_course_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `soh_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soh_course_records_ibfk_2` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_enrollments`
--
ALTER TABLE `soh_enrollments`
  ADD CONSTRAINT `soh_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `soh_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `soh_lectures`
--
ALTER TABLE `soh_lectures`
  ADD CONSTRAINT `soh_lectures_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `soh_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soh_lectures_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_materials`
--
ALTER TABLE `soh_materials`
  ADD CONSTRAINT `soh_materials_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_material_transactions`
--
ALTER TABLE `soh_material_transactions`
  ADD CONSTRAINT `soh_material_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `soh_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soh_material_transactions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `soh_students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `soh_material_transactions_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_research`
--
ALTER TABLE `soh_research`
  ADD CONSTRAINT `soh_research_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_sessions`
--
ALTER TABLE `soh_sessions`
  ADD CONSTRAINT `soh_sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `soh_students` (`id`),
  ADD CONSTRAINT `soh_sessions_ibfk_2` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soh_students`
--
ALTER TABLE `soh_students`
  ADD CONSTRAINT `soh_students_ibfk_1` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD CONSTRAINT `sponsors_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  ADD CONSTRAINT `staff_leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leave_requests_ibfk_2` FOREIGN KEY (`hod_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_leave_requests_ibfk_3` FOREIGN KEY (`final_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`requesting_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`requesting_dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
