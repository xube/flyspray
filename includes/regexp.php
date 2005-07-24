<?php

/*
   This script is used to check that all user-inputted data is safe to pass to the
   rest of Flyspray, including the sql database.  We don't want Flyspray to end
   up on BugTraq!
*/

//if (($_POST['lang_code']) && (!preg_match ("/^(de|dk|en|fr|it|nl|pl|es)$/", $_POST['lang_code']))) {
//        print "Invalid language code."; exit;
//}

if (isset($_GET['getfile']) && !empty($_GET['getfile']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['getfile'])) {

     //continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
        //print "Getfile request is invalid."; exit;
    };
};

if (isset($_GET['order']) && !empty($_GET['order']))
{
    // Yes. Now check its regex format for safety -- Limited range
    // Added | to end of match list to allow for blank variable
    $regex='/^(id|proj|type|date|sev|cat|status|due|lastedit|pri|openedby|reportedin|assignedto|prog|duedate|)$/';
    if (preg_match ($regex, $_GET['order']) &&
        preg_match ($regex, $_GET['order2'])) {

      //continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
        //print "Order request is invalid."; exit;
    };
};

if (isset($_GET['sort']) && !empty($_GET['sort']))
{
    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^(asc|desc)$/", $_GET['sort'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Sorting request is invalid."; exit;
    };

};

if (isset($_GET['project']) && !empty($_GET['project'])) {

    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['project'])) {

      // continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Project request is invalid."; exit;
    };
};

if (isset($_GET['page']) && !empty($_GET['page']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['page'])) {

        //continue

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Page request is invalid."; exit;
    };
};

if (isset($_REQUEST['action']) && !empty($_REQUEST['action']))
{
    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^(logout|newtask|update|close|reopen|addcomment|chpass|registeruser|newuser|newgroup|globaloptions|newproject|updateproject|addattachment|edituser|editgroup|update_list|add_to_list|update_category|add_category|add_related|remove_related|add_notification|remove_notification|editcomment|deletecomment|deleteattachment|addreminder|deletereminder|update_version_list|add_to_version_list|addtogroup|movetogroup|requestreopen|takeownership|requestclose|newdep|removedep|sendmagic|sendcode|makeprivate|makepublic|denypmreq|massaddnotify|massremovenotify|masstakeownership)$/", $_REQUEST['action'])) {

       // continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "$tmp_action - Action request is invalid."; exit;
    };
};


if (isset($_REQUEST['do']) && !empty($_REQUEST['do'])) {

    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^(admin|pm|reports|authenticate|chpass|chproject|details|depends|index|loginbox|modify|newgroup|newproject|newtask|newuser|changelog|register|report|myprofile|lostpw|editcomment|error)$/", $_REQUEST['do'])) {

       // continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "$tmp_action - Do request is invalid."; exit;
    };
};

if (isset($_REQUEST['id']) && !empty($_REQUEST['id']))
{
     // Yes. Now check its regex format for safety -- Numbers only
    if (is_array($_REQUEST['id'])) {
        foreach($_REQUEST['id'] as $id) {
            if (!preg_match ("/^\d+$/", $id)) {
               $fs->Redirect($fs->CreateURL('error', null));
//                 die("ID request is invalid.");
            };
        };
    } else {
        if (preg_match ("/^\d+$/", $_REQUEST['id'])) {


        // continue;

        } else {

            //print "ID request is invalid."; exit;
            $fs->Redirect($fs->CreateURL('error', null));
        };
     };
 };


if (isset($_REQUEST['user_name']) && !empty($_REQUEST['user_name']))
{
    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^[a-zA-Z0-9_.-]+$/", $_REQUEST['user_name'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Username request is invalid."; exit;
    };
};

if (isset($_GET['real_name']) && !empty($_GET['real_name'])){

    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^[\sa-zA-Z0-9_-]+$/", $_GET['real_name'])) {

      // continue;

    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "RealName request is invalid."; exit;
    };
};

if (isset($_GET['email_address']) && !empty($_GET['email_address']))
{
    // Yes. Now check its regex format for safety -- Limited range
    //if (preg_match ("/^[A-Za-z0-9\._-]+@([A-Za-z][A-Za-z0-9-]{1,62})(\.[A-Za-z][A-Za-z0-9-]{1,62})+$/", $_GET['email_address'])) {

    // New regexp from FS#382 - I suck at regexps; someone tell me if it's safe.
    if (preg_match ("/^[a-z0-9._-']+(?:\+[a-z0-9._-]+)?[a-z0-9.-]+\.[a-z]{2,4}+$/i", $_GET['email_address'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Email Address request is invalid."; exit;
    };
};

if (isset($_GET['notify_type']) && !empty($_GET['notify_type']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['notify_type'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Notify Type request is invalid."; exit;
    };
};

if (isset($_GET['jabber_id']) && !empty($_GET['jabber_id']))
{
    // Yes. Now check its regex format for safety -- Limited range
    // Credit: http://xrl.us/9x3
    //if (preg_match ("/^[A-Za-z0-9\._-]+@([A-Za-z][A-Za-z0-9-]{1,62})(\.[A-Za-z][A-Za-z0-9-]{1,62})+$/", $_GET['jabber_id'])) {

   // New regexp from FS#382 - I suck at regexps; someone tell me if it's safe.
   if (preg_match ("/^[a-z0-9._-']+(?:\+[a-z0-9._-]+)?[a-z0-9.-]+\.[a-z]{2,4}+$/i", $_GET['jabber_id'])) {
       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Jabber ID request is invalid."; exit;
    };
};

if (isset($_GET['area']) && !empty($_GET['area']))
{
    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^(editcomment|comments|attachments|related|notify|users|tt|res|groups|remind|system|history|pendingreq|prefs|cat|os|ver|editgroup|newproject)$/", $_GET['area'])) {

       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Area request is invalid."; exit;
    };
};

if (isset($_GET['report']) && !empty($_GET['report']))
{
    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^(summary|changelog|events|severity|age)$/", $_GET['report'])) {
       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Report request is invalid."; exit;
    };
};

if (isset($_GET['pagenum']) && !empty($_GET['pagenum']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['pagenum'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Page Number request is invalid."; exit;
    };
};

if (isset($_GET['perpage']) && !empty($_GET['perpage']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['perpage'])) {

       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Per Page request is invalid."; exit;
    };
};

if (isset($_GET['dev']) && !empty($_GET['dev']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^(\d+|notassigned)$/", $_GET['dev']))
    {
       // continue;
    } else
    {
      $fs->Redirect($fs->CreateURL('error', null));
//         print "Developer request is invalid."; exit;
    }
}

if (isset($_GET['sev']) && !empty($_GET['sev'])) {

    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['sev'])) {

       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Severity request is invalid."; exit;
    };
};

if (isset($_GET['cat']) && !empty($_GET['cat']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['cat'])) {

       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Category request is invalid."; exit;
    };
};

if (isset($_GET['status']) && !empty($_GET['status']))
{
    // Yes. Now check its regex format for safety -- Numbers only
    if (preg_match ("/^(\d+|all|closed)$/", $_GET['status'])) {

       // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Status request is invalid."; exit;
    };
};

if (isset($_REQUEST['magic']) && !empty($_REQUEST['magic'])) {

    // Yes. Now check its regex format for safety -- Limited range
    if (preg_match ("/^[a-zA-Z0-9_-]+$/", $_REQUEST['magic'])) {

      // continue;
    } else {

      $fs->Redirect($fs->CreateURL('error', null));
//         print "Magic URL is invalid."; exit;
    };
};
?>
