<?php

  /********************************************************\
  | Project Managers Toolbox                               |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  | This script is for Project Managers to modify settings |
  | for their project, including general permissions,      |
  | members, group permissions, and dropdown list items.   |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoPm extends FlysprayDoAdmin
{
    var $default_handler = 'prefs';

    function is_projectlevel() {
        return true;
    }

    // **********************
    // Begin all area_ functions
    // **********************

    function area_pendingreq()
    {
        global $db, $page, $proj;

        $sql = $db->Execute("SELECT  *
                             FROM  {admin_requests} ar
                        LEFT JOIN  {tasks} t ON ar.task_id = t.task_id
                        LEFT JOIN  {users} u ON ar.submitted_by = u.user_id
                            WHERE  ar.project_id = ? AND resolved_by = 0
                         ORDER BY  ar.time_submitted ASC", array($proj->id));

        $page->assign('pendings', $sql->GetArray());
    }

    function area_prefs(){}

    function area_user() {
        global $proj;
        FlysprayDo::error(array(ERROR_INPUT, L('error17'), CreateUrl(array('pm', 'proj' . $proj->id))));
    }

    // **********************
    // End of area_ functions
    // **********************

    // **********************
    // Begin all action_ functions
    // **********************

    function action_updateproject()
    {
        global $proj, $db, $baseurl;

        if (Post::val('delete_project')) {
            $url = (Post::val('move_to')) ? CreateURL(array('pm', 'proj' . Post::num('move_to'), 'prefs')) : $baseurl;

            if (Backend::delete_project($proj->id, Post::val('move_to'))) {
                return array(SUBMIT_OK, L('projectdeleted'), $url);
            } else {
                return array(ERROR_INPUT, L('projectnotdeleted'), $url);
            }
        }

        if (!Post::val('project_title')) {
            return array(ERROR_RECOVER, L('emptytitle'));
        }

        $cols = array( 'project_title', 'theme_style', 'lang_code', 'default_task', 'default_entry',
                'intro_message', 'others_view', 'anon_open', 'send_digest', 'anon_view_tasks', 'anon_group',
                'notify_email', 'notify_jabber', 'notify_subject', 'notify_reply', 'roadmap_field',
                'feed_description', 'feed_img_url', 'comment_closed', 'auto_assign', 'override_user_lang');
        $args = array_map('Post_to0', $cols);
        $cols[] = 'notify_types';
        $args[] = implode(' ', (array) Post::val('notify_types'));
        $cols[] = 'changelog_reso';
        $args[] = implode(' ', (array) Post::val('changelog_reso'));

        // carefully check the project prefix...
        $prefix = Post::val('project_prefix');
        // already in use?
        $use = $db->GetOne('SELECT project_id FROM {projects} WHERE project_prefix = ? AND project_id != ?',
                            array($prefix, $proj->id));
        if (ctype_alnum($prefix) && $prefix != 'FS' && !$use) {
            $cols[] = 'project_prefix';
            $args[] =  $prefix;
        } else {
            return array(ERROR_RECOVER, L('badprefix'));
        }

        $cols[] = 'last_updated';
        $args[] = time();
        $cols[] = 'default_cat_owner';
        $args[] =  Flyspray::username_to_id(Post::val('default_cat_owner'));
        $args[] = $proj->id;

        $db->Execute("UPDATE  {projects}
                         SET  ".join('=?, ', $cols)."=?
                       WHERE  project_id = ?", $args);

        $db->Execute('UPDATE {projects} SET visible_columns = ? WHERE project_id = ?',
                      array(trim(Post::val('visible_columns')), $proj->id));

        return array(SUBMIT_OK, L('projectupdated'));
    }

    // **********************
    // End of action_ functions
    // **********************

	function show($area = null)
	{
		global $page, $fs, $db, $proj;

        $page->pushTpl('pm.menu.tpl');

        $this->handle('area', $area);

		$page->setTitle($fs->prefs['page_title'] . L('pmtoolbox'));
		$page->pushTpl('pm.'.$area.'.tpl');
	}

	function _onsubmit()
	{
        global $fs, $db, $proj, $user;

        list($type, $msg, $url) = $this->handle('action', Post::val('action'));
        if ($type != NO_SUBMIT) {
        	$proj = new Project($proj->id);
        }

        return array($type, $msg, $url);
	}

	function is_accessible()
	{
		global $user, $proj;
		return $user->perms('manage_project') && $proj->id;
	}
}

?>