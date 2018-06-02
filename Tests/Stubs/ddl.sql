--
-- Joomla Unit Test DDL
--

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `username` TEXT NOT NULL DEFAULT '',
  `email` TEXT NOT NULL DEFAULT '',
  `password` TEXT NOT NULL DEFAULT '',
  `usertype` TEXT NOT NULL DEFAULT '',
  `block` INTEGER NOT NULL DEFAULT '0',
  `sendEmail` INTEGER DEFAULT '0',
  `resetCount` INTEGER DEFAULT '0',
  `registerDate` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastvisitDate` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastResetTime` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activation` TEXT NOT NULL DEFAULT '',
  `params` TEXT NOT NULL DEFAULT ''
);

CREATE INDEX `idx_users_usertype` ON `users` (`usertype`);
CREATE INDEX `idx_users_name` ON `users` (`name`);
CREATE INDEX `idx_users_block` ON `users` (`block`);
CREATE INDEX `idx_users_username` ON `users` (`username`);
CREATE INDEX `idx_users_email` ON `users` (`email`);

-- --------------------------------------------------------