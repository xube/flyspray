<?php
// This file is used to check that all user-inputted data is safe to pass to the
// rest of Flyspray, including the sql database.  We don't want Flyspray to end
// up on BugTraq! 

if ($_GET['getfile']) {

    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['getfile'])) {

     //continue;
      
    } else {

        print "Getfile request is invalid."; exit;
    };
};

if ($_GET['order']) {

    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^(id|type|date|sev|cat|status|prog)$/", $_GET['order'])) {

      //continue;
      
    } else {

        print "Order request is invalid."; exit;
    };
};

if ($_GET['sort']) {

    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^(asc|desc)$/", $_GET['sort'])) {

      // continue;
    } else {

        print "Sorting request is invalid."; exit;
    };

};

if ($_GET['project']) {

    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['project'])) {

      // continue; 
      
    } else {

        print "Project request is invalid."; exit;
    };
};

if ($_GET['page']) {

    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['page'])) {

        print "Request is fine: " . $_GET['page'];
        // Other actions here.
    } else {

        print "Page request is invalid."; exit;
    };
};

if ($_GET['action'] || $_POST['action']) {
    
    // Store input
    if ($_POST['action']) {
        $tmp_action = $_POST['action'];
    } else {
        $tmp_action = $_GET['action'];
    };

    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^(logout|newtask|update|close|reopen|addcomment|chpass|registeruser|newuser|newgroup|globaloptions|newproject|updateproject|addattachment|edituser|editgroup|update_list|add_to_list|update_category|add_category|add_related|remove_related|add_notification|remove_notification|editcomment|deletecomment)$/", $tmp_action)) {

       // continue;
       
    } else {

        print "Action request is invalid."; exit;
    };
};

if ($_GET['id']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['id'])) {

      // continue; 
      
    } else {

        print "ID request is invalid."; exit;
    };
};

if ($_GET['user_name']) {
    
    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^[a-zA-Z0-9_-]+$/", $_GET['user_name'])) {

      // continue; 
    } else {

        print "Username request is invalid."; exit;
    };
};

if ($_GET['real_name']) {
    
    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^[\sa-zA-Z0-9_-]+$/", $_GET['real_name'])) {

      // continue;

    } else {

        print "RealName request is invalid."; exit;
    };
};

if ($_GET['email_address']) {
    
    // Yes. Now check its' regex format for safety -- Limited range
    // Credit: http://xrl.us/9x3
    if (preg_match ("/^[A-Za-z0-9\._-]+@([A-Za-z][A-Za-z0-9-]{1,62})(\.[A-Za-z][A-Za-z0-9-]{1,62})+$/", $_GET['email_address'])) {

      // continue;
    } else {

        print "Email Address request is invalid."; exit;
    };
};

if ($_GET['notify_type']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['notify_type'])) {

      // continue;
    } else {

        print "Notify Type request is invalid."; exit;
    };
};

if ($_GET['jabber_id']) {
    
    // Yes. Now check its' regex format for safety -- Limited range
    // Credit: http://xrl.us/9x3
    if (preg_match ("/^[A-Za-z0-9\._-]+@([A-Za-z][A-Za-z0-9-]{1,62})(\.[A-Za-z][A-Za-z0-9-]{1,62})+$/", $_GET['jabber_id'])) {

       // continue;
    } else {

        print "Jabber ID request is invalid."; exit;
    };
};

if ($_GET['area']) {
    
    // Yes. Now check its' regex format for safety -- Limited range
    if (preg_match ("/^(comments|attachments|related|notify|options|projects|users|tasktype|resolution)$/", $_GET['area'])) {

       // continue;
    } else {

        print "Area request is invalid."; exit;
    };
};

if ($_GET['pagenum']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['pagenum'])) {

      // continue;
    } else {

        print "Page Number request is invalid."; exit;
    };
};

if ($_GET['perpage']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['perpage'])) {

       // continue;
    } else {

        print "Per Page request is invalid."; exit;
    };
};

if ($_GET['dev']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^(\d+|notassigned)$/", $_GET['dev'])) {

       // continue;
    } else {

        print "Developer request is invalid."; exit;
    };
};

if ($_GET['sev']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['sev'])) {

       // continue;
    } else {

        print "Severity request is invalid."; exit;
    };
};

if ($_GET['cat']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^\d+$/", $_GET['cat'])) {

       // continue;
    } else {

        print "Category request is invalid."; exit;
    };
};

if ($_GET['status']) {
    
    // Yes. Now check its' regex format for safety -- Numbers only
    if (preg_match ("/^(\d+|all)$/", $_GET['status'])) {

       // continue;
    } else {

        print "Category request is invalid."; exit;
    };
};
?>