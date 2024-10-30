<?php

function get_blu_authentication(){
   register_setting( 'authentication', 'blu_auth_settings', 'authenticate_validate_input');

   add_settings_section(
      'blulogistics_section', 
      __( '', 'blulogistics' ), 
      'blu_auth_settings_section_callback', 
      'authentication'
   );

   add_settings_field( 
      'blu_text_field_0', 
      __( 'Merchant ID :', 'blulogistics' ), 
      'blu_auth_text_field_0_render', 
      'authentication', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_1', 
      __( 'Authentication Key :', 'blulogistics' ), 
      'blu_auth_text_field_1_render', 
      'authentication', 
      'blulogistics_section' 
   );
}

function blu_auth_text_field_0_render(  ) { 

   $options = get_option( 'blu_auth_settings' );
   ?>
   <input type='text' name='blu_auth_settings[blu_merchant_id]' value='<?php echo $options['blu_merchant_id']; ?>'>
   <?php

}

function blu_auth_text_field_1_render(  ) { 

   $options = get_option( 'blu_auth_settings' );
   ?>
   <input type='text' name='blu_auth_settings[blu_authenticate]' value='<?php echo $options['blu_authenticate']; ?>'>
   <?php

}

function blu_auth_settings_section_callback(  ) { 

   $title = '<h3>AUTHENTICATION</h3>';
   echo __( $title, 'blulogistics' );
}

function blu_authenticate_submit( ){ ?>
   <?php $blu_auth_result = get_option('blu_auth_result'); 
      if($blu_auth_result == 'success'){?>
       <b style="color: green; text-align:center">Account Authenticated  </b>
   <?php } ?>
   <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Authenticate"></p>
<?php }


function authenticate_validate_input( $input ) {

   // Create our array for storing the validated options
   $valid = $merchant = array();
   $merchant['merchant_id'] = $valid['blu_merchant_id'] = sanitize_text_field($input['blu_merchant_id']);
   $merchant['merchant_auth_key'] = $valid['blu_authenticate'] = sanitize_text_field($input['blu_authenticate']);

   $prev_option = get_option('blu_auth_settings');

   if(empty($input['blu_merchant_id'])) {
      blsm_save_message( 'error merchant', __( 'Please check and ensure your Merchant ID and Authentication Key are valid and updated. For further assistance, contact blu.') );
      $valid['blu_merchant_id'] = $prev_option['blu_merchant_id'];
      $error = '1';
   }

   if(empty($input['blu_authenticate'])) {
      blsm_save_message( 'error key', __( 'Please check and ensure your Merchant ID and Authentication Key are valid and updated. For further assistance, contact blu.') );
      $valid['blu_authenticate'] = $prev_option['blu_authenticate'];
      $error = '1';
   }

   if(!$error){
         /** verify merchant ID and authentication key **/
         $BluApi = new BluAPI();
         $response = $BluApi->blsm_merchant_authentication($merchant);
         $response = json_decode($response, true);
         if(empty($response)){
            update_option('blu_auth_result','failed');
            blsm_save_message( 'error key', __( "We're sorry, seems like there is a technical issue! Please try again later or contact blu for immediate assistance.") );
            return $valid;
         }
         else if($response['result'][0] == 'success'){
            update_option('blu_auth_result','success');
            blsm_save_message( 'success', __( 'Account Authenticated.') );
            return $valid;
         }
         else{
            update_option('blu_auth_result','failed');
            blsm_save_message( 'error key', __( 'Account Authentication Failed. 
                                             Please check and ensure your Merchant ID and Authentication Key are valid and updated. For further assistance, contact blu.') );
            return $valid;
         }
   }
 
}

?>