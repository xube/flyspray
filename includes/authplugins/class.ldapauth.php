<?php

/*
 * LDAP authentication plugin
 */
class LDAPAuth extends FlysprayAuth
{
    function LDAPAuth() {}
    
    function getOrder() {
        return 2;
    }

    /*
     * Authenticates login, when user specifies a name
     * and password.
     *
     * @param string $username
     * @param string $password
     * @return mixed a valid user ID or array(ERROR_TYPE, ERROR_MSG, REDIRECT_TO)
     */
    function checkLogin($username, $password) {
        global $fs, $db;
        if (!$fs->prefs['ldap_enabled']) {
            return false;
        }

        // Does user exist in LDAP server?
      
        $ldapconn = ldap_connect($fs->prefs['ldap_server']);
        if (!$ldapconn) {
            return array(ERROR_RECOVER, L('ldapconerror'));
        }
           
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (defined('LDAP_OPT_NETWORK_TIMEOUT')) // Available as of PHP 5.3.0
          ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
	      
        $email = null;
        $realName = null;
        
        // we have multiple methods here, every LDAP user seems to have his own
        // favourite-this-is-the-only-way method ...
        switch ($fs->prefs['ldap_bind_method'])
        {
          case 'anonymous':

            $ldapsearch = ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "{$fs->prefs['ldap_userkey']}={$username}");
            $ldapentries = ldap_get_entries($ldapconn, $ldapsearch);

            if ($ldapentries > 0) {
            // fixed bug because anonymous auth always suceeds
            // if $ldapentry['uid'][0] is null then $ldapbind = true
                foreach ($ldapentries as $ldapentry) {
                    $ldapbind = ldap_bind($ldapconn, $ldapentry['uid'][0], $password);
                    if ($ldapbind) {
                        break;
                    }
                }
            }
            break;

          case 'bind_dn':
            $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_bind_dn'], $fs->prefs['ldap_bind_pw']);
            $ldapsearch = @ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "({$fs->prefs['ldap_userkey']}={$username})");
            
            if (ldap_errno($ldapconn) != 0)
              return array(ERROR_RECOVER, L('ldaperror') . ':' . ldap_error($ldapconn));
            
            //if (is_array($ldapentries) && isset($ldapentries['count']) && $ldapentries['count'] > 0) {
            if (ldap_count_entries($ldapconn, $ldapsearch) > 0)
            {
                $ldapentries = @ldap_get_entries($ldapconn, $ldapsearch);
                for ($ldapentry = @ldap_first_entry($ldapconn, $ldapsearch);
                     $ldapentry !== false;
                     $ldapentry = @ldap_next_entry($ldapconn, $ldapsearch))
                {
                    if ($ldapentry === false)
                      break;
                    
                    $user_dn  = @ldap_get_dn($ldapconn, $ldapentry);
                    
                    // test their credentials
                    $ldapbind = @ldap_bind($ldapconn, $user_dn, $password);
                    if ($ldapbind) {
                      $attrs = @ldap_get_attributes($ldapconn, $ldapentry);
                      
                      if ($fs->prefs['ldap_realnamekey'] && isset($attrs[$fs->prefs['ldap_realnamekey']]))
                        $realName = $attrs[$fs->prefs['ldap_realnamekey']][0];
                      
                      if ($fs->prefs['ldap_emailkey'] && isset($attrs[$fs->prefs['ldap_emailkey']]))
                        $email = $attrs[$fs->prefs['ldap_emailkey']][0];
                      
                        break;
                    }
                }
                
                @ldap_free_result($ldapsearch);
            }
            else
              return array(ERROR_RECOVER, L('ldaperror') . ':Invalid Credentials.  Bad Username/Password.');
            
            if (ldap_errno($ldapconn) != 0)
              return array(ERROR_RECOVER, L('ldaperror') . ':' . ldap_error($ldapconn));
            
            break;
            
          case 'direct':
            
            $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_userkey'] . '=' . $username . ',' . $fs->prefs['ldap_basedn'], $password);
            break;
          
          default:
            return array(ERROR_RECOVER, L('ldaperror') . ':Unrecognized LDAP Bind Method');
            break;
        }

        // if all OK, add user to flyspray DB
        if ($ldapbind) {
            @ldap_close($ldapconn);
            //                          $user_ame, $password, $realname, $jabber_id, $email, $notify_type, $time_zone, $group_in
            return Backend::create_user($username, $password, $realName, '', $email, 1, 0, $fs->prefs['anon_group']);
            return true;
        }
        return array(ERROR_RECOVER, L('ldaperror') . ':' . ldap_error($ldapconn));
    }
    
    function checkCookie() {
        return false;
    }
}

?>
