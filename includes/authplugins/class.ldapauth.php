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

        // we have multiple methods here, every LDAP user seems to have his own
        // favourite-this-is-the-only-way method ...
        if ($fs->prefs['ldap_bind_method'] == 'anonymous') {

            $ldapsearch = @ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "{$fs->prefs['ldap_userkey']}={$username}");
            $ldapentries = @ldap_get_entries($ldapconn, $ldapsearch);

            foreach ((array) $ldapentries as $ldapentry) {
                $ldapbind = @ldap_bind($ldapconn, $ldapentry['uid'][0], $password);
                if ($ldapbind) {
                    break;
                }
            }
        } elseif ($fs->prefs['ldap_bind_method'] == 'bind_dn') {

            $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_bind_dn'], $fs->prefs['ldap_bind_pw']);
            $ldapsearch = @ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "{$fs->prefs['ldap_userkey']}={$username}");
            $ldapentries = @ldap_get_entries($ldapconn, $ldapsearch);

            foreach ((array) $ldapentries as $ldapentry) {
                $ldapbind = @ldap_bind($ldapconn, $ldapentry['uid'][0], $password);
                if ($ldapbind) {
                    break;
                }
            }
        } elseif ($fs->prefs['ldap_bind_method'] == 'direct') {

            $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_userkey'] . '=' . $username . ',' . $fs->prefs['ldap_basedn'], $password);
        }

        // if all OK, ad user to flyspray DB
        if ($ldapbind) {
            @ldap_close($ldapconn);
            return Backend::create_user($username, $password, $username, '', '', 1, 0, $fs->prefs['anon_group']);
        }
        return array(ERROR_RECOVER, L('ldaperror') . ':' . ldap_error());
    }
    
    function checkCookie() {
        return false;
    }
}

?>