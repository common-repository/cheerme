<?php
if(array_key_exists('submit_key',$_POST))
	{
		$public_key = sanitize_text_field($_POST['public_key']);
		$private_key = sanitize_text_field($_POST['private_key']);
		$enable = sanitize_text_field($_POST['enable']);			
		$cheerme_checkout_enable = sanitize_text_field($_POST['checkoutenable']);	
		$cheerme_redeeem_location = sanitize_text_field($_POST['cheermeredeeemcheckoutlocation']);
		
		update_option('cheerme_site_public_key',$public_key);
		update_option('cheerme_site_private_key',$private_key);
		update_option('cheerme_site_enable',$enable);			
		update_option('cheerme_checkout_enable',$cheerme_checkout_enable);		
		update_option('cheerme_redeeem_location',$cheerme_redeeem_location);
		
				
	?>
		<div id="setting-error-settings-updated" style="padding:10px;" class="updated_settings_error notice is-dismissible"><strong>Settings have been Saved.</strong></div>
	<?php
		}
			$cheeme_site_public_key = get_option('cheerme_site_public_key','none');
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$cheerme_site_enable = get_option('cheerme_site_enable','none');						
			$cheerme_checkout_enable = get_option('cheerme_checkout_enable','none'); 			
			$cheerme_redeeem_location = get_option('cheerme_redeeem_location','none'); 
			
			if($cheerme_site_enable == 'disable')
			{
		?> 
				<div class="error notice">
					<p><?php _e( 'Please click Enable to use the Plugin.', 'my_plugin_textdomain' ); ?></p>
				</div>
			
		<?php } ?>
			<div class="wrap cheerme-key-panel">
				<div class="cheerme-left-container">
					<img src="<?PHP echo plugins_url('../../images/logo.png', __FILE__);?>"/>
					<p class="cheerme-main-heading">Welcome To CheerMe</p>
					<p class="cheerme-head-content">CheerMe is a loyalty management platform that lets you create a fully-customized loyalty program and increase customer engagement as well as retention.</p>
					<p class="cheerme-head-content">To integrate your loyalty program into your website or application, enable the plugin.	</p>
					<p class="link-msg">Learn how to integrate <a href="https://cheerme.io/" target="_blank">CheerMe</a> in WooCommerce Store.</p></br></br>
				</div>
				<div class="cheerme-right-container">
					<h3 class="cheerme-keys-title">Insert CheerMe Keys</h3> </br>
					<form method="post" action="">
						<input type="radio" class="cheerme-radios"  name="enable" value="enable" checked="checked" <?php echo ($cheerme_site_enable =='enable')?'checked':'' ?>/>Enable
						<input type="radio" class="cheerme-radios" name="enable" value="disable" <?php echo ($cheerme_site_enable =='disable')?'checked':'' ?>/>Disable</br></br>
						
						<label class="cheerme-public-key-label"  for="header_scripts">Public Key:</label>
						<input type="text" class="cheerme-public-key-text" name="public_key" value="<?php echo $cheeme_site_public_key;?>"/></br></br>
						<label class="cheerme-private-key-label" style="" for="header_scripts">Private Key:</label>
						<input type="text" class="cheerme-private-key-text" name="private_key" value="<?php echo $cheerme_site_private_key;?>"/></br></br>
						<p><b>Note:</b> <i>Copy Private and Public keys from CheerMe Admin Panel > Settings > Developer Setting.</i></p>
						
						<h3 class="cheerme-keys-title">Show On Checkout Page</h3>						 								
						<input type="radio" class="cheerme-radios"  name="checkoutenable" value="enable" checked="checked" <?php echo ($cheerme_checkout_enable =='enable')?'checked':'' ?>/>Enable					
						<input type="radio" class="cheerme-radios" name="checkoutenable" value="disable" <?php echo ($cheerme_checkout_enable =='disable')?'checked':'' ?>/>Disable</br>											
						<h3 class="cheerme-keys-title">Redeem Rewards Widget Location</h3>	
						
						<select class="" name="cheermeredeeemcheckoutlocation"> 		
						
                        <option value="woocommerce_review_order_before_payment" <?php if($cheerme_redeeem_location=="woocommerce_review_order_before_payment") echo 'selected="selected"'; ?> > Before Payment Details </option>  

                        <option value="woocommerce_review_order_before_submit" <?php if($cheerme_redeeem_location=="woocommerce_review_order_before_submit") echo 'selected="selected"'; ?> > Before Order Place Button </option>						
						
						<option value="woocommerce_checkout_before_customer_details" <?php if($cheerme_redeeem_location=="woocommerce_checkout_before_customer_details") echo 'selected="selected"'; ?> > Before Customer Details </option>
						
						<option value="woocommerce_before_checkout_billing_form" <?php if($cheerme_redeeem_location=="woocommerce_before_checkout_billing_form") echo 'selected="selected"'; ?> > Before Customer Billing Details </option>
						
						<option value="woocommerce_after_checkout_billing_form" <?php if($cheerme_redeeem_location=="woocommerce_after_checkout_billing_form") echo 'selected="selected"'; ?> > After Customer Billing Details </option> 			
						
						<option value="woocommerce_after_order_notes" <?php if($cheerme_redeeem_location=="woocommerce_after_order_notes") echo 'selected="selected"'; ?> > After Order Notes </option> 								 	
						
						</select> </br></br> 
						
						<input type="submit" name="submit_key" class="button button-primary" value="Save Changes"/>
					</form>
					
					<!--<a href="<?php echo plugins_url(); ?>">Download</a>-->
				</div>
			</div>	