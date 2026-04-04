-- Church Facility Manager DB Snapshot
-- Generated: 2026-04-04 22:57:57
-- Source: DESKTOP-57SPTNP

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `buildings`;
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `buildings` (`id`, `name`, `description`, `created_at`) VALUES ('1', 'North Building', '', '2026-03-25 20:32:22');
INSERT INTO `buildings` (`id`, `name`, `description`, `created_at`) VALUES ('5', 'South Building', NULL, '2026-03-31 11:28:47');

DROP TABLE IF EXISTS `cleaning_schedule_rooms`;
CREATE TABLE `cleaning_schedule_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_room` (`schedule_id`,`room_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `cleaning_schedule_rooms_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `cleaning_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cleaning_schedule_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('1', '1', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('2', '2', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('3', '3', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('4', '4', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('5', '5', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('6', '6', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('7', '7', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('8', '8', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('9', '9', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('10', '10', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('11', '11', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('12', '12', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('14', '13', '66');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('15', '14', '65');
INSERT INTO `cleaning_schedule_rooms` (`id`, `schedule_id`, `room_id`) VALUES ('16', '15', '94');

DROP TABLE IF EXISTS `cleaning_schedule_task_groups`;
CREATE TABLE `cleaning_schedule_task_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `task_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_tg` (`schedule_id`,`task_group_id`),
  KEY `task_group_id` (`task_group_id`),
  CONSTRAINT `cleaning_schedule_task_groups_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `cleaning_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cleaning_schedule_task_groups_ibfk_2` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cleaning_schedule_task_groups` (`id`, `schedule_id`, `task_group_id`) VALUES ('2', '13', '1');

DROP TABLE IF EXISTS `cleaning_schedule_tasks`;
CREATE TABLE `cleaning_schedule_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sched_task` (`schedule_id`,`task_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `cleaning_schedule_tasks_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `cleaning_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cleaning_schedule_tasks_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('1', '1', '1');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('2', '2', '2');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('3', '3', '3');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('4', '4', '5');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('5', '5', '8');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('6', '6', '9');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('7', '7', '1');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('8', '8', '2');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('9', '9', '3');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('10', '10', '5');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('11', '11', '12');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('12', '12', '15');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('13', '14', '5');
INSERT INTO `cleaning_schedule_tasks` (`id`, `schedule_id`, `task_id`) VALUES ('14', '15', '2');

DROP TABLE IF EXISTS `cleaning_schedules`;
CREATE TABLE `cleaning_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `frequency` enum('daily','weekdays','specific_days','weekly','biweekly','monthly','yearly') NOT NULL DEFAULT 'weekly',
  `frequency_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'e.g. {"days":[1,3,5]} for specific weekdays, {"day_of_month":15}, {"month":3,"day":1}' CHECK (json_valid(`frequency_config`)),
  `assign_to_type` enum('user','role') NOT NULL DEFAULT 'user',
  `assign_to_user_id` int(11) DEFAULT NULL,
  `assign_to_role` varchar(50) DEFAULT NULL COMMENT 'e.g. custodial',
  `deadline_time` time DEFAULT NULL COMMENT 'optional deadline time of day',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assign_to_user_id` (`assign_to_user_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `cleaning_schedules_ibfk_1` FOREIGN KEY (`assign_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cleaning_schedules_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('1', 'Breakdown Sunday', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('2', 'Clean Carpets', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('3', 'Disinfect Changing Table', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('4', 'Empty Diaper Genie', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('5', 'Empty Trash Small', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('6', 'Mop', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:23', '2026-04-04 09:15:23');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('7', 'Breakdown Sunday', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('8', 'Clean Carpets', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('9', 'Disinfect Changing Table', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('10', 'Empty Diaper Genie', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('11', 'Scrub Sinks', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('12', 'Spot Clean Door Glass', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 09:15:43', '2026-04-04 09:15:43');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('13', 'Test 1', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 12:19:24', '2026-04-04 12:20:26');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('14', 'Empty Diaper Genie', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 12:43:35', '2026-04-04 12:43:35');
INSERT INTO `cleaning_schedules` (`id`, `name`, `frequency`, `frequency_config`, `assign_to_type`, `assign_to_user_id`, `assign_to_role`, `deadline_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES ('15', 'Clean Carpets', 'weekly', '{\"day_of_week\":1}', 'user', '16', NULL, NULL, '1', '16', '2026-04-04 12:44:36', '2026-04-04 12:44:36');

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `equipment_catalog`;
CREATE TABLE `equipment_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` enum('furniture','av_tech','kitchen','other') NOT NULL DEFAULT 'other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `floors`;
CREATE TABLE `floors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `floor_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `building_id` (`building_id`),
  CONSTRAINT `floors_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `floors` (`id`, `building_id`, `name`, `floor_order`, `created_at`) VALUES ('1', '1', 'First Floor', '1', '2026-03-25 20:32:45');
INSERT INTO `floors` (`id`, `building_id`, `name`, `floor_order`, `created_at`) VALUES ('2', '1', 'Second Floor', '2', '2026-03-25 20:33:01');
INSERT INTO `floors` (`id`, `building_id`, `name`, `floor_order`, `created_at`) VALUES ('7', '5', 'First Floor', '1', '2026-03-31 11:29:08');
INSERT INTO `floors` (`id`, `building_id`, `name`, `floor_order`, `created_at`) VALUES ('8', '5', 'Second Floor', '2', '2026-03-31 12:21:45');

DROP TABLE IF EXISTS `janitor_task_assignments`;
CREATE TABLE `janitor_task_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_group_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `deadline` datetime DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL COMMENT 'if auto-triggered from a reservation',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'user id of janitor',
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `schedule_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`assigned_date`),
  KEY `idx_assignee` (`assigned_to`,`assigned_date`),
  KEY `task_group_id` (`task_group_id`),
  KEY `room_id` (`room_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `fk_assignment_schedule` (`schedule_id`),
  KEY `fk_jta_task` (`task_id`),
  CONSTRAINT `fk_assignment_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `cleaning_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_jta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `janitor_task_assignments_ibfk_1` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `janitor_task_assignments_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `janitor_task_assignments_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `janitor_task_assignments_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('1', NULL, '1', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('2', NULL, '1', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('3', NULL, '1', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('4', NULL, '1', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('5', NULL, '1', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('6', NULL, '1', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('7', NULL, '1', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('8', NULL, '1', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('9', NULL, '1', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '1');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('10', NULL, '2', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('11', NULL, '2', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('12', NULL, '2', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('13', NULL, '2', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('14', NULL, '2', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('15', NULL, '2', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('16', NULL, '2', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('17', NULL, '2', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('18', NULL, '2', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '2');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('19', NULL, '3', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('20', NULL, '3', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('21', NULL, '3', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('22', NULL, '3', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('23', NULL, '3', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('24', NULL, '3', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('25', NULL, '3', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('26', NULL, '3', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('27', NULL, '3', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '3');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('28', NULL, '5', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('29', NULL, '5', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('30', NULL, '5', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('31', NULL, '5', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('32', NULL, '5', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('33', NULL, '5', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('34', NULL, '5', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('35', NULL, '5', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('36', NULL, '5', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '4');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('37', NULL, '8', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('38', NULL, '8', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('39', NULL, '8', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('40', NULL, '8', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('41', NULL, '8', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('42', NULL, '8', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('43', NULL, '8', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('44', NULL, '8', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('45', NULL, '8', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '5');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('46', NULL, '9', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('47', NULL, '9', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('48', NULL, '9', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('49', NULL, '9', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('50', NULL, '9', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('51', NULL, '9', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('52', NULL, '9', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('53', NULL, '9', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('54', NULL, '9', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '6');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('55', NULL, '1', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('56', NULL, '1', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('57', NULL, '1', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('58', NULL, '1', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('59', NULL, '1', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('60', NULL, '1', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('61', NULL, '1', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('62', NULL, '1', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('63', NULL, '1', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '7');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('64', NULL, '2', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('65', NULL, '2', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('66', NULL, '2', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('67', NULL, '2', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('68', NULL, '2', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('69', NULL, '2', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('70', NULL, '2', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('71', NULL, '2', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('72', NULL, '2', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '8');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('73', NULL, '3', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('74', NULL, '3', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('75', NULL, '3', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('76', NULL, '3', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('77', NULL, '3', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('78', NULL, '3', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('79', NULL, '3', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('80', NULL, '3', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('81', NULL, '3', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '9');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('82', NULL, '5', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('83', NULL, '5', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('84', NULL, '5', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('85', NULL, '5', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('86', NULL, '5', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('87', NULL, '5', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('88', NULL, '5', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('89', NULL, '5', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('90', NULL, '5', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '10');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('91', NULL, '12', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('92', NULL, '12', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('93', NULL, '12', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('94', NULL, '12', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('95', NULL, '12', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('96', NULL, '12', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('97', NULL, '12', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('98', NULL, '12', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('99', NULL, '12', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '11');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('100', NULL, '15', '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('101', NULL, '15', '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('102', NULL, '15', '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('103', NULL, '15', '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('104', NULL, '15', '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('105', NULL, '15', '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('106', NULL, '15', '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:47', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('107', NULL, '15', '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:48', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('108', NULL, '15', '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 09:15:48', '12');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('109', '1', NULL, '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('110', '1', NULL, '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('111', '1', NULL, '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('112', '1', NULL, '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('113', '1', NULL, '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('114', '1', NULL, '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('115', '1', NULL, '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('116', '1', NULL, '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('117', '1', NULL, '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '18', 'pending', NULL, '2026-04-04 12:19:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('118', '1', NULL, '66', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'in_progress', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('119', '1', NULL, '66', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('120', '1', NULL, '66', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('121', '1', NULL, '66', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('122', '1', NULL, '66', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('123', '1', NULL, '66', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('124', '1', NULL, '66', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('125', '1', NULL, '66', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('126', '1', NULL, '66', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:20:27', '13');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('127', NULL, '5', '65', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('128', NULL, '5', '65', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('129', NULL, '5', '65', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('130', NULL, '5', '65', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('131', NULL, '5', '65', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('132', NULL, '5', '65', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('133', NULL, '5', '65', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('134', NULL, '5', '65', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('135', NULL, '5', '65', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:43:37', '14');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('136', NULL, '2', '94', '2026-04-06', '2026-04-06 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('137', NULL, '2', '94', '2026-04-13', '2026-04-13 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('138', NULL, '2', '94', '2026-04-20', '2026-04-20 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('139', NULL, '2', '94', '2026-04-27', '2026-04-27 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('140', NULL, '2', '94', '2026-05-04', '2026-05-04 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('141', NULL, '2', '94', '2026-05-11', '2026-05-11 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('142', NULL, '2', '94', '2026-05-18', '2026-05-18 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('143', NULL, '2', '94', '2026-05-25', '2026-05-25 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');
INSERT INTO `janitor_task_assignments` (`id`, `task_group_id`, `task_id`, `room_id`, `assigned_date`, `deadline`, `reservation_id`, `assigned_to`, `status`, `completed_at`, `created_at`, `schedule_id`) VALUES ('144', NULL, '2', '94', '2026-06-01', '2026-06-01 16:00:00', NULL, '16', 'pending', NULL, '2026-04-04 12:44:40', '15');

DROP TABLE IF EXISTS `janitor_task_checklist`;
CREATE TABLE `janitor_task_checklist` (
  `assignment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`assignment_id`,`task_id`),
  KEY `task_id` (`task_id`),
  KEY `fk_jtc_completed_by` (`completed_by`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_jtc_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `janitor_task_checklist_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `janitor_task_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `janitor_task_checklist_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('1', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('2', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('3', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('4', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('5', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('6', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('7', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('8', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('9', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('10', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('11', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('12', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('13', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('14', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('15', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('16', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('17', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('18', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('19', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('20', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('21', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('22', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('23', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('24', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('25', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('26', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('27', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('28', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('29', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('30', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('31', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('32', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('33', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('34', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('35', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('36', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('37', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('38', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('39', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('40', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('41', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('42', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('43', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('44', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('45', '8', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('46', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('47', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('48', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('49', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('50', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('51', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('52', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('53', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('54', '9', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('55', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('56', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('57', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('58', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('59', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('60', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('61', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('62', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('63', '1', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('64', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('65', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('66', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('67', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('68', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('69', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('70', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('71', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('72', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('73', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('74', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('75', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('76', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('77', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('78', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('79', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('80', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('81', '3', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('82', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('83', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('84', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('85', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('86', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('87', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('88', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('89', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('90', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('91', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('92', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('93', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('94', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('95', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('96', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('97', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('98', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('99', '12', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('100', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('101', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('102', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('103', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('104', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('105', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('106', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('107', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('108', '15', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('109', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('109', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('109', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('110', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('110', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('110', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('111', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('111', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('111', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('112', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('112', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('112', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('113', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('113', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('113', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('114', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('114', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('114', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('115', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('115', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('115', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('116', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('116', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('116', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('117', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('117', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('117', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('118', '30', '1', '2026-04-04 12:29:13', '16');
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('118', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('118', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('119', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('119', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('119', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('120', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('120', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('120', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('121', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('121', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('121', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('122', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('122', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('122', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('123', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('123', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('123', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('124', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('124', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('124', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('125', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('125', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('125', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('126', '30', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('126', '31', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('126', '32', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('127', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('128', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('129', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('130', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('131', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('132', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('133', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('134', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('135', '5', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('136', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('137', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('138', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('139', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('140', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('141', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('142', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('143', '2', '0', NULL, NULL);
INSERT INTO `janitor_task_checklist` (`assignment_id`, `task_id`, `completed`, `completed_at`, `completed_by`) VALUES ('144', '2', '0', NULL, NULL);

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `materials_catalog`;
CREATE TABLE `materials_catalog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `type` enum('material','supply','tool') NOT NULL DEFAULT 'supply',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name_type` (`name`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `materials_catalog` (`id`, `name`, `type`, `created_at`) VALUES ('1', 'Vacuum, Standard', 'tool', '2026-03-25 21:47:04');
INSERT INTO `materials_catalog` (`id`, `name`, `type`, `created_at`) VALUES ('2', 'Rag', 'tool', '2026-03-25 21:48:12');

DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `organizations` (`id`, `name`, `created_at`) VALUES ('1', 'Kevin Gunn', '2026-03-28 15:56:58');
INSERT INTO `organizations` (`id`, `name`, `created_at`) VALUES ('2', 'Men\'s Ministry', '2026-03-30 16:55:29');

DROP TABLE IF EXISTS `reservation_exceptions`;
CREATE TABLE `reservation_exceptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `exception_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_res_date` (`reservation_id`,`exception_date`),
  CONSTRAINT `reservation_exceptions_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `reservation_rooms`;
CREATE TABLE `reservation_rooms` (
  `reservation_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`reservation_id`,`room_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `reservation_rooms_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('5', '66');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('6', '67');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('7', '85');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('10', '61');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('10', '62');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('10', '65');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('10', '89');
INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`) VALUES ('10', '92');

DROP TABLE IF EXISTS `reservation_task_groups`;
CREATE TABLE `reservation_task_groups` (
  `reservation_id` int(11) NOT NULL,
  `task_group_id` int(11) NOT NULL,
  PRIMARY KEY (`reservation_id`,`task_group_id`),
  KEY `task_group_id` (`task_group_id`),
  CONSTRAINT `reservation_task_groups_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_task_groups_ibfk_2` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_rule` varchar(50) DEFAULT NULL COMMENT 'weekly | biweekly | monthly | daily',
  `recurrence_end_date` date DEFAULT NULL,
  `parent_reservation_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cleanup_mode` enum('no','auto','custom') NOT NULL DEFAULT 'auto',
  PRIMARY KEY (`id`),
  KEY `idx_start` (`start_datetime`),
  KEY `idx_org` (`organization_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reservations` (`id`, `title`, `organization_id`, `start_datetime`, `end_datetime`, `notes`, `is_recurring`, `recurrence_rule`, `recurrence_end_date`, `parent_reservation_id`, `created_by`, `created_at`, `updated_at`, `cleanup_mode`) VALUES ('5', '2', NULL, '2026-03-20 10:00:00', '2026-03-20 13:00:00', NULL, '0', NULL, NULL, NULL, '16', '2026-03-30 08:54:24', '2026-03-30 08:54:24', 'auto');
INSERT INTO `reservations` (`id`, `title`, `organization_id`, `start_datetime`, `end_datetime`, `notes`, `is_recurring`, `recurrence_rule`, `recurrence_end_date`, `parent_reservation_id`, `created_by`, `created_at`, `updated_at`, `cleanup_mode`) VALUES ('6', '3', NULL, '2026-03-20 13:00:00', '2026-03-20 14:00:00', NULL, '0', NULL, NULL, NULL, '16', '2026-03-30 08:54:37', '2026-03-30 08:54:37', 'auto');
INSERT INTO `reservations` (`id`, `title`, `organization_id`, `start_datetime`, `end_datetime`, `notes`, `is_recurring`, `recurrence_rule`, `recurrence_end_date`, `parent_reservation_id`, `created_by`, `created_at`, `updated_at`, `cleanup_mode`) VALUES ('7', 'Kevin\'s Birthday', '1', '2026-03-18 13:00:00', '2026-03-18 17:00:00', NULL, '0', NULL, NULL, NULL, '16', '2026-03-30 10:09:00', '2026-03-30 10:09:00', 'auto');
INSERT INTO `reservations` (`id`, `title`, `organization_id`, `start_datetime`, `end_datetime`, `notes`, `is_recurring`, `recurrence_rule`, `recurrence_end_date`, `parent_reservation_id`, `created_by`, `created_at`, `updated_at`, `cleanup_mode`) VALUES ('10', 'Men\'s Breakfast', '2', '2026-03-07 09:00:00', '2026-03-07 12:00:00', NULL, '1', 'monthly:nth:first:SAT', NULL, NULL, '16', '2026-03-30 16:55:46', '2026-03-30 21:48:41', 'auto');

DROP TABLE IF EXISTS `room_default_task_groups`;
CREATE TABLE `room_default_task_groups` (
  `room_id` int(11) NOT NULL,
  `task_group_id` int(11) NOT NULL,
  PRIMARY KEY (`room_id`,`task_group_id`),
  KEY `task_group_id` (`task_group_id`),
  CONSTRAINT `room_default_task_groups_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_default_task_groups_ibfk_2` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('56', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('57', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('59', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('60', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('65', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('66', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('67', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('71', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('72', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('73', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('80', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('81', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('82', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('88', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('93', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('110', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('119', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('122', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('123', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('125', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('128', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('131', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('132', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('135', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('179', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('180', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('181', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('185', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('191', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('192', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('193', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('194', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('202', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('203', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('204', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('205', '1');
INSERT INTO `room_default_task_groups` (`room_id`, `task_group_id`) VALUES ('211', '1');

DROP TABLE IF EXISTS `room_equipment`;
CREATE TABLE `room_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `is_movable` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_equipment` (`room_id`,`equipment_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `room_equipment_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `room_link_members`;
CREATE TABLE `room_link_members` (
  `link_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`,`room_id`),
  KEY `idx_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('3', '66', 'Sanctuary');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('3', '94', 'Sanctuary');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('4', '76', 'Stair');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('4', '99', 'Stair 3');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('5', '78', 'Stair');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('5', '98', 'Stair 2 (copy)');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('6', '77', 'Elevator');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('6', '100', 'Elevator');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('7', '110', 'Lobby');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('7', '117', 'Lobby U');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('8', '144', 'Stair 1');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('8', '145', 'Stair 1');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('9', '156', 'Stair 5');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('9', '162', 'Stair 5 (copy)');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('10', '65', 'Gym');
INSERT INTO `room_link_members` (`link_id`, `room_id`, `original_name`) VALUES ('10', '92', 'Gym');

DROP TABLE IF EXISTS `room_links`;
CREATE TABLE `room_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `building_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_building` (`building_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('3', 'Sanctuary', '1', '2026-03-30 13:57:36');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('4', 'Stair 3', '1', '2026-03-31 08:34:31');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('5', 'Stair 2', '1', '2026-03-31 08:34:45');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('6', 'Elevator', '1', '2026-03-31 08:35:28');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('7', 'Lobby', '1', '2026-03-31 09:31:10');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('8', 'Stair 1', '1', '2026-03-31 10:45:21');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('9', 'Stair 5', '1', '2026-03-31 17:10:26');
INSERT INTO `room_links` (`id`, `name`, `building_id`, `created_at`) VALUES ('10', 'Gym', '1', '2026-03-31 17:29:31');

DROP TABLE IF EXISTS `room_tasks`;
CREATE TABLE `room_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly','after_every_use','as_needed') NOT NULL DEFAULT 'daily',
  `schedule_config` text DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_task` (`room_id`,`task_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `room_tasks_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_tasks_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `task_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `floor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `abbreviation` varchar(20) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `default_setup` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `map_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of [x,y] pairs in feet' CHECK (json_valid(`map_points`)),
  `is_reservable` tinyint(1) NOT NULL DEFAULT 1,
  `is_storage` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `floor_id` (`floor_id`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('2', '1', 'Gymnasium', NULL, NULL, NULL, NULL, NULL, '2026-03-25 20:44:45', '2026-03-25 20:44:45', NULL, '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('56', '1', 'Counseling Room', NULL, NULL, NULL, NULL, NULL, '2026-03-29 13:50:26', '2026-03-31 17:04:54', '[[0,106],[14,106],[14,120],[0,120]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('57', '1', 'Break Room', NULL, NULL, NULL, NULL, NULL, '2026-03-29 13:50:55', '2026-03-29 19:18:03', '[[14,106],[30,106],[30,120],[14,120]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('58', '1', 'CommOpps', NULL, NULL, NULL, NULL, NULL, '2026-03-29 13:51:44', '2026-03-31 17:04:54', '[[30,106],[44,106],[44,120],[30,120]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('59', '1', 'Men\'s South', NULL, NULL, NULL, NULL, NULL, '2026-03-29 13:53:18', '2026-03-31 17:04:54', '[[53,106],[62,106],[62,120],[53,120]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('60', '1', 'Women\'s South', 'WS', NULL, NULL, NULL, NULL, '2026-03-29 13:54:15', '2026-03-31 17:04:54', '[[44,106],[53,106],[53,120],[44,120]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('61', '1', 'Kitchen', 'Kit', NULL, NULL, NULL, NULL, '2026-03-29 13:55:12', '2026-03-29 19:18:03', '[[0,80],[40,80],[40,100],[0,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('62', '1', 'Coffee', 'C', NULL, NULL, NULL, NULL, '2026-03-29 13:56:20', '2026-03-29 19:18:03', '[[40,88],[49,88],[49,100],[40,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('64', '1', 'Electrical', 'Elec', NULL, NULL, NULL, NULL, '2026-03-29 15:03:45', '2026-03-31 17:04:54', '[[49,88],[62,88],[62,100],[49,100]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('65', '1', 'Gym', 'Gym', NULL, NULL, NULL, NULL, '2026-03-29 15:11:18', '2026-04-04 13:00:12', '[[0,0],[58,0],[58,39],[62,39],[62,45],[58,45],[58,80],[62,80],[62,88],[40,88],[40,80],[4,80],[4,48],[0,48],[0,29],[4,29],[4,11],[0,11]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('66', '1', 'Sanctuary', 'Sanc', NULL, NULL, NULL, NULL, '2026-03-29 15:13:06', '2026-04-01 10:50:14', '[[120,6],[120,0],[129,0],[129,4],[148,4],[148,60],[142,60],[142,60],[137,65],[140,68],[136,72],[133,69],[128,74],[128,80],[72,80],[72,73],[72,61],[68,61],[68,50],[74,50],[82,50],[82,43],[86,43],[113,16],[113,6]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('67', '1', 'Nursery', 'Nurs', NULL, NULL, NULL, NULL, '2026-03-29 15:43:58', '2026-03-29 19:18:03', '[[173,45],[173,20],[164,20],[164,14],[168,14],[168,0],[187,0],[187,80],[173,80],[162,69],[172,59],[167,54],[166,54],[166,45]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('68', '1', 'Lounge', 'L', NULL, NULL, NULL, NULL, '2026-03-29 15:49:35', '2026-03-31 17:04:54', '[[154,6],[168,6],[168,14],[154,14]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('69', '1', 'Storage', 'S', NULL, NULL, NULL, NULL, '2026-03-29 15:50:38', '2026-03-31 18:51:41', '[[161,0],[168,0],[168,6],[161,6]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('70', '1', 'Janitorial', 'J', NULL, NULL, NULL, NULL, '2026-03-29 15:51:02', '2026-03-31 17:04:54', '[[161,0],[161,6],[154,6],[154,0]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('71', '1', 'Women\'s North', 'WN', NULL, NULL, NULL, NULL, '2026-03-29 15:52:09', '2026-03-31 17:04:54', '[[154,14],[164,14],[164,20],[167,20],[167,31],[154,31]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('72', '1', 'Men\'s North', 'MN', NULL, NULL, NULL, NULL, '2026-03-29 15:53:40', '2026-03-31 17:04:54', '[[154,38],[166,38],[166,54],[160,54],[154,54]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('73', '1', 'Nursery Restroom', 'N.R.', NULL, NULL, NULL, NULL, '2026-03-29 15:58:38', '2026-03-31 20:10:20', '[[167,20],[173,20],[173,29],[167,29]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('74', '1', 'Family Room', 'F.R.', NULL, NULL, NULL, NULL, '2026-03-29 17:10:07', '2026-03-29 19:18:03', '[[154,31],[167,31],[167,29],[173,29],[173,45],[166,45],[166,38],[154,38]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('75', '1', 'Elevator Machine Room', 'E.M.', NULL, NULL, NULL, NULL, '2026-03-29 17:14:29', '2026-03-31 17:04:54', '[[162,54],[167,54],[172,59],[166,65],[162,61]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('76', '1', 'Stair 3', NULL, NULL, NULL, NULL, NULL, '2026-03-29 17:18:28', '2026-03-31 17:04:54', '[[157,64],[162,69],[165,72],[162,75],[152,65],[153,64]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('77', '1', 'Elevator', 'Elev', NULL, NULL, NULL, NULL, '2026-03-29 17:27:36', '2026-03-31 17:04:54', '[[162,61],[166,65],[162,69],[158,65]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('78', '1', 'Stair 2', 'ST', NULL, NULL, NULL, NULL, '2026-03-29 17:30:52', '2026-03-31 17:04:54', '[[143,94],[140,97],[132,89],[132,85],[133,84]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('79', '1', 'Preschool Checkin', 'T.C.', NULL, NULL, NULL, NULL, '2026-03-29 17:34:08', '2026-03-31 20:13:00', '[[114,86],[122,86],[128,92],[128,100],[114,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('80', '1', 'Preschool Restroom', 'RR', NULL, NULL, NULL, NULL, '2026-03-29 17:37:11', '2026-03-31 20:13:00', '[[108,86],[114,86],[114,100],[108,100]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('81', '1', 'Prayer Room West', 'PR.W.', NULL, NULL, NULL, NULL, '2026-03-29 17:39:48', '2026-03-29 19:18:03', '[[68,86],[80,86],[80,100],[68,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('82', '1', 'Prayer Room East', 'PR.E.', NULL, NULL, NULL, NULL, '2026-03-29 17:40:51', '2026-03-29 19:18:03', '[[68,106],[80,106],[80,120],[68,120]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('83', '1', 'Preschool West', 'T.W.', NULL, NULL, NULL, NULL, '2026-03-29 17:42:14', '2026-03-31 20:13:00', '[[80,86],[108,86],[108,100],[80,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('84', '1', 'Preschool East', 'T.E.', NULL, NULL, NULL, NULL, '2026-03-29 17:42:35', '2026-03-31 20:13:00', '[[80,106],[108,106],[108,120],[80,120]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('85', '1', 'Preschool Main', 'T.M.', NULL, NULL, NULL, NULL, '2026-03-29 17:43:18', '2026-03-31 20:13:00', '[[108,100],[137,100],[147,110],[147,120],[108,120],[108,106],[80,106],[80,100]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('86', '1', 'Vestibule', 'V', NULL, NULL, NULL, NULL, '2026-03-29 17:45:43', '2026-03-31 17:04:54', '[[157,120],[147,120],[147,112],[157,112]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('87', '1', 'Vestibule', 'V', NULL, NULL, NULL, NULL, '2026-03-29 17:54:53', '2026-03-31 17:04:54', '[[179,80],[187,80],[187,90],[179,90]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('88', '1', 'Children\'s Lobby', 'C.L.', NULL, NULL, NULL, NULL, '2026-03-29 17:55:39', '2026-03-31 08:19:07', '[[146,71],[149,74],[155,68],[162,75],[165,72],[173,80],[179,80],[179,90],[178,90],[157,111],[157,112],[147,112],[147,110],[137,100],[128,100],[128,92],[122,86],[132,86],[132,89],[140,97],[143,94],[136,87],[142,81],[139,78]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('89', '1', 'Corridor B', 'C-B', NULL, NULL, NULL, NULL, '2026-03-29 17:58:38', '2026-03-31 17:04:54', '[[0,100],[80,100],[80,106],[68,106],[68,120],[62,120],[62,106],[0,106]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('90', '1', 'Corridor A', 'C-A', NULL, NULL, NULL, NULL, '2026-03-29 18:00:14', '2026-03-31 17:04:54', '[[62,0],[68,0],[68,100],[62,100],[62,57],[58,57],[58,45],[62,45],[62,39],[62,19],[58,19],[58,19],[58,0]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('91', '1', 'Corridor C', 'C-C', NULL, NULL, NULL, NULL, '2026-03-29 18:00:49', '2026-03-31 17:04:54', '[[68,80],[128,80],[148,60],[148,0],[154,0],[154,54],[162,54],[162,61],[158,65],[157,64],[153,64],[132,85],[132,86],[68,86]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('92', '2', 'Gym', 'Gym', NULL, NULL, NULL, NULL, '2026-03-29 19:19:26', '2026-04-04 13:00:12', '[[0,0],[58,0],[58,80],[0,80]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('93', '1', 'Green Room', 'GR', NULL, NULL, NULL, NULL, '2026-03-29 19:27:34', '2026-03-31 11:25:53', '[[68,0],[110,0],[110,6],[98,6],[74,30],[74,50],[68,50]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('94', '2', 'Sanctuary', 'Sanc', NULL, NULL, NULL, NULL, '2026-03-30 07:58:51', '2026-03-31 11:26:06', '[[74,30],[98,6],[120,6],[120,0],[129,0],[129,4],[135,4],[135,37],[105,66],[72,66],[72,61],[68,61],[68,50],[74,50]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('98', '2', 'Stair 2', 'S2(', NULL, NULL, NULL, NULL, '2026-03-31 08:33:37', '2026-03-31 17:05:50', '[[139,78],[142,81],[136,87],[143,94],[140,97],[132,89],[132,85]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('99', '2', 'Stair 3', 'S3', NULL, NULL, NULL, NULL, '2026-03-31 08:34:03', '2026-03-31 17:05:50', '[[153,64],[157,64],[162,69],[165,72],[162,75],[155,68],[149,74],[146,71]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('100', '2', 'Elevator', 'ELEV', NULL, NULL, NULL, NULL, '2026-03-31 08:35:10', '2026-03-31 17:05:50', '[[162,61],[166,65],[162,69],[158,65]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('102', '2', 'Children\'s Lobby Up', 'CLU', NULL, NULL, NULL, NULL, '2026-03-31 08:37:05', '2026-03-31 10:23:21', '[[146,71],[149,74],[155,68],[162,75],[165,72],[173,80],[179,80],[179,90],[178,90],[157,111],[157,112],[147,112],[147,110],[137,100],[128,100],[128,93],[132,89],[140,97],[143,94],[136,87],[142,81],[139,78]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('103', '1', 'Office Corridor Down', 'OCD', NULL, NULL, NULL, NULL, '2026-03-31 09:08:44', '2026-03-31 17:04:54', '[[75,-8],[139,-8],[139,0],[75,0]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('104', '1', 'Office 105', 'O-105', NULL, NULL, NULL, NULL, '2026-03-31 09:10:42', '2026-03-31 17:04:54', '[[123,-17],[139,-17],[139,-8],[123,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('105', '1', 'Office 104', 'O-104', NULL, NULL, NULL, NULL, '2026-03-31 09:12:00', '2026-03-31 17:04:54', '[[113,-17],[123,-17],[123,-8],[113,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('106', '1', 'Office 103', 'O-103', NULL, NULL, NULL, NULL, '2026-03-31 09:14:34', '2026-03-31 17:04:54', '[[103,-17],[113,-17],[113,-8],[103,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('107', '1', 'Office 102', 'O-102', NULL, NULL, NULL, NULL, '2026-03-31 09:15:07', '2026-03-31 17:04:54', '[[93,-17],[103,-17],[103,-8],[93,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('108', '1', 'Office 101', 'O-101', NULL, NULL, NULL, NULL, '2026-03-31 09:15:29', '2026-03-31 17:04:54', '[[83,-17],[93,-17],[93,-8],[83,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('109', '1', 'Reception', 'Rec', NULL, NULL, NULL, NULL, '2026-03-31 09:15:49', '2026-03-31 17:04:54', '[[71,-17],[83,-17],[83,-8],[71,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('110', '1', 'Lobby', 'Lobby', NULL, NULL, NULL, NULL, '2026-03-31 09:18:20', '2026-03-31 16:50:25', '[[30,-17],[71,-17],[71,-8],[75,-8],[75,0],[30,0]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('111', '2', 'Office 2-1', 'O21', NULL, NULL, NULL, NULL, '2026-03-31 09:24:31', '2026-03-31 17:05:50', '[[71,-17],[83,-17],[83,-8],[71,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('112', '2', 'Office 2-2', 'O22', NULL, NULL, NULL, NULL, '2026-03-31 09:26:34', '2026-03-31 17:05:50', '[[83,-17],[93,-17],[93,-8],[83,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('113', '2', 'Office 1-3', 'O23', NULL, NULL, NULL, NULL, '2026-03-31 09:27:06', '2026-03-31 17:05:50', '[[93,-17],[103,-17],[103,-8],[93,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('114', '2', 'Office 2-4', 'O24', NULL, NULL, NULL, NULL, '2026-03-31 09:27:32', '2026-03-31 17:05:50', '[[103,-17],[113,-17],[113,-8],[103,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('115', '2', 'Office 2-5', 'O25', NULL, NULL, NULL, NULL, '2026-03-31 09:27:55', '2026-03-31 17:05:50', '[[113,-17],[127,-17],[127,-8],[113,-8]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('116', '2', 'Conference Room', 'CR', NULL, NULL, NULL, NULL, '2026-03-31 09:30:09', '2026-03-31 10:43:14', '[[127,-17],[160,-17],[160,-4],[127,-4]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('117', '2', 'Lobby', 'L', NULL, NULL, NULL, NULL, '2026-03-31 09:30:58', '2026-03-31 09:31:31', '[[30,-17],[71,-17],[71,-8],[75,-8],[75,0],[30,0]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('118', '2', 'Office Corridor U', 'OCU', NULL, NULL, NULL, NULL, '2026-03-31 09:32:08', '2026-03-31 17:05:50', '[[75,-8],[127,-8],[127,-4],[151,-4],[151,0],[75,0]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('119', '2', 'Fireside Room', 'F.R.', NULL, NULL, NULL, NULL, '2026-03-31 09:48:40', '2026-03-31 16:54:07', '[[0,80],[43,80],[43,86],[68,86],[68,97],[43,97],[43,111],[0,111]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('120', '2', 'Storage 28', 'S28', NULL, NULL, NULL, NULL, '2026-03-31 09:50:26', '2026-03-31 18:50:56', '[[0,111],[68,111],[68,120],[0,120]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('121', '2', 'Pantry', 'P', NULL, NULL, NULL, NULL, '2026-03-31 09:53:11', '2026-03-31 17:05:50', '[[62,97],[68,97],[68,111],[62,111]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('122', '2', 'Fireside Cafe', 'F.C.', NULL, NULL, NULL, NULL, '2026-03-31 09:55:51', '2026-03-31 09:56:04', '[[43,97],[62,97],[62,111],[43,111]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('123', '2', '204', '204', NULL, NULL, NULL, NULL, '2026-03-31 10:02:00', '2026-03-31 10:12:02', '[[68,93],[88,93],[88,111],[68,111]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('125', '2', '203', '203', NULL, NULL, NULL, NULL, '2026-03-31 10:11:48', '2026-03-31 10:12:06', '[[88,93],[108,93],[108,111],[88,111]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('126', '2', 'Men\'s Shower 1', 'MS1', NULL, NULL, NULL, NULL, '2026-03-31 10:16:31', '2026-03-31 17:05:50', '[[108,100],[115,100],[115,111],[108,111]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('127', '2', 'Men\'s Shower 2', 'MS2', NULL, NULL, NULL, NULL, '2026-03-31 10:17:00', '2026-03-31 17:05:50', '[[115,100],[122,100],[122,111],[115,111]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('128', '2', 'Men\'s Up', 'MU', NULL, NULL, NULL, NULL, '2026-03-31 10:18:26', '2026-03-31 17:05:50', '[[108,93],[128,93],[128,100],[137,100],[143,106],[143,111],[128,111],[128,104],[122,104],[122,100],[108,100]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('129', '2', 'Janitor Closet 3', 'JC3', NULL, NULL, NULL, NULL, '2026-03-31 10:20:53', '2026-03-31 17:05:50', '[[122,104],[128,104],[128,111],[122,111]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('130', '2', 'Storage 27', NULL, NULL, NULL, NULL, NULL, '2026-03-31 10:22:40', '2026-03-31 18:50:56', '[[68,111],[143,111],[143,106],[147,110],[147,120],[68,120]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('131', '2', '201', '201', NULL, NULL, NULL, NULL, '2026-03-31 10:24:29', '2026-03-31 10:25:26', '[[160,0],[178,0],[178,18],[160,18]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('132', '2', '202', '202', NULL, NULL, NULL, NULL, '2026-03-31 10:25:38', '2026-03-31 10:25:54', '[[160,18],[178,18],[178,36],[160,36]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('133', '2', 'Women\'s Shower 1', 'WS1', NULL, NULL, NULL, NULL, '2026-03-31 10:29:08', '2026-03-31 17:05:50', '[[172,36],[178,36],[178,46],[172,46]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('134', '2', 'Women\'s Shower 2', 'WS2', NULL, NULL, NULL, NULL, '2026-03-31 10:29:59', '2026-03-31 17:05:50', '[[172,46],[178,46],[178,56],[172,56]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('135', '2', 'Women\'s Up', 'WU', NULL, NULL, NULL, NULL, '2026-03-31 10:31:24', '2026-03-31 17:05:50', '[[167,56],[178,56],[178,80],[173,80],[162,69],[166,65],[162,61]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('136', '2', 'Women\'s Lounge Up', 'WLU', NULL, NULL, NULL, NULL, '2026-03-31 10:32:52', '2026-03-31 17:05:50', '[[160,36],[172,36],[172,50],[160,50]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('138', '2', 'Storage 26', 'S26', NULL, NULL, NULL, NULL, '2026-03-31 10:35:00', '2026-03-31 18:50:56', '[[178,0],[188,0],[188,80],[178,80]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('139', '2', 'Storage 25', 'S25', NULL, NULL, NULL, NULL, '2026-03-31 10:37:26', '2026-03-31 18:50:56', '[[148,54],[154,54],[148,60],[145,57]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('140', '2', 'Roof Access', 'RA', NULL, NULL, NULL, NULL, '2026-03-31 10:38:51', '2026-03-31 17:05:50', '[[141,61],[145,57],[148,60],[144,64]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('141', '2', 'Storage 23', 'S23', NULL, NULL, NULL, NULL, '2026-03-31 10:39:24', '2026-03-31 18:50:56', '[[68,0],[120,0],[120,6],[98,6],[74,30],[68,30]]', '1', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('142', '2', 'Electrical 2', 'E2', NULL, NULL, NULL, NULL, '2026-03-31 10:41:33', '2026-03-31 17:05:50', '[[68,30],[74,30],[74,50],[68,50]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('143', '2', 'Workshop', 'WS', NULL, NULL, NULL, NULL, '2026-03-31 10:42:43', '2026-03-31 17:05:50', '[[43,80],[62,80],[62,86],[43,86]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('144', '2', 'Stair 1', 'ST1', NULL, NULL, NULL, NULL, '2026-03-31 10:44:30', '2026-03-31 17:05:50', '[[62,57],[62,80],[58,80],[58,57]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('145', '1', 'Stair 1', 'S1', NULL, NULL, NULL, NULL, '2026-03-31 10:44:55', '2026-03-31 17:04:54', '[[62,57],[62,74],[58,74],[58,57]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('146', '1', 'Storage 24', 'S24', NULL, NULL, NULL, NULL, '2026-03-31 10:51:47', '2026-03-31 18:51:41', '[[58,19],[62,19],[62,39],[58,39]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('147', '1', 'Storage G1', 'SG1', NULL, NULL, NULL, NULL, '2026-03-31 10:55:28', '2026-03-31 18:51:41', '[[0,11],[4,11],[4,19],[0,19]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('148', '1', 'Storage G2', 'SG2', NULL, NULL, NULL, NULL, '2026-03-31 10:56:01', '2026-03-31 18:51:41', '[[0,19],[4,19],[4,29],[0,29]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('149', '1', 'Storage Gym 3', 'SG3', NULL, NULL, NULL, NULL, '2026-03-31 10:56:33', '2026-03-31 18:51:41', '[[0,48],[4,48],[4,59],[0,59]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('150', '1', 'Storage Gym 4', 'SG4', NULL, NULL, NULL, NULL, '2026-03-31 10:57:09', '2026-03-31 17:04:54', '[[0,59],[4,59],[4,80],[0,80]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('151', '1', 'Storage Gym 5', 'SG5', NULL, NULL, NULL, NULL, '2026-03-31 10:57:56', '2026-03-31 18:51:41', '[[58,74],[62,74],[62,80],[58,80]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('152', '1', 'Elevator 2', 'E2', NULL, NULL, NULL, NULL, '2026-03-31 10:59:23', '2026-03-31 18:51:41', '[[110,0],[120,0],[120,6],[110,6]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('153', '1', 'Video Room', 'VR', NULL, NULL, NULL, NULL, '2026-03-31 11:01:26', '2026-03-31 17:04:54', '[[148,60],[140,68],[137,65],[142,60]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('154', '1', 'Tech', 'T', NULL, NULL, NULL, NULL, '2026-03-31 11:01:56', '2026-03-31 18:51:41', '[[128,74],[133,69],[136,72],[128,80]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('155', '2', 'Stair 4', 'ST4', NULL, NULL, NULL, NULL, '2026-03-31 11:04:32', '2026-03-31 17:05:50', '[[129,0],[148,0],[148,4],[129,4]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('156', '2', 'Stair 5', 'ST5', NULL, NULL, NULL, NULL, '2026-03-31 11:05:39', '2026-03-31 17:05:50', '[[68,61],[72,61],[72,81],[68,81]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('157', '2', 'Balcony', 'B', NULL, NULL, NULL, NULL, '2026-03-31 11:07:29', '2026-03-31 11:13:23', '[[105,66],[108,63],[112,67],[136,44],[132,40],[135,37],[135,4],[148,4],[148,0],[154,0],[154,54],[148,54],[141,61],[144,64],[122,86],[68,86],[68,81],[72,81],[72,66]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('158', '2', 'Sound Booth', 'SB', NULL, NULL, NULL, NULL, '2026-03-31 11:09:06', '2026-03-31 11:09:21', '[[108,63],[132,40],[136,44],[112,67]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('162', '1', 'Stair 5', 'S5', NULL, NULL, NULL, NULL, '2026-03-31 11:13:39', '2026-03-31 17:10:40', '[[68,61],[72,61],[72,73],[68,73]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('163', '1', 'Stair 4 (copy)', 'S4(', NULL, NULL, NULL, NULL, '2026-03-31 11:14:30', '2026-03-31 17:04:54', '[[129,0],[141,0],[141,4],[129,4]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('164', '1', 'Janitorial 5', 'J5', NULL, NULL, NULL, NULL, '2026-03-31 11:15:25', '2026-03-31 17:04:54', '[[141,0],[148,0],[148,4],[141,4]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('165', '1', 'Storage 6', 'S6', NULL, NULL, NULL, NULL, '2026-03-31 11:15:47', '2026-03-31 18:51:41', '[[68,73],[72,73],[72,80],[68,80]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('166', '1', 'Hospitality 1', 'H1', NULL, NULL, NULL, NULL, '2026-03-31 11:17:58', '2026-03-31 18:51:41', '[[152,65],[155,68],[149,74],[146,71]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('167', '1', 'Hospitality 2', 'H2', NULL, NULL, NULL, NULL, '2026-03-31 11:18:42', '2026-03-31 18:51:41', '[[139,78],[142,81],[136,87],[133,84]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('168', '1', 'Stage', 'Sg', NULL, NULL, NULL, NULL, '2026-03-31 11:23:57', '2026-03-31 11:25:48', '[[98,6],[113,6],[113,16],[86,43],[74,43],[74,30]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('169', '1', 'Baptistery', 'B', NULL, NULL, NULL, NULL, '2026-03-31 11:24:39', '2026-03-31 11:24:47', '[[74,43],[82,43],[82,50],[74,50]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('170', '7', 'Chapel', 'Ch', NULL, NULL, NULL, NULL, '2026-03-31 11:29:42', '2026-04-03 12:33:56', '[[12,50],[51,50],[51,106],[12,106]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('174', '7', 'Storage Chair', 'S.C.', NULL, NULL, NULL, NULL, '2026-03-31 11:37:46', '2026-03-31 18:52:10', '[[12,106],[24,106],[24,121],[16,121],[16,116],[12,116]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('176', '7', 'Lounge', 'L', NULL, NULL, NULL, NULL, '2026-03-31 11:39:07', '2026-03-31 12:10:18', '[[24,106],[51,106],[51,121],[24,121]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('177', '7', 'Fellowship', 'F', NULL, NULL, NULL, NULL, '2026-03-31 11:40:06', '2026-03-31 12:10:36', '[[51,41],[79,41],[79,66],[90,66],[90,70],[74,70],[74,90],[77,90],[77,106],[74,106],[74,121],[51,121]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('178', '7', 'Storage 1', NULL, NULL, NULL, NULL, NULL, '2026-03-31 11:41:08', '2026-03-31 18:52:10', '[[16,30],[16,26],[51,26],[51,38],[12,38],[12,30]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('179', '7', '100', '100', NULL, NULL, NULL, NULL, '2026-03-31 11:42:32', '2026-03-31 12:09:53', '[[74,106],[90,106],[90,121],[74,121]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('180', '7', 'Men\'s East', 'M.E.', NULL, NULL, NULL, NULL, '2026-03-31 11:44:35', '2026-03-31 17:06:15', '[[77,98],[90,98],[90,106],[77,106]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('181', '7', 'Women\'s East', 'W.E.', NULL, NULL, NULL, NULL, '2026-03-31 11:44:59', '2026-03-31 17:06:15', '[[77,90],[90,90],[90,98],[77,98]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('182', '7', 'Kitchen', 'K', NULL, NULL, NULL, NULL, '2026-03-31 11:46:31', '2026-03-31 12:09:43', '[[74,70],[90,70],[90,90],[74,90]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('183', '7', 'Mech 10', 'M-10', NULL, NULL, NULL, NULL, '2026-03-31 11:48:23', '2026-03-31 17:06:15', '[[79,59],[90,59],[90,66],[79,66]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('184', '7', 'Storage 10', 'S-10', NULL, NULL, NULL, NULL, '2026-03-31 11:49:01', '2026-03-31 18:52:10', '[[79,41],[90,41],[90,59],[79,59]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('185', '7', '101', '101', NULL, NULL, NULL, NULL, '2026-03-31 11:50:10', '2026-03-31 12:09:32', '[[65,26],[90,26],[90,41],[65,41]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('186', '7', 'Storage 11', 'S-11', NULL, NULL, NULL, NULL, '2026-03-31 11:51:36', '2026-03-31 18:52:10', '[[61,31],[65,31],[65,41],[61,41]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('187', '7', 'Stair 1', 'St1', NULL, NULL, NULL, NULL, '2026-03-31 11:52:28', '2026-03-31 17:06:15', '[[51,31],[55,31],[55,41],[51,41]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('188', '7', 'Janitorial 1', 'J-1', NULL, NULL, NULL, NULL, '2026-03-31 11:54:13', '2026-03-31 17:06:15', '[[61,26],[65,26],[65,31],[61,31]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('189', '7', 'Corridor A', 'C-A', NULL, NULL, NULL, NULL, '2026-03-31 11:54:54', '2026-04-03 12:32:08', '[[0,18],[72,18],[72,14],[90,14],[90,26],[61,26],[61,41],[55,41],[55,26],[46,26],[46,22],[40,22],[40,26],[29,26],[29,22],[10,22],[10,26],[0,26]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('190', '7', 'Janitorial 2', 'J2', NULL, NULL, NULL, NULL, '2026-03-31 11:55:44', '2026-03-31 17:06:15', '[[51,26],[55,26],[55,31],[51,31]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('191', '7', 'Women\'s West', 'W.W.', NULL, NULL, NULL, NULL, '2026-03-31 11:58:01', '2026-03-31 17:06:15', '[[81,0],[90,0],[90,14],[81,14]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('192', '7', 'Men\'s West', 'MW', NULL, NULL, NULL, NULL, '2026-03-31 11:58:53', '2026-03-31 17:06:15', '[[72,0],[81,0],[81,14],[72,14]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('193', '7', '102', '102', NULL, NULL, NULL, NULL, '2026-03-31 12:00:54', '2026-03-31 12:09:22', '[[43,0],[72,0],[72,18],[43,18]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('194', '7', '103', '103', NULL, NULL, NULL, NULL, '2026-03-31 12:01:47', '2026-03-31 12:09:16', '[[14,0],[43,0],[43,18],[14,18]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('195', '7', 'Office', 'Off', NULL, NULL, NULL, NULL, '2026-03-31 12:04:42', '2026-03-31 12:09:14', '[[0,0],[14,0],[14,18],[0,18]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('196', '7', 'Mech 0', NULL, NULL, NULL, NULL, NULL, '2026-03-31 12:14:55', '2026-03-31 17:06:15', '[[12,116],[16,116],[16,121],[12,121]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('197', '7', 'Electrical 2', 'E2', NULL, NULL, NULL, NULL, '2026-03-31 12:15:38', '2026-03-31 17:06:15', '[[12,26],[16,26],[16,30],[12,30]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('198', '7', 'Stair 2', 'ST-2', NULL, NULL, NULL, NULL, '2026-03-31 12:18:40', '2026-03-31 17:06:15', '[[10,22],[22,22],[22,26],[10,26]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('199', '7', 'Mech 11', 'M-11', NULL, NULL, NULL, NULL, '2026-03-31 12:19:12', '2026-03-31 17:06:15', '[[40,22],[46,22],[46,26],[40,26]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('200', '7', 'Storage 12', 'S-12', NULL, NULL, NULL, NULL, '2026-03-31 12:19:52', '2026-03-31 18:52:10', '[[22,22],[29,22],[29,26],[22,26]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('202', '8', '204', '204', NULL, NULL, NULL, NULL, '2026-03-31 12:24:29', '2026-03-31 12:32:49', '[[26,0],[42,0],[42,17],[26,17]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('203', '8', '203', '203', NULL, NULL, NULL, NULL, '2026-03-31 12:24:53', '2026-03-31 12:32:49', '[[42,0],[58,0],[58,17],[42,17]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('204', '8', '205', '205', NULL, NULL, NULL, NULL, '2026-03-31 12:26:59', '2026-03-31 12:35:54', '[[0,0],[26,0],[26,17],[21,17],[21,22],[10,22],[10,25],[0,25]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('205', '8', '202', '202', NULL, NULL, NULL, NULL, '2026-03-31 12:28:51', '2026-03-31 12:32:49', '[[58,0],[90,0],[90,21],[74,21],[74,17],[58,17]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('206', '8', 'Storage 22', 'S-22', NULL, NULL, NULL, NULL, '2026-03-31 12:30:54', '2026-03-31 18:52:27', '[[31,21],[41,21],[41,25],[31,25]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('207', '8', 'Mech 20', 'M-20', NULL, NULL, NULL, NULL, '2026-03-31 12:31:30', '2026-03-31 17:06:32', '[[41,21],[46,21],[46,25],[41,25]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('208', '8', 'Stair 1 U', 'S1U', NULL, NULL, NULL, NULL, '2026-03-31 12:32:10', '2026-03-31 17:06:31', '[[51,29],[55,29],[55,37],[51,37]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('209', '8', 'Stair 2 U', 'S2U', NULL, NULL, NULL, NULL, '2026-03-31 12:33:13', '2026-03-31 17:06:32', '[[10,22],[21,22],[21,25],[10,25]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('210', '8', 'Corridor B', 'C-B', NULL, NULL, NULL, NULL, '2026-03-31 12:35:16', '2026-03-31 17:06:32', '[[21,17],[74,17],[74,21],[90,21],[90,25],[55,25],[55,29],[51,29],[51,25],[46,25],[46,21],[31,21],[31,25],[21,25]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('211', '8', '201', '201', NULL, NULL, NULL, NULL, '2026-03-31 12:37:33', '2026-03-31 12:41:04', '[[55,25],[87,25],[87,41],[55,41],[51,41],[51,37],[55,37]]', '1', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('212', '8', 'S-20', NULL, NULL, NULL, NULL, NULL, '2026-03-31 12:38:43', '2026-03-31 18:52:27', '[[32,34],[51,34],[51,45],[32,45]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('213', '8', 'Storage 21', 'S-21', NULL, NULL, NULL, NULL, '2026-03-31 12:40:40', '2026-03-31 18:52:27', '[[32,45],[51,45],[51,65],[32,65]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('214', '8', 'Storage 15', 'S1', NULL, NULL, NULL, NULL, '2026-03-31 12:41:56', '2026-03-31 18:52:27', '[[12,111],[22,111],[22,121],[12,121]]', '0', '1');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('215', '2', 'Corridor D', 'C-D', NULL, NULL, NULL, NULL, '2026-03-31 16:53:13', '2026-03-31 17:05:50', '[[58,0],[68,0],[68,86],[62,86],[62,57],[58,57]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('216', '2', 'Corridor E', NULL, NULL, NULL, NULL, NULL, '2026-03-31 16:54:24', '2026-03-31 17:05:50', '[[68,86],[122,86],[154,54],[154,0],[151,0],[151,-4],[160,-4],[160,50],[172,50],[172,56],[167,56],[158,65],[157,64],[153,64],[132,85],[132,89],[128,93],[68,93]]', '0', '0');
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `abbreviation`, `room_number`, `capacity`, `description`, `default_setup`, `created_at`, `updated_at`, `map_points`, `is_reservable`, `is_storage`) VALUES ('217', '7', 'Stage', 'S', NULL, NULL, NULL, NULL, '2026-04-03 12:34:06', '2026-04-03 12:34:19', '[[12,38],[51,38],[51,50],[12,50]]', '1', '0');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('auto_generate_assignments', '1', NULL, '2026-04-03 15:53:46');
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('default_deadline_time', '16:00', 'Default deadline time for scheduled tasks', '2026-04-01 17:51:49');
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('pwa_date_strip_back', '7', NULL, '2026-04-04 12:42:32');
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('pwa_date_strip_forward', '14', NULL, '2026-04-04 12:42:32');
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('schedule_generation_days', '60', 'How many days ahead to generate assignments', '2026-04-03 15:53:46');
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES ('scheduling_mode', 'none', 'deadline or timeslot', '2026-04-04 12:50:03');

DROP TABLE IF EXISTS `supplies`;
CREATE TABLE `supplies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  `supplier` varchar(100) DEFAULT NULL,
  `part_number` varchar(50) DEFAULT NULL,
  `rack` int(11) DEFAULT NULL,
  `shelf` int(11) DEFAULT NULL,
  `slot` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `low_stock` int(11) DEFAULT NULL,
  `high_stock` int(11) DEFAULT NULL,
  `order_qty` int(11) DEFAULT NULL,
  `order_unit` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('1', 'Plastic Bottles with Sprayers - 16 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-11686', '1', '1', '1', 'Bottles', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('2', 'Plastic Bottles with Sprayers - 24 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-7272', '1', '1', '2', 'Bottles', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('3', 'Plastic Bottles with Sprayers - 32 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-7273', '1', '1', '3', 'Bottles', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('4', 'Uline Aloe Hand Soap - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-17081', '2', '3', '1', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('5', 'CLR PRO Calcium, Lime and Rust Remover - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-18419', '2', '3', '2', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('6', 'Uline 2in1 Cleaner and Disinfectant - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19374', '2', '4', '1', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('7', 'Uline Multi-Purpose Cleaner - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-20690', '2', '4', '2', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('8', 'Uline Neutral Floor Cleaner - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-26142', '2', '5', '1', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('9', 'Betco Carpet Cleaner - 1 Gallon', '2026-04-04 08:55:23', '1', 'ULINE', 'S-26147', '2', '5', '2', 'Bottles', '2', '6', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('10', 'Uline Antibacterial Hand Soap - 7.5 oz Dispenser', '2026-04-04 08:55:23', '1', 'ULINE', 'S-20661', '3', '2', '1', 'Bottles', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('11', 'Lysol Toilet Bowl Cleaner - 32 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-7141', '3', '3', '5', 'Bottles', '4', '16', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('12', 'Uline Black Latex Gloves - Small', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19810S', '2', '2', '2', 'Boxes', '1', '3', '2', 'Box');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('13', 'Uline Black Latex Gloves - Medium', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19810M', '2', '2', '3', 'Boxes', '1', '3', '2', 'Box');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('14', 'Uline Black Latex Gloves - Large', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19810L', '2', '2', '4', 'Boxes', '1', '3', '2', 'Box');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('15', 'Uline Black Latex Gloves - XL', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19810X', '2', '2', '5', 'Boxes', '1', '3', '2', 'Box');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('16', 'Kleenex Boutique Facial Tissue', '2026-04-04 08:55:23', '1', 'ULINE', 'S-6873', '4', '3', '1', 'Boxes', '6', '42', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('17', 'Toilet Seat Covers', '2026-04-04 08:55:23', '1', 'ULINE', 'S-7276', '4', '3', '2', 'Boxes', '6', '18', '2', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('18', 'Uline Kraft Multi-Fold Towels', '2026-04-04 08:55:23', '1', 'ULINE', 'S-13735', '4', '1', '1', 'Bundles', '4', '16', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('19', 'Bar Keepers Friend - 21 oz Powder', '2026-04-04 08:55:23', '1', 'ULINE', 'S-20095', '3', '3', '1', 'Cans', '2', '14', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('20', 'Weiman Stainless Steel Cleaner and Polish - 17 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-21521', '3', '3', '2', 'Cans', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('21', 'Goof Off Spray - 12 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19127', '3', '3', '3', 'Cans', '2', '8', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('22', 'Uline Foaming Glass Cleaner - 19 oz', '2026-04-04 08:55:23', '1', 'ULINE', 'S-22344', '3', '3', '4', 'Cans', '2', '14', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('23', 'Uline EZ Pull Center Pull Paper Towels', '2026-04-04 08:55:23', '1', 'ULINE', 'S-15752', '1', '2', '1', 'Cases', '4', '12', '8', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('24', 'Uline Toilet Tissue', '2026-04-04 08:55:23', '1', 'ULINE', 'S-7131', '1', '3', '1', 'Cases', '1', '2', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('25', 'Uline Economy Trash Liners - 12-16 Gallon Clear', '2026-04-04 08:55:23', '1', 'ULINE', 'S-11671C', '3', '4', '1', 'Cases', '2', '2', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('26', 'Sanitary Napkin Receptacle Liners', '2026-04-04 08:55:23', '1', 'ULINE', 'S-15960', '3', '4', '2', 'Cases', '1', '2', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('27', 'Uline Industrial Trash Liners - 40-45 Gallon Black', '2026-04-04 08:55:23', '1', 'ULINE', 'S-5109', '3', '5', '1', 'Cases', '1', '2', '1', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('28', 'Kirkland Signature Paper Towels', '2026-04-04 08:55:23', '1', 'Costco', '100234271', '4', '4', '1', 'Rolls', '12', '36', '2', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('29', 'Spill Kit', '2026-04-04 08:55:23', '1', NULL, NULL, '4', '1', '2', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('30', 'Uline Disinfecting Wipes - Fresh Scent 75 ct', '2026-04-04 08:55:23', '1', 'ULINE', 'S-19459FRESH', '4', '2', '1', 'Cans', '2', '14', '2', 'Case');
INSERT INTO `supplies` (`id`, `name`, `created_at`, `quantity`, `supplier`, `part_number`, `rack`, `shelf`, `slot`, `unit`, `low_stock`, `high_stock`, `order_qty`, `order_unit`) VALUES ('31', 'Uline Deluxe Urinal Screen - Cotton Blossom', '2026-04-04 08:55:23', '1', 'Uline', 'S-18729COTTN', '3', '2', '2', 'Case', '1', '2', '1', 'Case');

DROP TABLE IF EXISTS `task_catalog`;
CREATE TABLE `task_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` enum('cleaning','maintenance','setup') NOT NULL DEFAULT 'cleaning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_catalog` (`id`, `name`, `description`, `category`, `created_at`) VALUES ('1', 'Vacuum Carpet', '', 'cleaning', '2026-03-25 21:38:21');

DROP TABLE IF EXISTS `task_equipment`;
CREATE TABLE `task_equipment` (
  `task_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`equipment_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `task_equipment_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `task_group_preferred_workers`;
CREATE TABLE `task_group_preferred_workers` (
  `task_group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`task_group_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_group_preferred_workers_ibfk_1` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_group_preferred_workers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `task_group_tasks`;
CREATE TABLE `task_group_tasks` (
  `task_group_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`task_group_id`,`task_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `task_group_tasks_ibfk_1` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_group_tasks_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_group_tasks` (`task_group_id`, `task_id`, `sort_order`) VALUES ('1', '30', '0');
INSERT INTO `task_group_tasks` (`task_group_id`, `task_id`, `sort_order`) VALUES ('1', '31', '1');
INSERT INTO `task_group_tasks` (`task_group_id`, `task_id`, `sort_order`) VALUES ('1', '32', '2');

DROP TABLE IF EXISTS `task_groups`;
CREATE TABLE `task_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type_id` int(11) NOT NULL,
  `estimated_minutes` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_type_id` (`task_type_id`),
  KEY `fk_tg_parent` (`parent_id`),
  CONSTRAINT `fk_tg_parent` FOREIGN KEY (`parent_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_groups_ibfk_1` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_groups` (`id`, `parent_id`, `sort_order`, `name`, `description`, `task_type_id`, `estimated_minutes`, `created_at`, `updated_at`) VALUES ('1', NULL, '0', 'Test 1', NULL, '1', '15', '2026-04-04 12:18:31', '2026-04-04 12:18:31');

DROP TABLE IF EXISTS `task_materials`;
CREATE TABLE `task_materials` (
  `task_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`material_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `task_materials_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_materials_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `task_preferred_workers`;
CREATE TABLE `task_preferred_workers` (
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_preferred_workers_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_preferred_workers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `task_rooms`;
CREATE TABLE `task_rooms` (
  `task_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`room_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `task_rooms_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('1', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '89');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '90');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '91');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '215');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('2', '216');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('3', '74');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('3', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('3', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '89');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '90');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '91');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '215');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('4', '216');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('5', '67');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('5', '74');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('5', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('6', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '57');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '62');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '93');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '119');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '170');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '176');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '177');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('7', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '56');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '58');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '67');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '68');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '74');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '79');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '81');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '82');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '83');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '84');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '88');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '103');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '118');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '123');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '125');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '131');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '132');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '136');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '179');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '185');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '193');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '194');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '202');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '203');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '204');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '205');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('8', '211');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '62');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '126');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '127');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '133');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '134');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('9', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('10', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('11', '126');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('11', '127');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('11', '133');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('11', '134');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('12', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('13', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('14', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('14', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('14', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('14', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('14', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('15', '110');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('16', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('16', '119');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('16', '215');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('16', '216');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('17', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('18', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('19', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('19', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('19', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('20', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '62');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '126');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '127');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '133');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '134');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('21', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('22', '110');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '56');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '57');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '58');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '67');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '68');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '74');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '77');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '79');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '81');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '82');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '83');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '84');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '85');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '88');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '89');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '90');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '91');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '93');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '103');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '118');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '119');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '123');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '125');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '131');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '132');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '136');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '168');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '170');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '176');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '177');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '179');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '185');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '189');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '193');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '194');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '202');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '203');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '204');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '205');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '210');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '211');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '215');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '216');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('23', '217');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('24', '110');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('25', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('26', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('26', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('26', '88');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('26', '187');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('26', '198');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('27', '61');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('27', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '57');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '67');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '79');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '83');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '84');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '185');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '193');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '194');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('28', '204');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '182');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '193');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('29', '194');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('30', '65');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('30', '66');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('30', '67');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('30', '88');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('30', '110');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '56');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '57');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '81');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '82');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '88');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '93');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '119');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '122');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '123');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '125');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '131');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '132');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '179');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '185');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '193');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '194');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '202');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '203');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '204');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '205');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('31', '211');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '60');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '71');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '73');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '80');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '135');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '181');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '191');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('32', '192');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('33', '59');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('33', '72');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('33', '128');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('33', '180');
INSERT INTO `task_rooms` (`task_id`, `room_id`) VALUES ('33', '192');

DROP TABLE IF EXISTS `task_supplies`;
CREATE TABLE `task_supplies` (
  `task_id` int(11) NOT NULL,
  `supply_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`supply_id`),
  KEY `supply_id` (`supply_id`),
  CONSTRAINT `task_supplies_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_supplies_ibfk_2` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('1', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('1', '27');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('1', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('2', '9');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('3', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('3', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('4', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('4', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('6', '25');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('6', '26');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('7', '27');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('8', '25');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('9', '8');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('10', '27');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('11', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('12', '19');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('13', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('13', '11');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('13', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('14', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('14', '11');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('14', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('15', '22');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('15', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('16', '22');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('16', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('17', '22');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('17', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('18', '4');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('18', '24');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('28', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('28', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('29', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('29', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('30', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('30', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('31', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('31', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('32', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('32', '28');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('33', '6');
INSERT INTO `task_supplies` (`task_id`, `supply_id`) VALUES ('33', '28');

DROP TABLE IF EXISTS `task_tools`;
CREATE TABLE `task_tools` (
  `task_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  PRIMARY KEY (`task_id`,`tool_id`),
  KEY `tool_id` (`tool_id`),
  CONSTRAINT `task_tools_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_tools_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('9', '6');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('11', '7');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('12', '8');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('13', '9');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('14', '9');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('18', '4');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('21', '2');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('22', '2');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('23', '1');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('24', '3');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('25', '3');
INSERT INTO `task_tools` (`task_id`, `tool_id`) VALUES ('26', '5');

DROP TABLE IF EXISTS `task_types`;
CREATE TABLE `task_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `priority_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `task_types` (`id`, `name`, `priority_order`, `created_at`) VALUES ('1', 'Cleaning', '1', '2026-04-04 08:55:22');

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type_id` int(11) NOT NULL,
  `estimated_minutes` int(11) NOT NULL DEFAULT 5,
  `reusable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_type_id` (`task_type_id`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('1', 'Breakdown Sunday', 'Wipe down Tables with 2in1 and put away. Empty Coffee Trash. Roll up Rugs and store above Tables. Put Communion Elements in Refrigerator.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('2', 'Clean Carpets', 'Use Carpet Machine to clean Coffee Stains.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('3', 'Disinfect Changing Table', 'Spray 2in1 on a Paper Towel and wipe down surfaces.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('4', 'Disinfect Water Fountain', 'Spray 2in1 on fountain and wipe off with Paper Towels.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('5', 'Empty Diaper Genie', 'Empty from below.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('6', 'Empty Trash Bathroom', 'Empty trash Including stall bags.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('7', 'Empty Trash Large', 'Empty trash being careful of leaking bags.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('8', 'Empty Trash Small', 'Empty trash being careful of leaking bags.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('9', 'Mop', 'Mix 6oz floor cleaner and 3gal hot water in mop bucket.', '1', '5', '1', '2026-04-04 08:55:22', '2026-04-04 08:55:22');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('10', 'Pickup Trash', 'Pickup communion cups and other trash on floor and seats, and in seat backs.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('11', 'Scrub Showers', 'If used, spray shower with 2in1, scrub with shower brush, rinse with water.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('12', 'Scrub Sinks', 'Use Barkeepers Friend in bowl, Scrub with sink brush. Finish with 2in1 and Paper Towels.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('13', 'Scrub Toilets', 'Scrub Toilet Bowls with Toilet Bowl Cleaner and Toilet Brush. Spray entire Toilet with 2in1 and wipe with Paper Towels.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('14', 'Scrub Urinals', 'Scrub toilet bowls and urinals with brush and disinfect under seats.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('15', 'Spot Clean Door Glass', 'Clean handprints off glass doors.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('16', 'Spot Clean Glass', 'Clean visibly dirty spots.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('17', 'Spot Clean Mirrors', 'Clean visibly dirty spots.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('18', 'Stock Bathroom Supplies', 'Refill Paper Towels, hand soap, Toilet Paper.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('19', 'Stock Kitchen Supplies', 'Refill Paper Towels and hand soap.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('20', 'Straighten Chairs', 'Arrange chairs in straight rows on floor marks.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('21', 'Sweep', 'Sweep Floors with broom.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('22', 'Sweep Stairs', 'Sweep Stairs with broom.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('23', 'Vacuum Light Traffic', 'Use Backpack Vacuum.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('24', 'Vacuum High Traffic', 'Use corded Vacuum.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('25', 'Vacuum Rugs', 'Use corded Vacuum.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('26', 'Vacuum Stairs', 'Use handheld Vacuum from janitorial closet.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('27', 'Wash Towels', 'Collect Towels from kitchens and janitorial closets in both buildings. Wash in Laundry Room.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('28', 'Wipe down Counters', 'Spray with 2in1, wipe with Paper Towel.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('29', 'Wipe down Sink', 'Spray with 2in1, wipe with Paper Towel.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('30', 'Wipe down Stair Rails', 'Spray 2in1 on a Paper Towel and wipe down surfaces.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('31', 'Wipe down Tables', 'Spray with 2in1, wipe with Paper Towel.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('32', 'Wipe down Toilets', 'Spray Seat, Rim, and Handle with 2in1, wipe with Paper Towel.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');
INSERT INTO `tasks` (`id`, `name`, `description`, `task_type_id`, `estimated_minutes`, `reusable`, `created_at`, `updated_at`) VALUES ('33', 'Wipe down Urinals', 'Spray Rim and Handle with 2in1, wipe with Paper Towel.', '1', '5', '1', '2026-04-04 08:55:23', '2026-04-04 08:55:23');

DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('1', 'Backpack Vacuum', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('2', 'Broom', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('3', 'Corded Vacuum', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('4', 'Hand Towel Refill', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('5', 'Handheld Vacuum', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('6', 'Mop', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('7', 'Shower Brush', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('8', 'Sink Brush', '2026-04-04 08:55:23', '1');
INSERT INTO `tools` (`id`, `name`, `created_at`, `quantity`) VALUES ('9', 'Toilet Brush', '2026-04-04 08:55:23', '1');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','scheduler','custodial','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('16', 'Kevin Gunn', 'Facilities Manager', 'k.gunn@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'admin', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('17', 'Enas Blanchard', 'Office Manager', 'e.blanchard@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'scheduler', '1', '2026-03-25 21:03:26', '2026-03-25 21:53:55');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('18', 'Ryan Rough', 'Custodian', 'r.rough@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'custodial', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('19', 'Josh Wallis', 'Custodian', 'j.wallis@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'custodial', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('20', 'Kyle Bostock', 'Lead Pastor', 'k.bostock@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('21', 'Eris Pappas', 'Executive Pastor', 'e.pappas@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('22', 'Mark Hammer', 'Executive Pastor', 'm.hammer@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('23', 'Chad Morrison', 'Associate Pastor — Youth', 'c.morrison@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('24', 'Nate Lacy', 'Associate Pastor — Family', 'n.lacy@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('25', 'Eric Thompson', 'Associate Pastor — Connection', 'e.thompson@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('26', 'Will Shupp', 'Associate Pastor', 'w.shupp@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('27', 'Holly Curto', 'Receptionist', 'h.curto@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('28', 'Kathy Hellman', 'Bookkeeper', 'k.hellman@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('29', 'Kristine Oliver', 'Worship Director', 'k.oliver@northsummit.com', '$2y$10$B6XIhJx.GNAmS2GXdNXlCu4mWt8my7/lKNU46sFWwi3X5ZQPCS5hO', 'staff', '1', '2026-03-25 21:03:26', '2026-03-25 21:03:26');
INSERT INTO `users` (`id`, `name`, `title`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('30', 'Josh Wallis', 'Janitor', 'joshwallis2@gmail.com', '$2y$10$UwayChlTul8hJmlXKU.dq.vTYxmph9699wxYtB0zYnYkB0MelsxXK', 'staff', '1', '2026-04-01 19:07:23', '2026-04-01 19:07:23');

SET FOREIGN_KEY_CHECKS = 1;
