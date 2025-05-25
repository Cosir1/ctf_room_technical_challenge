CREATE DATABASE IF NOT EXISTS `event_scoring`;
USE `event_scoring`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` enum('user','judge','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('pending','active','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

CREATE TABLE `judges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);

CREATE TABLE `event_judges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_judge` (`event_id`,`judge_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`)
);

CREATE TABLE `scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `points` int(11) NOT NULL CHECK (`points` between 1 and 100),
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_judge_score` (`event_id`,`user_id`,`judge_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`)
);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `event_judges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_judge` (`event_id`,`judge_id`),
  ADD KEY `judge_id` (`judge_id`);

ALTER TABLE `judges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `user_id` (`user_id`);

ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_judge_score` (`event_id`,`user_id`,`judge_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `judge_id` (`judge_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);


ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    
ALTER TABLE `event_judges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `judges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `event_judges`
  ADD CONSTRAINT `event_judges_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `event_judges_ibfk_2` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`);

ALTER TABLE `judges`
  ADD CONSTRAINT `judges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`);
COMMIT;

