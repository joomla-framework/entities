--
-- Joomla Unit Test DDL
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `cid` INTEGER NOT NULL DEFAULT '0',
  `type` INTEGER NOT NULL DEFAULT '0',
  `name` TEXT NOT NULL DEFAULT '',
  `alias` TEXT NOT NULL DEFAULT '',
  `imptotal` INTEGER NOT NULL DEFAULT '0',
  `impmade` INTEGER NOT NULL DEFAULT '0',
  `clicks` INTEGER NOT NULL DEFAULT '0',
  `clickurl` TEXT NOT NULL DEFAULT '',
  `state` INTEGER NOT NULL DEFAULT '0',
  `catid` INTEGER NOT NULL DEFAULT '0',
  `description` TEXT NOT NULL,
  `custombannercode` TEXT NOT NULL,
  `sticky` INTEGER NOT NULL DEFAULT '0',
  `ordering` INTEGER NOT NULL DEFAULT '0',
  `metakey` text NOT NULL,
  `params` text NOT NULL,
  `own_prefix` INTEGER NOT NULL DEFAULT '0',
  `metakey_prefix` TEXT NOT NULL DEFAULT '',
  `purchase_type` INTEGER NOT NULL DEFAULT '-1',
  `track_clicks` INTEGER NOT NULL DEFAULT '-1',
  `track_impressions` INTEGER NOT NULL DEFAULT '-1',
  `checked_out` INTEGER NOT NULL DEFAULT '0',
  `checked_out_time` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reset` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `language` TEXT NOT NULL DEFAULT '',
  `created_by` INTEGER NOT NULL DEFAULT '0',
  `created_by_alias` TEXT NOT NULL DEFAULT '',
  `modified` TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` INTEGER NOT NULL DEFAULT '0',
  `version` INTEGER NOT NULL DEFAULT '1'
);

CREATE INDEX `idx_state` ON `banners` (`state`);
CREATE INDEX `idx_own_prefix` ON `banners` (`own_prefix`);
CREATE INDEX `idx_banner_catid` ON `banners` (`catid`);
CREATE INDEX `idx_language` ON `banners` (`language`);

-- --------------------------------------------------------

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

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` INTEGER NOT NULL,
  `profile_key` TEXT NOT NULL DEFAULT '',
  `profile_value` TEXT NOT NULL DEFAULT '',
  `ordering` INTEGER NOT NULL DEFAULT '0',
  CONSTRAINT `idx_user_profiles_lookup` UNIQUE (`user_id`,`profile_key`)
);

-- --------------------------------------------------------

