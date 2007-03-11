<?php

  /*********************************************************\
  | Register a new user (when confirmation codes is used)   |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoRegister extends FlysprayDo
{
    function action_registeruser()
    {
        global $user, $db, $fs;

        if (!Post::val('user_pass') || !Post::val('confirmation_code')) {
            return array(ERROR_RECOVER, L('formnotcomplete'));
        }

        if (Post::val('user_pass') != Post::val('user_pass2')) {
            return array(ERROR_RECOVER, L('nomatchpass'));
        }

        // Check that the user entered the right confirmation code
        $sql = $db->Execute('SELECT * FROM {registrations} WHERE magic_url = ?',
                             array(Post::val('magic_url')));
        $reg_details = $sql->FetchRow();

        if ($reg_details['confirm_code'] != trim(Post::val('confirmation_code'))) {
            return array(ERROR_RECOVER, L('confirmwrong'));
        }

        if (!Backend::create_user($reg_details['user_name'], Post::val('user_pass'), $reg_details['real_name'], $reg_details['jabber_id'],
                                  $reg_details['email_address'], $reg_details['notify_type'], $reg_details['time_zone'], $fs->prefs['anon_group'])) {
            return array(ERROR_RECOVER, L('usernametaken'));
        }

        return array(SUBMIT_OK, L('accountcreated'), CreateUrl('register', null, null, array('regdone' => 1)));
    }

    function action_sendcode()
    {
        global $user, $db, $fs, $conf, $baseurl;

        if (!Post::val('user_name') || !Post::val('real_name')
            || !Post::val('email_address'))
         {
            // If the form wasn't filled out correctly, show an error
            return array(ERROR_RECOVER, L('registererror'));
        }

        $email =  Post::val('email_address');
        $jabber_id = Post::val('jabber_id');

        //email is mandatory
        if (!$email || !Flyspray::check_email($email)) {
            return array(ERROR_RECOVER, L('novalidemail'));
        }
        //jabber_id is optional
        if ($jabber_id && !Jabber::check_jid($jabber_id)) {
            return array(ERROR_RECOVER, L('novalidjabber'));
        }

        $user_name = Backend::clean_username(Post::val('user_name'));

        // Limit lengths
        $real_name = substr(trim(Post::val('real_name')), 0, 100);
        // Remove doubled up spaces and control chars
        $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);

        if (!$user_name || !$real_name) {
            return array(ERROR_RECOVER, L('entervalidusername'));
        }

        // Delete registration codes older than 24 hours
        $yesterday = time() - 86400;
        $db->Execute('DELETE FROM {registrations} WHERE reg_time < ?', array($yesterday));

        $taken = $db->GetOne('SELECT COUNT(*) FROM {users} u, {registrations} r
                               WHERE u.user_name = ? OR r.user_name = ?',
                               array($user_name, $user_name));
        if ($taken) {
            return array(ERROR_RECOVER, L('usernametaken'));
        }

        $taken = $db->GetOne("SELECT COUNT(*)
                                FROM {users}
                               WHERE jabber_id = ? AND jabber_id != ''
                                     OR email_address = ? AND email_address != ''",
                                array($jabber_id, $email));
        if ($taken) {
            return array(ERROR_RECOVER, L('emailtaken'));
        }

        // Generate a random bunch of numbers for the confirmation code and the confirmation url
        foreach(array('randval','magic_url') as $genrandom) {
            $$genrandom = md5(uniqid(rand(), true));
        }

        // Convert those numbers to a seemingly random string using crypt
        $confirm_code = crypt($randval, $conf['general']['cookiesalt']);

        //send the email first.
        if (Notifications::send(Post::val('email_address'), ADDRESS_EMAIL, NOTIFY_CONFIRMATION, array($baseurl, $magic_url, $user_name, $confirm_code))) {

            //email sent succefully, now update the database.
            $reg_values = array(time(), $confirm_code, $user_name, $real_name,
                        Post::val('email_address'), Post::val('jabber_id'),
                        Post::num('notify_type'), $magic_url, Post::num('time_zone'));
            // Insert everything into the database
            $query = $db->Execute("INSERT INTO  {registrations}
                                 ( reg_time, confirm_code, user_name, real_name,
                                   email_address, jabber_id, notify_type,
                                   magic_url, time_zone )
                                  VALUES ( " . fill_placeholders($reg_values) . ' )', $reg_values);

                if ($query) {
                    return array(SUBMIT_OK, L('codesent'), './');
                }

        } else {
            return array(ERROR_INPUT, L('codenotsent'));
        }
    }

    function action_newuser()
    {
        global $user;

        if ($user->can_self_register()) {
            return FlysprayDoAdmin::action_newuser();
        } else {
            return array(ERROR_PERMS);
        }
    }

    function is_accessible()
    {
        global $user;
        return $user->can_register() || $user->can_self_register();
    }

    function _onsubmit()
    {
        return $this->handle('action', Req::val('action'));
    }

    function show()
    {
        global $page, $db, $user, $fs;

        $page->setTitle($fs->prefs['page_title'] . L('registernewuser'));

        if (Get::val('regdone')) {
            $page->pushTpl('register.ok.tpl');
        } else if ($user->can_register()) {
            // 32 is the length of the magic_url
            if (Get::has('magic_url') && strlen(Get::val('magic_url')) == 32) {
                // If the user came here from their notification link
                $sql = $db->GetOne('SELECT reg_id FROM {registrations} WHERE magic_url = ?',
                                    array(Get::val('magic_url')));

                if (!$sql) {
                    FlysprayDo::error(array(ERROR_INPUT, L('error18')));
                }

                $page->pushTpl('register.magic.tpl');
            } else {
                $page->pushTpl('register.no-magic.tpl');
            }
        } else {
            $page->pushTpl('common.newuser.tpl');
        }
    }
}

?>
