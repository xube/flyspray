<?php

  /*********************************************************\
  | Register a new user (when confirmation codes is used)   |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once BASEDIR . '/includes/external/recaptchalib.php';
require_once BASEDIR . '/includes/class.jabber2.php';

class FlysprayDoRegister extends FlysprayDo
{
    function action_registeruser()
    {
        global $user, $db, $fs;

        if (!Post::val('user_pass')) {
            return array(ERROR_RECOVER, L('formnotcomplete'));
        }

        if (Post::val('user_pass') != Post::val('user_pass2')) {
            return array(ERROR_RECOVER, L('nomatchpass'));
        }

        if (strlen(Post::val('user_pass')) < MIN_PW_LENGTH) {
            return array(ERROR_RECOVER, L('passwordtoosmall'));
        }

        // Check that the user entered the right confirmation code
        $reg_details = $db->x->getRow('SELECT * FROM {registrations} WHERE magic_url = ?',
                                        array(Post::val('magic_url')));

        if (!$reg_details) {
            return array(ERROR_RECOVER, L('confirmwrong'));
        }

        $uid = Backend::create_user($reg_details['user_name'], Post::val('user_pass'), $reg_details['real_name'], $reg_details['jabber_id'],
                                    $reg_details['email_address'], $reg_details['notify_type'], $reg_details['time_zone'], $fs->prefs['anon_group']);
        if (!$uid) {
            return array(ERROR_RECOVER, L('usernametaken'));
        }

        $db->x->execParam('DELETE FROM {registrations} WHERE magic_url = ?',
                           array(Post::val('magic_url')));

        return array(SUBMIT_OK, L('accountcreated'), CreateUrl('register', array('regdone' => 1)));
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
        $db->x->execParam('DELETE FROM {registrations} WHERE reg_time < ?', $yesterday);

        $taken = $db->x->getRow('SELECT u.user_id FROM {users} u, {registrations} r
                                  WHERE u.user_name = ? OR r.user_name = ?',
                                 null, array($user_name, $user_name));
        if ($taken) {
            return array(ERROR_RECOVER, L('usernametaken'));
        }

        $taken = $db->x->getRow("SELECT user_id
                                   FROM {users}
                                  WHERE jabber_id = ? AND jabber_id != NULL
                                        OR email_address = ? AND email_address != NULL",
                                   null, array($jabber_id, $email));
        if ($taken) {
            return array(ERROR_RECOVER, L('emailtaken'));
        }

        if($fs->prefs['use_recaptcha']) {

            $solution =& new reCAPTCHA_Solution();
            $solution->privatekey = $fs->prefs['recaptcha_private_key'];
            $solution->challenge = Post::val('recaptcha_challenge_field');
            $solution->response = Post::val('recaptcha_response_field');
            $solution->remoteip = $_SERVER['REMOTE_ADDR'];

            if(!$solution->isValid()) {
                return array(ERROR_RECOVER, $solution->error_code);
            }
        }

        $magic_url = substr(md5(uniqid(rand(), true)), 0, 20);

        //send the email first.
        if (Notifications::send(Post::val('email_address'), ADDRESS_EMAIL, NOTIFY_CONFIRMATION, array($baseurl, $magic_url, $user_name))) {

            //email sent succefully, now update the database.
            $reg_values = array('reg_time' => time(), 
                                'user_name'=> $user_name, 
                                'real_name' => $real_name, 
                                'email_address' => Post::val('email_address'), 
                                'jabber_id' => Post::val('jabber_id'), 
                                'notify_type' => Post::num('notify_type'), 
                                'magic_url' => $magic_url, 
                                'time_zone' => Post::num('time_zone'));
            // Insert everything into the database
            $query = $db->x->autoExecute('{registrations}', $reg_values);

             if (!PEAR::isError($query)) {
                    return array(SUBMIT_OK, L('codesent'), $baseurl);
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
        global $user, $baseurl;
        if (!$user->can_register() && !$user->can_self_register()) {
            Flyspray::Redirect($baseurl);
        }
        return true;
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
            if (Req::has('magic_url') && strlen(Req::val('magic_url')) == 32) {
                // If the user came here from their notification link
                $sql = $db->x->GetOne('SELECT reg_id FROM {registrations} WHERE magic_url = ?',
                                    null, Req::val('magic_url'));

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
