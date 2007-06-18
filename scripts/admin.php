<?php

  /***********************************************\
  | Administrator's Toolbox                       |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                      |
  | This script allows members of a global Admin  |
  | group to modify the global preferences, user  |
  | profiles, global lists, global groups, pretty |
  | much everything global.                       |
  \***********************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoAdmin extends FlysprayDo
{
    var $default_handler = 'prefs';

    // **********************
    // Begin all area_ functions
    // **********************

    function area_prefs()
    {
    	global $db, $page;
        $sql = $db->Execute('SELECT * FROM {lists}
                              WHERE project_id = 0
                           ORDER BY list_type, list_name');
        $page->assign('lists', $sql->GetArray());
    }

    function area_editgroup()
    {
    	global $db, $page, $proj, $fs;

        $group = Flyspray::getGroupDetails(Req::num('group_id'));
        if (!$group || $group['project_id'] != $proj->id) {
            FlysprayDo::error(array(ERROR_RECOVER, L('groupnotexist'), CreateURL(array('pm', 'proj' . $proj->id, 'groups' ))));
        }

        $newparams = array();
        foreach ($fs->perms as $perm) {
            $newparams[$perm] = $group[$perm];
        }

        $page->assign('newparams', $newparams);
        $page->assign('group', $group);
        $page->assign('groups', Flyspray::listGroups());
    }

    function area_newuser()
    {
    	global $db, $page;
        $page->assign('groups', Flyspray::listGroups());
    }

    function area_user()
    {
    	global $db, $page;

        $id = Flyspray::username_to_id(Req::val('user_id'));

        $theuser = new User($id);
        if ($theuser->isAnon()) {
            FlysprayDo::error(array(ERROR_INPUT, L('error5')));
        }
        $page->assign('all_groups', Flyspray::listallGroups($theuser->id));
        $page->assign('groups', Flyspray::listGroups());
        $page->assign('theuser', $theuser);
    }

    function area_groups()
    {
    	global $db, $page, $proj;

        $page->assign('group_list', Flyspray::listallGroups());
        $sql = $db->Execute('SELECT g.group_id, g.group_name, g.group_desc,
                                    g.group_open, count(uig.user_id) AS num_users
                               FROM {groups} g
                          LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                              WHERE g.project_id = ?
                           GROUP BY g.group_id', array($proj->id));
        $page->assign('groups', $sql->GetArray());
    }

    function area_users()
    {
    	global $fs, $db, $proj, $user, $page;

        // Prepare the sorting
        $order_keys = array('username' => 'user_name',
                            'realname' => 'real_name',
                            'email'    => 'email_address',
                            'jabber'   => 'jabber_id',
                            'regdate'  => 'register_date',
                            'status'   => 'account_enabled');
        $order_column[0] = $order_keys[Filters::enum(Get::val('order', 'username'), array_keys($order_keys))];
        $order_column[1] = $order_keys[Filters::enum(Get::val('order2', 'username'), array_keys($order_keys))];
        $sortorder  = sprintf('%s %s, %s %s, u.user_id ASC',
                $order_column[0], Filters::enum(Get::val('sort', 'desc'), array('asc', 'desc')),
                $order_column[1], Filters::enum(Get::val('sort2', 'desc'), array('asc', 'desc')));

        // Search options
        $search_keys = array('user_name', 'real_name', 'email_address', 'jabber_id');
        $where = 'WHERE 1=1 ';
        $args = array();
        foreach ($search_keys as $key) {
            if (Get::val($key) != '') {
                $where .= sprintf(' AND %s LIKE ? ', $key);
                $args[] = '%' . Get::val($key) . '%';
            }
        }
        // Search for users in a specific group
        $groups = Get::val('group_id');
        if (is_array($groups) && count($groups) && !in_array(0, $groups)) {
            $where = ' LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id ' . $where;
            $where .= ' AND (' . substr(str_repeat(' uig.group_id = ? OR ', count($groups)), 0, -3) . ' ) ';
            $args = array_merge($args, $groups);
        }

        $sql = $db->Execute('SELECT u.user_id, u.user_name, u.real_name, u.register_date,
                                  u.jabber_id, u.email_address, u.account_enabled
                             FROM {users} u '
                         . $where .
                        'ORDER BY ' . $sortorder, $args);

        $users = GroupBy($sql, 'user_id');
        $page->assign('user_count', count($users));

        // Offset and limit
        $user_list = array();
        $offset = (max(Get::num('pagenum') - 1, 0)) * 50;
        for ($i = $offset; $i < $offset + 50 && $i < count($users); $i++) {
            $user_list[] = $users[$i];
        }

        // Get the user groups in a separate query because groups may be hidden
        // because of search options which are disregarded here
        if (count($user_list)) {
            $in = implode(',', array_map(create_function('$x', 'return reset($x);'), $user_list));
            $sql = $db->Execute('SELECT user_id, g.group_id, g.group_name, g.project_id
                                 FROM {groups} g
                            LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                                WHERE user_id IN ('. $in .')');
            $user_groups = GroupBy($sql, 'user_id', array('group_id', 'group_name', 'project_id'), !REINDEX);
            $page->assign('user_groups', $user_groups);
        }

        $page->assign('all_groups', Flyspray::listallGroups());
        $page->assign('user_list', $user_list);
    }

    function area_fields()
    {
    	global $fs, $db, $proj, $user, $page;

        $sql = $db->Execute('SELECT * FROM {lists}
                           ORDER BY project_id, list_type, list_name');
        $page->assign('lists', $sql->GetArray());
    }

    function area_lists()
    {
    	global $db, $proj, $page;

        $sql = $db->Execute('SELECT l.*, count(f.field_id) AS in_use
                               FROM {lists} l
                          LEFT JOIN {fields} f ON f.list_id = l.list_id
                              WHERE l.project_id = ?
                           GROUP BY l.list_id
                           ORDER BY list_type, list_name', array($proj->id));
        $page->assign('lists', $sql->GetArray());
    }

    function area_list()
    {
    	global $fs, $db, $proj, $user, $page;

        $sql = $db->Execute('SELECT list_type, list_name FROM {lists} WHERE list_id = ?',
                             array(Req::val('list_id')));
        $row = $sql->FetchRow();
        $list_type = reset($row);
        $list_name = next($row);

        if ($list_type != LIST_CATEGORY) {
            $page->assign('rows', $proj->get_edit_list(Req::val('list_id')));
        }
        $page->assign('list_type', $list_type);
        $page->assign('list_name', $list_name);
    }

    function area_system() {}
    function area_newproject() {}

    // **********************
    // End of area_ functions
    // **********************

    // **********************
    // Begin all action_ functions
    // **********************

    function action_globaloptions()
    {
    	global $fs, $db, $proj, $user;

    	foreach (array_keys($fs->prefs) as $setting) {
    		$db->Execute('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?',
                          array(Post::val($setting, ($fs->prefs[$setting] == '1') ? null : $fs->prefs[$setting]), $setting));
        }

        return array(SUBMIT_OK, L('optionssaved'));
    }

    function action_activate_user()
    {
    	global $fs, $db, $proj, $user;

        if (!Post::val('user_pass')) {
            return array(ERROR_RECOVER, L('formnotcomplete'));
        }

        $reg = $db->Execute('SELECT * FROM {registrations} WHERE user_name = ?',
                             array(Post::val('user_name')));

    	if ($details = $reg->FetchRow()) {
            Backend::create_user($details['user_name'], Post::val('user_pass'), $details['real_name'], $details['jabber_id'],
                                 $details['email_address'], $details['notify_type'], $details['time_zone'], $fs->prefs['anon_group']);
        } else {
            return array(ERROR_RECOVER, L('nounregistereduser'));
        }

        return array(SUBMIT_OK, L('useractivated'));
    }

    function action_add_field()
    {
    	global $fs, $db, $proj, $user;

    	if (!Post::val('field_name')) {
            return array(ERROR_RECOVER, L('fieldsmissing'));
        }

        $db->Execute('INSERT INTO {fields} (field_name, field_type, list_id, project_id)
                           VALUES (?, ?, ?, ?)',
                      array(Post::val('field_name'), Post::num('field_type'), Post::num('list_id'), $proj->id));

        $proj = new Project($proj->id);

        return array(SUBMIT_OK, L('fieldadded'));
    }

    function action_update_fields()
    {
    	global $fs, $db, $proj, $user;

    	$types = Post::val('field_type');
        $names = Post::val('field_name');
        $lists = Post::val('list_id');
        $tense = Post::val('version_tense');
        $delete = Post::val('delete');
        $force = Post::val('force_default');
        $required = Post::val('value_required');

        foreach (Post::val('id', array()) as $id) {
            if (isset($delete[$id])) {
                $db->Execute('DELETE FROM {fields} WHERE field_id = ? AND project_id = ?',
                            array($id, $proj->id));
                // sort of permission check (the query below does not check project_id)
                if ($db->Affected_Rows()) {
                    $db->Execute('DELETE FROM {field_values} WHERE field_id = ?', array($id));
                }
                continue;
            }

            $default[$id] = Post::val('field' . $id, 0);
            if ($types[$id]['field_type'] == FIELD_DATE && $default[$id]) {
                $default[$id] = Flyspray::strtotime($default[$id]);
            }

            $db->Execute('UPDATE {fields} SET field_name = ?, field_type = ?, list_id = ?, value_required = ?,
                                            version_tense = ?, default_value = ?, force_default = ?
                         WHERE field_id = ? AND project_id = ?',
                        array($names[$id], $types[$id], array_get($lists, $id, null), array_get($required, $id, 0),
                              array_get($tense, $id, 0), $default[$id],
                              array_get($force, $id, 0), $id, $proj->id));
        }
        $proj = new Project($proj->id);

        return array(SUBMIT_OK, L('fieldsupdated'));
    }

    function action_add_list()
    {
    	global $fs, $db, $proj, $user;

    	$sql = $db->Execute('INSERT INTO {lists} (list_name, list_type, project_id)
                                  VALUES (?, ?, ?)',
                             array(Post::val('list_name'), Post::num('list_type'), $proj->id));

        if (Post::num('list_type') == LIST_CATEGORY) {
            $list_id = $db->GetOne('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', array($proj->id));
            $db->Execute('INSERT INTO {list_category} (list_id, lft, rgt, category_name)
                               VALUES (?, ?, ?, ?)', array($list_id, 1, 2, 'root'));
        }
        return array(SUBMIT_OK, L('listadded'));
    }

    function action_update_list()
    {
    	global $fs, $db, $proj, $user;

        $listname     = Post::val('list_name');
        $listposition = Post::val('list_position');
        $listshow     = Post::val('show_in_list');
        $listtense    = Post::val('version_tense');
        $listdelete   = Post::val('delete');
        $listid       = Post::val('id');

        for ($i = 0; $i < count($listname); $i++) {
            if ($listname[$i] != '') {
                $params = array($listname[$i], intval($listposition[$i]), intval(array_get($listshow, $i, 0)), $listid[$i], $proj->id);
                $version_tense = '';
                if (is_array($listtense)) {
                    $version_tense = 'version_tense = ?,';
                    array_unshift($params, intval($listtense[$i]));
                }

                $db->Execute('UPDATE  {list_items} lb
                           LEFT JOIN  {lists} l ON l.list_id = lb.list_id
                                 SET  '. $version_tense .' item_name = ?, list_position = ?, show_in_list = ?
                               WHERE  list_item_id = ? AND project_id = ?',
                                 $params);
            } else {
                return array(ERROR_RECOVER, L('fieldsmissing'));
            }
        }

        if (is_array($listdelete) && count($listdelete)) {
            $deleteids = 'list_item_id = ' . join(' OR list_item_id =', array_map('intval', array_keys($listdelete)));
            $db->Execute("DELETE lb FROM {list_items} lb
                     LEFT JOIN {lists} l ON l.list_id = lb.list_id
                         WHERE project_id = ? AND ($deleteids)",
                        array($proj->id));
        }

        return array(SUBMIT_OK, L('listupdated'));
    }

    function action_update_lists()
    {
    	global $fs, $db, $proj, $user;

    	$types = Post::val('list_type');
        $names = Post::val('list_name');
        $delete = Post::val('delete');

        foreach (Post::val('id', array()) as $id) {
            if (isset($delete[$id])) {
                $db->Execute('DELETE FROM {lists} WHERE list_id = ? AND project_id = ?',
                           array($id, $proj->id));
                continue;
            }
            $db->Execute('UPDATE {lists} SET list_name = ?, list_type = ? WHERE list_id = ? AND project_id = ?',
                        array($names[$id], $types[$id], $id, $proj->id));
        }

        return array(SUBMIT_OK, L('listsupdated'));
    }

    function action_add_category()
    {
    	global $fs, $db, $proj, $user;

    	if (!Post::val('list_name')) {
            return array(ERROR_RECOVER, L('fieldsmissing'));
        }

        // Get right value of last node
        $right = intval($db->GetOne('SELECT rgt FROM {list_category} WHERE category_id = ?',
                                     array(Post::val('parent_id', -1))));

        $db->Execute('UPDATE {list_category} lc
                 LEFT JOIN {lists} l ON lc.list_id = l.list_id
                       SET rgt=rgt+2
                     WHERE rgt >= ? AND project_id = ?',
                     array($right, $proj->id));
        $db->Execute('UPDATE {list_category} lc
                 LEFT JOIN {lists} l ON lc.list_id = l.list_id
                       SET lft=lft+2
                     WHERE lft >= ? AND project_id = ?',
                     array($right, $proj->id));

        $db->Execute("INSERT INTO  {list_category}
                                   ( list_id, category_name, show_in_list, category_owner, lft, rgt )
                           VALUES  (?, ?, 1, ?, ?, ?)",
                array(Post::val('list_id'), Post::val('list_name'),
                      Post::val('category_owner', 0) == '' ? '0' : Flyspray::username_to_id(Post::val('category_owner', 0)),
                      $right, $right+1));

        return array(SUBMIT_OK, L('listitemadded'));
    }

    function action_update_category()
    {
    	global $fs, $db, $proj, $user;

    	$listname     = Post::val('list_name');
        $listshow     = Post::val('show_in_list');
        $listid       = Post::val('id');
        $listdelete   = Post::val('delete');
        $listlft      = Post::val('lft');
        $listrgt      = Post::val('rgt');
        $missing      = 0;

        for ($i = 0; $i < count($listname); $i++) {
            if ($listname[$i] != '') {
                if (!isset($listshow[$i])) {
                    $listshow[$i] = 0;
                }

                $db->Execute('UPDATE  {list_category} lc
                           LEFT JOIN  {lists} l ON l.list_id = lc.list_id
                                 SET  category_name = ?,
                                      show_in_list = ?, category_owner = ?,
                                      lft = ?, rgt = ?
                               WHERE  category_id = ? AND project_id = ?',
                          array($listname[$i], intval($listshow[$i]), Flyspray::username_to_id(Post::val('category_owner' . $i)), $listlft[$i], $listrgt[$i], $listid[$i], $proj->id));
                // Correct visibility for sub categories
                if ($listshow[$i] == 0) {
                    foreach ($listname as $key => $value) {
                        if ($listlft[$key] > $listlft[$i] && $listrgt[$key] < $listrgt[$i]) {
                            $listshow[$key] = 0;
                        }
                    }
                }
            } else {
                $missing = - SUBMIT_OK + ERROR_RECOVER;
            }
        }

        if (is_array($listdelete) && count($listdelete)) {
            $deleteids = " category_id = " . join(" OR category_id =", array_map('intval', array_keys($listdelete)));
            $db->Execute("DELETE lc FROM {list_category} lc
                       LEFT JOIN {lists} l ON lc.list_id = l.list_id
                           WHERE project_id = ? AND ($deleteids)",
                          array($proj->id));
        }

        return array(SUBMIT_OK + $missing, L('listupdated'));
    }

    function action_newuser()
    {
        global $fs, $db, $proj, $user, $page;

        if (!Post::val('user_name') || !Post::val('real_name')
            || !Post::val('email_address'))
        {
            // If the form wasn't filled out correctly, show an error
            return array(ERROR_RECOVER, L('registererror'));
        }

        if (Post::val('user_pass') != Post::val('user_pass2')) {
            return array(ERROR_RECOVER, L('nomatchpass'));
        }

        if (strlen(Post::val('user_pass')) && strlen(Post::val('user_pass')) < MIN_PW_LENGTH) {
            return array(ERROR_RECOVER, L('passwordtoosmall'));
        }

        if ($user->perms('is_admin')) {
            $group_in = Post::val('group_in');
        } else {
            $group_in = $fs->prefs['anon_group'];
        }

        $taken = $db->Execute("SELECT *
                                FROM {users}
                               WHERE jabber_id = ? AND jabber_id != ''
                                     OR email_address = ? AND email_address != ''",
                              array(Post::val('jabber_id'), Post::val('email_address')));
        if ($taken->RecordCount()) {
            return array(ERROR_RECOVER, L('emailtaken'));
        }

        if (!Backend::create_user(Post::val('user_name'), Post::val('user_pass'),
                              Post::val('real_name'), Post::val('jabber_id'),
                              Post::val('email_address'), Post::num('notify_type'),
                              Post::num('time_zone'), $group_in)) {
            return array(ERROR_RECOVER, L('usernametaken'));
        }

        if (!$user->perms('is_admin')) {
            $page->pushTpl('register.ok.tpl');
            $page->finish('footer.tpl');
        }

        return array(SUBMIT_OK, L('newusercreated'));
    }

    function action_newgroup()
    {
        global $fs, $db, $proj, $user;

        if (!Post::val('group_name')) {
            return array(ERROR_RECOVER, L('groupanddesc'));
        } else {
            // Check to see if the group name is available
            $taken = $db->Execute("SELECT  *
                                    FROM  {groups}
                                   WHERE  group_name = ? AND project_id = ?",
                                  array(Post::val('group_name'), $proj->id));

            if ($taken->RecordCount()) {
                return array(ERROR_RECOVER, L('groupnametaken'));
            }

            $cols = array_merge(array('group_name', 'group_desc', 'group_open'), $fs->perms);

            $params = array_map('Post_to0', $cols);
            array_unshift($params, $proj->id);

            $db->Execute("INSERT INTO  {groups} (project_id, ". join(',', $cols).")
                             VALUES  (". fill_placeholders($cols, 1) . ')', $params);

            return array(SUBMIT_OK, L('newgroupadded'));
        }
    }

    function action_newproject()
    {
        global $fs, $db, $proj, $user;

        if (!Post::val('project_title')) {
            return array(ERROR_RECOVER, L('emptytitle'));
        }

        $viscols =    $fs->prefs['visible_columns']
                    ? $fs->prefs['visible_columns']
                    : 'id summary progress';

        $db->Execute('INSERT INTO  {projects}
                                 ( project_title, theme_style, intro_message, notify_subject, default_task,
                                   others_view, anon_open, notify_reply, feed_img_url, feed_description,
                                   visible_columns, lang_code, notify_email, notify_jabber)
                         VALUES  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                  array(Post::val('project_title'), Post::val('theme_style'),
                        Post::val('intro_message'), '', '', Post::val('others_view', 0),
                        Post::val('anon_open', 0), '', '', '', $viscols,
                        Post::val('lang_code', 'en'), '', ''));

        $pid = $db->GetOne('SELECT project_id FROM {projects} ORDER BY project_id DESC');

        // now find an unused project prefix
        $existing = $db->GetCol('SELECT project_prefix FROM {projects}');
        $existing[] = 'FS';
        $suggestion = 'PR' . $pid;
        while (in_array($suggestion, $existing)) {
            $suggestion = 'PR' . mt_rand();
        }
        $db->Execute('UPDATE {projects} SET project_prefix = ? WHERE project_id = ?', array($suggestion, $pid));

        $args = array_fill(0, count($fs->perms), '1');
        array_unshift($args, 'Project Managers',
                      'Permission to do anything related to this project.', 1, intval($pid));

        $db->Execute("INSERT INTO  {groups}
                                 ( group_name, group_desc, group_open, project_id,
                                   ".join(',', $fs->perms).")
                         VALUES  ( ". fill_placeholders($fs->perms, 4) .")", $args);

        return array(SUBMIT_OK, L('projectcreated'), CreateURL(array('pm', 'proj' . $pid, 'prefs')));
    }

    function action_edituser()
    {
        global $fs, $db, $proj, $user, $do, $conf;

        if (Post::val('delete_user')) {
            // check that he is not the last user
            if ($db->GetOne('SELECT count(*) FROM {users}') > 1) {
                Backend::delete_user(Post::val('user_id'));
                return array(SUBMIT_OK, L('userdeleted'), CreateURL(array('admin', 'groups')));
            } else {
                return array(ERROR_RECOVER, L('lastuser'));
            }
        }

        if (!Post::val('real_name') || !Post::val('email_address')) {
            return array(ERROR_RECOVER, L('realandnotify'));
        }

        if ( (!$user->perms('is_admin') || $user->id == Post::val('user_id')) && !Post::val('oldpass')
             && (Post::val('changepass') || Post::val('confirmpass')) ) {
            return array(ERROR_RECOVER, L('nooldpass'));
        }
        if (Post::val('changepass') || Post::val('confirmpass')) {
            if (Post::val('changepass') != Post::val('confirmpass')) {
                return array(ERROR_RECOVER, L('passnomatch'));
            }
            if (Post::val('oldpass')) {
                $sql = $db->Execute('SELECT user_pass, password_salt FROM {users} WHERE user_id = ?', array(Post::val('user_id')));
                $oldpass =  $sql->FetchRow();

                $oldsalt = $oldpass['password_salt'] ? $oldpass['password_salt'] : null;

                if (Flyspray::cryptPassword(Post::val('oldpass'), $oldsalt) != $oldpass['user_pass']) {
                    return array(ERROR_RECOVER, L('oldpasswrong'));
                }
            }

            $new_salt = md5(uniqid(mt_rand(), true));
            $new_hash = Flyspray::cryptPassword(Post::val('changepass'), $new_salt);
            $db->Execute('UPDATE {users} SET user_pass = ?, password_salt = ? WHERE user_id = ?',
                            array($new_hash, $new_salt, Post::val('user_id')));

            // If the user is changing their password, better update their cookie hash
            if ($user->id == Post::val('user_id')) {
                Flyspray::setcookie('flyspray_passhash',
                        hash_hmac('md5', $new_hash, $conf['general']['cookiesalt']), time()+3600*24*30);
            }
        }

        // Check for existing email / jabber ID
        $taken = $db->GetOne("SELECT COUNT(*)
                                FROM {users}
                               WHERE (jabber_id = ? AND jabber_id != ''
                                     OR email_address = ? AND email_address != '')
                                     AND user_id != ?",
                              array(Post::val('jabber_id'), Post::val('email_address'), Post::val('user_id')));
        if ($taken) {
            return array(ERROR_RECOVER, L('emailtaken'));
        }

        $db->Execute('UPDATE  {users}
                         SET  real_name = ?, email_address = ?, notify_own = ?,
                              jabber_id = ?, notify_type = ?, show_contact = ?,
                              dateformat = ?, dateformat_extended = ?, defaultorder = ?,
                              tasks_perpage = ?, time_zone = ?, defaultsortcolumn = ?,
                              notify_blacklist = ?, lang_code = ?
                       WHERE  user_id = ?',
                array(Post::val('real_name'), Post::val('email_address'), Post::val('notify_own', 0),
                    Post::val('jabber_id', 0), Post::num('notify_type'), Post::num('show_contact'),
                    Post::val('dateformat', 0), Post::val('dateformat_extended', 0),
                    Post::val('defaultorder', 'asc'), Post::val('tasks_perpage'), Post::val('time_zone'),
                    implode(' ', Post::val('defaultsortcolumn')), implode(' ', Post::val('notify_blacklist', array())),
                    Post::val('lang_code', ''), Post::val('user_id')));
        if ($do == 'myprofile') {
            $user = new User($user->id);
        }

        if ($user->perms('is_admin')) {
            $db->Execute('UPDATE {users} SET account_enabled = ?  WHERE user_id = ?',
                    array(Post::val('account_enabled', 0), Post::val('user_id')));

            $db->Execute('UPDATE {users_in_groups} SET group_id = ?
                         WHERE group_id = ? AND user_id = ?',
                    array(Post::val('group_in'), Post::val('old_global_id'), Post::val('user_id')));
        }

        return array(SUBMIT_OK, L('userupdated'));
    }

    function action_addusertogroup()
    {
        global $fs, $db, $proj, $user;

        if (!$user->perms('manage_project')) {
            return array(ERROR_PERMS);
        }

        $users = Post::val('uid', Post::val('users'));
        if (is_array($users)) {
            $users = implode(',', array_keys($users));
        }

        $result = Backend::add_user_to_group($users, Post::num('user_to_group'), $proj->id);

        switch ($result) {
            case -1: return array(ERROR_PERMS);
            case 0:  return array(ERROR_RECOVER, L('usernotexist'));
            case 1:  return array(SUBMIT_OK, L('userremovedproject'));
            case 2:  return array(SUBMIT_OK, L('useradded'));
        }
    }

    function action_add_to_list()
    {
        global $fs, $db, $proj, $user;

        if (!Post::val('item_name')) {
            return array(ERROR_RECOVER, L('fillallfields'));
        }

        $position = Post::num('list_position');
        if (!$position) {
            $position = intval($db->GetOne('SELECT max(list_position)+1
                                              FROM {list_items}
                                             WHERE list_id = ?',
                                            array(Post::val('list_id'))));
        }

        $cols = array('item_name', 'list_id');
        if (Post::val('version_tense')) {
            $cols[] = 'version_tense';
        }

        $params = array();
        $params[] = $position;
        $params = array_merge($params, array_map('Post_to0', $cols));

        $db->Execute('INSERT INTO  {list_items}
                                   (list_position,'. implode(',', $cols) .', show_in_list)
                           VALUES  (?,'. fill_placeholders($cols) .', 1)',
                    $params);

        return array(SUBMIT_OK, L('listitemadded'));
    }

    function action_editgroup()
    {
        global $fs, $db, $proj, $user;

        if (!Post::val('group_name')) {
            return array(ERROR_RECOVER, L('groupanddesc'));
        }

        $cols = array('group_name', 'group_desc', 'group_open');

        // Add a user to a group
        Backend::add_user_to_group(Post::val('uid'), Post::val('group_id'), $proj->id);

        if (Post::val('delete_group') && Post::val('group_id') != '1') {
            $db->Execute('DELETE FROM {groups} WHERE group_id = ?', Post::val('group_id'));

            if (Post::val('move_to')) {
                $db->Execute('UPDATE {users_in_groups} SET group_id = ? WHERE group_id = ?',
                            array(Post::val('move_to'), Post::val('group_id')));
            }

            return array(SUBMIT_OK, L('groupupdated'), CreateURL( array((($proj->id) ? 'pm' : 'admin'), 'proj' . $proj->id, 'groups')));
        }
        // Allow all groups to update permissions except for global Admin
        if (Post::val('group_id') != '1') {
            $cols = array_merge($fs->perms, $cols);
        }

        $args = array_map('Post_to0', $cols);

        $args[] = Post::val('group_id');
        $args[] = $proj->id;

        $db->Execute('UPDATE  {groups}
                       SET  ' .join('=?,', $cols) . '=?
                     WHERE  group_id = ? AND project_id = ?', $args);

        return array(SUBMIT_OK, L('groupupdated'));
    }

    // **********************
    // End of action_ functions
    // **********************

	function show($area = null)
	{
		global $page, $fs, $db, $proj;

        $page->pushTpl('admin.menu.tpl');

        $this->handle('area', $area);

		$page->setTitle($fs->prefs['page_title'] . L('admintoolboxlong'));
		$page->pushTpl('admin.'.$area.'.tpl');
	}

	function _onsubmit()
	{
        global $fs, $db, $proj, $user;

        $proj = new Project(0);
        $proj->setCookie();

        list($type, $msg, $url) = $this->handle('action', Post::val('action'));
        if ($type != NO_SUBMIT) {
        	$fs = new Flyspray;
        	$user->get_perms();
        }

        return array($type, $msg, $url);
	}

	function is_accessible()
	{
		global $user;
		return $user->perms('is_admin');
	}
}

?>
