-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: project
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `contest_participants`
--

DROP TABLE IF EXISTS `contest_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contest_participants` (
  `contestId` varchar(50) NOT NULL,
  `userId` varchar(50) NOT NULL,
  `score` int(11) DEFAULT 0,
  PRIMARY KEY (`contestId`,`userId`),
  KEY `userId` (`userId`),
  CONSTRAINT `contest_participants_ibfk_1` FOREIGN KEY (`contestId`) REFERENCES `contests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contest_participants_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contest_participants`
--

LOCK TABLES `contest_participants` WRITE;
/*!40000 ALTER TABLE `contest_participants` DISABLE KEYS */;
INSERT INTO `contest_participants` VALUES ('c3','u1',100),('c3','u3',0);
/*!40000 ALTER TABLE `contest_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contest_problems`
--

DROP TABLE IF EXISTS `contest_problems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contest_problems` (
  `contestId` varchar(50) NOT NULL,
  `problemId` varchar(50) NOT NULL,
  PRIMARY KEY (`contestId`,`problemId`),
  KEY `problemId` (`problemId`),
  CONSTRAINT `contest_problems_ibfk_1` FOREIGN KEY (`contestId`) REFERENCES `contests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contest_problems_ibfk_2` FOREIGN KEY (`problemId`) REFERENCES `problems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contest_problems`
--

LOCK TABLES `contest_problems` WRITE;
/*!40000 ALTER TABLE `contest_problems` DISABLE KEYS */;
INSERT INTO `contest_problems` VALUES ('c3','p5');
/*!40000 ALTER TABLE `contest_problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contests`
--

DROP TABLE IF EXISTS `contests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contests` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `participants` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contests`
--

LOCK TABLES `contests` WRITE;
/*!40000 ALTER TABLE `contests` DISABLE KEYS */;
INSERT INTO `contests` VALUES ('c1','CodeForge Round #45','Global','2023-11-01',1200,'Upcoming'),('c2','Local University Clash','Local','2023-10-20',45,'Past'),('c3','Head-to-Head Sprint','Local','2024-01-15',2,'Active');
/*!40000 ALTER TABLE `contests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `problems`
--

DROP TABLE IF EXISTS `problems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `problems` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `difficulty` varchar(50) DEFAULT NULL,
  `solvedBy` int(11) DEFAULT 0,
  `tags` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `problems`
--

LOCK TABLES `problems` WRITE;
/*!40000 ALTER TABLE `problems` DISABLE KEYS */;
INSERT INTO `problems` VALUES ('p1','Hello World Logic','Basic','Easy',1,'intro,syntax'),('p2','Even or Odd','Math','Easy',0,'logic,numbers'),('p3','Array Sum','Arrays','Easy',0,'loops,arrays'),('p4','Tree Query','Trees','Hard',120,'trees,lca'),('p5','String Hashing','Strings','Medium',890,'hashing,strings');
/*!40000 ALTER TABLE `problems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` varchar(50) NOT NULL,
  `problemId` varchar(50) DEFAULT NULL,
  `userId` varchar(50) DEFAULT NULL,
  `contestId` varchar(50) DEFAULT NULL,
  `verdict` varchar(10) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `runtime` int(11) DEFAULT NULL,
  `memory` int(11) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `failedTestCase` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `problemId` (`problemId`),
  KEY `contestId` (`contestId`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`problemId`) REFERENCES `problems` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`contestId`) REFERENCES `contests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES ('s4','p5','u1','c3','AC','2024-01-15 10:05:00',45,1100,'C++',NULL),('s5','p5','u3','c3','WA','2024-01-15 10:06:00',50,1150,'C++',NULL),('sub_101718','p1','u1',NULL,'AC','2026-01-23 15:17:18',NULL,NULL,'C++',NULL);
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topicstats`
--

DROP TABLE IF EXISTS `topicstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topicstats` (
  `topic` varchar(100) NOT NULL,
  `solved` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  `weaknessScore` int(11) DEFAULT NULL,
  PRIMARY KEY (`topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topicstats`
--

LOCK TABLES `topicstats` WRITE;
/*!40000 ALTER TABLE `topicstats` DISABLE KEYS */;
/*!40000 ALTER TABLE `topicstats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `universities`
--

DROP TABLE IF EXISTS `universities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `universities` (
  `name` varchar(255) NOT NULL,
  `totalSolves` int(11) DEFAULT 0,
  `activeUsers` int(11) DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `universities`
--

LOCK TABLES `universities` WRITE;
/*!40000 ALTER TABLE `universities` DISABLE KEYS */;
INSERT INTO `universities` VALUES ('National Institute',15600,200),('State College of Engineering',9800,95),('Tech University of Science',12450,120);
/*!40000 ALTER TABLE `universities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT 1200,
  `university` varchar(255) DEFAULT NULL,
  `solvedCount` int(11) DEFAULT 0,
  `rank` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `university` (`university`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`university`) REFERENCES `universities` (`name`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `username`, `rating`, `university`, `solvedCount`, `rank`, `password`, `role`) VALUES ('u1','Ismail',1451,'Tech University of Science',343,'Specialist','$2y$10$Y1VFdlmktCvNnnVqDlXgM.ZBN.rLyGJiA1W0OUJewqEHSxtDKBVKu','admin'),('u2','Tamjid',1520,'State College of Engineering',360,'Expert','$2y$10$4slot9b0kf91FPs/jCJt6udvmw8Qz6zQOwyIuZun1jTZjj0i2fosq','user'),('u3','David',1300,'National Institute',150,'Pupil','$2y$10$4slot9b0kf91FPs/jCJt6udvmw8Qz6zQOwyIuZun1jTZjj0i2fosq','user'),('u4','Anon',0,'Tech University of Science',0,'Newbie','$2y$10$4slot9b0kf91FPs/jCJt6udvmw8Qz6zQOwyIuZun1jTZjj0i2fosq','user');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================
-- GAMIFICATION SCHEMA (Phase 1)
-- ============================================

-- Add gamification columns to users
ALTER TABLE users ADD COLUMN xp INT DEFAULT 0;
ALTER TABLE users ADD COLUMN current_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN longest_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN last_solved_date DATE DEFAULT NULL;

-- Levels table
CREATE TABLE IF NOT EXISTS levels (
    level INT PRIMARY KEY,
    title VARCHAR(50) NOT NULL,
    xp_required INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO levels (level, title, xp_required) VALUES
(1, 'Code Sprout', 0),
(2, 'Byte Explorer', 50),
(3, 'Logic Wizard', 120),
(4, 'Syntax Seeker', 250),
(5, 'Algorithm Apprentice', 450),
(6, 'Data Dynamo', 700),
(7, 'Code Commander', 1000),
(8, 'Binary Baron', 1400),
(9, 'Pixel Paladin', 1900),
(10, 'CodeForge Champion', 2500);

-- Badges table
CREATE TABLE IF NOT EXISTS badges (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO badges (id, name, description, icon) VALUES
('first_steps', 'First Steps', 'Solved your first quest!', 'bi-star-fill'),
('on_a_roll', 'On a Roll', 'Maintained a 3-day streak!', 'bi-fire'),
('century', 'Century', 'Earned 100 XP!', 'bi-trophy-fill'),
('explorer', 'Explorer', 'Solved quests in 3+ different topics!', 'bi-compass'),
('topic_master', 'Topic Master', '100% mastery in any one topic!', 'bi-graph-up-arrow'),
('sprout_master', 'Sprout Master', 'Solved 10 Easy quests!', 'bi-seedling-fill');

-- User badges table
CREATE TABLE IF NOT EXISTS user_badges (
    userId VARCHAR(50) NOT NULL,
    badgeId VARCHAR(50) NOT NULL,
    earnedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (userId, badgeId),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badgeId) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Trigger: automatic XP/streak/solvedCount on AC submission
-- Badge logic is handled in PHP (checkAndAwardBadges) after commit for cleaner conditional lookups.
DELIMITER //

DROP TRIGGER IF EXISTS after_submission_ac //
CREATE TRIGGER after_submission_ac
AFTER INSERT ON submissions
FOR EACH ROW
BEGIN
    IF NEW.verdict = 'AC' THEN
        DECLARE diff VARCHAR(50) DEFAULT NULL;
        DECLARE xp_to_add INT DEFAULT 10;
        DECLARE last_date DATE DEFAULT NULL;
        DECLARE curr_streak INT DEFAULT 0;
        DECLARE long_streak INT DEFAULT 0;

        SELECT difficulty INTO diff FROM Problems WHERE id = NEW.problemId;

        IF diff = 'Easy' THEN
            SET xp_to_add = 10;
        ELSEIF diff = 'Medium' THEN
            SET xp_to_add = 25;
        ELSEIF diff = 'Hard' THEN
            SET xp_to_add = 50;
        END IF;

        UPDATE Users
        SET solvedCount = solvedCount + 1,
            xp = xp + xp_to_add
        WHERE id = NEW.userId;

        SELECT last_solved_date, current_streak, longest_streak
        INTO last_date, curr_streak, long_streak
        FROM Users WHERE id = NEW.userId;

        IF last_date IS NULL THEN
            SET curr_streak = 1;
        ELSEIF last_date = CURDATE() THEN
            SET curr_streak = curr_streak;
        ELSEIF last_date = CURDATE() - INTERVAL 1 DAY THEN
            SET curr_streak = curr_streak + 1;
        ELSE
            SET curr_streak = 1;
        END IF;

        IF curr_streak > long_streak THEN
            SET long_streak = curr_streak;
        END IF;

        UPDATE Users
        SET current_streak = curr_streak,
            longest_streak = long_streak,
            last_solved_date = CURDATE()
        WHERE id = NEW.userId;
    END IF;
END//
DELIMITER ;

-- ============================================
-- PHASE 2 SCHEMA STUBS
-- ============================================

ALTER TABLE users ADD COLUMN coins INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS hints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problemId VARCHAR(50) NOT NULL,
    hintText TEXT NOT NULL,
    cost INT DEFAULT 10,
    FOREIGN KEY (problemId) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hint_purchases (
    userId VARCHAR(50) NOT NULL,
    hintId INT NOT NULL,
    purchasedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (userId, hintId),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hintId) REFERENCES hints(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS classrooms (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    teacherId VARCHAR(50) NOT NULL,
    FOREIGN KEY (teacherId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS classroom_members (
    classroomId VARCHAR(50) NOT NULL,
    studentId VARCHAR(50) NOT NULL,
    PRIMARY KEY (classroomId, studentId),
    FOREIGN KEY (classroomId) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (studentId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: skill-tree gating (Phase 2) would benefit from a topic_difficulty_stats table
-- tracking per-user, per-topic, per-difficulty solve counts for efficient gating.

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-27 12:08:33
