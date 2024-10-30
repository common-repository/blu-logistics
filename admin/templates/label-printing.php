<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function get_blu_label_printing(){
   register_setting( 'labelPrinting', 'blu_label_settings', 'label_validate_input' );

   add_settings_section(
      'blulogistics_section', 
      __( '', 'blulogistics' ), 
      'blu_label_settings_section_callback', 
      'labelPrinting'
   );

   add_settings_field( 
      'blu_text_field_0', 
      __( 'Enable Label Generator :', 'blulogistics' ), 
      'blu_label_text_field_0_render', 
      'labelPrinting', 
      'blulogistics_section' 
   );

   add_settings_field( 
      'blu_text_field_2', 
      __( 'Default Label Size :', 'blulogistics' ), 
      'blu_label_text_field_2_render', 
      'labelPrinting', 
      'blulogistics_section' 
   );
}

function blu_label_text_field_0_render(  ) { 

   $options = get_option( 'blu_label_settings' ); 
   ?>
   <input type="radio" value="Enable" name="blu_label_settings[blu_enable_label]" <?php if($options['blu_enable_label'] == 'Enable') echo 'checked="checked"' ?>>Enable
   <input type="radio" value="Disable" name="blu_label_settings[blu_enable_label]" <?php if($options['blu_enable_label'] == 'Disable') echo 'checked="checked"' ?>>Disable
   <?php

}

function blu_label_text_field_1_render(  ) { 

   $options = get_option( 'blu_label_settings' );
   if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
      $order_status = wc_get_order_statuses();
   }
   ?>
   <select name="blu_label_settings[blu_order_status]">
      <?php foreach($order_status as $index=>$key) :?>
         <option value="<?php echo $index ?>" <?php if($options['blu_order_status'] == $index) echo 'selected="selected"' ?>><?php echo $key ?></option>
      <?php endforeach;?>
   </select>
   <p class="info">(After printing a label, order status will be changed to your selection.)</p>
   <?php

}

function blu_label_text_field_2_render(  ) { 

   $options = get_option( 'blu_label_settings' );
   if(empty($options['blu_label_print'])) $options['blu_label_print'] = "A4";
   ?>
   <select name="blu_label_settings[blu_label_print]">
         <option value="A4" <?php if($options['blu_label_print'] == "A4") echo 'selected="selected"' ?>>A4</option>
         <option value="A5" <?php if($options['blu_label_print'] == "A5") echo 'selected="selected"' ?>>A5</option>
         <option value="A6" <?php if($options['blu_label_print'] == "A6") echo 'selected="selected"' ?>>A6</option>
   </select>
   <p class="info">(Your blu parcel labels will be generated in this size on the 'blu Orders' pages)</p>
   <?php

}

function blu_label_settings_section_callback(  ) { 

   $title = '<h3>LABEL GENERATOR</h3>';
   echo __( $title, 'blulogistics' );

}

function blu_label_printing_submit( ){ ?>
   <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Update"></p>
<?php }

function label_validate_input( $input ) {

   // Create our array for storing the validated options
   $valid = array();
   $valid['blu_enable_label'] = sanitize_text_field($input['blu_enable_label']);
   $valid['blu_order_status'] = sanitize_text_field($input['blu_order_status']);
   $valid['blu_label_print'] = sanitize_text_field($input['blu_label_print']);

   $prev_option = get_option('blu_label_settings');

   blsm_save_message( 'success', __( 'Settings Updated.') );

   return $valid;
 
}

?>