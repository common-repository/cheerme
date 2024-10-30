<?php
/*
Plugin Name: Cheerme
Plugin URI: https://wordpress.org/plugins/Cheerme/
Description: To embed cheerMe loyalty program into your website.
Version: 1.1.8
Author: CheerMe
Author URI: https://cheerme.io/
*/

if ( ! defined( 'ABSPATH' ) ) exit; 
define('CHEERME_PLUGIN_DIR',str_replace('\\','/',dirname(__FILE__)));
class WC_CheerMe_Init
{
	
	public function __construct()
		{
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$this->register_callbacks();
				add_action( 'admin_menu', array( $this, 'add_submenu_item' ) );
				//add_action( 'wp_body_open', array( $this, 'insert_html_in_body' ) );
				add_action( 'wp_footer', array( $this, 'cheerme_add_footer_script') );
				add_action( 'admin_enqueue_scripts', array( $this, 'cheerme_page_style' ) );
				add_action( 'user_register', array( $this , 'cheerme_registration' ), 10, 1 );
				add_action( 'profile_update', array( $this , 'cheerme_user_update' ), 10, 1 ); 
				add_action( 'woocommerce_thankyou', array( $this , 'cheerme_add_order' ), 10, 1 );
				add_action( 'woocommerce_process_shop_order_meta', array( $this , 'cheerme_process_order' ), 10, 1 );
				add_action( 'delete_user', array( $this , 'cheerme_remove_user' ), 10, 1 );
				add_action( 'delete_post', array( $this , 'cheerme_delete_order' ), 10, 1);
				add_action( 'wp_trash_post', array( $this , 'cheerme_delete_order' ), 10, 1);
				add_action( 'untrash_post', array( $this , 'cheerme_untrash_order' ), 10, 1);
				add_action( 'woocommerce_order_refunded', array( $this , 'cheerme_order_refunded' ), 10, 2 );											
				
				
				$cheerme_checkout_enable = get_option('cheerme_checkout_enable','none'); 			
			    $cheerme_redeeem_location = get_option('cheerme_redeeem_location','none'); 
				
				
				
				if ($cheerme_checkout_enable == 'enable'){
				
				    // $cheerMeBrandingStatus = $this->getCheerMeBrandingStatus();
					
					// if($cheerMeBrandingStatus == true){
					
				        add_action($cheerme_redeeem_location,  array( $this , 'cheerMeShowCouponOnCheckout' ), 10 ); 
						 
					
					// }
				}
				
				//add_action('woocommerce_review_order_after_submit',  array( $this , 'showCheerMeEarnPoints' ), 10,1);
 				   
			}
			else   
			{
				add_action( 'admin_notices', array( $this, 'my_error_notice' ) );
			}
		}
	
	
	protected function register_callbacks()
	{
		add_action('cheerme_trigger_action' , array($this ,'cheerme_trigger_hook'));
	}		
	
	
	protected function getCheerMeBrandingStatus(){
	
	    $cheerMeBrandingStatus = false;
	
	    $current_user_id=get_current_user_id();
		$cheerMePublicKey = get_option('cheerme_site_public_key','none');
		
		$getCheerMeWidgetInfoArgs = array( 'method'  => 'GET',
						'timeout'     => 300,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array('PublicKey'=>$cheerMePublicKey)
						);
						
		$getCheerMeWidgetInfoUrl = "https://api.cheerme.io/api/Public/Widget/Get";
		
		$cheerMeWidgetInfoApiContent = wp_remote_get( $getCheerMeWidgetInfoUrl, $getCheerMeWidgetInfoArgs );
		
		$getCheerMeWidgetInfoResponse = wp_remote_retrieve_body( $cheerMeWidgetInfoApiContent );
		
		$getCheerMeWidgetInfoResponse = json_decode($getCheerMeWidgetInfoResponse);
		
		$getCheerMeWidgetInfoSuccess = $getCheerMeWidgetInfoResponse->Success;

		
		if($getCheerMeWidgetInfoSuccess == true){ 
		    
            $cheerMeWidgetInfoData = $getCheerMeWidgetInfoResponse->Data;     
            
			$cheerMeWidgetInfoWidgetLayout = $cheerMeWidgetInfoData->WidgetLayouts;	

            $cheerMeBrandingStatus = $cheerMeWidgetInfoWidgetLayout->Branding; 
			
		}	
		
		return $cheerMeBrandingStatus;
		
	}
	
	public function showCheerMeEarnPoints(){
	    
		$current_user_id=get_current_user_id();
		$cheerMePublicKey = get_option('cheerme_site_public_key','none');
		
		$cheerMeOrderTotal = WC()->cart->total;
		 
		$cheerMeEarnPoints = "";
		
		$getCheerMeEarnPointsArgs = array( 'method'  => 'GET',
						'timeout'     => 300,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array('PublicKey'=>$cheerMePublicKey,'customerId'=>$current_user_id)
						);
						
		$getCheerMeEarnPointsUrl = "https://api.cheerme.io/api/public/order/GetPoints?orderTotal=" . $cheerMeOrderTotal;
		
		$cheerMeEarnPointsApiContent = wp_remote_get( $getCheerMeEarnPointsUrl, $getCheerMeEarnPointsArgs );
		
		$cheerMeEarnPointsApiResponse = wp_remote_retrieve_body( $cheerMeEarnPointsApiContent );
		
		$cheerMeEarnPointsApiResponse = json_decode($cheerMeEarnPointsApiResponse);
		
		$getCheerMeEarnPointsSuccess = $cheerMeEarnPointsApiResponse->Success;

		
		if($getCheerMeEarnPointsSuccess == true){ 
		    
            $cheerMeEarnPoints = $cheerMeEarnPointsApiResponse->Message; 
			
		}	
		?>
		<div class="cheerMe-earnPoints"> <?php echo $cheerMeEarnPoints; ?>  </div>   
        <style> .cheerMe-earnPoints{color: #28c128;font-weight: bold;} </style>
    <?php	
	
	}
	
	
	public function cheerMeShowCouponOnCheckout(){	 	
        		 
		$current_user_id=get_current_user_id();
		$cheerMePublicKey = get_option('cheerme_site_public_key','none');
		
		$getCheermeRedeemableRewardsArgs = array( 'method'  => 'GET',
						'timeout'     => 300,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array('PublicKey'=>$cheerMePublicKey,'customerId'=>$current_user_id)
						);
						
		$getCheermeRedeemableRewardsUrl = "https://api.cheerme.io/api/public/Reward/GetRedeemableRewards";
		
		$cheermeRedeemableRewardsApiContent = wp_remote_get( $getCheermeRedeemableRewardsUrl, $getCheermeRedeemableRewardsArgs );
		
		$getCheermeRedeemableRewardsListResponse = wp_remote_retrieve_body( $cheermeRedeemableRewardsApiContent );
		
		$getCheermeRedeemableRewardsListResponse = json_decode($getCheermeRedeemableRewardsListResponse);
		
		$getRewardListSuccess = $getCheermeRedeemableRewardsListResponse->Success;
		
		$cheerMeBrandingStatus = $this->getCheerMeBrandingStatus();
		
		?>
			<div class="cheerme-coupon-wrapper"> 	
		<?php
		
		$this->showCheerMeEarnPoints();
		
		if($getRewardListSuccess == true){ 
		    
            $cheermeRedeemableRewardsData = $getCheermeRedeemableRewardsListResponse->Data;     
            
			$cheermeRedeemableRewardsList = $cheermeRedeemableRewardsData->Rewards;	

            $cheermeCustomerTotalRewardPoints = $cheermeRedeemableRewardsData->CustomerPoints;		
            $cheermeCustomerPointCurrency = $cheermeRedeemableRewardsData->PointCurrency;

			
			
			if(count($cheermeRedeemableRewardsList) > 0){
			?>
			       
                 				
			    <div class="cheerme-redeems-wrapper"> 
				     <input type="hidden" value="<?php echo $cheermeCustomerPointCurrency; ?>" class="cheerme-currency-type" />
					<div class="open-cheerme-reward-list" > Redeem Your <?php echo $cheermeCustomerPointCurrency;  ?> ( You have <?php echo $cheermeCustomerTotalRewardPoints ; ?> <?php echo $cheermeCustomerPointCurrency; ?> )  </div>
					<div class="cheerme-reward-list-content">
					<select class="cheerme-reward-list">
					    <option value=""> Select <?php echo $cheermeCustomerPointCurrency; ?> </option>
					    					
            <?php			  
                foreach($cheermeRedeemableRewardsList as $cheermeRedeemableReward){
		            $RewardName =  $cheermeRedeemableReward->RewardName;
					$RewardImageURL =  $cheermeRedeemableReward->ImageURL;
					$RewardMessage =  $cheermeRedeemableReward->Message;
					$RewardId =  $cheermeRedeemableReward->Id;
					
					?>
					   
					    <option value="<?php echo $RewardId; ?>">
					        <?php echo $RewardName; ?> - <?php echo $RewardMessage; ?>   
                        </option> 								
							   					
					<?php
					
				}	
            ?>				
			    
					</select>
					<button type="button" class="cheerMeRedeemBtn" > Redeem  <img width="15px" class="cheerme-loader-img" src="<?PHP echo plugins_url('/images/loading.gif', __FILE__);?>"/> </button>
					<div class="alert-danger cheerme-msg"> </div> 
					<?php if($cheerMeBrandingStatus == true){ ?>
						<div class="powered-by-cheerme"> Powered by <a href="https://cheerme.io/" target="blank" > cheerme.io </a> </div>
					<?php } ?>
					</div>  
                </div>                				
								
				
			<?php
				add_action( 'wp_footer', array( $this, 'addCheerMeReedemScript') );
			}
			
			
		}									
	    
		?>
			</div>
			<?php
			
	}		   	
	
	public function addCheerMeReedemScript()		
	{ ?>			 
		<script type="text/javascript">			
		
		   jQuery(document).ready(function(){	
		   
		        jQuery(".cheerMeRedeemBtn").prop("disabled", true);  
             	   
			    jQuery(".cheerme-reward-list").change(function(){		
			        var selectedRewards = jQuery(this).children("option:selected").val();	
                    if(selectedRewards != ""){
                        jQuery(".cheerMeRedeemBtn").prop("disabled", false); 
                    }				
				    else{
				        jQuery(".cheerMeRedeemBtn").prop("disabled", true); 
				    }    						 	
			    });	  
	 			
			 
			var cheerMeAdminAjaxUrl = '<?php echo admin_url('admin-ajax.php');  ?>'; 
			
            jQuery("div").delegate(".cheerMeRedeemBtn", "click", function(ev){			
			
				ev.preventDefault();
			    var cheerMeRewardId = jQuery(".cheerme-reward-list").children("option:selected").val(); 
				var cheerMeCurrencyType = jQuery(".cheerme-redeems-wrapper .cheerme-currency-type").val();
				
				var customerBalPoints = "";
				
				if(cheerMeRewardId == ""){
				    var cheerMeErrorMsg = '<div class="error">Please select redeem first!</div>'; 	
					jQuery(".cheerme-msg").html('');
					jQuery(".cheerme-msg").html(cheerMeErrorMsg);
					jQuery(".cheerme-msg").show();
					hideCheerMeMsg();
					return false; 
				}
			    
             			 
				// wc_checkout_params is required to continue, ensure the object exists
				if ( typeof wc_checkout_params === 'undefined' ) {
				
				    var cheerMeErrorMsg = '<div class="error">Something went wrong, Please try again!</div>'; 	
					jQuery(".cheerme-msg").html('');
					jQuery(".cheerme-msg").html(cheerMeErrorMsg);
					jQuery(".cheerme-msg").show();
                    hideCheerMeMsg();					
					return false;
										
				}
				
				jQuery(".cheerMeRedeemBtn").prop("disabled", true); 
				jQuery(".cheerMeRedeemBtn .cheerme-loader-img").toggleClass("show");
				
				jQuery.ajax({
					type: "POST",
					url:cheerMeAdminAjaxUrl,
					data:{ 
					action: 'applycheerMeCouponInCart', 
					cheerMeRewardId: cheerMeRewardId,
				},
			   success: function(data)
			   { 
			       
			       var data = JSON.parse(data);
				   var IsSuccess = data.IsSuccess;
				   var message = data.Message;
				   var coupon = data.Coupon
				   
				    if(IsSuccess == true && coupon == ""){
				         
                        var cheerMeErrorMsg = '<div class="error"> You have already redeemed ' + cheerMeCurrencyType + ' and coupon already applied to your order.  </div>'; 						
						jQuery(".cheerme-msg").html('');						
				        jQuery(".cheerme-msg").html(cheerMeErrorMsg);  
						
						jQuery(".cheerMeRedeemBtn").prop("disabled", false); 
				        jQuery(".cheerMeRedeemBtn .cheerme-loader-img").toggleClass("show");
						jQuery(".cheerme-msg").show();
						hideCheerMeMsg();	
                        return true; 						
                      						
				    }
				   
				   if(IsSuccess == true && coupon != ""){	
	              
	                     	
						customerBalPoints = message;
						
						var cheerMeWoodata = {
							security:		wc_checkout_params.apply_coupon_nonce,
							coupon_code: coupon 
						};
	
						jQuery.ajax({
								type:		'POST',
								url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' ),
								data:		cheerMeWoodata,
								success:	function( applyCouponResult ) {
									
									if ( applyCouponResult ) { 
										
										
										var th = message;
										
										if(applyCouponResult.indexOf("applied successfully") !== -1){
										
											jQuery( document.body ).trigger( 'applied_coupon_in_checkout', [ cheerMeWoodata.coupon_code ] );
											jQuery( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
											
											var cheerMeSuccessMsg = '<div class="success">You have redeemed ' + cheerMeCurrencyType + ' and coupon is applied to your order.</div>'; 
											jQuery(".cheerme-msg").html('');
											jQuery(".cheerme-msg").html(cheerMeSuccessMsg);  
											jQuery(".open-cheerme-reward-list").text(customerBalPoints);
											
										}
										
										if(applyCouponResult.indexOf("already applied") !== -1){
										
											var cheerMeSuccessMsg = '<div class="error">You have already redeemed ' + cheerMeCurrencyType + ' and coupon already applied to your order.</div>'; 
											jQuery(".cheerme-msg").html('');
											jQuery(".cheerme-msg").html(cheerMeSuccessMsg);
											
										} 
										
										if(applyCouponResult.indexOf("does not exist") !== -1){
										
											var cheerMeSuccessMsg = '<div class="error">Something went wrong, Please try again!</div>'; 
											jQuery(".cheerme-msg").html('');
											jQuery(".cheerme-msg").html(cheerMeSuccessMsg);
											
										} 
										
										jQuery(".cheerMeRedeemBtn").prop("disabled", false); 
				                        jQuery(".cheerMeRedeemBtn .cheerme-loader-img").toggleClass("show");
										jQuery(".cheerme-msg").show();
										hideCheerMeMsg();

										
									}else{
									      
										var cheerMeSuccessMsg = '<div class="error">Something went wrong, Please try again!</div>'; 
										jQuery(".cheerme-msg").html('');
										jQuery(".cheerme-msg").html(cheerMeSuccessMsg); 
										
										jQuery(".cheerMeRedeemBtn").prop("disabled", false); 
				                        jQuery(".cheerMeRedeemBtn .cheerme-loader-img").toggleClass("show");
										jQuery(".cheerme-msg").show();
										hideCheerMeMsg();
									}
								},
								dataType: 'html'
							}); 
                         						
				   } 
			       else{		   
				        var cheerMeErrorMsg = '<div class="error">' + message + '</div>'; 						
						jQuery(".cheerme-msg").html('');						
				        jQuery(".cheerme-msg").html(cheerMeErrorMsg);  
						
						jQuery(".cheerMeRedeemBtn").prop("disabled", false); 
				        jQuery(".cheerMeRedeemBtn .cheerme-loader-img").toggleClass("show");
						jQuery(".cheerme-msg").show();
						hideCheerMeMsg();
				    }
					
			   }
			 }); 

			});
			 
			function hideCheerMeMsg(){
			    setTimeout(function(){ jQuery(".cheerme-msg").hide(); }, 10000);
			}
              			
			 });		 
	    </script>		
			 
		<style> 	          		
		    .cheerme-coupon-wrapper input{padding:10px !important;}	
		    .cheerme-coupon-wrapper select{padding:9px !important; width: 250px !important;}	
            .cheerme-coupon-wrapper .cheerme-loader-img{ width:15px; display:none;}	
			
			#place_order{margin-bottom: 5px;}
            .show{ display: inline-block !important; }	
            .float-right{ float:right; }
			.float-left{ float:left; }
 			.clear{clear:both}
			.success{color:green;font-size: 12px;}
			.error{color:red;font-size: 12px;} 
			
			.cheerme-coupon-wrapper{
			    margin-bottom:10px; 
			} 
			.cheerme-coupon-wrapper .open-cheerme-reward-list{
			    font-size: 17px;  
			}
			
			.powered-by-cheerme{
				font-size: 10px;
				font-style: italic;
			}
		</style>				
	
	<?php		
	
	}
	
   

	
	public function cheerme_trigger_hook($action_id)
	{
		$current_user_id=get_current_user_id();
		$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
		$api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Action/TriggerAction?guid='.$action_id.'&customerId='.$current_user_id, array(
				 'headers' => array(
						 'PrivateKey' => $cheerme_site_private_key
				)
				
			 ) );
			
			 $body = json_decode( $api_response['body'] );
			 
			  if($body->Success == '1')
			{
				return true;
			}
			else
			{
				return false;
			} 
		
	}
	
	/* function to add menu in woocommerce */
	
	public function add_submenu_item()
		{
			add_submenu_page( 'woocommerce', 'CheerMe.io', 'CheerMe.io', 'manage_options', 'cheerme-settings', array($this , 'cheerme_page_layout') );
		}
		
	public function cheerme_page_layout()
		{
			require_once(CHEERME_PLUGIN_DIR . '/include/views/html_keys_form.php');
		}
	
	
	/* function to add div in body */
		
	public function insert_html_in_body()
		{
		?>
			<div id='cheerme-launcher'></div>
		<?php
		
		}
		
		
	
	/* function to add script in footer */
	
	public function cheerme_add_footer_script()
		{
			$current_user_id=get_current_user_id();
			$cheerme_site_public_key = get_option('cheerme_site_public_key','none');
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$cheerme_site_enable = get_option('cheerme_site_enable','none');
			if($cheerme_site_enable == 'enable')
			{
			?>
				<div id='cheerme-launcher'></div>
				<script>  window.cheerMeConfig = {
					publicKey: <?php echo '\''.$cheerme_site_public_key.'\''?>,
					customerId: <?php echo '\''.$current_user_id.'\''?>,
					customerToken: 'I am customer token',
					}
				</script>
				<script async src='https://cdnfrontend.s3.ap-south-1.amazonaws.com/CDN/cheerme-prod/cheerme.min.js' charset='utf-8'></script>
				<?php
			}
			
		}
		
	/* function to admin scripts & styles in admin */
		
	public function cheerme_page_style()
		{
			wp_register_style('cheerme_custom_style' , plugins_url( '/css/custom.css' , __FILE__ ) );
			wp_enqueue_style('cheerme_custom_style');	
		}


	/* function to perform after user registration */
		
	public function cheerme_registration( $user_id )	{	
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$users_data=get_userdata( $user_id );
			$email=$users_data->user_email;
			$created_date=$users_data->user_registered;
			$all_meta_for_user = get_user_meta( $user_id );
			$last_name = $all_meta_for_user['last_name'][0];
			$first_name = $all_meta_for_user['first_name'][0];
			if(isset($_POST['first_name']))		
			{				
				$first_name=sanitize_text_field($_POST['first_name']);				
				$last_name=sanitize_text_field($_POST['last_name']);			
			}
			elseif(isset($_POST['billing_first_name']))	
			{				
				$first_name=sanitize_text_field($_POST['billing_first_name']);				
				$last_name=sanitize_text_field($_POST['billing_last_name']);			
			}			
			
			if($first_name == '')
			{
				$first_name='NA';
			}
			if($last_name == '')			
			{
				$last_name='NA';
			}
			
			$api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Customer/CreateOrUpdate', array(
				'headers' => array(
						'PrivateKey' => $cheerme_site_private_key
				),
				'body' => array(
					'FirstName'   => $first_name,
					'LastName'  => $last_name, 
					'Email' => $email,
					'ExternalId' => $user_id,
					'ExtenalCreatedAt'=>$created_date,
					'ExternalUpdatedAt'=>$created_date
				)
			) );
			
		//	$body = json_decode( $api_response['body'] );
		}
		
		
	/* Function to update user profile */
		
	public function cheerme_user_update( $user_id )
	{
		if (is_user_logged_in()) {
				
			if ( ! isset( $_POST['password_1'] ) || '' == $_POST['password_1'] ) {
				$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
				$users_data=get_userdata( $user_id );
				$email=$users_data->user_email;
				$created_date=$users_data->user_registered;
				$all_meta_for_user = get_user_meta( $user_id );
				$last_name = $all_meta_for_user['last_name'][0];
				$first_name = $all_meta_for_user['first_name'][0];
				if(isset($_POST['first_name']))		
				{				
					$first_name=sanitize_text_field($_POST['first_name']);				
					$last_name=sanitize_text_field($_POST['last_name']);			
				}
				elseif(isset($_POST['billing_first_name']))	
				{				
					$first_name=sanitize_text_field($_POST['billing_first_name']);				
					$last_name=sanitize_text_field($_POST['billing_last_name']);			
				}			
				
				if($first_name == '')
				{
					$first_name='NA';
				}
				if($last_name == '')			
				{
					$last_name='NA';
				}
				
				$api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Customer/CreateOrUpdate', array(
					'headers' => array(
							'PrivateKey' => $cheerme_site_private_key
					),
					'body' => array(
						'FirstName'   => $first_name,
						'LastName'  => $last_name, 
						'Email' => $email,
						'ExternalId' => $user_id,
						'ExtenalCreatedAt'=>$created_date,
						'ExternalUpdatedAt'=>$created_date
					)
				) ); 
			}
		}
	}
	
	/* function to display the error notice */	
		
	public function my_error_notice()
		{
		?>
			<div class='error notice'>
					<p>Cheerme requires WooCommerce to be activated.</p>
			</div>
		<?php
		}
		
	
	/* function to perform after user place an order */
	public function cheerme_add_order($order_id)
		{
			try
			{
				$order = wc_get_order( $order_id );

					
				$cheerme_flag=get_post_meta($order_id,'cheerme_flag',true);
				 if($cheerme_flag != 1)
				 {
					$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
					$order = new WC_Order( $order_id );
					
					$myuser_id = (int)$order->user_id;
					if ( $myuser_id != 0 || $myuser_id !='') 
					{
						$users_data = get_userdata($myuser_id);
						
						//// user data 
						$email=$users_data->user_email;
						$created_date=$users_data->user_registered;
						$all_meta_for_user = get_user_meta( $myuser_id );
						$last_name = $all_meta_for_user['last_name'][0];
						$first_name = $all_meta_for_user['first_name'][0];
						if(isset($_POST['first_name']))		
						{				
							$first_name=sanitize_text_field($_POST['first_name']);				
							$last_name=sanitize_text_field($_POST['last_name']);			
						}
						elseif(isset($_POST['billing_first_name']))	
						{				
							$first_name=sanitize_text_field($_POST['billing_first_name']);				
							$last_name=sanitize_text_field($_POST['billing_last_name']);			
						}			
						
						if($first_name == '')
						{
							$first_name='NA';
						}
						if($last_name == '')			
						{
							$last_name='NA';
						}
						
						$created_at = $order->get_date_created()->date('Y-m-d H:i:s');
						$updated_at = $order->get_date_modified()->date('Y-m-d H:i:s');
						$total = $order->get_total();
						$coupons=$order->get_coupon_codes();
						$discount=$order->get_discount_total();
						
						
						$coupon_name=$coupons[0];
						$order_refunded_amount=$order->get_total_refunded();
						$final_total=$total-$order_refunded_amount;
						$transaction_id=$order->get_transaction_id();
						$subtotal=$order->get_subtotal();
						$payment_status='paid';
						  $api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Order/CreateOrUpdate', array(
							'headers' => array(
									'PrivateKey' => $cheerme_site_private_key
							),
							'body' => array(
								'ExternalId'   => $order_id,
								'SubTotal' => $subtotal,
								'GrandTotal'  => $final_total, 
								'ExternalCreatedAt' => $created_at,
								'ExternalUpdatedDate' => $updated_at,
								'RewardableTotal' => $final_total,
								'CustomerExternalId' => $myuser_id,
								'PaymentStatus' => $payment_status,
								'CouponCodes' => $coupons,
								'CouponCode' => $coupon_name,
								'DiscountValue' => $discount,
								'Customer' => array(
										'FirstName'   => $first_name,
										'LastName'  => $last_name, 
										'Email' => $email,
										'ExternalId' => $myuser_id,
										'ExtenalCreatedAt'=>$created_date,
										'ExternalUpdatedAt'=>$created_date
									)
							)
						) );
						
						add_post_meta($order_id,'cheerme_flag','1');
					}
					
					
				}
			
			
			
			}
			catch(Exception $ex)
			{
				// echo "FAILURE: " . $ex->getMessage();
				
			}
		}
		
		
		
	/* function to perform after update an order */
		
	public function cheerme_process_order($order_id)
		{
			try
			{
				$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
					$order = new WC_Order( $order_id );
					$myuser_id = (int)$order->user_id;
					if ( $myuser_id != 0 || $myuser_id !='') 
					{
						// $user_info = get_userdata($myuser_id);
						$users_data = get_userdata($myuser_id);
						
						//// user data 
						$email=$users_data->user_email;
						$created_date=$users_data->user_registered;
						$all_meta_for_user = get_user_meta( $myuser_id );
						$last_name = $all_meta_for_user['last_name'][0];
						$first_name = $all_meta_for_user['first_name'][0];
						if(isset($_POST['first_name']))		
						{				
							$first_name=sanitize_text_field($_POST['first_name']);				
							$last_name=sanitize_text_field($_POST['last_name']);			
						}
						elseif(isset($_POST['billing_first_name']))	
						{				
							$first_name=sanitize_text_field($_POST['billing_first_name']);				
							$last_name=sanitize_text_field($_POST['billing_last_name']);			
						}			
						
						if($first_name == '')
						{
							$first_name='NA';
						}
						if($last_name == '')			
						{
							$last_name='NA';
						}
						
						$created_at = $order->get_date_created()->date('Y-m-d H:i:s');
						$updated_at = $order->get_date_modified()->date('Y-m-d H:i:s');
						$total = $order->get_total();
						$coupons=$order->get_coupon_codes();
						$discount=$order->get_discount_total();
						$coupon_name=$coupons[0];
						$order_refunded_amount=$order->get_total_refunded();
						$final_total=$total-$order_refunded_amount;
						$transaction_id=$order->get_transaction_id();
						$subtotal=$order->get_subtotal();
						$payment_status='paid';
						  $api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Order/CreateOrUpdate', array(
							'headers' => array(
									'PrivateKey' => $cheerme_site_private_key
							),
							'body' => array(
								'ExternalId'   => $order_id,
								'SubTotal' => $subtotal,
								'GrandTotal'  => $final_total, 
								'ExternalCreatedAt' => $created_at,
								'ExternalUpdatedDate' => $updated_at,
								'RewardableTotal' => $final_total,
								'CustomerExternalId' => $myuser_id,
								'PaymentStatus' => $payment_status,
								'CouponCodes' => $coupons,
								'CouponCode' => $coupon_name,
								'DiscountValue' => $discount,
								'Customer' => array(
										'FirstName'   => $first_name,
										'LastName'  => $last_name, 
										'Email' => $email,
										'ExternalId' => $myuser_id,
										'ExtenalCreatedAt'=>$created_date,
										'ExternalUpdatedAt'=>$created_date
									)
							)
							
							) );	
							
							
					
					}
					
			
			}
			catch(Exception $ex)
			{
				
				
			}
		}
		
	/* function to perform when user is deleted*/
		
	public function cheerme_remove_user( $user_id )
		{
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$api_responsed = wp_remote_post( 'https://api.cheerme.io/api/Public/Customer/Delete?externalId='.$user_id, array(
						'headers' => array(
								'PrivateKey' => $cheerme_site_private_key
						)
						
					) );
		}
		
	/* function to perform when order is deleted*/
	
	public function cheerme_delete_order( $id ) 
		{
			global $post_type;
			if($post_type !== 'shop_order') {
				return;
			}

			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$api_responses = wp_remote_post( 'https://api.cheerme.io/api/Public/Order/Delete?externalId='.$id, array(
					'headers' => array(
							'PrivateKey' => $cheerme_site_private_key
						)
								
					) );
					
		}
		
	/* function to perform when order is recovered*/
	
	public function cheerme_untrash_order($id)
		{
			global $post_type;
			if($post_type !== 'shop_order') {
				return;
			}
			$this->cheerme_process_order($id);
			
		}
		
	public function cheerme_order_refunded( $order_id, $refund_id ) 
		{ 
			$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
			$order = new WC_Order($order_id);
			$order_status=$order->get_status();
			$total = $order->get_total();
			$order_refunded_amount=$order->get_total_refunded();
			
			$final_total=$total-$order_refunded_amount;
			if($final_total <= $order_refunded_amount)
			{
				$cheerme_site_private_key = get_option('cheerme_site_private_key','none');
				$api_responses = wp_remote_post( 'https://api.cheerme.io/api/Public/Order/Delete?externalId='.$order_id, array(
						'headers' => array(
								'PrivateKey' => $cheerme_site_private_key
							)
									
						) );
						
			}
			else
			{
				$myuser_id = (int)$order->user_id;
				if ( $myuser_id != 0 || $myuser_id !='') 
				{
					// $user_info = get_userdata($myuser_id);
					$users_data = get_userdata($myuser_id);
						
						//// user data 
						$email=$users_data->user_email;
						$created_date=$users_data->user_registered;
						$all_meta_for_user = get_user_meta( $myuser_id );
						$last_name = $all_meta_for_user['last_name'][0];
						$first_name = $all_meta_for_user['first_name'][0];
						if(isset($_POST['first_name']))		
						{				
							$first_name=sanitize_text_field($_POST['first_name']);				
							$last_name=sanitize_text_field($_POST['last_name']);			
						}
						elseif(isset($_POST['billing_first_name']))	
						{				
							$first_name=sanitize_text_field($_POST['billing_first_name']);				
							$last_name=sanitize_text_field($_POST['billing_last_name']);			
						}			
						
						if($first_name == '')
						{
							$first_name='NA';
						}
						if($last_name == '')			
						{
							$last_name='NA';
						}
						
					$created_at = $order->get_date_created()->date('Y-m-d H:i:s');
					$updated_at = $order->get_date_modified()->date('Y-m-d H:i:s');
					$total = $order->get_total();
					$coupons=$order->get_used_coupons();
					$coupon_name=$coupons[0];
					$discount=$order->get_discount_total();
					$transaction_id=$order->get_transaction_id();
					$subtotal=$order->get_subtotal();
					$payment_status='paid';
					  
						$api_response = wp_remote_post( 'https://api.cheerme.io/api/Public/Order/CreateOrUpdate', array(
						'headers' => array(
								'PrivateKey' => $cheerme_site_private_key
						),
						'body' => array(
							'ExternalId'   => $order_id,
							'SubTotal' => $subtotal,
							'GrandTotal'  => $final_total, 
							'ExternalCreatedAt' => $created_at,
							'ExternalUpdatedDate' => $updated_at,
							'RewardableTotal' => $final_total,
							'CustomerExternalId' => $myuser_id,
							'PaymentStatus' => $payment_status,
							'CouponCodes' => $coupons,
							'CouponCode' => $coupon_name,
							'DiscountValue' => $discount,
								'Customer' => array(
										'FirstName'   => $first_name,
										'LastName'  => $last_name, 
										'Email' => $email,
										'ExternalId' => $myuser_id,
										'ExtenalCreatedAt'=>$created_date,
										'ExternalUpdatedAt'=>$created_date
									)
						)
					) );
					
				}	
			}
			
		}
	
	
}
	
	add_action( "wp_ajax_applycheerMeCouponInCart",'applycheerMeCouponInCart'); 
    add_action( "wp_ajax_nopriv_applycheerMeCouponInCart", 'applycheerMeCouponInCart');

	function applycheerMeCouponInCart()
	{
	    
	    $cheerMeRewardId = $_POST['cheerMeRewardId'];
		
		$cheerMeCustomerId = get_current_user_id(); 
		$cheerMePublicKey = get_option('cheerme_site_public_key','none'); 
          
		$cheerMeApiCoupon = '';
	 		 
		// if already coupon applied then msg seen
		if ( WC()->cart->has_discount( ) ) { 
		
		    $result=array("IsSuccess"=>true,"Message"=>"","Coupon"=>$cheerMeApiCoupon); 
				
			echo json_encode($result);  
			wp_die(); 
			return true; 
		} 
		
		/*
		$cheerMeApiCoupon = '4c688ba452034'; 
		
		$result=array("IsSuccess"=>true,"Message"=>"","Coupon"=>$cheerMeApiCoupon); 
				
		echo json_encode($result);  
		wp_die();
		return true; */  
		
		$getCheermeCouponArgs = array( 'method'  => 'GET',
						'timeout'     => 300,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array('PublicKey'=>$cheerMePublicKey,'customerId'=>$cheerMeCustomerId)
						);
						
		$getCheermeCouponUrl = "https://api.cheerme.io/api/public/Reward/AssignCoupon?rewardId=".$cheerMeRewardId;
		
		$getCheermeCouponContent = wp_remote_get( $getCheermeCouponUrl, $getCheermeCouponArgs );
		
		$getCheermeCouponContentResponse = wp_remote_retrieve_body( $getCheermeCouponContent );
		
		$getCheermeCouponContentResponse = json_decode($getCheermeCouponContentResponse);
		
		$getCheermeCouponSuccess = $getCheermeCouponContentResponse->Success;
		$getCheermeCouponMessage = $getCheermeCouponContentResponse->Message;
		
		
		if($getCheermeCouponSuccess == true){
		    
			$cheerMeApiCoupon =  $getCheermeCouponContentResponse->Coupon;
			
			if($cheerMeApiCoupon != null){
			  
			    $current_user_id=get_current_user_id();
				$cheerMePublicKey = get_option('cheerme_site_public_key','none');
				
				$cheermeCustomerTotalRewardPoints = "";
				
				$getCheermeRedeemableRewardsArgs = array( 'method'  => 'GET',
								'timeout'     => 300,
								'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
								'headers' 		=> array('PublicKey'=>$cheerMePublicKey,'customerId'=>$current_user_id)
								);
								
				$getCheermeRedeemableRewardsUrl = "https://api.cheerme.io/api/public/Reward/GetRedeemableRewards";
				
				$cheermeRedeemableRewardsApiContent = wp_remote_get( $getCheermeRedeemableRewardsUrl, $getCheermeRedeemableRewardsArgs );
				
				$getCheermeRedeemableRewardsListResponse = wp_remote_retrieve_body( $cheermeRedeemableRewardsApiContent );
				
				$getCheermeRedeemableRewardsListResponse = json_decode($getCheermeRedeemableRewardsListResponse);
				
				$getRewardListSuccess = $getCheermeRedeemableRewardsListResponse->Success;

				
				if($getRewardListSuccess == true){ 
					
					$cheermeRedeemableRewardsData = $getCheermeRedeemableRewardsListResponse->Data;     
					
					$cheermeRedeemableRewardsList = $cheermeRedeemableRewardsData->Rewards;	

					$cheermeCustomerTotalRewardPoints = $cheermeRedeemableRewardsData->CustomerPoints; 
					$cheermeCustomerPointCurrency = $cheermeRedeemableRewardsData->PointCurrency;
					if($cheermeCustomerTotalRewardPoints != null){
					   $cheermeCustomerTotalRewardPoints =  "Redeem Your " . $cheermeCustomerPointCurrency . " ( You have " .  $cheermeCustomerTotalRewardPoints . " " . $cheermeCustomerPointCurrency . " )";
					     
					}
				}	
			
			    $result=array("IsSuccess"=>true,"Message"=>$cheermeCustomerTotalRewardPoints,"Coupon"=>$cheerMeApiCoupon); 
			   
			}else{
			
			    $result=array("IsSuccess"=>false,"Message"=>"Something went wrong, Please try again","Coupon"=>$cheerMeApiCoupon);  
			
			}			  
			
		    
		}else{
		    $result=array("IsSuccess"=>false,"Message"=>$getCheermeCouponMessage,"Coupon"=>$cheerMeApiCoupon); 
		}
				   
		echo json_encode($result);
		wp_die(); 
	    
	}

if( class_exists( 'WC_CheerMe_Init' ) ){
	$WC_CheerMe_Init = new WC_CheerMe_Init();
	
}