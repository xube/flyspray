CREATE TABLE `flyspray_reminders` (
  `reminder_id` mediumint(10) NOT NULL auto_increment,
  `task_id` mediumint(10) NOT NULL default '0',
  `to_user_id` mediumint(3) NOT NULL default '0',
  `from_user_id` mediumint(3) NOT NULL default '0',
  `start_time` varchar(12) NOT NULL default '0',
  `how_often` mediumint(12) NOT NULL default '0',
  `last_sent` varchar(12) NOT NULL default '0',
  `reminder_message` longtext NOT NULL,
  PRIMARY KEY  (`reminder_id`)
) TYPE=MyISAM COMMENT='Scheduled reminders about tasks' AUTO_INCREMENT=19 ;


ALTER TABLE `flyspray_tasks` ADD `is_closed` MEDIUMINT( 1 ) NOT NULL AFTER `opened_by` ;

UPDATE flyspray_tasks SET is_closed = '1' WHERE item_status = '8';

ALTER TABLE `flyspray_projects` ADD `inline_images` MEDIUMINT( 1 ) NOT NULL AFTER `show_logo` ;

ALTER TABLE `flyspray_tasks` ADD `closure_comment` LONGTEXT NOT NULL AFTER `closed_by` ;