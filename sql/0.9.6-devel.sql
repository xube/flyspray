#
# Table structure for table `flyspray_reminders`
#

CREATE TABLE `flyspray_reminders` (
  `reminder_id` mediumint(10) NOT NULL auto_increment,
  `task_id` mediumint(10) NOT NULL default '0',
  `user_id` mediumint(3) NOT NULL default '0',
  `start_time` mediumint(12) NOT NULL default '',
  `how_often` mediumint(12) NOT NULL default '',
  `last_sent` mediumint(12) NOT NULL default '0',
  PRIMARY KEY  (`reminder_id`)
) TYPE=MyISAM COMMENT='Scheduled reminders about tasks' AUTO_INCREMENT=1 ;