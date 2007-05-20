<?php

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require BASEDIR . '/lang/en.php';

/**
 * get the language string $key
 * return string
 */

function L($key)
{
    global $language;
    if (empty($key)) {
        return '';
    }
    if (isset($language[$key])) {
        return $language[$key];
    }
    return "[[$key]]";
}

/**
 * html escaped variant of the previous
 * return $string
 */

function eL($key)
{
    return Filters::noXSS(L($key));
}

function load_translations()
{
    global $proj, $language, $user;
    // Load translations
    // if no valid lang_code, return english
    // valid == a-z and "_" case insensitive
    if (!preg_match('/^[a-z_]+$/iD', $proj->prefs['lang_code'])) {
        $proj->prefs['lang_code'] = 'en';
    }

    $lang = $proj->prefs['lang_code'];
    if (!$proj->prefs['override_user_lang'] && $user->infos['lang_code']) {
        $lang = $user->infos['lang_code'];
    }
    $translation = BASEDIR . "/lang/{$lang}.php";

    if ($lang != 'en' && is_readable($translation)) {
        include($translation);
        $language = array_merge($language, $translation);
    }
}

?>
