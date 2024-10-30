<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<title>Blu Logistics</title>


<?php wp_head(); ?>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

   <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('', __FILE__ ) ?>/admin/css/blu.min.css">
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('', __FILE__ )?>/admin/css/blu.common.css">

</head>


<?php 

$BluApi = new BluAPI();
$get_locations = $BluApi->blsmGetLocationAPI();

$mapLocation = json_encode($get_locations, JSON_HEX_APOS); // json_encode($var, JSON_HEX_APOS)
$count = 1;

?>

<body class="blu-remove-footer">

	<div class="row" id="bluport-map">
			<div class="col-md-12 col-sm-12 search_tab">
				<h2>
					Nearest location search by postal code:
					<input type="text" name="search_location" id="search_location">
					<input type="submit" name="search" class="btn button-small search" value="Search" id="search_location_btn">
					<button id="default-location" class="btn button button-small pull-right nearest"><span>Select bluPort</span></button> 
					<input type="hidden" name="latitude" id="latitude">
					<input type="hidden" name="longitude" id="longitude">
				</h2>

			</div>

			<div class="col-md-8 col-sm-8 no-padding-right">
				<div id="map_area">
				    <div id="map" style="width: 100%; height: 550px;"></div>
				</div>
			</div>
			
			<div class="col-md-4 col-sm-4 no-padding-left">
				<div class="sidebar">
					<div class="header">
						<div class="logo"></div>
					</div>

				<div class="address-list">
					<?php foreach ((array) $get_locations as $key => $value) {
							$address = $name = $location = ''; 
							if(($count % 2) == 0) $class = 'address-even';
							else $class = 'address-odd';
					?>
					<div class="map-address <?php echo $class ?> mapmarker" data-marker-id="<?php echo $value['apt_id']?>" data-lat="<?php echo $value['latitude']?>" data-lng="<?php echo $value['longitude']?>" id="location_<?php echo $value['apt_id']?>">
						<?php if(isset($value['name'])){ 
									if(is_array($value['name'])){ 
										foreach((array) $value['name'] as $index => $val ){   
										 		$name .= $val.'<br>';
									 	} 
									}
									else 
										$name .= $value['name'];
							} ?>
						<p class="map_location"><input type="radio" name="map_location" value="<?php echo $value['name']; ?>" id="map_location_<?php echo $value['apt_id']?>"> 
									<?php echo $name; ?></p>

							<!-- set tag for location description -->
							<?php if($value['loc_desc']){
									if(is_array($value['loc_desc'])){ 
										foreach((array) $value['loca_desc'] as $index => $val ){ 
											$location .= $val;?> 
										<p class="location-desc"><?php echo $val; ?></p> 
									<?php } } else { 
											$location = $value['loc_desc'];?>
										<p class="location-desc"><?php echo $value['loc_desc']; ?></p>
									<?php } ?>
							<?php } ?>

							<!-- set tag for address 1 -->
							<?php if(isset($value['address1'])){ 
									if(is_array($value['address1'])){ 
										foreach((array) $value['address1'] as $index => $val ){   
										 		$api_address = $address .= $val.'<br>';
									 	} 
									}
									else
										$api_address = $address .= $value['address1']; 
							} ?>

							<!-- set tag for address 2 -->
							<?php if(isset($value['address2'])){ 
									if(is_array($value['address2'])){ 
										foreach((array) $value['address2'] as $index => $val ){  
											$api_address = $address .= $val.'<br>'; 
									 	}
									} 
									else
											$api_address = $address .= $value['address2'];
							} ?>

							<input type="hidden" name="address" id="map_address_<?php echo $value['apt_id']?>" value="<?php echo $address;?>">

							<?php if($value['country']) $address .= ' '.$value['country']; ?>
							<?php if($value['zip_code']) $address .= ', '.$value['zip_code']; ?>
							<input type="hidden" name="selected_address" id="selected_map_address_<?php echo $value['apt_id']?>" value="<?php echo $name.' '.$location.' '.$address?>">
							<input type="hidden" name="selected_address_location" id="selected_map_location_<?php echo $value['apt_id']?>" value="<?php echo $name.'||'.$location.'||'.$api_address .'||'.$value['country'].' '.$value['zip_code'].'||'.$value['bluport_id']?>">
							<input type="hidden" name="country" id="map_country_<?php echo $value['apt_id']?>" value="<?php echo $value['country'];?>">
							<input type="hidden" name="zip_code" id="map_zip_code_<?php echo $value['apt_id']?>" value="<?php echo $value['zip_code'];?>">
							<p class="address"><?php echo $address; ?></p> 
					</div>
					<?php $count++; } ?>
			</div>
		</div>
	</div>
	</div>

<script type="text/javascript">
	var mapLocation = '<?php echo $mapLocation ?>';
</script>

<?php get_footer(); ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAgwjkXJVe2r-XejERu9qW9dqd-U6YQnkg" async defer
          type="text/javascript"></script>
</body>
</html>