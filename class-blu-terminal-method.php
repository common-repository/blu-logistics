<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Blu_Terminal_Delivery_Shipping_Method extends WC_Shipping_Method{

    public function __construct(){
    $this->id = 'blu_terminal_shipping_method';
      $this->method_title = __( 'bluPort Parcel Terminal', 'woocommerce' );

      // Load the settings.
      $this->init_form_fields();
      $this->init_settings();

      $this->zones_settings     = $this->id.'zones_settings';
      $this->rates_settings     = $this->id.'rates_settings';
      $this->option_key       = $this->id.'_table_rates';     //The key for wordpress options
      $this->options        = array();                        //the actual tabel rate options saved 
      $this->condition_array  = array();                      //holds an array of CONDITIONS for the select
      $this->country_array  = array();                        //holds an array of COUNTRIES for the select
      $this->counter      = 0;  

      $this->get_options();

      $this->create_select_arrays();

      // Define user set variables
      $this->enabled  = $this->get_option( 'enabled' );
      $this->title    = $this->get_option( 'title' );
    
    
      add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

      //And save our options
      add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_custom_settings' ) );
    }

    public function init_form_fields(){
      $this->form_fields = array(
        'enabled' => array(
          'title'     => __( 'Enable/Disable', 'woocommerce' ),
          'type'      => 'checkbox',
          'label'     => __( 'Enable bluPort Parcel Terminal', 'woocommerce' ),
          'default'     => 'yes'
        ),
        'title' => array(
          'title'     => __( 'Method Title', 'woocommerce' ),
          'type'      => 'text',
          'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
          'default'   => __( 'bluPort Parcel Terminal', 'woocommerce' ),
          
        ),
        'description' => array(
                'title'     => __( 'Description', 'woocommerce' ),
                'type'      => 'textarea',
                'description' => __( '', 'woocommerce' ),
                'default'   => __( 'Collect your parcel conveniently at a bluPort Parcel Terminal of your choice!', 'woocommerce' ),
            ),
        'tracking_url' => array(
        'title'     => __( 'Tracking URL ', 'woocommerce' ),
        'type'      => 'text',
        'description'   => __( '', 'woocommerce' ),
        'default'   => __( 'https://go.blu.today/parcel', 'woocommerce' ),
          
        ),
         'free_shipping' => array(
          'title'     => __( 'Free Shipping ', 'woocommerce' ),
          'type'      => 'checkbox',
          'description'   => __( '', 'woocommerce' ),
          'default'   => __( '', 'woocommerce' ),
          
        ),
        'table_rates_table' => array(
            'type'        => 'table_rates_table'
            ), 
    );
    }

    public function is_available( $package ){

      if ('no' == $this->enabled) {
            return false;
        }

        $dest_country = $package['destination']['country'];

        //now lets get the rates associated with this country
        $rates = $this->get_rates_for_country($dest_country);
        
        if($rates == null){
          //there is nothing available for this country so just return
          return false;
        }

      return true;
    }


    public function calculate_shipping($package=array()){

      $options = get_option( 'blu_shipping_settings' );

      //ok first lets get the country that this order is for
      $dest_country = $package['destination']['country'];

      //now lets get the rates associated with this country
      $rates = $this->get_rates_for_country($dest_country);
      
      if($rates == null){
        //there is nothing available for this country so just return
        return;
        
      }

      if($this->get_option('free_shipping') == 'yes'){
        $cost = '0';
      } else {
          if($rates[0]['condition'] == 'total'){
                  //price based comparisons
                  $cost = $this->find_matching_rate(WC()->cart->cart_contents_total, $rates);
          } else {
                  //weight based comparison
                  $cost = $this->find_matching_rate(WC()->cart->cart_contents_weight, $rates);
          }
      }

      // send the final rate to the user. 
      $this->add_rate( array(
        'id'  => $this->id,
        'label' => $this->title,
        'cost'  => $cost
      ));
    }

    function get_rates_for_country($country){
          $ret = array();
          
          foreach($this->options as $rate){
            if(in_array($country, $rate['countries'])){
              $ret[] =  $rate;
            }
            
          }

          if(count($ret) >0){
            return $ret;
          } else {
            return null; 
          }

    } 

    function find_matching_rate($value, $zones){

          foreach($zones as $zone){
            // remember * means infinity!

            $j = 0;
            $max = $zone['max'][0];
            for($i=0; $i<count($zone['max']); $i++){

              if($max < $zone['max'][$i])
              {
                $max = $zone['max'][$i];
                $j = $i;
              }
                 

              if($zone['max'][$i] == '*'){
                if($value >= $zone['min'][$i]){
                  $rate = $zone['shipping'][$i];
                }
              } else {
                if($value >= $zone['min'][$i] && $value < $zone['max'][$i]){
                  $rate = $zone['shipping'][$i];
                }
              }
            }

            if($rate == '')
            {
              $rate = $zone['shipping'][$j];
            }


            if($rate)
              return $rate;
            
            //OK if we got all the way to here, then we have NO match
            return null;
          }
        } 
        /**
        * admin_options
        * These generates the HTML for all the options
        */
        function admin_options() {
        ?>
        
          <style>
            
            .blu-zone-row{
              background-color: #ccc !important;
            }
            .blu-show-row{
                display: table-row;
                background-color: #0073aa;
            }
            .table-rate td.country-rate {
              width: 40%;
            }
          </style>
          <h2><?php _e('Table Rate Shipping Options','woocommerce'); ?></h2>
           <table class="form-table">
           <?php $this->generate_settings_html(); ?>
           </table> 
        <?php         
        }

        /**
        * This RETIEVES  all of our custom table settings
        */
        function get_options() {
          
          //Retrieve the zones & rates
          $this->options = array_filter( (array) get_option( $this->option_key ) );
          
          $x = 5;
        } 

        //*********************
        // PHP functions
        //***********************

                /**
         * Generates HTML for table_rate settings table.
         * this gets called automagically!
         */
        function generate_table_rates_table_html() {
          ob_start();
          
          
          
          //OK lets pump out some rows to see how it goes!!!
          // we put the jscript stuff at the top
          ?>
          
          <tr>
            <th scope="row" class="titledesc"><?php _e( 'Table Rates', JEM_DOMAIN ); ?></th>
            <td id="<?php echo $this->id; ?>_settings">
              <p class="description">When the weight or price of the product at check out are not in the range mentioned in the pricing table below then the shipping price will be calculated based on the highest defined range.</p>
              <div class="errorMsgs" style="display:none">
                <h3>Please enter valid range.</h3>
              </div>
              <table class="shippingrows widefat table-rate" id="<?php echo $this->id; ?>_table_rates">
                <thead>
                  <tr>
                    <th class="check-column"></th>
                    <th>Shipping Zone Name</th>
                    <th>Condition</th>
                    <th>Countries</th>
                  </tr>
                </thead>
                <tbody style="border: 1px solid black;">
                  <tr style="border: 1px solid black;">
                    <td colspan="5" class="add-zone-buttons">
                      <a href="#" class="add button">Add New Shipping Zone</a>
                      <a href="#" class="delete button">Delete Selected Zones</a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <script>
              var options = <?php echo json_encode($this->create_dropdown_options()); ?>;

            var country_array = <?php echo json_encode($this->country_array); ?>;
            var condition_array = <?php echo json_encode($this->condition_array); ?>;
            var pluginID = <?php echo json_encode($this->id); ?>;

            var lastID = 0;

              <?php
                
                  foreach($this->options as $key => $value){
                    
                    //add the key back into the json object
                    $value['key'] = $key;
                    $row = json_encode($value);
                    echo "jQuery('#{$this->id}_settings table tbody tr:last').before(create_zone_row({$row}));\n";
                  }
                  

                ?>
                 

            
            /**
            * This creates a new ZONE row
            */
            function create_zone_row(row){
              //lets get the ID of the last one
              
              var el = '#' + pluginID + '_settings .blu-zone-row';
              lastID = jQuery(el).last().attr('id');
              
              //Handle no rows
              if(typeof lastID == 'undefined' || lastID == ""){
                lastID =1;
              } else {
                lastID = Number(lastID) + 1;
              }
              
              zone_name = row["key"] ? row["key"] : lastID;

              var html = '\
                  <tr id="' + lastID + '" class="blu-zone-row" >\
                    <input type="hidden" value="' + lastID + '" name="key[' + lastID + ']"></input>\
                    <td><input type="checkbox" class="blu-zone-checkbox"></input></span></td>\
                    <td><input type="number" min="0" size="30" onkeydown="return false;" value="' + zone_name +'"  name="zone-name[' + lastID + ']"/></td>\
                    <td>\
                      <select name="condition[' + lastID + ']">\
                      ' + generate_condition_html(row.condition) +'\
                      </select>\
                    </td>\
                    <td class="country-rate">\
                      <select multiple="multiple" class="multiselect chosen_select" name="countries[' + lastID + '][]">\
                      ' + generate_country_html(row.countries) + '\
                          </select>\
                    </td>\
                  </tr>\
              ';  
              
              //This is the expandable/collapsable row for that holds the rates
              html += '\
                <tr class="blu-rate-holder">\
                  <td colspan="1">\
                  </td>\
                  <td colspan="3">\
                    <table class="blu-rate-table shippingrows widefat" id="' + lastID + '_rates">\
                      <thead>\
                        <tr>\
                          <th></th>\
                          <th style="width: 30%">Min Value</th>\
                          <th style="width: 30%">Max Value</th>\
                          <th style="width: 40%">Shipping Rate</th>\
                        </tr>\
                      </thead>\
                      ' + create_rate_row(lastID, row) +'\
                      <tr>\
                        <td colspan="4" class="add-rate-buttons">\
                          <a href="#" class="add button" name="key_' + lastID + '">Add New Rate</a>\
                          <a href="#" class="delete button">Delete Selected Rates</a>\
                        </td>\
                      </tr>\
                    </table>\
                  </td>\
                </tr>\
              ';
              
              return html;
            }

            /**
            * This creates a new RATE row
            * The container Table is passed in and this row is added to it
            */
            function create_rate_row(lastID, row){
              
              
              if(row == null || row.rates.length == 0){
                //lets manufacture a rows
                //create dummy row
                var row = {};
                row.key = "";
                row.condition = [""];
                row.countries = [];
                row.rates = [];
                row.rates.push([]);
                row.rates[0].min = "";
                row.rates[0].max = "";
                row.rates[0].shipping = "";
                }
              //loop thru all the rate data and create rows
              
              //handles if there are no rate rows yet
              if(typeof(row.min) == 'undefined' || row.min==null){
                row.min=[];
              }
              
              var html = '';
              for(var i=0; i<row.rates.length; i++){
                html += '\
                  <tr>\
                    <td>\
                      <input type="checkbox" class="blu-rate-checkbox" id="' + lastID + '"></input>\
                    </td>\
                    <td>\
                      <input type="number" min="0" size="20" placeholder="" name="min[' + lastID + '][]" value="' + row.rates[i].min + '"></input>\
                    </td>\
                    <td>\
                      <input type="number" min="0" size="20" placeholder="" name="max[' + lastID + '][]" value="' + row.rates[i].max + '"></input>\
                    </td>\
                    <td>\
                      <input type="number" min="0" size="10" step="0.1" placeholder="" name="shipping[' + lastID + '][]" value="' + row.rates[i].shipping + '"></input>\
                    </td>\
                  </tr>\
                ';
              }
              
              
              return html;
            } 
          
            /**
             * Handles the expansion contraction of the rate table for the zone
             */ 
            function expand_contract(){
          
              var row = jQuery(this).parent('td').parent('tr').next();
              
              if(jQuery(row).hasClass('blu-hidden-row')){
                jQuery(row).removeClass('blu-hidden-row').addClass('blu-show-row'); 
                jQuery(this).removeClass('expand-icon').addClass('collapse-icon');              
              } else {
                jQuery(row).removeClass('blu-show-row').addClass('blu-hidden-row');                               
                jQuery(this).removeClass('collapse-icon').addClass('expand-icon');              
              }
              
              
              
            }
            
            
            //**************************************
            // Generates the HTML for the country
            // select. Uses an array of keys to
            // determine which ones are selected
            //**************************************
            function generate_country_html(keys){
                      
              html = "";
              
              for(var key in country_array){
                
                if(keys.indexOf(key) != -1){
                  //we have a match
                  html += '<option value="' + key + '" selected="selected">' + country_array[key] + '</option>'; 
                } else {
                  html += '<option value="' + key + '">' + country_array[key] + '</option>'; 
                  
                }
              }
              
              return html;
            }
            
            
            //**************************************
            // Generates the HTML for the CONDITION
            // select. Uses an array of keys to
            // determine which ones are selected
            //**************************************
            function generate_condition_html(keys){
                      
              html = "";
              
              for(var key in condition_array){
                
                if(keys.indexOf(key) != -1){
                  //we have a match
                  html += '<option value="' + key + '" selected="selected">' + condition_array[key] + '</option>'; 
                } else {
                  html += '<option value="' + key + '">' + condition_array[key] + '</option>'; 
                  
                }
              }
              
              return html;
            }
            
            //***************************
            // Handle add/delete clicks
            //***************************

            //ZONE TABLE
            
            function range_overlapping(obj){
                var x1,y1,x2,y2,empty = 0;
                var selectedIndex, currentIndex = 0;

                jQuery('table > tbody > tr').not(':last').each(function() {
                      jQuery(this).removeClass('selected');
                      jQuery(this).removeClass('has-error');
                      jQuery(".errorMsgs").css('display','none');
                      jQuery('.add,.delete').removeClass('disable-select');
                });

                jQuery(obj).closest('tr').find("input[type='number']").each(function() {

                    selectedIndex = jQuery(obj).closest('tr').index();
                    jQuery(this).closest('tr').addClass('selected');
                    if(jQuery(this).parent().index() == 1)
                      x1 = parseFloat(this.value);
                    if(jQuery(this).parent().index() == 2)
                      x2 = parseFloat(this.value);
                });

                if(empty == 0){
                  jQuery(obj).parents('table > tbody > tr:not(:last)').find("input[type='number']").each(function() {

                      currentIndex = jQuery(this).parents('tr').index();
                      if(!jQuery(this).closest('tr').hasClass('selected')){
                          if(jQuery(this).parent().index() == 1){
                             y1 = parseFloat(this.value);
                          }
                          
                          if(jQuery(this).parent().index() == 2){
                            y2 = parseFloat(this.value);

                              if(( (x1 > x2) || (x1 < y2) && (currentIndex < selectedIndex)) ||
                                  ( (x1 > x2) || (x2 > y1) && (currentIndex > selectedIndex))){
                                  if(jQuery('table > tbody > tr').hasClass('selected')) {
                                      jQuery(obj).closest('tr').addClass('has-error');
                                      jQuery(".errorMsgs").css('display','');
                                      jQuery('.add,.delete').addClass('disable-select');
                                  }
                                  jQuery('input[name="save"]').attr('disabled','disabled');
                                  return;
                                } else {
                                  jQuery('input[name="save"]').removeAttr('disabled');
                                  jQuery(this).removeClass('has-error');
                                  jQuery(".errorMsgs").css('display','none');
                                }                  
                          }
                      } 
                  });
                }
            }
            
            jQuery("input[type=number]").bind('keyup input', function(){
                    range_overlapping(this);
            });
            
            
            /*
            * add new ZONE row
            */
            var zoneID = "#" + pluginID + "_settings";

            jQuery(zoneID).on('click', '.add-zone-buttons a.add', function(){
              
              //ok lets add a row!
              
              
              var id = "#" + pluginID + "_settings table tbody tr:last";
              //create empty row
              var row = {};
              row.key = "";
              row.min = [];
              row.rates = [];
              row.condition = [];
              row.countries = [];
              jQuery(id).before(create_zone_row(row));

              if(jQuery('#woocommerce_blu_terminal_shipping_method_free_shipping').is(':checked'))
              {
                jQuery('.blu-rate-table').hide()
              }

              //turn on select2 for our row
              if (jQuery().chosen) {
                jQuery("select.chosen_select").chosen({
                  width: '350px',
                  disable_search_threshold: 5
                });
              } else {
                jQuery("select.chosen_select").select2();
              }
              
              
              return false;
            });
            
            /**
             * Delete ZONE row
             */
            jQuery(zoneID).on('click', '.add-zone-buttons a.delete', function(){
    
              //loop thru and see what is checked - if it is zap it!
              var rowsToDelete = jQuery(this).closest('table').find('.blu-zone-checkbox:checked');
              
              jQuery.each(rowsToDelete, function(){
                
                var thisRow = jQuery(this).closest('tr');
                //first lets get the next sibl;ing to this row
                var nextRow = jQuery(thisRow).next();
                
                //it should be a rate row
                if(jQuery(nextRow).hasClass('blu-rate-holder')){
                  //remove it!
                  jQuery(nextRow).remove();
                } else {
                  //trouble at mill
                  return;
                }
                
                jQuery(thisRow).remove();
              });
              
              //TODO - need to delete associated RATES
                            
              return false;
            });
            
                        
            //RATE TABLES
            
            /**
            * ADD RATE BUTTON
            */
            jQuery(zoneID).on('click', '.add-rate-buttons a.add', function(){
              
              //we need to get the key of this zone - it's in the name of of the button
              var name = jQuery(this).attr('name');
              name = name.substring(4);
              var empty = 0;

              jQuery(this).closest('tr').prev().find("input[type='number']").each(function() {
                  if(!this.value) {
                     empty++;
                  }
              });

              range_overlapping(this);
              if(empty > 0){
                  alert('Please validate the last range before you create a new one.'); 
              } 
              else {
                  var row = create_rate_row(name, null);
                  jQuery(this).closest('tr').before(row);
                  jQuery(function() {
                      jQuery("input[type=number]").bind('keyup input', function(){
                        range_overlapping(this);
                      });
                  });
              }
              
              return false;
            });
          
            /**
             * Delete RATE roe
             */ 
            jQuery(zoneID).on('click', '.add-rate-buttons a.delete', function(){
              jQuery('input[name="save"]').removeAttr('disabled');
              //loop thru and see what is checked - if it is zap it!
              var rowsToDelete = jQuery(this).closest('table').find('.blu-rate-checkbox:checked');
              
              jQuery.each(rowsToDelete, function(){
                jQuery(this).closest('tr').remove();
              });
              
                            
              return false;
            });
            
            //These handle building the select arras
            
            
              <?php
                    echo "jQuery('#{$this->id}_settings').on('click', '.blu-expansion', expand_contract) ;\n";

                ?>
          </script>
          
          <?php
          return ob_get_clean();    
        }

        function create_select_arrays(){
          
          //first the CONDITION html
          $this->condition_array = array();
          $this->condition_array['weight'] = sprintf(__( 'Weight (%s)', 'MHTR_DOMAIN' ), get_option('woocommerce_weight_unit'));
          $this->condition_array['total'] = sprintf(__( 'Total Price (%s)', 'MHTR_DOMAIN' ), get_woocommerce_currency_symbol());
          

          //Now the countries
          $this->country_array = array('SG' => __( 'Singapore', 'woocommerce' ));
          
          // Get the country list from Woo....
          // foreach (WC()->countries->get_shipping_countries() as $id => $value) :
          //     $this->country_array[esc_attr($id)] = esc_js($value);
          // endforeach;    
          //  unset($this->country_array["SG"]);        
        }

        /**
         * Returns the latest counter 
        */
        function get_counter(){
          $this->counter = $this->counter + 1;
          return $this->counter;  
        }

        /**        
        * This generates the select option HTML for teh zones & rates tables
        */
        function create_select_html(){
          //first the CONDITION html
          $arr = array();
          $arr['weight'] = sprintf(__( 'Weight (%s)', 'MHTR_DOMAIN' ), get_option('woocommerce_weight_unit'));
            $arr['total'] = sprintf(__( 'Total Price (%s)', 'MHTR_DOMAIN' ), get_woocommerce_currency_symbol());
             
          //now create the html from the array
          $html= '';
          foreach ($arr as $key => $value) {
            $html .= '<option value=">' . $key . '">' . $value . '</option>'; 
          }
          
          $this->condition_html = $html;
          
          $html = '';
          $arr = array();
          //Now the countries
          
            // Get the country list from Woo....
          foreach (WC()->countries->get_shipping_countries() as $id => $value) :
              $arr[esc_attr($id)] = esc_js($value);
          endforeach;             
          
          //And create the HTML
          foreach ($arr as $key => $value) {
            $html .= '<option value=">' . $key . '">' . $value . '</option>'; 
          }

          $this->country_html = $html;          
          
        }       


        //Creates the HTML options for the selected
        
        function create_dropdown_html($arr){
          
          $arr = array();
          

          
          $this->condition_html = html;
        }
                    
        /**
         * Create dropdown options 
         */
        function create_dropdown_options() {
        
          $options = array();
        
          
            // Get the country list from Woo....
          foreach (WC()->countries->get_shipping_countries() as $id => $value) :
              $options['country'][esc_attr($id)] = esc_js($value);
          endforeach;
          
          // Now the conditions - cater for language & woo
            $option['condition']['weight'] = sprintf(__( 'Weight (%s)', 'JEM_DOMAIN' ), get_option('woocommerce_weight_unit'));
            $option['condition']['price'] = sprintf(__( 'Total (%s)', 'JEM_DOMAIN' ), get_woocommerce_currency_symbol());
            
            return $options;
        } 

        
        /**
        * This saves all of our custom table settings
        */
        function process_custom_settings() {
          
          //Arrays to hold the clean POST vars
          $keys =array();
          $zone_name =array();
          $condition = array();
          $countries = array();
          $min = array();
          $max = array();
          $shipping = array();
          
          
          //Take the POST vars, clean em up and put thme in nice arrays 
          if ( isset( $_POST[ 'key'] ) ) $keys = array_map( 'wc_clean', $_POST['key'] );
          if ( isset( $_POST[ 'zone-name'] ) ) $zone_name = array_map( 'wc_clean', $_POST['zone-name'] );
          if ( isset( $_POST[ 'condition'] ) ) $condition = array_map( 'wc_clean', $_POST['condition'] );
          //no wc_clean as multi-D arrays
          if ( isset( $_POST[ 'countries'] ) ) $countries = $_POST['countries'] ;
          if ( isset( $_POST[ 'min'] ) ) $min = $_POST['min'] ;
          if ( isset( $_POST[ 'max'] ) ) $max = $_POST['max'] ;
          if ( isset( $_POST[ 'shipping'] ) ) $shipping = $_POST['shipping'] ;

          //todo - need to add soem validation here and some error messages???
          
        
          
          //Master var of options - we keep it in one big bad boy
          $options = array();
          
          //OK we need to loop thru all of them - the keys will help us here - process by key
          foreach($keys as $key => $value){
            
            
            //we only process it if all the fields are set
            if(
              empty($zone_name[$key]) ||
              empty($condition[ $key ]) ||
              empty($countries[ $key ])
              ){
              //something is empty so don't save it
              continue;
              
            }
            
            
            //Get the zone name - this is our main key
            $name =  $zone_name[$key];
            
            
            //Going to add the rates now.
            //before we do that check if we have any empty rows and delete them
            $obj =array();
            foreach ($min[ $key ] as $k => $val) {
                  if(
                  empty($min[ $key ][$k]) &&
                  empty($max[ $key ][$k]) &&
                  empty($shipping[ $key][$k]) 
                )
                {
                  unset($min[ $key ][$k]);
                  unset($max[ $key ][$k]);
                  unset($shipping[ $key ][$k]);
                }
                else {
                  //add it to the object array
                  $obj[] = array("min" => $min[ $key ][ $k], "max" => $max[ $key ][ $k], "shipping" => $shipping[ $key ][ $k]);                 
                }
                  
            }   
            
            //OK now lets sort or array of objects!!
            usort($obj, 'self::cmp');
            
            //create the array to hold the data       
            $options[ $name ] = array();
            
            $options[ $name ][ 'condition'] = $condition[ $key ]; 
            $options[ $name ][ 'countries'] = $countries[ $key ]; 
            $options[ $name ][ 'min'] = $min[ $key ]; 
            $options[ $name ][ 'max'] = $max[ $key ]; 
            $options[ $name ][ 'shipping'] = $shipping[ $key ]; 
            $options[ $name ][ 'rates'] = $obj;     //This is the sorted rates object!

          }

          //SAVE IT
          update_option( $this->option_key, $options ); 
        } 
        
        //Comparision function for usort of associative arrays
        function cmp($a, $b){
          return $a['min'] - $b['min'];
        }

}