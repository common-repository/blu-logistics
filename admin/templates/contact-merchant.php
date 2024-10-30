<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function get_contact_merchant(){
   register_setting( 'merchantID', 'blu_settings',  'contact_merchant_validate_input');

   add_settings_section(
      'blulogistics_section', 
      __( '', 'blulogistics' ),  
      'blu_settings_section_callback', 
      'merchantID'
   );

   add_settings_field( 
      'blu_text_field_0', 
      __( '<span style="color:red">*</span>Company Name :', 'blulogistics' ), 
      'blu_text_field_0_render', 
      'merchantID', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_1', 
      __( '<span style="color:red">*</span>Your Name :', 'blulogistics' ), 
      'blu_text_field_1_render', 
      'merchantID', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_2', 
      __( '<span style="color:red">*</span>Your Email :', 'blulogistics' ), 
      'blu_text_field_2_render', 
      'merchantID', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_3', 
      __( '<span style="color:red">*</span>Contact Number :', 'blulogistics' ), 
      'blu_text_field_3_render', 
      'merchantID', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_4', 
      __( '<span style="color:red">*</span>Woocommerce Shop URL :', 'blulogistics' ), 
      'blu_text_field_4_render', 
      'merchantID', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_5', 
      __( 'Comments :', 'blulogistics' ), 
      'blu_text_field_5_render', 
      'merchantID', 
      'blulogistics_section' 
   );
}

function blu_text_field_0_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <input type='text' name='blu_settings[blu_company_name]' value='<?php echo $options['blu_company_name']; ?>'>
   <?php

}


function blu_text_field_1_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <input type='text' class="allow-alphabet" name='blu_settings[blu_your_name]' value='<?php echo $options['blu_your_name']; ?>'>
   <?php

}


function blu_text_field_2_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <input type='text' name='blu_settings[blu_your_email]' value='<?php echo $options['blu_your_email']; ?>'>
   <?php

}

function blu_text_field_3_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <input type='text' class="allow-number nocopy" name='blu_settings[blu_contact_number]' value='<?php echo $options['blu_contact_number']; ?>' maxLength="10">
   <?php

}

function blu_text_field_4_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <input type='text' name='blu_settings[blu_website]' value='<?php echo trim($options['blu_website']); ?>'>
   <p class="info">For eg: www.example.com</p>
   <?php

}

function blu_text_field_5_render(  ) { 

   $options = get_option( 'blu_settings' );
   ?>
   <textarea cols='40' rows='5' name='blu_settings[blu_comments]'> 
      <?php echo $options['blu_comments']; ?>
   </textarea>
   <?php

}

function blu_settings_section_callback(  ) { 

   $title = '<h3>Contact us to get your Merchant ID and Authentication Key now!</h3>';
   echo __( $title, 'blulogistics' );

}

function blu_contact_merchant_submit( ){ ?>
   <p class="submit"><input type="submit" name="contact_merchant" id="contact_merchant" class="button button-primary" value="Get in Touch"></p>
<?php }


function contact_merchant_validate_input( $input ) {

   // Create our array for storing the validated options

   $valid = array();
   $valid['blu_company_name'] = sanitize_text_field($input['blu_company_name']);
   $valid['blu_your_name'] = sanitize_text_field($input['blu_your_name']);
   $valid['blu_your_email'] = sanitize_text_field($input['blu_your_email']);
   $valid['blu_contact_number'] = sanitize_text_field($input['blu_contact_number']);
   $valid['blu_website'] = sanitize_text_field($input['blu_website']);

   $prev_option = get_option('blu_settings');

   $error = '0';

   if(empty(trim($input['blu_company_name']))) {
      if($error == '0')
         blsm_save_message( 'error key', __( "Your company name is missing! Help us fill it in please.") );
         $error = '1';
   }

   if(empty(trim($input['blu_your_name']))) {
      if($error == '0')
         blsm_save_message( 'error key', __( "Your name is missing! Help us fill it in please.") );
         $error = '1';
   }

   if(empty(trim($input['blu_your_email']))) {
      if($error == '0')
         blsm_save_message( 'error key', __( "Don't forget to fill in your e-mail address!") );
         $error = '1';
   }
   else if(!is_email($input['blu_your_email'])){
      if($error == '0')
         blsm_save_message( 'error key', __( "Is your e-mail address correct? Seems like something is off.") );
         $error = '1';
   }

   if(empty(trim($input['blu_contact_number'])))
   {
      if($error == '0')
         blsm_save_message( 'error key', __( "We'd love to speak with you, but we need your contact number. Please fill it in.") );
         $error = '1';
   }
   elseif(strlen(trim($input['blu_contact_number'])) < 10) {
      if($error == '0')
         blsm_save_message( 'error key', __( "Please enter a valid Contact Number (ensure that you have keyed in the country code as well).") );
         $error = '1';
   }


   if(empty(trim($input['blu_website'])))
   {
      if($error == '0')
         blsm_save_message( 'error key', __( "Give us your Woocommerce Shop's URL please!.") );
         $error = '1';
   }
   else{
         if(!_is_valid_url(trim($input['blu_website'])))
         {
            $result = false;
         }
         else{
               $BluApi = new BluAPI();
               $result = $BluApi->blsmCheckWebsiteExist(trim($input['blu_website']));
         }

         if(!$result)
         {
            if($error == '0')
               blsm_save_message( 'error key', __( "Is the shop URL you keyed in correct? Seems like something is off.") );
               $error = '1';
         }
   } 

   if(!$error)
   {
      $current_user = wp_get_current_user();

      $headers = array('Content-Type: text/html; charset=UTF-8');
      $subject = $input['blu_your_name'].' New request from Merchant on Woocommerce';

      $heading = 'NEW REQUEST FROM MERCHANT ON WOOCOMMERCE: '.$input['blu_company_name'];

      global $woocommerce;
      $email_heading = __( $heading, 'woocommerce' );

      $mailer        = WC()->mailer();
      // get the preview email content
      ob_start();
      include( 'contact-email-template.php' );
      $message       = ob_get_clean();

      // create a new email
      $email         = new WC_Email();

      // wrap the content with the email template and then add styles
      $message       = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

      wp_mail( 'blucare@go.blu.today', $subject, $message,$headers); // send mail

      blsm_save_message( 'success', __( 'Thank you! We will get in touch with you soon.') );
   }
   else {
      return $input;
   }
 
}

/**
* @param website
* @return true or false
**/
function _is_valid_url($website)
{
   return ( ! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$website)) ? false : true;
}

?>