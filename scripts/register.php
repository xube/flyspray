<?php
get_language_pack($lang, 'register');

// The application preferences allow anonymous signups
if ($flyspray_prefs['anon_open'] != "0" && !$_SESSION['userid']) {

// The first page of signup.
if (!$_GET['page']) {
?>
<form name="form1" action="index.php" method="get" id="registernewuser">

<h1><?php echo $register_text['registernewuser'];?></h1>

<p><em><?php echo $register_text['requiredfields'];?></em> <strong>*</strong></p>

  <table class="admin">
    <tr>
      <td>
        <input type="hidden" name="do" value="register">
        <input type="hidden" name="page" value="2">
        <label for="username"><?php echo $register_text['username'];?></label></td>
      <td><input id="username" name="user_name" type="text" size="20" maxlength="20"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="realname"><?php echo $register_text['realname'];?></label></td>
      <td><input id="realname" name="real_name" type="text" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="emailaddress"><?php echo $register_text['emailaddress'];?></label></td>
      <td><input id="emailaddress" name="email_address" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td><label for="jabberid"><?php echo $register_text['jabberid'];?></label></td>
      <td><input id="jabberid" name="jabber_id" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td><label><?php echo $register_text['notifications'];?></label></td>
      <td>
      <input type="radio" name="notify_type" value="1"><?php echo $register_text['email'];?> <br>
      <input type="radio" name="notify_type" value="2"><?php echo $register_text['jabber'];?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
      <?php echo $register_text['note'];?>
      </td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['sendcode'];?>" onclick="Disable1()">
      </td>
    </tr>
  </table>
</form>

<?php
} elseif ($_GET['page'] == '2') {
  if (!empty($_GET['user_name'])
      && !empty($_GET['real_name'])
      && (($_GET['email_address'] != '' && $_GET['notify_type'] == '1')
           OR ($_GET['jabber_id'] != '' && $_GET['notify_type'] == '2'))
  ) {

    // Check to see if the username is available
    $check_username = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_name = ?",
			    array($_GET['user_name']));
    if ($fs->dbCountRows($check_username)) {
      echo "<p class=\"admin\">{$register_text['usernametaken']}<br>";
      echo "<a href=\"javascript:history.back();\">{$register_text['goback']}</a></p>";
    } else {

    // Check that a confirmation code has been generated
    if (!$_SESSION['reg_ref']) {

      // Delete registration codes older than 24 hours
      $now = date(U);
      $yesterday = $now - '86400';
      $remove = $fs->dbQuery("DELETE FROM flyspray_registrations WHERE reg_time < ?",
				array($yesterday));

      // Generate a random bunch of numbers
      // This function came from ZenTrack http://zentrack.phpzen.net/
      function make_seed() {
         list($usec, $sec) = explode(' ', microtime());
         return (float) $sec + ((float) $usec * 100000);
      }
      mt_srand(make_seed());
      $randval = mt_rand();

      // Convert those numbers to a seemingly random string using crypt
      $code = crypt($randval, $cookiesalt);

      // Store the registration reference in the session
      $_SESSION['reg_ref'] = $now;
      // Insert everything into the database
      $save_code = $fs->dbQuery("INSERT INTO flyspray_registrations
			          (reg_time, confirm_code) VALUES (?,?)",
				array($now, $code));

    // End of generating a confirmation code and storing it etc
    };

    // Since we're not guaranteed of having the code passed from the above statement...
    $get_code = $fs->dbQuery("SELECT * FROM flyspray_registrations WHERE reg_time = ?",
				array($_SESSION['reg_ref']));
	$code_details = $fs->dbFetchArray($get_code);

$message = "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}\n
{$register_text['addressused']}\n
{$code_details['confirm_code']}";

      // Check how they want to receive their code
      if ($_GET['notify_type'] == '1') {

      $fs->SendEmail(
                      $_GET['email_address'],
                      $message
                      );

      } elseif ($_GET['notify_type'] == '2') {
        $fs->JabberMessage(
                             $flyspray_prefs['jabber_server'],
                             $flyspray_prefs['jabber_port'],
                             $flyspray_prefs['jabber_username'],
                             $flyspray_prefs['jabber_password'],
                             $_GET['jabber_id'],
                             "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}",
                             $message,
                             "Flyspray"
                             );
      };



?>

<h1><?php echo $register_text['registernewuser'];?></h1>
<form action="index.php" name="form2" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td colspan="2">
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="registeruser">

      <input type="hidden" name="user_name" value="<?php echo $_GET['user_name']; ?>">
      <input type="hidden" name="real_name" value="<?php echo $_GET['real_name']; ?>">
      <input type="hidden" name="email_address" value="<?php echo $_GET['email_address']; ?>">
      <input type="hidden" name="jabber_id" value="<?php echo $_GET['jabber_id']; ?>">
      <input type="hidden" name="notify_type" value="<?php echo $_GET['notify_type']; ?>">
      <?php echo $register_text['entercode']; ?>
      </td>
    </tr>
    <tr>
      <td><label for="confirmationcode"><?php echo $register_text['confirmationcode']; ?></label></td>
      <td><input id="confirmationcode" name="confirmation_code" type="text" size="20" maxlength="20"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass"><?php echo $register_text['password'];?></label></td>
      <td><input id="userpass" name="user_pass" type="password" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass2"><?php echo $register_text['confirmpass'];?></label></td>
      <td><input id="userpass2" name="user_pass2" type="password" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['registeraccount'];?>">
      </td>
    </tr>
  </table>
   
   </form>


<?php
// End of checking if the username is taken
};

// If they didn't fill the form in, show an error
} else {
?>

<p class="admin">
 <?php echo $register_text['registererror']; ?>
 <br>
 <a href="javascript:history.back();"><?php echo $register_text['goback']; ?></a>
</p>

<?php
// End of checking that the user has filled in the form correctly
};

// End of pages
};
?>

</body>
</html>

<?php
};
?>
