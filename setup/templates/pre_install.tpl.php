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
			<?php echo $message; ?>
			<h1>Pre-installation check</h1>
			<h2>PHP and supported libraries</h2>
			<div class="installBlock">
				<table class="formBlock">
				<tr>
					<td class="heading">Library</td>
					<td class="heading">Status</td>
					<td class="heading">&nbsp;</td>
				</tr>
				<tr>
					<td>PHP >= <?php echo $required_php; ?></td>
					<td align="left"><b><?php echo $php_output; ?></b></td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td valign="top"> - ADOdb Library</td>
					<td align="left"><b><?php echo $adodb_output; ?></b></td>
					<td align="center">
					<?php
					if ($adodb_status)
					{
						echo '&nbsp;';
					}
					else
					{
					?>
					<form name="adodb" method="post" action="index.php" style="display:inline">
						<input type="hidden" name="action" value="index" />
						<input type="hidden" name="what" value="adodb" />
						<input type="submit" name="install_adodb" value="Install" class="button orange" title="Install ADOdb library" />
					</form>
					<?php
					}
					?>
					</td>
				</tr>
				<tr>
					<td class="heading">Database</td>
					<td class="heading">in PHP</td>
					<td class="heading"><?php echo $product_name; ?></td>
				</tr>
				<?php echo $database_output; ?>
				</table>
				<p>
				If any of these items are highlighted
				in red then please take actions to correct them. Failure to do so
				could lead to your <?php echo $product_name; ?> installation not functioning
				correctly. You will need at least one type of database support with ADOdb libraries.
				</p>
				<?php if (!$adodb_status){ ?>
				<p>
				The installer was unable to find the ADOdb library in the <strong>"PHP include_path"</strong>
				or <?php echo $product_name; ?> Application path. Use the install button to install
				the ADOdb library. This process will download the library and install it in your
				<?php echo $product_name; ?> base folder. It will take about 3-4 minutes depending on
				your server network speed.
				</p>
				<?php } ?>
			</div>
			<div class="clr"></div>
	
			<h2>Recommended settings:</h2>
			<div class="installBlock">
				<table class="formBlock">
				<tr>
					<td class="heading">Directive</td>
					<td class="heading">Recommended</td>
					<td class="heading">Actual</td>
				</tr>
				<?php echo $php_settings; ?>
				</table>
				<p>
				These settings are recommended for PHP in order to ensure full
				compatibility with <?php echo $product_name; ?>.
				</p>
				<p>
				However, <?php echo $product_name; ?> will still operate if your
				settings do not quite match the recommended shown here.
				</p>
			</div>
			<div class="clr"></div>
	
			<h2>Directory and File Permissions:</h2>
			<div class="installBlock">
				<table class="formBlock">
				<tr>
					<td valign="top">../flyspray.conf.php</td>
					<td align="left"><b><?php echo $config_output; ?></b></td>
					<td>&nbsp;</td>
				</tr>
				<?php if ($htaccess_required) { ?>
				<tr>
					<td valign="top">../.htaccess</td>
					<td align="left"><b><?php echo $htaccess_status; ?></b></td>
					<td>&nbsp;</td>
				</tr>
				<?php } ?>
				</table>
				<p>
				In order for <?php echo $product_name; ?> to function
				correctly it needs to be able to access or write to certain files
				or directories. If you see "Unwriteable" you need to change the
				permissions on the file or directory to allow <?php echo $product_name; ?>
				to write to it.
				</p>
				<?php if (!$config_status){ ?>
				<p>
				The installer has detected that the <strong>flyspray.conf.php</strong> file is not
				writeable. Please make it writeable by the web-server user or world writeable to
				proceed with the setup. Alternatively if you wish to proceed, the installer will
				make available the contents of the configuration file at the end of the setup. You
				will then have to manually copy and paste the contents into the configuration file
				located at <strong><?php echo APPLICATION_PATH . '/flyspray.conf.php'; ?></strong>.
				</p>
				<?php } ?>
			</div>
			<div class="clr"></div>
	
			<h2>Proceed to Licence Agreement:</h2>
			<div class="installBlock">
				<form class="formBlock farRight" action="index.php" method="post" name="adminForm" style="display:inline;">
				<input type="hidden" name="action" value="licence" />
				<input name="next" type="submit" class="button" value="Next >>" <?php echo ($status) ? '' : 'disabled'; ?> />
				</form>
				<?php if (!$status) { ?>
				<p>
				You seem to have problems with the Pre-install configuration. Once you have fixed the
				problem, please refresh the page to be able to proceed to the next stage of
				<?php echo $product_name; ?> setup.
				</p>
				<?php }else { ?>
				<p>
				All configurations seems to be in place. You may proceed to the Licence Agreement page.
				</p>
				<?php } ?>
			</div>
			<div class="clr"></div>
			</div><!-- end of right -->
			<div class="clr"></div>