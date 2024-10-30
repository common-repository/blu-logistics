<?php
/**
 * Admin View: Email Template Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<p><b><?php echo $input['blu_your_name']?></b> would like to use the blu Logistics module on Woocommerce.</p>

<p>Details of his/her store.</p>

<p>Company Name : <?php echo $input['blu_company_name']?></p>

<p>Customer Name : <?php echo $input['blu_your_name']?></p>

<p>Customer Email : <?php echo $input['blu_your_email']?></p>

<p>Contact Number : <?php echo $input['blu_contact_number']?></p>

<p>Website : <?php echo $input['blu_website']?></p>

<p>Comments/Requests : <?php echo $input['blu_comments']?></p>
