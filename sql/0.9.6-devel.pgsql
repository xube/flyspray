CREATE TABLE flyspray_reminders (
  reminder_id	    SERIAL NOT NULL,
  task_id	    INTEGER NOT NULL default '0',
  to_user_id	    INTEGER NOT NULL default '0',
  from_user_id	    INTEGER NOT NULL default '0',
  start_time	    TEXT NOT NULL default '0',
  how_often	    INTEGER NOT NULL default '0',
  last_sent	    TEXT NOT NULL default '0',
  reminder_message  TEXT NOT NULL,
  PRIMARY KEY (reminder_id)
); 

ALTER TABLE flyspray_tasks ADD is_closed INTEGER;
ALTER TABLE flyspray_tasks ALTER COLUMN is_closed SET DEFAULT 0;
UPDATE flyspray_tasks SET is_closed = 0;
UPDATE flyspray_tasks SET is_closed = 1 WHERE item_status = 8;
ALTER TABLE flyspray_tasks ALTER COLUMN is_closed SET NOT NULL;

ALTER TABLE flyspray_projects ADD inline_images INTEGER;
UPDATE flyspray_projects SET inline_images = 0;
ALTER TABLE flyspray_projects ALTER COLUMN inline_images SET NOT NULL;

ALTER TABLE flyspray_tasks ADD closure_comment TEXT;
UPDATE flyspray_tasks SET closure_comment = '';
ALTER TABLE flyspray_tasks ALTER COLUMN closure_comment SET NOT NULL;
ALTER TABLE flyspray_tasks ALTER COLUMN closure_comment SET DEFAULT '';

ALTER TABLE flyspray_users ADD dateformat TEXT;
UPDATE flyspray_users SET dateformat = '';
ALTER TABLE flyspray_users ALTER COLUMN dateformat SET NOT NULL;
ALTER TABLE flyspray_users ALTER COLUMN dateformat SET DEFAULT '';

ALTER TABLE flyspray_users ADD dateformat_extended TEXT;
UPDATE flyspray_users SET dateformat_extended = '';
ALTER TABLE flyspray_users ALTER COLUMN dateformat_extended SET NOT NULL;
ALTER TABLE flyspray_users ALTER COLUMN dateformat_extended SET DEFAULT '';

INSERT INTO flyspray_prefs(pref_name, pref_value, pref_desc) VALUES ('dateformat', '', 'Default date format for new users and guests used in the task list');
INSERT INTO flyspray_prefs(pref_name, pref_value, pref_desc) VALUES ('dateformat_extended', '', 'Default date format for new users and guests used in task details');

ALTER TABLE flyspray_list_category ADD parent_id INTEGER;
UPDATE flyspray_list_category SET parent_id = 0;
ALTER TABLE flyspray_list_category ALTER COLUMN parent_id SET NOT NULL;
ALTER TABLE flyspray_list_category ALTER COLUMN parent_id SET DEFAULT 0;

CREATE TABLE flyspray_history (
  history_id	SERIAL NOT NULL,
  task_id	INTEGER NOT NULL default '0',
  user_id	INTEGER NOT NULL default '0',
  event_date	TEXT NOT NULL default '',
  event_type	INTEGER NOT NULL default '0',
  field_changed TEXT NOT NULL default '',
  old_value	TEXT NOT NULL default '',
  new_value	TEXT NOT NULL default '',
  PRIMARY KEY (history_id)
);

ALTER TABLE flyspray_projects ADD visible_columns TEXT;
UPDATE flyspray_projects SET visible_columns = 'id category tasktype severity summary dateopened status progress';
ALTER TABLE flyspray_projects ALTER COLUMN visible_columns SET NOT NULL;

ALTER TABLE flyspray_list_version ADD version_tense INTEGER;
UPDATE flyspray_list_version SET version_tense = '2';
ALTER TABLE flyspray_list_version ALTER COLUMN version_tense SET NOT NULL;
