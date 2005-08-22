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
				<h1>Administration setup</h1>
				<h2>Setup all the Application values</h2>
				<div class="installBlock">
				<table class="formBlock" style="width:68%;">
					<tr>
						<td width="200" align="right">Site name</td>
						<td align="center"><input class="inputbox" type="text" name="site_name" size="30" value="<?php echo $site_name; ?>" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td align="center" class="small">e.g. "XYZ Bug Tracker"</td>
					</tr>
					<tr>
						<td align="right"><?php echo $product_name; ?> URL</td>
						<td align="center">
							<input class="inputbox" type="text" name="site_url" value="<?php echo $site_url; ?>" size="30" />
						</td>
					</tr>
					<tr>
						<td align="right">System Path</td>
						<td align="center">
							<input class="inputbox" type="text" name="absolute_path" value="<?php echo $absolute_path; ?>" size="30" />
						</td>
					</tr>
					<?php echo $admin_email; ?>
					<?php echo $admin_username; ?>
					<?php echo $admin_password; ?>
				</table>
				<p>
				The Database has been populated. Please follow the instructions to complete the configuration.
				</p>
				<p>
				Type in the name for your <?php echo $product_name; ?> site. This name is used in email messages
				so make it something meaningful.
				</p>
				<p>
				If URL and Path looks correct then please do not change.
				If you are not sure then please contact your ISP or administrator. Usually
				the values displayed here will work for your site.
				</p>
				<input type="hidden" name="db_type" value="<?php echo $db_type; ?>" />
				<input type="hidden" name="db_hostname" value="<?php echo $db_hostname; ?>" />
				<input type="hidden" name="db_username" value="<?php echo $db_username; ?>" />
				<input type="hidden" name="db_password" value="<?php echo $db_password; ?>" />
				<input type="hidden" name="db_name" value="<?php echo $db_name; ?>" />
				<input type="hidden" name="db_prefix" value="<?php echo $db_prefix; ?>" />
				<input type="hidden" name="db_setup_options" value="<?php echo $db_setup_options; ?>" />
				</div>
				<div class="clr"></div>
				<h2>Proceed to final Setup</h2>
				<div class="installBlock">
				<div class="formBlock farRight" style="display:inline;">
					<input type="hidden" name="action" value="complete" />
					<input class="button" type="submit" name="next" value="Next >>" />
				</div>
				<p>
				Proceed to complete <?php echo $product_name; ?> setup.
				</p>
				</div>
			</form>
			</div><!-- end of right -->
			<div class="clr"></div>