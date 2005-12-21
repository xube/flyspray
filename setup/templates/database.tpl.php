<?php
// +----------------------------------------------------------------------
// | PHP Source
// +----------------------------------------------------------------------
// | Copyright (C) 2005 by Jeffery Fernandez <developer@jefferyfernandez.id.au>
// +----------------------------------------------------------------------
// |
// | Copyright: See COPYING file that comes with this distribution
// +----------------------------------------------------------------------
//
if (!defined('VALID_FLYSPRAY')) die('Sorry you cannot access this file directly');
?>
      <div id="right">
         <form action="index.php" method="post" name="database_form">
         <?php echo $message; ?>
         <h1>Database Setup</h1>
         <h2>Database configuration</h2>
         <div class="installBlock">
            <table class="formBlock">
            <tr>
               <td><strong>Install/Upgrade<strong></td>
               <td align="left">
                  <?php echo $db_setup_options; ?>
               </td>
            </tr>
            <tr>
               <td>Host Name</td>
               <td align="left"><input class="inputbox" type="text" name="db_hostname" value="<?php echo $db_hostname; ?>" /></td>
            </tr>
            <tr>
               <td>Database Type</td>
               <td>
               <select name="db_type">
                  <?php
                  $selected = '';
                  foreach ($databases as $which => $database)
                  {
                  $selected = ($db_type == $which && $selected == '') ? 'selected' : '';

                  if ($database[0])
                  {
                     echo "<option  class=\"inputbox\"  value=\"$which\" $selected>$which</option>";
                  }
                  }
                  ?>
               </select>
               </td>
            </tr>
            <tr>
               <td>Database user name</td>
               <td align="left"><input class="inputbox" type="text" name="db_username" value="<?php echo $db_username; ?>" /></td>
            </tr>
            <tr>
               <td>Database password</td>
               <td align="left"><input class="inputbox" type="password" name="db_password" value="<?php echo $db_password; ?>" /></td>
            </tr>
            <tr>
               <td>Database name</td>
               <td align="left"><input class="inputbox" type="text" name="db_name" value="<?php echo $db_name; ?>" /></td>
            </tr>
            <tr>
               <td>Table prefix</td>
               <td align="left"><input class="inputbox" type="text" name="db_prefix" value="<?php echo $db_prefix; ?>" /></td>
            </tr>
            <tr>
               <td>Drop existing tables?</td>
               <td align="left"><input type="checkbox" name="db_delete" value="delete" <?php echo ($db_delete==1) ? 'checked="checked"' : ''; ?> /></td>
            </tr>
            <tr>
               <td>Backup tables?</td>
               <td align="left"><input type="checkbox" name="db_backup" value="backup" <?php echo (($db_backup==1) ? 'checked="checked"' : ''); ?> /></td>
            </tr>
            </table>
            <p>Follow the steps described below to setup <?php echo $product_name; ?>'s Database schema.</p>
            <p>
            1) Select if this is to be a clean <strong>install</strong> or an <strong>upgrade</strong> from
            <?php echo $product_name; ?> version 0.9.7.
            </p>
            <p>
            2) Enter the <strong>database hostname</strong> of the server <?php echo $product_name; ?> is to be installed on,
            this is usually 'localhost'.
            </p>
            <p>
            3) Enter the <strong>database username and password</strong>. <?php echo $product_name; ?> requires that you have a
            database setup with a username and password to install the database schema. If you are not sure about
            these details, please consult with your administrator or web hosting provider.
            </p>
            <p>
            4) Enter the <strong>database table prefix</strong>. If this is the first time you are setting up
            <?php echo $product_name; ?>, you can choose the prefix you want the <?php echo $product_name; ?>
            tables to have.
            <?php echo $product_name; ?> version 0.9.7 had a table prefix of <em>flyspray_</em>, so do not change it if
            you are upgrading.
            </p>
            <p>
            5) <strong>Backing up before Upgrading</strong>. If you are upgrading from <?php echo $product_name; ?> version 0.9.7,
            it is strongly advised to make a backup of the databases/tables which may be affected with the username &amp; password you
            provide for this setup. <?php echo $product_name; ?> authors will not be held responsibile for any loss of data you
            experience if things go wrong. However we try to do our best to avoid such circumstances.
            </p>
            <p style="font-weight:bold;color:orange;">
            For security measure, you will not be able to <i style="color:#47617B;">drop existing tables</i>
            through this interface unless you select the <i style="color:#47617B;">backup tables</i> checkbox.
            Backed up tables will be stored in the same database with a prefixed timestamp.
            </p>
         </div>
         <div class="clr"></div>
         <h2>Proceed to Administration setup</h2>
         <div class="installBlock">
            <div class="formBlock farRight">
            <input type="hidden" name="action" value="administration" />
            <input class="button" type="submit" name="next" value="Next >>" />
            </div>
            <p>
            Proceed to configure the the Admin parameters.
            </p>
         </div>
         </form>
      </div><!-- end of right -->
      <div class="clr"></div>