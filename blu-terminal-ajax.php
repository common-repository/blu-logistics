<?php

/**
* search response on google map
**/

if($_REQUEST["search_radius"])
{
	$location['lat'] = '';
	$location['long'] =  '';
	if($_REQUEST["search_location"]){
		// We get the JSON results from this request
		$geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($_REQUEST["search_location"]).'');
		// We convert the JSON to an array
		$geo = json_decode($geo, true);

		// If everything is cool
		if ($geo['status'] = 'OK') {
		  $location['lat'] = $geo['results'][0]['geometry']['location']['lat'];
		  $location['long'] =  $geo['results'][0]['geometry']['location']['lng'];
		}
	}

	echo json_encode($location);
	exit();
}

/**
* set session when bluPort Parcel is selected
**/
if($_REQUEST['change_address'])
{
	session_start();
	$_SESSION['order_address'] = $_REQUEST['address'];
	$_SESSION['order_address_location'] = $_REQUEST['location'];

	$selected_location = explode('||', $_REQUEST['location']);
	$selected_location[2] = str_replace("SG","Singapore",$selected_location[2]);
	unset($selected_location[4]);

	foreach ($selected_location as $key) {
		if($key)
			$display_location .= $key.'<br>';
	}
	$label .= '<p class="address_title"> Selected bluPort Parcel Terminal : </p>';
	$label .= '<p class="selected_address">'.$display_location.'</p>';

	echo $label;
	die();
}

/**
* set session when Ship to different address is selected
**/
if($_REQUEST['action'] == 'shipping_address'){
	$_SESSION['if_ship_to_different_address'] = '1';
}


?>