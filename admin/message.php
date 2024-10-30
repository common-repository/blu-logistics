<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'contact_merchant'; 
?>

<?php 
if($active_tab == 'contact_merchant'){
		$blu_auth_result = get_option('blu_auth_result'); 
		if($blu_auth_result == "success"){ ?>
				<div class="notice is-dismissible">
					<p>Welcome back!</p>
				</div>
		<?php } else { ?>
				<div class="error settings-error notice is-dismissible alert-warning"> 
					<p><strong>Welcome to blu’s shipping module! We’re so excited to have you onboard. To unlock the potential of this module, key in your Merchant ID and Authentication Key in the Authentication tab. If you don't have your Authentication details, contact us via the web form below and we'll get in touch with you ASAP! :)</strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
				</div>
<?php } } ?>



<?php if($active_tab == 'contact_merchant'){
$admin_path = plugin_dir_path(__FILE__);
$plugin_path = str_replace('/admin', '', $admin_path);

if( file_exists($admin_path . 'settings.php')  && file_exists($plugin_path . 'blu-shipping.php')) { ?>

      <div class="notice">
          <p class="plugin-install">Important - Module Installation Information</p>
          <div style="padding-left:10px;display:inline">
          <table>
      
          <tbody>
            <tr>
	            <td align="left">
	            <b>Your plugin has been installed correctly and the following files have been configured.</b><br>
	            <b style="color:green">settings.php <br>blu-shipping.php</b>
	            <br>
	            </td>
          	</tr>
          </tbody></table>
            </div>
        </div>

<?php } else { ?>

      <div class="notice" id="error_installed"> 
	      <p><strong>Oops, looks like something went wrong during the installation of the plugin.</strong>
	      </p>
      </div>

<?php } } ?>