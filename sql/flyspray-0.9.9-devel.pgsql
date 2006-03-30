-- This wonderful pgsql updatescript is brought to you by Gutzmann EDV (Arne Kršger feat. Heiko Reese)

-- Simplified by Ondrej Jirman

-- =============================================================================
--
-- get out of the magic_gpc hell -- Pierre Habouzit :: 2005-10-30 22:00
--
-- this has been generated automatically (vim macros) from the SQL schema, and
-- replaces quotes in *EVERY* textual item
-- this is exhaustive (maybe a bit too much, but It can't harm)
--
-- ========================================================================={{{=

BEGIN;

UPDATE flyspray_admin_requests SET
	 reason_given = REPLACE(REPLACE(reason_given, '\\\'', '\''), '\\"', '"'),
	 time_submitted = REPLACE(REPLACE(time_submitted, '\\\'', '\''), '\\"', '"'),
	 time_resolved = REPLACE(REPLACE(time_resolved, '\\\'', '\''), '\\"', '"'),
	 deny_reason = REPLACE(REPLACE(deny_reason, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_assigned SET
	 user_or_group = REPLACE(REPLACE(user_or_group, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_attachments SET
	 orig_name = REPLACE(REPLACE(orig_name, '\\\'', '\''), '\\"', '"'),
	 file_name = REPLACE(REPLACE(file_name,  '\\\'', '\''), '\\"', '"'),
	 file_desc = REPLACE(REPLACE(file_desc, '\\\'', '\''), '\\"', '"'),
	 file_type = REPLACE(REPLACE(file_type, '\\\'', '\''), '\\"', '"'),
	 date_added = REPLACE(REPLACE(date_added,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_comments SET
	 date_added = REPLACE(REPLACE(date_added, '\\\'', '\''), '\\"', '"'),
	 comment_text = REPLACE(REPLACE(comment_text, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_groups SET
	 group_name = REPLACE(REPLACE(group_name,  '\\\'', '\''), '\\"', '"'),
	 group_desc = REPLACE(REPLACE(group_desc,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_history SET
	 event_date = REPLACE(REPLACE(event_date, '\\\'', '\''), '\\"', '"'),
	 field_changed = REPLACE(REPLACE(field_changed,  '\\\'', '\''), '\\"', '"'),
	 old_value = REPLACE(REPLACE(old_value,  '\\\'', '\''), '\\"', '"'),
	 new_value = REPLACE(REPLACE(new_value,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_list_category SET
	 category_name = REPLACE(REPLACE(category_name,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_list_os SET
	 os_name = REPLACE(REPLACE(os_name, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_list_resolution SET
	 resolution_name = REPLACE(REPLACE(resolution_name,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_list_tasktype SET
	 tasktype_name = REPLACE(REPLACE(tasktype_name,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_list_version SET
	 version_name = REPLACE(REPLACE(version_name, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_notification_messages SET
	 message_subject = REPLACE(REPLACE(message_subject,  '\\\'', '\''), '\\"', '"'),
	 message_body = REPLACE(REPLACE(message_body,  '\\\'', '\''), '\\"', '"'),
	 time_created = REPLACE(REPLACE(time_created,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_notification_recipients SET
	 notify_method = REPLACE(REPLACE(notify_method,  '\\\'', '\''), '\\"', '"'),
	 notify_address = REPLACE(REPLACE(notify_address,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_prefs SET
	 pref_name = REPLACE(REPLACE(pref_name, '\\\'', '\''), '\\"', '"'),
	 pref_value = REPLACE(REPLACE(pref_value, '\\\'', '\''), '\\"', '"'),
	 pref_desc = REPLACE(REPLACE(pref_desc,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_projects SET
	 project_title = REPLACE(REPLACE(project_title,  '\\\'', '\''), '\\"', '"'),
	 theme_style = REPLACE(REPLACE(theme_style,  '\\\'', '\''), '\\"', '"'),
	 intro_message = REPLACE(REPLACE(intro_message,  '\\\'', '\''), '\\"', '"'),
	 visible_columns = REPLACE(REPLACE(visible_columns,  '\\\'', '\''), '\\"', '"'),
	 notify_email = REPLACE(REPLACE(notify_email,  '\\\'', '\''), '\\"', '"'),
	 notify_jabber = REPLACE(REPLACE(notify_jabber,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_registrations SET
	 reg_time = REPLACE(REPLACE(reg_time,  '\\\'', '\''), '\\"', '"'),
	 confirm_code = REPLACE(REPLACE(confirm_code, '\\\'', '\''), '\\"', '"'),
	 user_name = REPLACE(REPLACE(user_name,  '\\\'', '\''), '\\"', '"'),
	 real_name = REPLACE(REPLACE(real_name,  '\\\'', '\''), '\\"', '"'),
	 email_address = REPLACE(REPLACE(email_address,  '\\\'', '\''), '\\"', '"'),
	 jabber_id = REPLACE(REPLACE(jabber_id, '\\\'', '\''), '\\"', '"'),
	 magic_url = REPLACE(REPLACE(magic_url,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_reminders SET
	 start_time = REPLACE(REPLACE(start_time,  '\\\'', '\''), '\\"', '"'),
	 last_sent = REPLACE(REPLACE(last_sent,  '\\\'', '\''), '\\"', '"'),
	 reminder_message = REPLACE(REPLACE(reminder_message, '\\\'', '\''), '\\"', '"');

UPDATE flyspray_tasks SET
	 date_opened = REPLACE(REPLACE(date_opened,  '\\\'', '\''), '\\"', '"'),
	 date_closed = REPLACE(REPLACE(date_closed,  '\\\'', '\''), '\\"', '"'),
	 closure_comment = REPLACE(REPLACE(closure_comment,  '\\\'', '\''), '\\"', '"'),
	 item_summary = REPLACE(REPLACE(item_summary,  '\\\'', '\''), '\\"', '"'),
	 detailed_desc = REPLACE(REPLACE(detailed_desc, '\\\'', '\''), '\\"', '"'),
	 last_edited_time = REPLACE(REPLACE(last_edited_time, '\\\'', '\''), '\\"', '"'),
	 due_date = REPLACE(REPLACE(due_date,  '\\\'', '\''), '\\"', '"');

UPDATE flyspray_users SET
	 user_name = REPLACE(REPLACE(user_name,  '\\\'', '\''), '\\"', '"'),
	 user_pass = REPLACE(REPLACE(user_pass,  '\\\'', '\''), '\\"', '"'),
	 real_name = REPLACE(REPLACE(real_name, '\\\'', '\''), '\\"', '"'),
	 jabber_id = REPLACE(REPLACE(jabber_id,  '\\\'', '\''), '\\"', '"'),
	 email_address = REPLACE(REPLACE(email_address,  '\\\'', '\''), '\\"', '"'),
	 dateformat = REPLACE(REPLACE(dateformat,  '\\\'', '\''), '\\"', '"'),
	 dateformat_extended = REPLACE(REPLACE(dateformat_extended,  '\\\'', '\''), '\\"', '"'),
	 magic_url = REPLACE(REPLACE(magic_url, '\\\'', '\''), '\\"', '"'),
	 last_search = REPLACE(REPLACE(last_search,  '\\\'', '\''), '\\"', '"');

-- =========================================================================}}}=

-- =============================================================================
--
-- Florian Schmitz, Added on 05 November 05
--
-- RSS/Atom feeds
--
-- (Updated 08 November 2005 by Mac Newbold - rename limit column to max_items,
--  because limit is a reserved word in sql)
--
-- =============================================================================

ALTER TABLE flyspray_projects ADD COLUMN feed_img_url TEXT NOT NULL DEFAULT '';
ALTER TABLE flyspray_projects ADD COLUMN feed_description TEXT NOT NULL DEFAULT '';

INSERT INTO flyspray_prefs(pref_name, pref_value, pref_desc) VALUES ('cache_feeds', '0', '0 = do not cache feeds, 1 = cache feeds on disk, 2 = cache feeds in DB');

CREATE TABLE flyspray_cache (
  id SERIAL,
  type varchar(4) NOT NULL default '',
  content text NOT NULL,
  topic varchar(30) NOT NULL default '',
  last_updated int NOT NULL default 0,
  project int NOT NULL default 0,
  max_items int NOT NULL default 0
);

-- Mac Newbold, 08 November 2005
-- Fix closure_comment from '0' to '' so they are properly blank.

UPDATE flyspray_tasks SET closure_comment='' WHERE closure_comment='0';

-- Florian Schmitz, 10 November 2005
-- FS#718

ALTER TABLE flyspray_projects DROP inline_images;

-- Florian Schmitz, 11 November 2005
-- FS#610

ALTER TABLE flyspray_projects DROP show_logo;

-- Florian Schmitz, 13 November 2005

-- Ondrej Jirman: useless hassle, types TEXT and VARCHAR are essentially the same in postgres
--ALTER TABLE flyspray_users ALTER COLUMN user_name TYPE VARCHAR;
--ALTER TABLE flyspray_registrations ALTER COLUMN user_name TYPE VARCHAR;

-- Tony Collins, 19 November 2005
-- Changed field for FS#329

ALTER TABLE flyspray_tasks ALTER COLUMN assigned_to TYPE VARCHAR ;
ALTER TABLE flyspray_tasks ALTER COLUMN assigned_to SET DEFAULT '0' ;

-- =============================================================================
--
-- add indexes -- Pierre Habouzit :: 2005-11-19 13:07
--
-- unique to ensure some invariants in the DB rather than in the code
-- others to speed up queries
--
-- =========================================================================={{{
-- lists tables

-- already exists
--CREATE INDEX flyspray_list_category_project_id_idx ON flyspray_list_category(project_id);

CREATE INDEX flyspray_list_category_project_id_idx ON flyspray_list_category(project_id);
CREATE INDEX flyspray_list_os_project_id_idx ON flyspray_list_os(project_id);
CREATE INDEX flyspray_list_resolution_project_id_idx ON flyspray_list_resolution(project_id);
CREATE INDEX flyspray_list_tasktype_project_id_idx ON flyspray_list_tasktype(project_id);
CREATE INDEX flyspray_list_version_project_id_version_tense_idx ON flyspray_list_version(project_id, version_tense);

-- join tables
CREATE UNIQUE INDEX flyspray_related_this_task_related_task_idx ON flyspray_related(this_task, related_task);
CREATE UNIQUE INDEX flyspray_dependencies_task_id_dep_task_id_idx ON flyspray_dependencies(task_id, dep_task_id);
CREATE UNIQUE INDEX flyspray_notifications_task_id_user_id_idx ON flyspray_notifications(task_id, user_id);

-- user and group related indexes
CREATE UNIQUE INDEX flyspray_users_in_groups_group_id_user_id_idx ON flyspray_users_in_groups(group_id, user_id);
CREATE INDEX flyspray_users_in_groups_user_id_idx ON flyspray_users_in_groups(user_id);
CREATE INDEX flyspray_groups_belongs_to_project_idx ON flyspray_groups(belongs_to_project);

-- task related indexes
CREATE INDEX flyspray_attachments_task_id_comment_id_idx ON flyspray_attachments(task_id, comment_id);
CREATE INDEX flyspray_comments_task_id_idx ON flyspray_comments(task_id);

CREATE INDEX flyspray_tasks_attached_to_project_idx ON flyspray_tasks(attached_to_project);
CREATE INDEX flyspray_tasks_task_severity_idx ON flyspray_tasks(task_severity);
CREATE INDEX flyspray_tasks_task_type_idx ON flyspray_tasks(task_type);
CREATE INDEX flyspray_tasks_product_category_idx ON flyspray_tasks(product_category);
CREATE INDEX flyspray_tasks_item_status_idx ON flyspray_tasks(item_status);
CREATE INDEX flyspray_tasks_is_closed_idx ON flyspray_tasks(is_closed);
CREATE INDEX flyspray_tasks_assigned_to_idx ON flyspray_tasks(assigned_to);
CREATE INDEX flyspray_tasks_closedby_version_idx ON flyspray_tasks(closedby_version);
CREATE INDEX flyspray_tasks_due_date_idx ON flyspray_tasks(due_date);

-- ==========================================================================}}}

-- Florian Schmitz, 21 November 2005, FS#344

ALTER TABLE flyspray_projects ADD COLUMN notify_subject VARCHAR(100) NOT NULL DEFAULT '';

-- Tony Collins, 22 November 2005 (FS#329)

ALTER TABLE flyspray_assigned DROP user_or_group;
ALTER TABLE flyspray_assigned RENAME assignee_id TO user_id;
CREATE INDEX flyspray_assigned_task_id_user_id_idx ON flyspray_assigned( task_id , user_id );

-- Tony Collins, 23 November 2005 (FS#329)

ALTER TABLE flyspray_groups ADD COLUMN add_to_assignees SMALLINT NOT NULL DEFAULT 0;
UPDATE flyspray_groups SET add_to_assignees = assign_others_to_self;

-- Florian Schmitz, 18 December 2005 (FS#723)
-- Ondrej Jirman: default changed to 'en', this fixes problem when incorrect
-- language code (de) is shown on project mgmt page for the first time after
-- update
ALTER TABLE flyspray_projects ADD COLUMN lang_code VARCHAR(10) NOT NULL DEFAULT 'en';

-- Florian Schmitz, 24 December 2005 (FS#287)
CREATE TABLE flyspray_list_status (
  status_id SERIAL PRIMARY KEY,
  status_name varchar(20) NOT NULL default '',
  list_position INT NOT NULL default 0,
  show_in_list INT NOT NULL default 0,
  project_id INT NOT NULL default 0
);

INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (1, 'Unconfirmed', 1, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (2, 'New', 2, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (3, 'Assigned', 3, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (4, 'Researching', 4, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (5, 'Waiting on Customer', 5, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (6, 'Requires testing', 6, 1, 0);
INSERT INTO flyspray_list_status (status_id, status_name, list_position, show_in_list, project_id) VALUES (7, 'Reopened', 7, 1, 0);

SELECT pg_catalog.setval('flyspray_list_status_status_id_seq', 7, true);

-- Florian Schmitz, 5 January 2006
INSERT INTO flyspray_prefs ( pref_name , pref_value , pref_desc )
VALUES ('last_update_check', '0', 'Time when the last update check was done.');

-- Florian Schmitz, 14 January 2006
CREATE TABLE flyspray_searches (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,
  search_string TEXT NOT NULL,
  time INT NOT NULL
);

-- Tony Collins, 22 January 2006
ALTER TABLE flyspray_groups ADD COLUMN add_votes SMALLINT NOT NULL DEFAULT 0;
UPDATE flyspray_groups SET add_votes = view_reports;

CREATE TABLE flyspray_votes (
  vote_id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  task_id INT NOT NULL,
  date_time VARCHAR(12) NOT NULL
);

UPDATE flyspray_groups SET add_votes = '1' WHERE group_id = 1;

-- Gutzmann EDV, 23 January 2006
update flyspray_prefs set pref_value ='0.9.9(devel)' where pref_name = 'fs_ver';

-- Florian Schmitz, 29 January 2006
UPDATE flyspray_tasks SET due_date = 0 WHERE due_date = '';

-- Florian Schmitz, 31 January 2006
ALTER TABLE flyspray_projects ADD COLUMN comment_closed INT NOT NULL DEFAULT 0;

-- Florian Schmitz, 21 February 2006
ALTER TABLE flyspray_users ALTER COLUMN last_search SET DEFAULT '';
UPDATE flyspray_users SET last_search = '' WHERE last_search IS NULL;
ALTER TABLE flyspray_users ALTER COLUMN last_search SET NOT NULL;

-- Florian Schmitz, 28 February 2006, FS#824
ALTER TABLE flyspray_tasks ALTER COLUMN closure_comment SET DEFAULT '';
UPDATE flyspray_tasks SET closure_comment = '' WHERE closure_comment IS NULL;
ALTER TABLE flyspray_tasks ALTER COLUMN closure_comment SET NOT NULL;

-- Florian Schmitz, 2 March 2006, FS#829
ALTER TABLE flyspray_groups ADD COLUMN edit_own_comments SMALLINT NOT NULL DEFAULT 0;
UPDATE flyspray_groups SET edit_own_comments = edit_comments;

-- Florian Schmitz, 2 March 2006, FS#836
-- Ondrej Jirman: already in 0.9.8 pgsql schema dump
--ALTER TABLE flyspray_projects MODIFY notify_email TEXT NOT NULL DEFAULT '';
--ALTER TABLE flyspray_projects MODIFY notify_jabber TEXT NOT NULL DEFAULT ''; 

-- Florian Schmitz, 4 March 2006
UPDATE flyspray_groups SET add_votes = 1 WHERE group_id = 2 OR group_id = 3 OR group_id = 6;

DELETE FROM flyspray_list_status WHERE status_id = 7;

-- Florian Schmitz, 24 March 2006
ALTER TABLE flyspray_comments ADD COLUMN last_edited_time VARCHAR(12) NOT NULL DEFAULT '0';

-- Florian Schmitz, 25 March 2006
ALTER TABLE flyspray_cache ADD UNIQUE (type, topic, project, max_items);

-- FS#750 Per-user option to enable notifications for own changes
ALTER TABLE flyspray_users ADD COLUMN notify_own SMALLINT NOT NULL DEFAULT 0;
UPDATE flyspray_users SET notify_own = notify_type;

-- Florian Schmitz, 26 March 2006
ALTER TABLE flyspray_tasks ADD COLUMN anon_email VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE flyspray_tasks ADD COLUMN task_token VARCHAR(32) NOT NULL DEFAULT '0';

COMMIT;
