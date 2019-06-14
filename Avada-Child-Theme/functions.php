<?php
ob_start();
//error_reporting(0);

function theme_enqueue_styles() {
	
	//wp_enqueue_style( 'avada-parent-stylesheet', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'avada-stylesheet' ) );
}

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );



add_action( 'wp_enqueue_scripts', 'load_old_jquery_fix', 100 );

function load_old_jquery_fix() {
	
	 
    if ( ! is_admin() ) {
        //wp_deregister_script( 'jquery' );
		 wp_enqueue_script( 'jquery', ( "//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js" ), false, '1.11.3' );
		
        //wp_register_script( 'jquery', ( "//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js" ), false, '1.11.3' );
		//wp_enqueue_script( 'jquery' );
        
    }
}

function avada_lang_setup() {
	$lang = get_stylesheet_directory() . '/languages';
	load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

// count word 
function limit_words($string, $word_limit)
	{
		$words = explode(" ",$string);
		return implode(" ",array_splice($words,0,$word_limit));
	}

// login and logout link 
add_filter( 'wp_nav_menu_items', 'add_loginout_link', 10, 2 );
function add_loginout_link( $items, $args ) {
//	echo "=================>";
//	echo "<pre>";
//	print_r($args);

/*	global $woocommerce;
    $pro_items = $woocommerce->cart->get_cart();

//$items .= count($pro_items)."-----".$args->menu->name;
		if((count($pro_items) > 0) && $args->menu->name == 'Header menu'){	
		
			$items .= '<li class="fusion-custom-menu-item fusion-menu-cart custom-fusion-main-menu-cart fusion-active-cart-icons fusion-last-menu-item">
	<a class="fusion-main-menu-icon fusion-main-menu-icon-active" href="'.$woocommerce->cart->get_cart_url().'"></a>
	<div class="fusion-custom-menu-item-contents fusion-menu-cart-items" style="left: auto; right: 0px;">';	

			foreach($pro_items as $item => $values) { 
			
				$_product = $values['data']->post; 
				 $thumb_image =  wp_get_attachment_image_src(get_post_thumbnail_id( $values['product_id']),'thumbnail');
				 $product_name = strip_tags($_product->post_title);
				// $thumbnail_id = ( $_product->variation_id && has_post_thumbnail( $_product->variation_id )  ) ? $_product->variation_id : $_product->id;
//				 '.get_the_post_thumbnail( $thumbnail_id, 'recent-works-thumbnail' ).'
				 
				 
				 $items .= '<div class="fusion-menu-cart-item"><a href="'.get_permalink($_product->id).'"><img width="66" height="31" src="'.$thumb_image[0].'" class="attachment-recent-works-thumbnail size-recent-works-thumbnail wp-post-image" alt="'.$product_name.'" title="">
							<div class="fusion-menu-cart-item-details"><span class="fusion-menu-cart-item-title">'.$product_name.'</span><span class="fusion-menu-cart-item-quantity">'.$values['quantity'].' x <span class="amount">'.wc_price($values['data']->price, $args).'</span></span></div></a></div>';
		 	} 
			
			$items .= '<div class="fusion-menu-cart-checkout"><div class="fusion-menu-cart-link"><a href="'.$woocommerce->cart->get_cart_url().'"> View Cart</a></div><div class="fusion-menu-cart-checkout-link"><a href="'.$woocommerce->cart->get_checkout_url().'">Checkout</a></div></div></div></li>';
			
	}*/

    if (is_user_logged_in() && $args->menu->name == 'Footer Nav') {
		
        $items .= '<li><a href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'">My Account</a>
		<ul class="sub-menu"><li><a href="'.wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ).'">Logout</a></li></ul></li>';
		
    }elseif (!is_user_logged_in() && $args->menu->name == 'Footer Nav') {
		
        $items .= '<li><a href="'. get_permalink( get_option('woocommerce_myaccount_page_id') ) .'">Dealer Log In</a></li>';

    }	

	
    return $items;
}

function state_list_by_country(){

	global $woocommerce;
	$state_obj   = new WC_Countries();
	
	$cname = trim($_POST['cname']);
	$state_list = $state_obj->get_states( $cname );
	
	if(count($state_list) > 0 && !empty($state_list)){

		echo '<select name="billing_state"><option value="">Please select region, state or province</option>';
		foreach($state_list as $state){
	
			echo "<option value=\"". $state."\">" . $state ."</option>";
		}
		
		echo '</select>';
	}else{
		
		echo '<input type="text" name="billing_state" value="">';	
	}
	
	die(); // to prevent concatent zero in return value
}

add_action('wp_ajax_state_list_by_country', 'state_list_by_country'); // Call when user logged in
add_action('wp_ajax_nopriv_state_list_by_country', 'state_list_by_country'); // Call when user in not logged in

////////////////////////////////////// custom search for dealer according zipcode radius /////////////////////////////////////////
// constants for setting the $units data member
define('_UNIT_MILES', 'm');
define('_UNIT_KILOMETERS', 'k');

// constants for passing $sort to get_zips_in_range()
define('_ZIPS_SORT_BY_DISTANCE_ASC', 1);
define('_ZIPS_SORT_BY_DISTANCE_DESC', 2);
define('_ZIPS_SORT_BY_ZIP_ASC', 3);
define('_ZIPS_SORT_BY_ZIP_DESC', 4);
	  
// constant for miles to kilometers conversion
define('_M2KM_FACTOR', 1.609344);

$last_error = "";            // last error message set by this class
$last_time = 0;              // last function execution time (debug info)
$units = _UNIT_MILES;        // miles or kilometers
$decimals = 2;               // decimal places for returned distance

function calculate_mileage($lat1, $lat2, $lon1, $lon2) {

	  // used internally, this function actually performs that calculation to
	  // determine the mileage between 2 points defined by lattitude and
	  // longitude coordinates.  This calculation is based on the code found
	  // at http://www.cryptnet.net/fsp/zipdy/
	   
	  // Convert lattitude/longitude (degrees) to radians for calculations
	  $lat1 = deg2rad($lat1);
	  $lon1 = deg2rad($lon1);
	  $lat2 = deg2rad($lat2);
	  $lon2 = deg2rad($lon2);
	  
	  // Find the deltas
	  $delta_lat = $lat2 - $lat1;
	  $delta_lon = $lon2 - $lon1;
	
	  // Find the Great Circle distance 
	  $temp = pow(sin($delta_lat/2.0),2) + cos($lat1) * cos($lat2) * pow(sin($delta_lon/2.0),2);
	  $distance = 3956 * 2 * atan2(sqrt($temp),sqrt(1-$temp));
	
	  return $distance;
}

function chronometer()  {

	// chronometer function taken from the php manual.  This is used primarily
	// for debugging and anlyzing the functions while developing this class.  
	global $last_time;
	$now = microtime(TRUE);  // float, in _seconds_
	$now = $now + time();
	$malt = 1;
	$round = 7;
	
	if ($last_time > 0) {
	   /* Stop the chronometer : return the amount of time since it was started,
	   in ms with a precision of 3 decimal places, and reset the start time.
	   We could factor the multiplication by 1000 (which converts seconds
	   into milliseconds) to save memory, but considering that floats can
	   reach e+308 but only carry 14 decimals, this is certainly more precise */
	  
	   $retElapsed = round($now * $malt - $last_time * $malt, $round);
	  
	   $last_time = $now;
	  
	   return $retElapsed;
	} else {
	   // Start the chronometer : save the starting time
	
	   $last_time = $now;
	  
	   return 0;
	}
}

function get_zips_in_rangecustomer($lat,$long, $range, $sort, $include_base){
	
		  global $wpdb, $last_time, $units, $decimals;
			
		 $lat=(float)$lat;
		 $long=(float)$long;
		 // $details = $this->get_zip_point($zip);
		   // base zip details
		 $details=array($lat,$long);
		 
		//print_r($details);
		  if ($details == false) return false;
			  	
		//echo cos($details[0]);
		
		  $lat_range = $range/69.172;
		  $lon_range = abs($range/(cos($details[0]) * 69.172));
		  $min_lat = number_format($details[0] - $lat_range, "4", ".", "");
		  $max_lat = number_format($details[0] + $lat_range, "4", ".", "");
		  $min_lon = number_format($details[1] - $lon_range, "4", ".", "");
		  $max_lon = number_format($details[1] + $lon_range, "4", ".", "");
		  
		  $return = array(); 
		  
		   if($max_lon<=$min_lon){
			  
			  $sql = "SELECT 
						u1.user_id
						FROM
						wp_usermeta u1
							INNER JOIN
						wp_usermeta u2 ON u1.user_id = u2.user_id
							AND u1.meta_key = 'latitude' AND (u1.meta_value BETWEEN '$min_lat' AND '$max_lat')
						
						INNER JOIN
						wp_usermeta u3 ON u1.user_id = u3.user_id
							AND u3.meta_key = 'longitude' AND (u3.meta_value BETWEEN '$min_lon' AND '$max_lon')
						GROUP BY u1.user_id";

			} else {
				
			  $sql = "SELECT 
						u1.user_id
						FROM
						wp_usermeta u1
							INNER JOIN
						wp_usermeta u2 ON u1.user_id = u2.user_id
							AND u1.meta_key = 'latitude' AND (u1.meta_value BETWEEN '$min_lat' AND '$max_lat')
						
						INNER JOIN
						wp_usermeta u3 ON u1.user_id = u3.user_id
							AND u3.meta_key = 'longitude' AND (u3.meta_value BETWEEN '$max_lon' AND '$min_lon')
						GROUP BY u1.user_id";

			}
			//echo $sql;
			$results = $wpdb->get_results($sql);
			
			foreach($results as $row ){
				
					// loop through all 40 some thousand zip codes and determine whether
					// or not it's within the specified range.
					//print_r($row);
					//echo $row->user_id;
					//echo $row->user_login."<br>";
					
					$dist = calculate_mileage($details[0],$row->latitude,$details[1],$row->longitude);
					if ($units == _UNIT_KILOMETERS) $dist = $dist * _M2KM_FACTOR;
					
					if ($dist <= $range) {
					   	
							$return[str_pad($row->user_id, 4, "0", STR_PAD_LEFT)] = round($dist, $decimals);
					}else{
						
						    $return[str_pad($row->user_id, 4, "0", STR_PAD_LEFT)] = round($dist, $decimals);
					}
			 		
					//mysql_free_result($row);	
			}

		  // sort array
		  switch($sort)
		  {
			 case _ZIPS_SORT_BY_DISTANCE_ASC:
				asort($return);
				break;
				
			 case _ZIPS_SORT_BY_DISTANCE_DESC:
				arsort($return);
				break;
				
			 case _ZIPS_SORT_BY_ZIP_ASC:
				ksort($return);
				break;
				
			 case _ZIPS_SORT_BY_ZIP_DESC:
				krsort($return);
				break; 
		  }
		  
		  $last_time = chronometer();
		  
		  if (empty($return)) return false;
		  return $return;
			
	}


function dealer_locator_by_zipcode_radius($zipcode){
	
		global $last_time;
		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$zipcode."&sensor=false";
		$details=file_get_contents($url);
		$result = json_decode($details,true);
		
		$lat=$result['results'][0]['geometry']['location']['lat'];
			
		$lng=$result['results'][0]['geometry']['location']['lng'];
			
		$zips = get_zips_in_rangecustomer($lat,$lng,25, _ZIPS_SORT_BY_DISTANCE_ASC, true);

		$return=array();

		if(is_array($zips)){
			
			foreach ($zips as $key => $value) {
				  
			$return[] = $key;
			
			}
		}
		
		return $return;
}

function dealer_paginate($start,$limit,$total,$filePath,$otherParams) {
	global $lang;
	/*echo "Limit=>".$limit;
	echo "<br>Total=>".$total;die;*/
	$allPages = ceil($total/$limit);

	$currentPage = floor($start/$limit) + 1;

	$pagination = "";
	if ($allPages>10) {
		$maxPages = ($allPages>9) ? 9 : $allPages;

		if ($allPages>9) {
			if ($currentPage>=1&&$currentPage<=$allPages) {
				$pagination .= ($currentPage>4) ? " ... " : " ";

				$minPages = ($currentPage>4) ? $currentPage : 5;
				$maxPages = ($currentPage<$allPages-4) ? $currentPage : $allPages - 4;

				for($i=$minPages-4; $i<$maxPages+5; $i++) {
					$pagination .= ($i == $currentPage) ? "<span class='active-page'>".$i."</span>": "<a href='javascript:void(0)'  rel=\"".(($i-1)*$limit)."\" class='tahoma11boldlink'>".$i."</a> ";
				}
				$pagination .= ($currentPage<$allPages-4) ? " ... " : " ";
			} else {
				$pagination .= " ... ";
			}
		}
	} else {
		for($i=1; $i<$allPages+1; $i++) {
			$pagination .= ($i==$currentPage) ? "<span class='active-page'>".$i."</span>" : "<a href='javascript:void(0)'   rel=\"".(($i-1)*$limit)."\" class='tahoma11boldlink'>".$i."</a> ";
		}
	}

	echo $pagination;
	?>
	<script>
		jQuery(".tahoma11boldlink").click(function() {
					// getting the value that user typed
					var category = document.getElementById('category').value;
					var country = document.getElementById('country').value;
					var zipcode = document.getElementById('zipcode').value;
					
					var start=jQuery(this).attr('rel');
					
				 	//alert(start+"---"+country);
					//jQuery('#listOfDealer').html('');
					jQuery('#results').html('<img style="text-align:center;" src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif">');

						// ajax call
						jQuery.ajax({
							type: 'POST', 
							url: '../wp-admin/admin-ajax.php', 
							data: {'action': 'dealer_locator_list', 'category':category,'country':country,'zipcode':zipcode,'start':start},
							success: function(data){ 
								//alert(data);
								jQuery('#results').html(data);
							}
						});  
					
					//return false;
				});
	</script>	
<?php }

function dealer_locator_list(){
    global $wpdb;
	$q_limit = 9;
	
	$wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
	$table1 = $wpdb->prefix.'usermeta'; 
	$table2 = $wpdb->prefix.'user_profile_image'; 
	$category_id = trim($_POST['category']);
	$country_name = trim($_POST['country']);
	$zipcode = trim($_POST['zipcode']);
	$st = trim($_POST['start']);
	
	if( isset($st) && !empty($st))
		$start = $st;
	else
		$start = 0;
	
	$query_dealer = "SELECT 
					u1.user_id
					FROM
					$table1 u1
					INNER JOIN
					$table1 u3 ON u1.user_id = u3.user_id
					AND u3.meta_key = 'wp_capabilities' AND u3.meta_value like '%dealer%'
					INNER JOIN $table1 u15 ON u1.user_id = u15.user_id AND u15.meta_key = 'wp-approve-user' AND u15.meta_value = '1'";
					//INNER JOIN $table1 u7 ON u1.user_id = u7.user_id AND u7.meta_key = 'is_activated' AND u7.meta_value = '1'
	
	if(!empty($category_id)){
		
		$query_dealer .= " INNER JOIN
						  $table1 u2 ON u1.user_id = u2.user_id
							AND u1.meta_key = 'category' AND FIND_IN_SET('$category_id', u1.meta_value)";
	}
	
	if(!empty($country_name)){
		
		$query_dealer .= " INNER JOIN
						  $table1 u5 ON u1.user_id = u5.user_id
							AND ((u5.meta_key = 'billing_country' AND u5.meta_value = '$country_name')
							OR (u5.meta_key = 'shipping_country' AND u5.meta_value = '$country_name'))";
	}
	
	if(!empty($zipcode)){
		
//		$user_arr = dealer_locator_by_zipcode_radius($zipcode);
//	
//		if(count($user_arr) > 0){
//			
//			$resutl = implode(',',$user_arr);
//			
//			$query_dealer .= " INNER JOIN
//						  	$table1 u4 ON u1.user_id = u4.user_id
//								AND u4.user_id in($resutl)";
//		}else{
			
			$query_dealer .= " INNER JOIN
						  $table1 u4 ON u1.user_id = u4.user_id
								AND ((u4.meta_key = 'billing_postcode' AND u4.meta_value = '$zipcode')
								OR (u4.meta_key = 'shipping_postcode' AND u4.meta_value = '$zipcode'))";
//		}
						
	}	
	
	$query_dealer .= " GROUP BY u1.user_id";
	$total_dealer_list = $wpdb->get_results($query_dealer);
	$rows = count($total_dealer_list);
	
	$query_dealer .= " LIMIT $start , $q_limit";
	
	$dealer_list = $wpdb->get_results($query_dealer);
	$no_rows = count($dealer_list);
	
    if($no_rows > 0){
		foreach($dealer_list as $data){
			
			$dealer_image = $wpdb->get_results("select user_profile_image from $table2 where user_id='".$data->user_id."' and image_no=1");
			
			if($no_rows % 3 == 0){
				
				$class = "last_box";
			}else{
				
				$class = "";
			}
			?>
				<div class="profile_box <?php echo $class;?>">
                
                      <div class="profile_box_thum"> 
                      <a href="<?php echo get_site_url(); ?>/dealer-profile?id=<?php echo $data->user_id;?>">
                      
                  <?php if(count($dealer_image) > 0 && file_exists($wp_root_path.$dealer_image[0]->user_profile_image)){?>
                    <img src="<?php echo get_site_url().'/'.$dealer_image[0]->user_profile_image ?>" alt="User Profile Image" width="266" height="112">
                  <?php }else{?>
                    <img src="<?php echo get_stylesheet_directory_uri();?>/images/dealerprofilepicmain.jpg" alt="User Profile Image" width="266" height="112"/>
                  <?php }?>
                        </a>
                       </div>
                      <div class="profile_box_cnt">
                        <div class="profile_box_cnt_left"><a href="<?php echo get_site_url(); ?>/dealer-profile?id=<?php echo $data->user_id;?>"><?php echo limit_words(get_user_meta($data->user_id, 'billing_company', true),5); ?></a></div>
                        <div class="profile_box_cnt_right">
                          <p style="height:40px;"><?php echo limit_words(get_user_meta($data->user_id, 'description', true),12);?></p>
                          
                        <?php /*?>  <span class="rmore"><a href="<?php echo get_site_url(); ?>/dealer-profile?id=<?php echo $data->user_id;?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/readmore.png"></a></span><?php */?> </div>
                        <div class="clear"></div>
                      </div>
                      
                </div>
<?php }?>
	
    <div style="clear:both">Page: <span class="page_active"><?php  dealer_paginate($start, $q_limit, $rows, get_site_url(), "");?></span></div>
	
<?php }else{
		
		echo "No results found";	
	}
	
	die(); // to prevent concatent zero in return value
}


add_action('wp_ajax_dealer_locator_list', 'dealer_locator_list'); // Call when user logged in
add_action('wp_ajax_nopriv_dealer_locator_list', 'dealer_locator_list'); // Call when user in not logged in

function customer_paginate($start,$limit,$total,$filePath,$otherParams) {
	global $lang;
	/*echo "Limit=>".$limit;
	echo "<br>Total=>".$total;die;*/
	$allPages = ceil($total/$limit);

	$currentPage = floor($start/$limit) + 1;

	$pagination = "";
	if ($allPages>10) {
		$maxPages = ($allPages>9) ? 9 : $allPages;

		if ($allPages>9) {
			if ($currentPage>=1&&$currentPage<=$allPages) {
				$pagination .= ($currentPage>4) ? " ... " : " ";

				$minPages = ($currentPage>4) ? $currentPage : 5;
				$maxPages = ($currentPage<$allPages-4) ? $currentPage : $allPages - 4;

				for($i=$minPages-4; $i<$maxPages+5; $i++) {
					$pagination .= ($i == $currentPage) ? $i: "<a href='javascript:void(0)'  rel=\"".(($i-1)*$limit)."\" class='custtahoma11boldlink'>".$i."</a> ";
				}
				$pagination .= ($currentPage<$allPages-4) ? " ... " : " ";
			} else {
				$pagination .= " ... ";
			}
		}
	} else {
		for($i=1; $i<$allPages+1; $i++) {
			$pagination .= ($i==$currentPage) ? $i: "<a href='javascript:void(0)'   rel=\"".(($i-1)*$limit)."\" class='custtahoma11boldlink'>".$i."</a> ";
		}
	}

	echo $pagination;
	?>
	<script>
		jQuery(".custtahoma11boldlink").click(function() {

					var start=jQuery(this).attr('rel');
					var modelclass = document.getElementById('modelclass').value;
					var modelmake = document.getElementById('modelmake').value;
					var zipcode = document.getElementById('zipcode').value;
					//alert(category);
					jQuery('#listOfCustomer').html('<img style="text-align:center;" src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif">');
					jQuery.ajax({
						type: 'POST', 
						url: '../wp-admin/admin-ajax.php', 
						data: {'action': 'customer_profile_list', 'modelclass':modelclass,'modelmake':modelmake,'zipcode':zipcode,'start':start},
						success: function(data){ 
						//alert(data);
						jQuery('#listOfCustomer').html(data);
					}
				});
				});
	</script>	
<?php }

function customer_profile_list(){
    global $wpdb;
	$q_limit = 1;
	$wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
	$table1 = $wpdb->prefix.'usermeta'; 
	$table2 = $wpdb->prefix.'user_profile_image'; 
	$modelclass = trim($_POST['modelclass']);
	$modelmake = trim($_POST['modelmake']);
	$zipcode = trim($_POST['zipcode']);
	$st = trim($_POST['start']);
	
	if( isset($st) && !empty($st))
		$start = $st;
	else
		$start = 0;
		
	$query_customer = "SELECT 
						u1.user_id
						FROM
						$table1 u1
						INNER JOIN
						$table1 u2 ON u1.user_id = u2.user_id
						AND u2.meta_key = 'wp_capabilities' AND u2.meta_value like '%member%'
						INNER JOIN $table1 u6 ON u1.user_id = u6.user_id 
						AND u6.meta_key = 'is_activated' AND u6.meta_value = 1";
					
				
	if(!empty($modelclass)){
		
		$query_customer .= " INNER JOIN
							 $table1 u3 ON u1.user_id = u3.user_id
							 AND u3.meta_key = 'modelclass'	AND u3.meta_value like '%".$modelclass."%' ";
	}			
	
	if(!empty($modelmake)){
		
		$query_customer .= " INNER JOIN
							 $table1 u4 ON u1.user_id = u4.user_id
							 AND u4.meta_key = 'modelmake'	AND u4.meta_value like '%".$modelmake."%' ";
	}
	
	
	if(!empty($zipcode)){
		
		$query_customer .= " INNER JOIN
							 $table1 u5 ON u1.user_id = u5.user_id
							 AND ((u5.meta_key = 'billing_postcode'	AND u5.meta_value = '".$zipcode."')
							 OR (u5.meta_key = 'shipping_postcode' AND u5.meta_value = '".$zipcode."''))";
	}
	
	$query_customer .= " GROUP BY u1.user_id";
	$total_customer_list = $wpdb->get_results($query_customer);
	$rows = count($total_customer_list);
	
	$query_customer .= " LIMIT $start , $q_limit";
	
	$customer_list = $wpdb->get_results($query_customer);
	
    if(count($customer_list)>0){
		foreach($customer_list as $data){
			
			$customer_image = $wpdb->get_results("select user_profile_image from $table2 where user_id='".$data->user_id."' and image_no=1");
			
			if($customer_list % 3 == 0){
				
				$class = "last_box";
			}else{
				
				$class = "";
			}
			
			$user_info = get_userdata($data->user_id);

			?>
          <div class="profile_box <?php echo $class;?>">
          <div class="profile_box_thum"> 
          <a href="<?php echo get_site_url(); ?>/member-profile?id=<?php echo $data->user_id;?>">
          <?php if(count($customer_image) > 0 && file_exists($wp_root_path.$customer_image[0]->user_profile_image)){?>
            <img src="<?php echo get_site_url().'/'.$customer_image[0]->user_profile_image ?>" alt="Customer Profile Image" height="112" width="266">
          <?php }else{?>
            <img src="<?php echo get_stylesheet_directory_uri();?>/images/comingsoon.gif" alt="Customer Profile Image" height="112" width="266"/>
          <?php }?>
            </a>
           </div>
          <div class="profile_box_cnt">
            <div class="profile_box_cnt_left"><a href="<?php echo get_site_url(); ?>/member-profile?id=<?php echo $data->user_id;?>"><?php echo $user_info->first_name." ".$user_info->last_name; ?></a></div>
            <div class="profile_box_cnt_right">
              <p style="height:40px;"><?php echo limit_words(get_user_meta($data->user_id, 'description', true),12);?></p>
              <span class="rmore"><a href="<?php echo get_site_url(); ?>/member-profile?id=<?php echo $data->user_id;?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/readmore.png"></a></span> </div>
            <div class="clear"></div>
          </div>
        </div>
<?php }?>
<div style="clear:both">Page: <span class="page_active"><?php  customer_paginate($start, $q_limit, $rows, get_site_url(), "");?></span></div>
<?php }else{
		
		echo "No results found";	
	}
	
	die(); // to prevent concatent zero in return value
}


add_action('wp_ajax_customer_profile_list', 'customer_profile_list'); // Call when user logged in
add_action('wp_ajax_nopriv_customer_profile_list', 'customer_profile_list'); // Call when user in not logged in

// save crop image for front end
function save_user_crop_image(){

	global  $wpdb;
	
	$user = wp_get_current_user();
	$cust_id = $user->ID;
	

	$fileName = $cust_id .'one_'.time().'.jpg';
	
	//$imagepath = '/customer_image/'.$fileName;
	
	$baseAttID = $_REQUEST['imgno'];
	
// save image 	

	$upload_dir = wp_upload_dir();
	
	$url = $upload_dir['basedir'].'/user_crop_image/temp/'.$fileName;
	
	// remove the base64 part
	$base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $_POST['image']);
	$base64 = base64_decode($base64);

	$source = imagecreatefromstring($base64); // create

	imagejpeg($source, $url, 100); // save image
	
	
	$myrows = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."user_crop_image where user_id=$cust_id and user_crop_image_no=$baseAttID" );
	

	 if(count($myrows) > 0){
		
		$wpdb->update( 
			'wp_user_crop_image', 
			array( 
				'user_id' => $cust_id,  // string
				'user_crop_image' => $fileName,  // integer (number) 
				'user_crop_image_no' => $baseAttID,
			), 
			array( 'id' => $myrows[0]->id )
		);	
		
		//echo "updated";
		
			 
	 }else{
	 
		$wpdb->insert( 
			'wp_user_crop_image', 
			array( 
				'user_id' => $cust_id, 
				'user_crop_image' => $fileName,
				'user_crop_image_no' => $baseAttID, 
			) 
		);
		
		//echo "inserted";	
			
		
	 }
	
	echo $baseAttID;
	
	die(); // to prevent concatent zero in return value
}

add_action('wp_ajax_save_user_crop_image', 'save_user_crop_image'); // Call when user logged in
add_action('wp_ajax_nopriv_save_user_crop_image', 'save_user_crop_image'); // Call when user in not logged in


// save crop image for admin user profile
function save_user_crop_image_admin(){

	global  $wpdb;

	$cust_id = $_REQUEST['userID'];

	$fileName = $cust_id .'one_'.time().'.jpg';
	
	//$imagepath = '/customer_image/'.$fileName;
	
	$baseAttID = $_REQUEST['imgno'];
	
// save image 	

	$upload_dir = wp_upload_dir();
	
	$url = $upload_dir['basedir'].'/user_crop_image/temp/'.$fileName;
	
	// remove the base64 part
	$base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $_POST['image']);
	$base64 = base64_decode($base64);

	$source = imagecreatefromstring($base64); // create

	imagejpeg($source, $url, 100); // save image
	
	$myrows = $wpdb->get_results( "SELECT *  FROM ".$wpdb->prefix."user_crop_image where user_id=$cust_id and user_crop_image_no=$baseAttID" );

	 if(count($myrows) > 0){
		
		$wpdb->update( 
			'wp_user_crop_image', 
			array( 
				'user_id' => $cust_id,  // string
				'user_crop_image' => $fileName,  // integer (number) 
				'user_crop_image_no' => $baseAttID,
			), 
			array( 'id' => $myrows[0]->id )
		);	
		
		//echo "updated";
		
			 
	 }else{
	 
		$wpdb->insert( 
			'wp_user_crop_image', 
			array( 
				'user_id' => $cust_id, 
				'user_crop_image' => $fileName,
				'user_crop_image_no' => $baseAttID, 
			) 
		);
		
		//echo "inserted";	
			
		
	 }
	
	echo $baseAttID;
	
	die(); // to prevent concatent zero in return value
}

add_action('wp_ajax_save_user_crop_image_admin', 'save_user_crop_image_admin'); // Call when user logged in
add_action('wp_ajax_nopriv_save_user_crop_image_admin', 'save_user_crop_image_admin'); // Call when user in not logged in
// code for custom registraion form

function registration_form($first_name, $last_name, $email, $email_option, $signup_newsletter, $role, $billing_postcode, $modelclass, $modelmake,$testimonial, $billing_city, $username, $website, $bio, $billing_company, $billing_phone, $billing_country, $billing_address_1, $billing_address_2, $billing_state, $category, $password, $confirm_password ) {
	// $email_on_profile, $suffix, $fax,
	global $wp_roles;
	global $wpdb;
	global $woocommerce;
	
	$dynamic_css = '';
	if(isset($_POST['role']) && !empty($_POST['role'])){
		
		if($_POST['role']=='member'){
			
			$dynamic_css = '.dealer{display:none;}';
		}else{
			
			$dynamic_css = '.member{display:none;}';
		}
		
	}else{
		
		//$dynamic_css = '.dealer{display:none;}';	
		$dynamic_css = '.member{display:none;}';
	}
/*	    div {
        margin-bottom:2px;
    }*/
    echo '
    <style>'.$dynamic_css.'
    input{
        margin-bottom:4px;
    }
    </style>';
 
    echo '
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" class="register">
	<h2 class="legend">Personal Information</h2>
	
	<div>
    <label for="firstname">First Name <strong>*</strong></label>
    <input type="text" name="fname" value="' . ( isset( $_POST['fname']) ? $first_name : null ) . '">
    </div>
     
    <div>
    <label for="lastname">Last Name <strong>*</strong></label>
    <input type="text" name="lname" value="' . ( isset( $_POST['lname']) ? $last_name : null ) . '">
    </div>';
	
//echo '<div>
//    <label for="suffix">Suffix</label>
//    <input type="text" name="suffix" value="' . ( isset( $_POST['suffix']) ? $suffix : null ) . '">
//    </div>';
	
echo '<div>
    <label for="email">Email <strong>*</strong></label>
    <input type="text" name="email" value="' . ( isset( $_POST['email']) ? $_POST['email'] : null ) . '">
    </div>
	<div>
	 <div class="col_block" >
	<input class="col_text" type="checkbox" name="email_option" '.( !empty( $email_option) ? 'checked="checked"' : '' ) .' value="yes">
    <label class="col_text1" for="emailoption">Show Email On Frontend</label>
    </div>
	
	 <div class="col_block">
	<input class="col_text" type="checkbox" name="signup_newsletter" '.( !empty( $signup_newsletter) ? 'checked="checked"' : '' ) .' value="yes">
    <label class="col_text1" for="signup_newsletter">Sign Up for Newsletter</label>
    </div>
	 </div>';
/*	echo '<h2 class="legend">Account Type</h2><div>';

	$i=0;
	foreach ( $wp_roles->roles as $key=>$value ):
	
///		$key=='member' || 	
		if($key=='dealer'){
			
			if ( ! empty( $_POST['role']) && $key==$_POST['role'] ){
				
				$checked = 'checked="checked"';
			///}elseif(empty( $_POST['role']) && $key=='member'){
			}elseif(empty( $_POST['role']) && $key=='dealer'){	
				$checked = 'checked="checked"';	
			}else{
				
				$checked = '';	
			}
			
			
			echo '
    			<div class="col-main-text"><input '.$checked.' type="radio" onclick="change_type(this.value)" name="role" value="'.$key.'"><div class="col-part" for="role">'.$value['name'].' <strong>*</strong></div></div>';
			
			$i++;	
		}
		
	endforeach;		
	
echo '</div>';*/
	echo '<input type="hidden" name="role" value="dealer">';
	
	echo '<h2 class="legend">Address Information</h2>
	<p id="notify" class="dealer">Your profile will have to be approved before you can proceed</p>
	
	<div class="dealer">
    <label for="company">Company</label>
    <input type="text" name="billing_company" value="' . ( isset( $_POST['billing_company'] ) ? $billing_company : null ) . '">
    </div>
	
	<div class="dealer">
    <label for="telephone">Telephone</label>
    <input type="text" name="billing_phone" value="' . ( isset( $_POST['billing_phone'] ) ? $billing_phone : null ) . '">
    </div>
	
	<div>
    <label for="billing_postcode">Zip/Postal Code <strong>*</strong></label>
    <input type="text" name="billing_postcode" value="' . ( isset( $_POST['billing_postcode'] ) ? $billing_postcode : null ) . '">
    </div>
	
	<div class="member"><label for="modelclass">Racing Class</label>
	
	<select name="modelclass" id="modelclass">
		<option value=""></option>';
	
	$model_class = $wpdb->get_results("select * from ".$wpdb->prefix."racing_class");
	
	foreach($model_class as $mclass){
		
		if((isset($_POST['modelclass']) && ($modelclass == $mclass->model_class))){
			
			$selected = 'selected="selected"';
		}else{
			
			$selected = '';
		}
		
		echo '<option '.$selected.' value="'.$mclass->model_class.'">'.$mclass->model_class.'</option>';
			
	}
	echo '</select>
	</div>
	
	<div class="member"><label for="modelmake">Vehicle Make</label>
	
		<select name="modelmake" id="modelmake">
			<option value=""></option>';
		
		$model_make = $wpdb->get_results("select * from ".$wpdb->prefix."vehicle_make");
	
		foreach($model_make as $mmake){
			
			if((isset($_POST['modelmake']) && ($modelmake == $mmake->model_make))){
				
				$selected = 'selected="selected"';
			}else{
				
				$selected = '';
			}
			
			echo '<option '.$selected.' value="'.$mmake->model_make.'">'.$mmake->model_make.'</option>';
				
		}
		
	echo '</select>
	</div>
	<div class="member">
    <label for="testimonial">Testimonial of Strange Products</label>
    <textarea name="testimonial" maxlength="500" rows="5" placeholder="Max Length is 500 Characters.">' . ( isset( $_POST['testimonial']) ? $testimonial : null ) . '</textarea>
    </div>';
	
	

    $countries_obj   = new WC_Countries();
    $countries   = $countries_obj->__get('countries');
	
	/* woocommerce_form_field('billing_country', array(
		'type'       => 'select',
		'class'      => array( 'chzn-drop' ),
		'label'      => __('Country'),
		'options'    => $countries
		)
    );
	
	echo "<pre>";
	print_r( $countries_obj->get_states( 'AI' ));
	echo "</pre>";
	*/

	
echo "<script type='text/javascript'>

		function change_type(type){
			
			//alert(type);
			if(type=='dealer'){
				
				jQuery('.dealer').show();
				jQuery('.member').hide();
			}else{
				
				jQuery('.member').show();
				jQuery('.dealer').hide();
			}
		}
		
		function loadCity(val){
			//alert('test'+val);
			jQuery.ajax({
				type: 'POST', 
				url: '../wp-admin/admin-ajax.php', 
				data: {'action': 'state_list_by_country', 'cname':val},
				success: function(data){ 
				//alert(data);
				jQuery('#listOfCity').html(data);
			}
		});
		}
		</script>";
	
	
echo '<div class="dealer"><label for="billing_country">Country</label>
	
	<select name="billing_country" onchange="loadCity(this.value)">';
	foreach($countries as $key => $value):
	
		if(isset($_POST['billing_country']) && $_POST['billing_country']==$key){
			
			$selected = 'selected="selected"';
		}else{
			
			if($key == 'US' && !isset($_POST['billing_country'])){
				
				$selected = 'selected="selected"';
			}else{
				
				$selected = '';
			}
			
		}
	
		echo '<option '.$selected.' value="'.$key.'">'.$value.'</option>'; 
	endforeach;
	echo '</select>';

	
    echo '</div>
	
	<div class="dealer">
    <label for="billing_address_1">Full Address</label>
    <input type="text" name="billing_address_1" value="' . ( isset( $_POST['billing_address_1'] ) ? $billing_address_1 : null ) . '">
    </div>
	
	<div class="dealer">
    <input type="text" name="billing_address_2" value="' . ( isset( $_POST['billing_address_2'] ) ? $billing_address_2 : null ) . '">
    </div>
	
	<div class="dealer">
    <label for="city">City <strong>*</strong></label>
	<input type="text" name="billing_city" value="' . ( isset( $_POST['billing_city'] ) ? $billing_city : null ) . '">
    </div>';
	
echo '<div class="dealer">
    <label for="billing_state">State/Province</label>';
	
	   echo	'<span id="listOfCity">';
  
   if(isset($_POST['billing_country']) && !empty($_POST['billing_country'])){
	   
	   	$statelist = $countries_obj->get_states( $_POST['billing_country'] );
		
		if(count($statelist) > 0 && !empty($statelist)){

			echo '<select name="billing_state"><option value="">Please select region, state or province</option>';
			
			foreach($statelist as $key=>$state){
				
				if(isset($_POST['billing_state']) && $_POST['billing_state']==$key){
					
					$selected = 'selected="selected"';
				}else{
					
					$selected = '';
				}
		
				echo '<option '.$selected.' value=\''. $key.'\'>'. $state .'</option>';
			
			}
		echo '</select>';	
			
		}else{
		
			echo '<input type="text" name="billing_state" value="'.( isset( $_POST['billing_state'] ) ? $billing_state : null ) .'">';		
		}
		
   	}else{
		
			$statelist = $countries_obj->get_states( 'US' );

			echo '<select name="billing_state"><option value="">Please select region, state or province</option>';

			foreach($statelist as $key=>$state){
				
				if(isset($_POST['billing_state']) && $_POST['billing_state']==$key){
					
					$selected = 'selected="selected"';
				}else{
					
					$selected = '';
				}
		
				echo '<option '.$selected.' value=\''. $key.'\'>'. $state .'</option>';
			
			}
		echo '</select>';	
		
		/*echo '<select name="billing_state"><option value="">Please select region, state or province</option></select>';*/
	}	
	echo '</span></div>
	
   
	
	<div class="dealer"><label for="category">Category</label>
	
	<select name="category[]" multiple="multiple">';

	 $usermetatable = $wpdb->prefix.'usermeta';
	 $get_user_category = $wpdb->get_results("SELECT meta_value FROM $usermetatable where user_id = '".$user->ID."' and meta_key = 'category'");
			
	 $all_user_category = explode(",",$get_user_category[0]->meta_value);
	 $table_category = $wpdb->prefix.'user_category';
	 $all_category = $wpdb->get_results("SELECT * FROM $table_category");
	 foreach($all_category as $cat):

		  if(isset($_POST['category']) && in_array($cat->id,$category)){
			
			$selected = 'selected="selected"';
		}else{
			
			$selected = '';
		}
	
		echo '<option '.$selected.' value="'.$cat->id.'">'.$cat->category_name.'</option>'; 
	endforeach;
	echo '</select><span class="sel"><strong>*</strong>Use Ctrl key to select up to three categories.</span>';

	
    echo '</div>';
	
/*	echo '<div class="dealer"><label for="emailonprofile">Show Email On Profile</label>
	
	<select name="email_on_profile">';
	
	if(isset($_POST['email_on_profile']) && $_POST['email_on_profile']=='Yes'){
		
		$yes = 'selected="selected"';
	}else{
		
		$yes = '';
	}
	
	if(isset($_POST['email_on_profile']) && $_POST['email_on_profile']=='No'){
		
		$no = 'selected="selected"';
	}else{
		
		$no = '';
	}

		echo '<option '.$yes.' value="Yes">Yes</option>'; 
		echo '<option '.$no.' value="No">No</option>'; 
		
	echo '</select>';

    echo '</div>';
*/	
	echo '<div>
    <label for="username">Username <strong>*</strong></label>
    <input type="text" name="username" value="' . ( isset( $_POST['username'] ) ? $username : null ) . '">
    </div>
	
    <div>
    <label for="website">URL to Your Business Website</label>
    <input type="text" placeholder="http://" name="website" value="' . ( isset( $_POST['website']) ? $website : '' ) . '">
	<span style="margin-left:24px;">[For example: http://www.example.com]</span>
    </div>';
	
/*  <div class="dealer">
    <label for="fax">Phone no</label>
    <input type="text" name="fax" value="' . ( isset( $_POST['fax']) ? $fax : null ) . '">
    </div>';
	
	
    <div>
    <label for="nickname">Nickname</label>
    <input type="text" name="nickname" value="' . ( isset( $_POST['nickname']) ? $nickname : null ) . '">
    </div>*/
     
echo '<div>
    <label for="bio">Describe Your Company</label>
    <textarea name="bio" maxlength="1400" placeholder="Max Length is 1400 Characters.">' . ( isset( $_POST['bio']) ? $bio : null ) . '</textarea>
    </div>
	
	<h2 class="legend">Login Information</h2>
	
    <div>
    <label for="password">Password <strong>*</strong></label>
    <input type="password" name="password" value="">
    </div>
	
    <div>
    <label for="confirm_password">Confirm Password <strong>*</strong></label>
    <input type="password" name="confirm_password" value="">
    </div>
	
    <input type="submit" class="button" name="submit" value="Register"/>
    </form>
    ';
}

function registration_validation( $first_name, $last_name, $email, $role, $billing_postcode, $billing_city, $category, $username, $website, $password, $confirm_password)  {
	
	global $reg_errors;
	$reg_errors = new WP_Error;

	
	if ( empty( $username ) || empty( $password ) || empty( $email ) || empty( $first_name )  || empty( $last_name )  || empty( $billing_postcode )  || (empty( $billing_city ) && $role=='dealer') || ( empty($category) && $role=='dealer') || empty( $confirm_password )  || empty( $role )) {
		
		$reg_errors->add('field', 'Required form field is missing');
	}
	
	if ( empty( $first_name ) ) {
		
		$reg_errors->add( 'first_name', 'First name is required' );
	}
	
	if ( empty( $last_name ) ) {
		
		$reg_errors->add( 'last_name', 'Last name is required' );
	}
	
	if ( !empty( $email ) && !is_email( $email ) ) {
		$reg_errors->add( 'email_invalid', 'Email is not valid' );
	}
	
	if ( empty( $email ) ) {
		
		$reg_errors->add( 'email', 'Email is required' );
	}
		
	if (!empty( $email ) && email_exists( $email ) ) {
		$reg_errors->add( 'email', 'Email Already in use' );
	}
	
	if ( empty( $role ) ) {
		
		$reg_errors->add( 'role', 'You must include an account type' );
	}
	
	if ( empty( $billing_postcode ) ) {
		
		$reg_errors->add( 'billing_postcode', 'Zip/Postal Code is required' );
	}
	
	if ( empty( $billing_city ) && $role=='dealer') {
		
		$reg_errors->add( 'billing_city', 'City is required' );
	}
	
	if ( empty( $category ) && $role=='dealer') {
		
		$reg_errors->add( 'category', 'Category is required' );
	}
	
	if ( !empty( $username ) && (4 > strlen( $username )) ) {
    $reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
	}
	
	if ( !empty( $username ) && ! validate_username( $username ) ) {
		$reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
	}
	
	if ( !empty( $username ) && username_exists( $username ) != '' ) {
		$reg_errors->add( 'username_invalid', 'Sorry, that username already exists!' );
	}

	
	if ( empty( $username ) ) {
		
		$reg_errors->add( 'username', 'Username is required' );
	}
	
	if ( empty( $password ) ) {
		
		$reg_errors->add( 'password', 'Password is required' );
	}
	
	if ( !empty( $password ) && (5 > strlen( $password )) ) {
        $reg_errors->add( 'password_length', 'Password length must be greater than 5' );
    }
	
	if(!empty( $password)  && !empty( $confirm_password ) && ($password != $confirm_password)){
		
		$reg_errors->add( 'password_match', 'Password and Confirm Password should be match' );
	}
	
	
	if ( ! empty( $website ) ) {
		if ( ! filter_var( $website, FILTER_VALIDATE_URL ) ) {
				$reg_errors->add( 'website', 'Website is not a valid URL' );
		}
	}
	
	if ( is_wp_error( $reg_errors ) ) {
 

		echo '<ul class="woocommerce-error">';
		echo '<li>';
		
		foreach ( $reg_errors->get_error_messages() as $error ) {
		 
			echo '<strong>ERROR</strong>:';
			echo $error . '<br/>';
			
			 
		}
		echo '</li>';
		echo '</ul>';
 
	}
	
}

function complete_registration() {
    global $reg_errors, $first_name, $last_name, $email, $email_option, $signup_newsletter, $role, $billing_postcode, $modelclass, $modelmake,$testimonial, $billing_city, $username, $website, $bio, $billing_company, $billing_phone, $billing_country, $billing_address_1, $billing_address_2, $billing_state, $category, $password, $confirm_password;
	//$suffix,$email_on_profile,$fax, 
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
		
		if($role == 'member'){
			// insert field for member
				$userdata = array(
				'user_login'    =>   	$username,
				'user_nicename'    	=>   	strtolower($first_name."-".$last_name),
				'user_email'    =>   	$email,
				'user_pass'     =>   	$password,
				'user_url'      =>   	$website,
				'first_name'    =>  	$first_name,
				'last_name'     =>  	$last_name,
				'description'   =>   $bio,
				'role'          =>	 $role); 	
				
		}else{
			// insert field for dealer
				$userdata = array(
				'user_login'    =>   	$username,
				'user_nicename'    	=>   	strtolower($first_name."-".$last_name),
				'user_email'    =>   	$email,
				'user_pass'     =>   	$password,
				'user_url'      =>   	$website,
				'first_name'    =>  	$first_name,
				'last_name'     =>  	$last_name,
				'description'   =>   $bio,
				'role'          =>	 $role); 
				
		}

        $user = wp_insert_user( $userdata );
		
		if( $user != ''){
			
				// get lattitude and lognitude from googole api
				$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$billing_postcode."&sensor=false";
				$details=file_get_contents($url);
				$result = json_decode($details,true);
				
				$latitude = $result['results'][0]['geometry']['location']['lat'];
				$longitude = $result['results'][0]['geometry']['location']['lng'];
							

				if($role == 'member'){
	
					update_user_meta( $user, 'billing_first_name', $first_name);
					update_user_meta( $user, 'billing_last_name', $last_name);
					update_user_meta( $user, 'billing_city', $billing_city);
					update_user_meta( $user, 'billing_postcode', $billing_postcode);
					
					update_user_meta( $user, 'shipping_first_name', $first_name);
					update_user_meta( $user, 'shipping_last_name', $last_name);
					update_user_meta( $user, 'shipping_city', $billing_city);
					update_user_meta( $user, 'shipping_postcode', $billing_postcode);
					
					update_user_meta( $user, 'latitude', $latitude);
					update_user_meta( $user, 'longitude', $longitude);
					
					//update_user_meta( $user, 'suffix', $suffix);
					//update_user_meta( $user, 'email_on_profile', $email_on_profile);
					update_user_meta( $user, 'email_option', $email_option);
					update_user_meta( $user, 'signup_newsletter', $signup_newsletter);
					
					update_user_meta( $user, 'modelclass', $modelclass);
					update_user_meta( $user, 'modelmake', $modelmake);
					update_user_meta( $user, 'testimonial,', $testimonial);
									
				}else{
					
					update_user_meta( $user, 'billing_first_name', $first_name);
					update_user_meta( $user, 'billing_last_name', $last_name);
					update_user_meta( $user, 'billing_company', $billing_company);
					update_user_meta( $user, 'billing_phone', $billing_phone);
					update_user_meta( $user, 'billing_postcode', $billing_postcode);
					
					update_user_meta( $user, 'latitude', $latitude);
					update_user_meta( $user, 'longitude', $longitude);
					
					update_user_meta( $user, 'billing_address_1', $billing_address_1);
					update_user_meta( $user, 'billing_address_2', $billing_address_2);
					update_user_meta( $user, 'billing_city', $billing_city);
					update_user_meta( $user, 'billing_country', $billing_country);
					update_user_meta( $user, 'billing_state', $billing_state);

					update_user_meta( $user, 'shipping_first_name', $first_name);
					update_user_meta( $user, 'shipping_last_name', $last_name);
					update_user_meta( $user, 'shipping_company', $billing_company);
					update_user_meta( $user, 'shipping_phone', $billing_phone);
					update_user_meta( $user, 'shipping_postcode', $billing_postcode);
					update_user_meta( $user, 'shipping_address_1', $billing_address_1);
					update_user_meta( $user, 'shipping_address_2', $billing_address_2);
					update_user_meta( $user, 'shipping_city', $billing_city);
					update_user_meta( $user, 'shipping_country', $billing_country);
					update_user_meta( $user, 'shipping_state', $billing_state);
					
					//update_user_meta( $user, 'suffix', $suffix);
					//update_user_meta( $user, 'email_on_profile', $email_on_profile);
					update_user_meta( $user, 'email_option', $email_option);
					update_user_meta( $user, 'signup_newsletter', $signup_newsletter);

					update_user_meta( $user, 'category', implode(',',$category));
					//update_user_meta( $user, '_phone', $fax);
					update_user_meta( $user, 'nickname', $first_name."-".$last_name);
					
				}
				
				$first_name = ""; 
				$last_name = ""; 
				$email = ""; 
				//$suffix = ""; 
				$email_option = ""; 
				$signup_newsletter = ""; 
				$role = ""; 
				$billing_postcode = ""; 
				$modelclass = ""; 
				$modelmake = ""; 
				$testimonial = "";
				$billing_city = ""; 
				$username = ""; 
				$website = ""; 
				$bio = ""; 
				$billing_company = ""; 
				$billing_phone = ""; 
				$billing_country = ""; 
				$billing_address_1 = ""; 
				$billing_address_2 = ""; 
				$billing_state = ""; 
				$category = ""; 
				//$email_on_profile = ""; 
				//$fax = "";
				//$password = ""; 
				$confirm_password = "";
				
				// mail send to user for confirmation
				
				$activation_key = wp_hash($user);
		
			$user_id = $user;		
			$user_info = get_userdata($user_id);
			
//			$suffix_text = get_user_meta($user_id, 'suffix', true);
//			
//			if(!empty($suffix_text)){
//				
//				$user_name = $suffix_text.' '.$user_info->display_name;
//			}else{
//				
//				$user_name = $user_info->display_name;
//			}
			
			$user_name = $user_info->display_name;
			
			$admin_email = get_option('admin_email');
			//$admin_email = "gc@strangeengineering.net";
			//$user_data = get_user_by('id', 1113);
			//$admin_email = $user_data->data->user_email;
			
			$site_title = get_bloginfo( 'name' );
								
			// create md5 code to verify later
			$code = md5(time());
			// make it into a code to send it to user via email
			$string = array('id'=>$user_id, 'code'=>$code);
			// create the activation code and activation status
			update_user_meta($user_id, 'is_activated', 0);
			update_user_meta($user_id, 'activationcode', $code);
			// create the url
			$url = get_permalink( get_option('woocommerce_myaccount_page_id') ). '?ac=' .base64_encode( serialize($string));
			
			// basically we will edit here to make this nicer
			$html = 'Please click the following links <br/><br/> <a href="'.$url.'">'.$url.'</a>';
				
			$message = '<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
							<tbody><tr>
								<td align="center" valign="top" style="padding:20px 0 20px 0">
									
									<table bgcolor="FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #e0e0e0; font-family:Arial, Helvetica, sans-serif"">
										<tbody><tr>
											<td valign="top" bgcolor="000000">
												<a href="'.home_url().'" target="_blank"><img src="'.home_url().'/wp-content/uploads/2015/12/strangeengineeringlogo.gif" alt="'.$site_title.'" style="margin-bottom:10px" border="0" class="CToWUd"></a>
											</td>
										</tr>
										
										<tr>
											<td valign="top">
												<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Dear <strong>'.$user_name.'</strong>,</h1>
												<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Thank you for signing up for a Strange website profile. This is a two step process: Step 1 is confirming your email address now. Step 2 will be an admin confirming your dealer status. You will receive another email once your web profile is approved.</h1>
												<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Step 1.</h1>
												<p style="font-size:12px;line-height:16px;margin:0 0 16px 0">Your e-mail <a href="mailto:'.$user_info->user_email.'" target="_blank">'.$user_info->user_email.'</a> must be confirmed before using it to log in to our store.</p>
												<p style="font-size:12px;line-height:16px;margin:0 0 8px 0">To confirm the e-mail and instantly log in, please, use <a href="'.$url.'" style="color:#1e7ec8" target="_blank">this confirmation link</a>. This link is valid only once.</p>
												<p style="border:1px solid #e0e0e0;font-size:12px;line-height:16px;margin:0 0 16px 0;padding:13px 18px;background:#f9f9f9">
													Use the following values when prompted to log in:<br>
													<strong>E-mail:</strong> <a href="mailto:'.$user_info->user_email.'" target="_blank">'.$user_info->user_email.'</a><br>
													<strong>Password:</strong> '.$password.'</p><p>
												</p><p style="font-size:12px;line-height:16px;margin:0">If you have any questions about your account or any other matter, please feel free to contact us at <a href="'.home_url().'/contact-strange/" style="color:#1e7ec8" target="_blank">'.$admin_email.'</a> or by phone at 847-663-1701.</p>
											<p></p><p></p></td>
										</tr>
										<tr>
											<td bgcolor="#EAEAEA" align="center" style="background:#eaeaea;text-align:center"><center><p style="font-size:12px;margin:0">Thank you again, <strong>Strange Engineering</strong></p></center></td>
										</tr>
									</tbody></table>
								</td>
							</tr>
						</tbody></table>';
						
				$admin_message = 'New dealer registration for Strange Engineering.<br><br>Dealer detalis:<br>
<br>E-mail: '.$user_info->user_email;		
						
				echo '<style>.woocommerce-error{display:none;}</style>';
						
//				$headers[] = 'Content-Type: text/html; charset=UTF-8';
//				$headers[] .= 'From: '.$site_title.' <'.$admin_email.'>\r\n';	
				
				$headers[] = "MIME-Version: 1.0\n";
				$headers[] = "Content-type: text/html; charset=UTF-8\n";
				$headers[] = 'From: '.$site_title.' <'.$admin_email.'>';
				
				$admin_headers[] = "MIME-Version: 1.0\n";
				$admin_headers[] = "Content-type: text/html; charset=UTF-8\n";
				$admin_headers[] = 'From: '.$site_title.' <'.$user_info->user_email.'>';
				//wp_mail
				$usermail = wp_mail($user_info->user_email, 'Please activate your account', $message, $headers);
				$adm = wp_mail($admin_email.",lisa@strangeengineering.net", 'New Dealer Registration', $admin_message, $admin_headers);
				//echo $usermail."---->".$adm;
				if($usermail || $adm){	
					
					echo 'Registration complete. Goto <a class="reg-hover" href="' .   get_permalink( get_option('woocommerce_myaccount_page_id') )  . '">login page</a>.';  
					echo '<span> Account confirmation is required. Please, check your email in your Inbox folder or Spam folder for the confirmation link.To resend the confirmation email please <a class="reg-hover" href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'?u='.$user_id.'">click here</a>.</span>';

						$password = '';
		
				}else{
					
						echo '<strong>Error:</strong> Registration complete. Goto <a class="reg-hover" href="' .   get_permalink( get_option('woocommerce_myaccount_page_id') )  . '">login page</a>.';  
						echo '<span> Account confirmation is required. Please, check your email in your Inbox folder or Spam folder for the confirmation link.To resend the confirmation email please <a class="reg-hover" href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'?u='.$user_id.'">click here</a>.</span>';

						$password = '';	
				}
		}
		
 
    }
}

function custom_registration_function() {
    if ( isset($_POST['submit'] ) ) {
        registration_validation(  
		$_POST['fname'],
        $_POST['lname'],
        $_POST['email'],
        $_POST['role'],
		$_POST['billing_postcode'],
		$_POST['billing_city'],
		$_POST['category'],
		$_POST['username'],
		$_POST['website'],
        $_POST['password'],
		$_POST['confirm_password']
        );
         
        // sanitize user form input
		//$email_on_profile, $suffix,$fax, 
        global  $first_name, $last_name, $email, $email_option, $signup_newsletter, $role, $billing_postcode, $modelclass, $modelmake,$testimonial, $billing_city, $username, $website, $bio, $billing_company, $billing_phone, $billing_country, $billing_address_1, $billing_address_2, $billing_state, $category, $password, $confirm_password;
		
        $first_name =   sanitize_text_field( $_POST['fname'] );
        $last_name  =   sanitize_text_field( $_POST['lname'] );
        $email      =   sanitize_email( $_POST['email'] );
        //$suffix  =   sanitize_text_field( $_POST['suffix'] );		
		$email_option  =   sanitize_text_field( $_POST['email_option'] );
		$signup_newsletter  =   sanitize_text_field( $_POST['signup_newsletter'] );
		$role  =   sanitize_text_field( $_POST['role'] );
		$billing_postcode  =   sanitize_text_field( $_POST['billing_postcode'] );
		$modelclass  =   ""; //sanitize_text_field( $_POST['modelclass'] );
		$modelmake  =   ""; //sanitize_text_field( $_POST['modelmake'] );
		$testimonial  =   sanitize_text_field( $_POST['testimonial'] );	
		$billing_city  =   sanitize_text_field( $_POST['billing_city'] );
		$username   =   sanitize_user( $_POST['username'] );
		$website    =   esc_url( $_POST['website'] );
		$bio        =   esc_textarea( $_POST['bio'] );
		
		$billing_company  =   sanitize_text_field( $_POST['billing_company'] );
		$billing_phone  =   sanitize_text_field( $_POST['billing_phone'] );
		$billing_country  =   sanitize_text_field( $_POST['billing_country'] );
		$billing_address_1  =   sanitize_text_field( $_POST['billing_address_1'] );
		$billing_address_2  =   sanitize_text_field( $_POST['billing_address_2'] );
        $billing_state  =   sanitize_text_field( $_POST['billing_state'] );
		$category  =   $_POST['category'];
		//$email_on_profile  =   sanitize_text_field( $_POST['email_on_profile'] );
		//$fax  =   sanitize_text_field( $_POST['fax'] );
        $password   =   esc_attr( $_POST['password'] );
		$confirm_password   =   esc_attr( $_POST['confirm_password'] );
        
 
        // call @function complete_registration to create the user
        // only when no WP_error is found
        complete_registration(
       $first_name, $last_name, $email, $email_option, $signup_newsletter, $role, $billing_postcode, $modelclass, $modelmake, $testimonial,$billing_city, $username, $website, $bio, $billing_company, $billing_phone, $billing_country, $billing_address_1, $billing_address_2, $billing_state, $category,$password
        );
		// $email_on_profile, $suffix, $fax, 
    }
 
    registration_form(
        $first_name, $last_name, $email, $email_option, $signup_newsletter, $role, $billing_postcode, $modelclass, $modelmake, $testimonial, $billing_city, $username, $website, $bio, $billing_company, $billing_phone, $billing_country, $billing_address_1, $billing_address_2, $billing_state, $category, $password, $confirm_password
        );
		// $email_on_profile, $suffix,$fax, 
}

// Register a new shortcode: [cr_custom_registration]
add_shortcode( 'cr_custom_registration', 'custom_registration_shortcode' );
 
// The callback function that will replace [book]
function custom_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}

////////////Send confirmation link to user mail for verify account ////////////////


// this is just to prevent the user log in automatically after register
function wc_registration_redirect( $redirect_to ) {
			
			wp_logout();
			wp_redirect( get_permalink( get_option('woocommerce_myaccount_page_id') ).'?q=');
			exit;
        
}
// when user login, we will check whether this guy email is verify
function wp_authenticate_user_login( $userdata ) {
	
			global $wpdb;
			
			$user_status = $wpdb->get_results("select * from ".$wpdb->prefix."usermeta where meta_key='is_activated' and user_id='".$userdata->ID."'");
			$isActivated = get_user_meta($userdata->ID, 'is_activated', true);
			
//			echo count($user_status);
//			echo "<pre>";
//			print_r($user_status);
//			die("test");
			
			if($userdata->roles[0]=='administrator' || count($user_status)==0){	
			
					return $userdata;
			}else{
				
				if(count($userdata->data) == 0){
					
						$userdata = new WP_Error(
								'inkfool_confirmation_error',
								__( $userdata->errors['wpau_confirmation_error'][0], 'inkfool' ));
						
					
				}else{
					
					if ( !$isActivated ) {

							$userdata = new WP_Error(
											'inkfool_confirmation_error',
											__( '<strong>ERROR:</strong> Your account has to be activated before you can login. You can resent by clicking <a href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'?u='.$userdata->ID.'">here</a>', 'inkfool' )
											);		
					}
						
					
				}
					

				return $userdata;
			}
			
}

// when a user register we need to send them an email to verify their account
function my_user_register($user_id) {
	
		//$admin_email = get_option('admin_email');
		//$admin_email = "gc@strangeengineering.net";
		//$user_data = get_user_by('id', 1113);
		//$admin_email = $user_data->data->user_email;
		$admin_email = get_option('admin_email');
					
		$site_title = get_bloginfo( 'name' );
		 
		 // get user data
        $user_info = get_userdata($user_id);
		
//		$suffix_text = get_user_meta($user_id, 'suffix', true);
//		
//		if(!empty($suffix_text)){
//			
//			$user_name = $suffix_text.' '.$user_info->display_name;
//		}else{
//			
//			$user_name = $user_info->display_name;
//		}
		
		$user_name = $user_info->display_name;
		
        //create md5 code to verify later
        $code = md5(time());
        // make it into a code to send it to user via email
        $string = array('id'=>$user_id, 'code'=>$code);
		$activation_key = base64_encode( serialize($string));
		
        // create the activation code and activation status
        update_user_meta($user_id, 'is_activated', 0);
        update_user_meta($user_id, 'activationcode', $code);
        // create the url
        $url = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?ac=' .$activation_key;
			
		$html = 'Please click the following links <br/><br/> <a href="'.$url.'">'.$url.'</a>';
			
		$message = '<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
						<tbody><tr>
							<td align="center" valign="top" style="padding:20px 0 20px 0">
								
								<table bgcolor="FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #e0e0e0; font-family:Arial, Helvetica, sans-serif"">
									<tbody><tr>
										<td valign="top" bgcolor="000000">
											<a href="'.home_url().'" target="_blank"><img src="'.home_url().'/wp-content/uploads/2015/12/strangeengineeringlogo.gif" alt="'.$site_title.'" style="margin-bottom:10px" border="0" class="CToWUd"></a>
										</td>
									</tr>
									
									<tr>
										<td valign="top">
											<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Dear <strong>'.$user_name.'</strong>,</h1>
											<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Thank you for signing up for a Strange website profile. This is a two step process: Step 1 is confirming your email address now. Step 2 will be an admin confirming your dealer status. You will receive another email once your web profile is approved.</h1>
											<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Step 1.</h1>
											<p style="font-size:12px;line-height:16px;margin:0 0 16px 0">Your e-mail <a href="mailto:'.$user_info->user_email.'" target="_blank">'.$user_info->user_email.'</a> must be confirmed before using it to log in to our store.</p>
											<p style="font-size:12px;line-height:16px;margin:0 0 8px 0">To confirm the e-mail and instantly log in, please, use <a href="'.$url.'" style="color:#1e7ec8" target="_blank">this confirmation link</a>. This link is valid only once.</p>
											<p style="border:1px solid #e0e0e0;font-size:12px;line-height:16px;margin:0 0 16px 0;padding:13px 18px;background:#f9f9f9">
												Use the following values when prompted to log in:<br>
												<strong>E-mail:</strong> <a href="mailto:'.$user_info->user_email.'" target="_blank">'.$user_info->user_email.'</a></p><p></p><p style="font-size:12px;line-height:16px;margin:0">If you have any questions about your account or any other matter, please feel free to contact us at <a href="'.home_url().'/contact-strange/" style="color:#1e7ec8" target="_blank">'.$admin_email.'</a> or by phone at 847-663-1701.</p>
										<p></p><p></p></td>
									</tr>
									<tr>
										<td bgcolor="#EAEAEA" align="center" style="background:#eaeaea;text-align:center"><center><p style="font-size:12px;margin:0">Thank you again, <strong>Strange Engineering</strong></p></center></td>
									</tr>
								</tbody></table>
							</td>
						</tr>
			</tbody></table>';
			
			
		$headers[] = "MIME-Version: 1.0\n";
		$headers[] = "Content-type: text/html; charset=UTF-8\n";
		$headers[] = 'From: '.$site_title.' <'.$admin_email.'>';
		//print $headers; wp_mail
		//wp_mail send an email out to user
        if(wp_mail($user_info->user_email, 'Please activate your account', $message, $headers)){
			
			return 1;
		}else{
			
			return 0;
		}
		
}

// we need this to handle all the getty hacks i made
/*function my_init(){
	
	//echo "init function 222222222222222";
        // check whether we get the activation message
	
        if(isset($_GET['p'])){
                $data = unserialize(base64_decode($_GET['p']));
				echo "<pre>";
				print_r( $data);
                $code = get_user_meta($data['id'], 'activationcode', true);
                // check whether the code given is the same as ours
				echo "<br>";
				echo $code;
                if($code == $data['code']){
                        // update the db on the activation process
                        update_user_meta($data['id'], 'is_activated', 1);
                        wc_add_notice( __( '<strong>Success:</strong> Your account has been activated! ', 'inkfool' )  );
					   //echo '<strong>Success:</strong> Your account has been activated!';
                }else{
                        wc_add_notice( __( '<strong>Error:</strong> Activation fails, please contact our administrator. ', 'inkfool' )  );
						//echo '<strong>Error:</strong> Activation fails, please contact our administrator. ';
                }
        }
        if(isset($_GET['q'])){
                wc_add_notice( __( '<strong>Error:</strong> Your account has to be activated before you can login. Please check your email.', 'inkfool' ) );
			   //echo '<strong>Error:</strong> Your account has to be activated before you can login. Please check your email.';
        }
        if(isset($_GET['u'])){
				
                my_user_register($_GET['u']);
                wc_add_notice( __( '<strong>Succes:</strong> Your activation email has been resend. Please check your email.', 'inkfool' ) );
				//echo  '<strong>Succes:</strong> Your activation email has been resend. Please check your email.';
        }
}*/


add_action( 'wp', 'wppage_special_thing' );
function wppage_special_thing()
{
    if ('page' === get_post_type() AND is_singular()) {
	
		 if(isset($_GET['ac'])){
			 
					$data = unserialize(base64_decode($_GET['ac']));

					
					$code = get_user_meta($data['id'], 'activationcode', true);
					
//					print $data['code']; 
//					print $code."<br>";
//					return print $data['code'];
					
					// check whether the code given is the same as ours

					if(($code == $data['code']) and ($data['code'] != "" || $code != "")){
							// update the db on the activation process
							update_user_meta($data['id'], 'is_activated', 1);
							wc_add_notice( __( '<strong>Success:</strong> Your account has been activated! ', 'inkfool' )  );
						   //echo '<strong>Success:</strong> Your account has been activated!';
					}else{
							wc_add_notice( __( '<strong>Error:</strong> Activation fails, please contact our administrator. ', 'inkfool' )  );
							//echo '<strong>Error:</strong> Activation fails, please contact our administrator. ';
					}
			}
			
			
			if(isset($_GET['q'])){
					wc_add_notice( __( '<strong>Error:</strong> Your account has to be activated before you can login. Please check your email.', 'inkfool' ) );
				   //echo '<strong>Error:</strong> Your account has to be activated before you can login. Please check your email.';
			}
			if(isset($_GET['u'])){
					
					//print my_user_register($_GET['u']);
					
					if(my_user_register($_GET['u']) == 1){
						
						wc_add_notice( __( '<strong>Succes:</strong> Your activation email has been resent. Please check your email.', 'inkfool' ) );
						//echo  '<strong>Succes:</strong> Your activation email has been resend. Please check your email.';
					}else{
						
						wc_add_notice( __( '<strong>Error:</strong> Your activation email has been resent. Please check your email.', 'inkfool' ) );
						//echo '<strong>Error:</strong> Your activation email has been resend. Please check your email.', 'inkfool';
					}
			}
		 
       return;
		
	}

}

// hooks handler
add_filter('woocommerce_registration_redirect', 'wc_registration_redirect');
add_filter('wp_authenticate_user', 'wp_authenticate_user_login',10,2);
//add_action('user_register', 'my_user_register',10,2);


// adding custom field in account page 
add_action( 'woocommerce_after_my_account_avada_child', 'custom_woocommerce_after_my_account', 10, 1 );

function custom_woocommerce_after_my_account ($validation_errors)
{
    global $wpdb;
	global $woocommerce, $messages;
	
	if(isset($_POST['custom_save_account_details'])){
		
		$user = wp_get_current_user();
		
			$reg_errors = new WP_Error;
			
			//$account_suffix = 	! empty( $_POST[ 'account_suffix' ] ) ? wc_clean( $_POST[ 'account_suffix' ] ) : '';
			$account_first_name = ! empty( $_POST[ 'account_first_name' ] ) ? wc_clean( $_POST[ 'account_first_name' ] ) : '';
			$account_last_name = ! empty( $_POST[ 'account_last_name' ] ) ? wc_clean( $_POST[ 'account_last_name' ] ) : '';
			$account_email = ! empty( $_POST[ 'account_email' ] ) ? sanitize_email( $_POST[ 'account_email' ] ) : '';
			
			$email_option = ! empty( $_POST[ 'email_option' ] ) ? wc_clean( $_POST[ 'email_option' ] ) : '';
			
			$category = ! empty( $_POST[ 'category' ] ) ? wc_clean($_POST[ 'category' ]) : '';
			
			//$testimonial = ! empty( $_POST[ 'testimonial' ] ) ? wc_clean( $_POST[ 'testimonial' ] ) : '';
			
			//$url_video = ! empty( $_POST[ 'url_video' ] ) ? wc_clean( $_POST[ 'url_video' ] ) : '';
			$website = ! empty( $_POST[ 'website' ] ) ? wc_clean( $_POST[ 'website' ] ) : '';
			$fax = ! empty( $_POST[ 'fax' ] ) ? wc_clean( $_POST[ 'fax' ] ) : '';
			$description = ! empty( $_POST[ 'description' ] ) ? wc_clean( $_POST[ 'description' ] ) : '';
			//$modelmake = ! empty( $_POST[ 'modelmake' ] ) ? wc_clean( $_POST[ 'modelmake' ] ) : '';
			//$modelclass = ! empty( $_POST[ 'description' ] ) ? wc_clean( $_POST[ 'modelclass' ] ) : '';
			
			
			$password_current = ! empty( $_POST[ 'password_current' ] ) ? $_POST[ 'password_current' ] : '';
			$password_1 = ! empty( $_POST[ 'password_1' ] ) ? $_POST[ 'password_1' ] : '';
			$password_2 = ! empty( $_POST[ 'password_2' ] ) ? $_POST[ 'password_2' ] : '';
			$save_pass          = true;
			
			/*echo "<pre>";
			print_r($_POST);
			echo "</pre>";	
			echo "from submit";*/
		
		
			if ( empty( $account_first_name ) ) {
		
				$reg_errors->add( 'account_first_name', '<strong>First Name</strong>: is a required field.' );
			}
			
			if ( empty( $account_last_name ) ) {
				
				$reg_errors->add( 'account_last_name', '<strong>Last Name</strong>: is a required field.' );
			}
			
			if ( ! empty( $account_email ) ) {
			//echo "if";
				if ( ! is_email( $account_email ) ) {
					$reg_errors->add( 'email', '<strong>Email</strong>: Please provide a valid email address.' );
				} elseif ( email_exists( $account_email ) && $account_email !== $user->user_email ) {
					$reg_errors->add( 'email', '<strong>Email</strong>: This email address is already registered.' );
				}
				///$newuser->user_email = $account_email;
			}
			
			if ( empty( $account_email ) ) {
				
				$reg_errors->add( 'email', '<strong>Email</strong>: is required' );
			}
			
			
			
			if ( ! empty( $password_1) && ! wp_check_password( $password_current, $user->user_pass, $user->ID ) ) {
				$reg_errors->add( 'password', 'Your current password is incorrect.' );
				$save_pass = false;
			}
	
			if ( ! empty( $password_current ) && empty( $password_1) && empty( $password_2 ) ) {
				$reg_errors->add( 'password', 'Please fill out all password fields.' );
				$save_pass = false;
			} elseif ( ! empty( $password_1) && empty( $password_current ) ) {
				$reg_errors->add( 'password', 'Please enter your current password.' );
				$save_pass = false;
			} elseif ( ! empty( $password_1) && empty( $password_2 ) ) {
				$reg_errors->add( 'password', 'Please re-enter your password.' );
				$save_pass = false;
			} elseif ( ( ! empty( $password_1) || ! empty( $password_2 ) ) && $password_1 !== $password_2 ) {
				$reg_errors->add( 'password', 'New passwords do not match.' );
				$save_pass = false;
			}
	
			if ( $password_1 && $save_pass ) {
				//$newuser->user_pass = $password_1;
					//updated new password	
					$wpdb->update( 
						$wpdb->prefix.'users', 
						array( 
							'user_pass' => md5($password_1),  
						), 
						array( 'id' => $user->ID )
					);
			}
		
			if ( is_wp_error( $reg_errors ) && (count($reg_errors->get_error_messages()) > 0)) {
				
				$error_message = '<ul class="woocommerce-error">';
				
				
				foreach ( $reg_errors->get_error_messages() as $error ) {
					
					$error_message .= '<li>'.$error.'</li>';
					 
				}
				
				$error_message .= '</ul>';
				
				if(!empty($_POST['user_cropimage'])){
		    
			$user_profile_image = explode(',',$_POST['user_cropimage']);
			
			
			$user_cropimage_unique_id = array_unique($user_profile_image,SORT_REGULAR);
			$implodeIdWithComma = implode(',',$user_cropimage_unique_id);
			
			$table_name = $wpdb->prefix.'user_crop_image';
			
			$rec = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = '".$user->ID."' and user_crop_image_no IN($implodeIdWithComma) group by user_crop_image_no");
			//echo "<pre>";
			//print_r($rec);
			//echo "</pre>";
			$table_name1 = $wpdb->prefix.'user_profile_image';
			foreach($rec as $data){
					
				$select =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user->ID and image_no = $data->user_crop_image_no");
				
				
				if(count($select) > 0){
					
						$wpdb->query("UPDATE $table_name1 SET user_id=$user->ID,
						user_profile_image = 'wp-content/uploads/user_crop_image/$data->user_crop_image',
						image_no = '$data->user_crop_image_no'
						WHERE user_id = $user->ID and image_no = $data->user_crop_image_no");
						
						@copy('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, 'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
						
						@unlink('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
				 }else{
					
						$wpdb->insert("$table_name1", array(
									'user_id' => $user->ID,  
									 'user_profile_image' =>  'wp-content/uploads/user_crop_image/'.$data->user_crop_image,
									 "image_no" => $data->user_crop_image_no
	));	
					
						@copy('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, 'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
				
						@unlink('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
				 }
				
			}
			//exit;
			
		}
		?>		
				<script>
				jQuery('.woocommerce').prepend('<?php echo $error_message;?>');
				
				if(jQuery('.orders').closest('li').attr('class') == 'active'){
						//alert("orders");
						jQuery('.orders').closest('li').removeClass('active');
						jQuery('.account').closest('li').addClass('active');	
						
				}else if(jQuery('.address').closest('li').attr('class') == 'active'){
						//alert("address");
						jQuery('.address').closest('li').removeClass('active');
						jQuery('.account').closest('li').addClass('active');
				}
				</script>
		<?php 
		//echo "-----------------ERROR----------------";
		
		}else{ 
		
		
		//update_user_meta( $user->ID, 'suffix', $account_suffix);
		update_user_meta( $user->ID, 'first_name', $account_first_name);
		update_user_meta( $user->ID, 'last_name', $account_last_name);
		
		//update user email
		if($user->user_email <> $account_email){
			
//				$user_data = get_user_by('id', 1113);
//				$admin_email = $user_data->data->user_email;
				$admin_email = get_option('admin_email');
				$site_title = get_bloginfo( 'name' );
					
				$message = '<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
								<tbody><tr>
									<td align="center" valign="top" style="padding:20px 0 20px 0">
										
										<table bgcolor="FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #e0e0e0; font-family:Arial, Helvetica, sans-serif"">
											<tbody><tr>
												<td valign="top" bgcolor="000000">
													<a href="'.home_url().'" target="_blank"><img src="'.home_url().'/wp-content/uploads/2015/12/strangeengineeringlogo.gif" alt="'.$site_title.'" style="margin-bottom:10px" border="0" class="CToWUd"></a>
												</td>
											</tr>
											
											<tr>
												<td valign="top">
													<h1 style="font-size:22px;font-weight:normal;line-height:22px;margin:0 0 11px 0">Dear '.$user->user_login.',</h1>
													<p style="font-size:12px;line-height:16px;margin:0 0 16px 0">This notice confirms that your email was changed on Strange Engineering.</p>
													<p style="font-size:12px;line-height:16px;margin:0 0 8px 0">If you did not change your email, please contact the Site Administrator at
		<a href="mailto:'.$user_info->user_email.'" target="_blank">'.$user_info->user_email.'</a></p>
													<p style="border:1px solid #e0e0e0;font-size:12px;line-height:16px;margin:0 0 16px 0;padding:13px 18px;background:#f9f9f9">
														This email has been sent to '.$user->user_email.'</p><p>
													</p><p style="font-size:12px;line-height:16px;margin:0">If you have any questions about your account or any other matter, please feel free to contact us at <a href="'.home_url().'/contact-strange/" style="color:#1e7ec8" target="_blank">'.$admin_email.'</a> or by phone at 847-663-1701.</p>
												<p></p><p></p></td>
											</tr>
											<tr>
												<td bgcolor="#EAEAEA" align="center" style="background:#eaeaea;text-align:center"><center><p style="font-size:12px;margin:0">Thank you again, <strong>Strange Engineering</strong></p></center></td>
											</tr>
										</tbody></table>
									</td>
								</tr>
					</tbody></table>';
					
					
				$headers[] = "MIME-Version: 1.0\n";
				$headers[] = "Content-type: text/html; charset=UTF-8\n";
				$headers[] = 'From: '.$site_title.' <'.$admin_email.'>';
				
//				$headers[] = 'From: '.$site_title.' <'.$admin_email.'>\r\n';
				//wp_mail send an email out to user
				wp_mail($user->user_email, 'Notice of Email Change', $message, $headers);
				
				$wpdb->query("update ".$wpdb->prefix."users set user_email='".$account_email."' where id='".$user->ID."'");
		}
			
			$wpdb->update( 
				$wpdb->prefix.'users', 
				array( 
					'user_url' => $website,  
				), 
				array( 'id' => $user->ID )
			);
		//update_user_meta( $user->ID, 'url_video', $url_video);
		
		update_user_meta( $user->ID, 'email_option', $email_option);
		//update_user_meta( $user->ID, 'testimonial', $testimonial);
		
		//update_user_meta( $user->ID, '_phone', $fax);
		update_user_meta( $user->ID, 'billing_phone', $fax);
		update_user_meta( $user->ID, 'description', $description);
		
		//if($user->roles[0]=='dealer'){
			
		update_user_meta( $user->ID, 'category', @implode(',', $_POST[ 'category' ] ));
			//update_user_meta( $user->ID, 'modelmake', $modelmake);
			//update_user_meta( $user->ID, 'modelclass', $modelclass);
		//}
		
		
		//update_user_meta( $user->ID, 'password_current', $password_current);
		
		if(!empty($_POST['user_cropimage'])){
		    
			$user_profile_image = explode(',',$_POST['user_cropimage']);
			
			
			$user_cropimage_unique_id = array_unique($user_profile_image,SORT_REGULAR);
			$implodeIdWithComma = implode(',',$user_cropimage_unique_id);
			
			$table_name = $wpdb->prefix.'user_crop_image';
			
			$rec = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user->ID and user_crop_image_no IN($implodeIdWithComma) group by user_crop_image_no");
			//echo "<pre>";
			//print_r($rec);
			//echo "</pre>";
			$table_name1 = $wpdb->prefix.'user_profile_image';
			foreach($rec as $data){
					
				$select =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user->ID and image_no = $data->user_crop_image_no");
				
				
				 if(count($select) > 0){
						$wpdb->query("UPDATE $table_name1 SET user_id=$user->ID,
						user_profile_image = 'wp-content/uploads/user_crop_image/$data->user_crop_image',
						image_no = '$data->user_crop_image_no'
						WHERE user_id = $user->ID and image_no = $data->user_crop_image_no");
						
						@copy('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, 'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
						
						@unlink('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
			
				}else{
				
				 	$wpdb->insert("$table_name1", array(
   								'user_id' => $user->ID,  
   								 'user_profile_image' =>  'wp-content/uploads/user_crop_image/'.$data->user_crop_image,
   								 "image_no" => $data->user_crop_image_no
));	
				
					@copy('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, 'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
			
					@unlink('wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
				 }
				
			}
			//exit;
			
		}
		
		//echo "else no errors===================>";
		
		?>
			
				<script>
					jQuery('.woocommerce').prepend('<div class="woocommerce-message">Account details changed successfully.</div>');
					
					if(jQuery('.orders').closest('li').attr('class') == 'active'){
							//alert("orders");
							jQuery('.orders').closest('li').removeClass('active');
							jQuery('.account').closest('li').addClass('active');	
							
					}else if(jQuery('.address').closest('li').attr('class') == 'active'){
							//alert("address");
							jQuery('.address').closest('li').removeClass('active');
							jQuery('.account').closest('li').addClass('active');
					}
				</script>
				
	<?php	}
	}
	
	$user_account = wp_get_current_user();
	
	$user_details = $wpdb->get_results("select * from ".$wpdb->prefix."users where id='".$user_account->ID."'");
	
//	echo "<pre>";
//	print_r($user_details);
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri();?>/crop/example.css">
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri();?>/crop/crop.css">
<script src="<?php echo get_stylesheet_directory_uri();?>/crop/crop.js"></script>

	<h2 class="edit-account-heading"><?php _e( 'Edit Account', 'Avada' ); ?></h2>

	<form class="edit-account-form" action="" method="post">
    
		<p class="form-row form-row-first">
<?php /*?>        	<div class="three-col">
                <label for="account_suffix"><?php _e( 'Suffix', 'woocommerce' ); ?></label>
                <input type="text" class="input-text" name="account_suffix" id="account_suffix" value="<?php echo esc_attr( get_user_meta($user_account->ID, 'suffix', true )); ?>" />
            </div><?php */?>
            <div class="two-col">
                <label for="account_first_name"><?php _e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
                            <input type="text" class="input-text" name="account_first_name" id="account_first_name" value="<?php echo esc_attr( $user_account->first_name ); ?>" />
            </div>
            <div class="two-col">
            	<label for="account_last_name"><?php _e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="account_last_name" id="account_last_name" value="<?php echo esc_attr( $user_account->last_name ); ?>" />
            </div>
		</p>

		<p class="form-row form-row-wide">
        <div class="two-col">
			<label for="account_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="email" class="input-text" name="account_email" id="account_email" value="<?php echo esc_attr( $user_details[0]->user_email ); ?>" />
        </div>
		<div class="two-col">
			<label for="account_option"><?php _e( 'Show Email On Frontend', 'woocommerce' ); ?> </label>
			<?php if($user_account->email_option){?>
            <input type="checkbox" name="email_option" id="email_option" value="yes" checked="checked" />
            <?php }
			else{
			?>
            <input type="checkbox" name="email_option" id="email_option" value="yes"/>
            <?php }?>
        </div>
		</p>
        
        <?php //if($user_account->roles[0]=='dealer'){?>
		<p class="form-row form-row-wide">
        <div class="two-col">
			<label for="account_category"><?php _e( 'Category', 'woocommerce' ); ?></label>
			<select name="category[]" id="category" multiple="multiple" style="height:142px">
            <?php
			$usermetatable = $wpdb->prefix.'usermeta';
			$get_user_category = $wpdb->get_results("SELECT meta_value FROM $usermetatable where user_id = $user_account->ID and meta_key = 'category'");
			$all_user_category = explode(",",$get_user_category[0]->meta_value);
		 
			 $table_category = $wpdb->prefix.'user_category';
			 $all_category = $wpdb->get_results("SELECT * FROM $table_category");
			 
			 foreach($all_category as $cat):

				if((isset($_POST['category']) && in_array($cat->id,$_POST['category'])) || (!isset($_POST['category']) && in_array($cat->id,$all_user_category))){
				
					$selected = 'selected="selected"';
				}else{
					
					$selected = '';
				}
			
					echo '<option '.$selected.' value="'.$cat->id.'">'.$cat->category_name.'</option>'; 
			endforeach;
		?>
		</select>
		
            </div>
<?php /*?>		<div class="two-col">
			<label for="account_testimonial"><?php _e( 'Testimonial of Strange Products', 'woocommerce' ); ?> </label>
			<textarea name="testimonial" maxlength="1400" placeholder="Max Length is 1400 Characters." rows="8" ><?php echo esc_attr( $user_account->testimonial ); ?></textarea>
        </div><?php */?>
		</p>
      <?php /*} else{ ?>  
      		<p class="form-row form-row-wide">
            <div class="two-col">
                <label for="account_category"><?php _e( 'Racing Class', 'woocommerce' ); ?></label>
                <select name="modelclass" id="modelclass" class="search_select">
                <option value=""></option>';
                <?php 
                $model_class = $wpdb->get_results("select * from ".$wpdb->prefix."racing_class");
                
                foreach($model_class as $mclass){
                    
                    if((isset($_POST['modelclass']) && ($_POST['modelclass'] == $mclass->model_class))){
                        
                        $selected = 'selected="selected"';
                    }else{
                        
                        $selected = '';
                    }
                    
                    echo '<option '.$selected.' value="'.$mclass->model_class.'">'.$mclass->model_class.'</option>';
                        
                }
                ?>
                </select>
        </div>
		<div class="two-col">
			<label for="modelmake"><?php _e( 'Vehicle Make', 'woocommerce' ); ?> </label>
            <select name="modelmake" id="modelmake" class="search_select">
            <option value=""></option>
            <?php	
                $model_make = $wpdb->get_results("select * from ".$wpdb->prefix."vehicle_make");
            
                foreach($model_make as $mmake){
                    
                    if((isset($_POST['modelmake']) && ($_POST['modelmake'] == $mmake->model_make))){
                        
                        $selected = 'selected="selected"';
                    }else{
                        
                        $selected = '';
                    }
                    
                    echo '<option '.$selected.' value="'.$mmake->model_make.'">'.$mmake->model_make.'</option>';
                        
                }
            ?>	
            </select>
        </div>
        
         <div class="one-col">
            <label for="account_testimonial"><?php _e( 'Testimonial of Strange Products', 'woocommerce' ); ?> </label>
            <textarea name="testimonial" maxlength="1400" cols="5" placeholder="Max Length is 1400 Characters.">
            <?php echo esc_attr( $user_account->testimonial ); ?>
            </textarea>
        </div>
		</p>
      
      
      <?php } */?>
        <p class="form-row form-row-wide">
         <div class="two-col">
			<label for="account_fax"><?php _e( 'Phone', 'woocommerce' ); ?></label>
			 <input type="text" class="input-text" name="fax" id="fax" value="<?php echo esc_attr( $user_account->billing_phone ); ?>" />
            </div>
        <?php /*?><div class="two-col">
			<label for="account_video_url"><?php _e( 'URL to a video on YouTube or Vimeo', 'woocommerce' ); ?></label>
			 <input type="text" class="input-text" name="url_video" id="url_video" value="<?php echo esc_attr( $user_account->url_video ); ?>" />
         </div><?php */?>
		<div class="two-col">
			<label for="account-url"><?php _e( 'URL to Your Website', 'woocommerce' ); ?> </label>
			 <input type="text" class="input-text" name="website" id="website" value="<?php echo esc_attr( $user_details[0]->user_url ); ?>" />
        </div>
		</p>
         <p class="form-row form-row-wide">
       
		<div class="one-col">
        <?php //if($user_account->roles[0]=='dealer'){ $dealer_level = "Describe Your Business";}else{$dealer_level = "Describe Your Vehicle";}?>
			<label for="account_description"><?php _e( "Describe Your Business", 'woocommerce' ); ?> </label>
			 <textarea name="description" id="description" maxlength="1400" cols="5" placeholder="Max Length is 1400 Characters."><?php echo esc_attr( $user_account->description ); ?></textarea>
        </div>
		</p>
   
        
		<p class="form-row form-row-wide">
        <div class="two-col">
			<label for="password_current"><?php _e( 'Current Password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_current" id="password_current" value="<?php echo esc_attr( $user_account->password_current ); ?>" />
		</div>
        <div class="two-col">
			<label for="password_1"><?php _e( 'New Password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_1" id="password_1" value="<?php echo esc_attr( $user_account->password_1 ); ?>" />
        </div>
		</p>
		
		<p class="form-row form-row-wide">
        <div class="two-col">
			<label for="password_2"><?php _e( 'Confirm New Password', 'woocommerce' ); ?></label>
			<input type="password" class="input-text" name="password_2" id="password_2" value="<?php echo esc_attr( $user_account->password_2 ); ?>" />
            </div>
         <div class="two-col">
         </div>
		</p>	
       <div class="clearfix"></div>
        <div class="fieldset">  
        	<h2 class="legend upload-picture">Upload Profile Picture</h2>
            <ul class="form-list1">
                    <li style="clear:both; margin:0 0 0 0; border-bottom:2px solid #515151">
                        <div class="newupload" data-id="1">Upload an image</div> 
                        <div id="example1" class="example" style="clear:both;">
                        
                        <div id="example1" class="example" style="clear:both;">
                         <?php
						 $wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
						 $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImageone =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_account->ID and image_no =1");
						 
						 if(count($showProfileImageone) > 0 && file_exists($wp_root_path.$showProfileImageone[0]->user_profile_image)){ ?>
                        
                        <div class="default1" id="default1">
                        <div class="cropMain custom-crop" id="cropMain1">
                        <div class="crop-container" style="width: 800px; height: 330px;">
                        	<div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                             <img class="crop-img" src="<?php echo get_site_url().'/'.$showProfileImageone[0]->user_profile_image;?>" style="z-index: 5999; top: -73px; left: 40px; width: 720px; height: 540px;">
                        </div>
                        </div>

                        </div>
                                                
                         <?php
						 }
						 else{?>
                         <div class="preview-wrapper">
                            <img src="<?php echo get_stylesheet_directory_uri();?>/images/profilephotoplaceholder.gif" width="700" height="250">
                        </div>
                     <?php }?> 
                        
                       </div>
                       </div>
                    </li>
                    <div class="clear"></div>
                    
            
                    <?php /*?><li style="border-bottom:2px solid #666666"> 
                       
                        <div class="common-box left">
                        <div class="newupload" data-id="2">Upload an image</div>
                            <div id="example2" class="example">
                            
                         <?php 
						 $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImagetwo =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_account->ID and image_no =2");

						  if(count($showProfileImagetwo) > 0 && file_exists($wp_root_path.$showProfileImagetwo[0]->user_profile_image)){ 
							?>
                            	<div class="default2" id="default2"><div class="cropMain" id="cropMain2"><div class="crop-container" style="width: 420px; height: 260px;"><div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                               <img  src="<?php echo get_site_url().'/'.$showProfileImagetwo[0]->user_profile_image;?>" /> 
                                </div></div>
                                
                                </div>
                                <canvas width="340" height="180" id="canvas2" class="output" style="display: none;"></canvas>
                             <?php } ?>
                            
                            </div>
                            

                            
                             
                         </div>
                        
                        <div class="common-box right">
                        <div class="newupload" data-id="3">Upload an image</div>
                            <div id="example3" class="example">
                            
						<?php 
                         $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImagethree =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_account->ID and image_no =3");
						  if(count($showProfileImagethree) > 0 && file_exists($wp_root_path.$showProfileImagethree[0]->user_profile_image)){ 
							?>
                            	<div class="default3" id="default2"><div class="cropMain" id="cropMain2"><div class="crop-container" style="width: 420px; height: 260px;"><div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                               <img  src="<?php echo get_site_url().'/'.$showProfileImagethree[0]->user_profile_image;?>" /> 
                                </div></div>
                                
                                </div>
                                <canvas width="340" height="180" id="canvas2" class="output" style="display: none;"></canvas>
                             <?php } ?>
                            
                            </div>
                            
                            </div> 
                    </li><?php */?>	
            
                    <?php /*?><li>
                        
                        <div class="common-box left">
                        <div class="newupload" data-id="4">Upload an image</div>
                            <div id="example4" class="example">
                             <?php 
							 $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImagefour =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_account->ID and image_no =4");
						  if(count($showProfileImagefour) > 0 && file_exists($wp_root_path.$showProfileImagefour[0]->user_profile_image)){ 
							?>
                            	<div class="default4" id="default2"><div class="cropMain" id="cropMain2"><div class="crop-container" style="width: 420px; height: 260px;"><div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                               <img  src="<?php echo get_site_url().'/'.$showProfileImagefour[0]->user_profile_image;?>" /> 
                                </div></div>
                                
                                </div>
                                <canvas width="340" height="180" id="canvas2" class="output" style="display: none;"></canvas>
                             <?php } ?>
                            
                            
                            </div>
                        </div>
                            
                        <div class="common-box right">
                        <div class="newupload" data-id="5">Upload an image</div>
                            <div id="example5" class="example">
                            <?php 
							 $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImagefive =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_account->ID and image_no =5");
						  if(count($showProfileImagefive) > 0 && file_exists($wp_root_path.$showProfileImagefive[0]->user_profile_image)){ 
							?>
                            	<div class="default5" id="default2"><div class="cropMain" id="cropMain2"><div class="crop-container" style="width: 420px; height: 260px;"><div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                               <img  src="<?php echo get_site_url().'/'.$showProfileImagefive[0]->user_profile_image;?>" /> 
                                </div></div>
                                
                                </div>
                                <canvas width="340" height="180" id="canvas2" class="output" style="display: none;"></canvas>
                             <?php } ?>
                            
                            
                            </div> 
                        </div>
                    </li><?php */?>
            
                    <div class="clear"></div>		    
            </ul>
   		</div>
        	
		<div class="clear"></div>
		<p><input type="submit" class="fusion-button button-default button-medium button default medium alignright" name="custom_save_account_details" value="<?php _e( 'Save changes', 'woocommerce' ); ?>"/></p>
        <div id="crop_loader" style="float: right;margin-right: 60px;margin-top: -10px; display:none;"><img src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif" alt="Loader" /></div>
       
       

		<?php wp_nonce_field( 'custom_save_account_details' ); ?>
		<input type="hidden" name="action" value="custom_save_account_details"/>
		<div class="clearboth"></div>
        <input type="hidden" id="user_cropimage" name="user_cropimage" value="" />
	</form>
    <input type="file" class="uploadfile" id="uploadfile">
    <script>

		//  cropper settings
		// --------------------------------------------------------------------------

		// create new object crop
		// you may change the "one" variable to anything
		var one = new CROP();

		// link the .default class to the crop function
		//one.init('.default1');
		//one.init('.default2');
		//one.init('#default4');

		// load image into crop
		//one.loadImg('img/one.jpg');


		//  on click of button, crop the image
		// --------------------------------------------------------------------------

		//jQuery('body').on("click", "button", function() {
		//jQuery(".cropbutton").click(function() {

		var productImageNo =0 ;		
		
		function cropimageTosave()
		{	
	          
			// grab width and height of .crop-img for canvas
			jQuery(".button-default").hide();
			jQuery("#crop_loader").show();
			var	width = jQuery('#cropMain'+productImageNo+' .crop-container').width() - 80,  // new image width
				height = jQuery('#cropMain'+productImageNo+' .crop-container').height() - 80;  // new image height

			jQuery('#canvas'+productImageNo).remove();
			jQuery('#default'+productImageNo).after('<canvas width="'+width+'" height="'+height+'" id="canvas'+productImageNo+'"/>');
           
			var ctx = document.getElementById('canvas'+productImageNo).getContext('2d'),
				img = new Image,
				w = coordinates(one).w,
			    h = coordinates(one).h,
			    x = coordinates(one).x,
			    y = coordinates(one).y;
   
			img.src = coordinates(one).image;
       
			img.onload = function() {
				
				// draw image
			    ctx.drawImage(img, x, y, w, h, 0, 0, width, height);

			    // display canvas image
				jQuery('#canvas'+productImageNo).addClass('output').show().delay('4000').fadeOut('slow');
               // jQuery('#canvas'+productImageNo).addClass('output').show();  
			  
			  var canvas  = document.getElementById('canvas'+productImageNo);
				var dataUrl = canvas.toDataURL();
			 	//alert(dataUrl+"-test-"+productImageNo);
				// save the image to server
//				jQuery.ajax({
//					type: "post",
//					dataType: "json",
//					url: "save.php",
//					data: { image: dataUrl , imgno: productImageNo}
//				})
//				.done(function(data) {
//                	//alert("success"+data["url"]);
//					// You can pull the image URL using data.url, e.g.:
//					// jQuery('body').append('<img src="'+data.url+'" />');
//
//				});
//				
				
			jQuery.ajax({
				type: 'POST', 
				url: '<?php echo home_url();?>/wp-admin/admin-ajax.php', 
				data: {'action': 'save_user_crop_image', 'image': dataUrl , 'imgno':productImageNo},
				success: function(data){ 
					
					crop_image = jQuery('#user_cropimage').val();
					//alert(data+"---"+crop_image);
					
					if(crop_image == ''){
						
						jQuery('#user_cropimage').val(data);
						jQuery("#crop_loader").hide();
						jQuery(".button-default").show();
					}else{
						
						jQuery('#user_cropimage').val(crop_image+","+data);
						jQuery("#crop_loader").hide();
						jQuery(".button-default").show();
					}
				}
			});

			}

		}


		//  on click of .upload class, open .uploadfile (input file)
		// --------------------------------------------------------------------------

//		jQuery('body').on("click", ".newupload", function() 
//		{
//		
//		    jQuery('.uploadfile').click();
//		});
		
		jQuery( ".newupload" ).each(function(index)
		 {
		    
			
			jQuery(this).click(function()
			{
				var upid= jQuery(this).attr("data-id");
				productImageNo = upid;
				productImageNo = index+1;
				jQuery('.uploadfile').click();
		    });		  
		});

		// on input[type="file"] change
		jQuery('.uploadfile').change(function() { 		
		    loadImageFile();			
		    // resets input file
		    jQuery('.uploadfile').wrap('<form>').closest('form').get(0).reset();
		    jQuery('.uploadfile').unwrap();

		 });


		//  get input type=file IMG through base64 and send it to the cropper
		// --------------------------------------------------------------------------

		oFReader = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
		
		
		function loadImageFile()
		 {

		    if(document.getElementById("uploadfile").files.length === 0) return

		    var oFile = document.getElementById("uploadfile").files[0];

		    if(!rFilter.test(oFile.type)) {
		        return;
		    }

		  oFReader.readAsDataURL(oFile);
		}

		oFReader.onload = function (oFREvent) 
		{
		
		    jQuery('#example'+productImageNo).html('<div class="default'+productImageNo+'" id="default'+productImageNo+'"><div class="cropMain" id="cropMain'+productImageNo+'"></div><div class="cropSlider" id="cropSlider'+productImageNo+'"></div><input style="margin-top:0px" type="button" value="Crop" class="cropbutton" onclick="cropimageTosave();"><span style="font-size:12px; font-style:italic;margin-top:3px">(Crop button must be used to set the photo.)</span></div>');
			one = new CROP();
			one.init('.default'+productImageNo);
			one.loadImg(oFREvent.target.result);

		};
		
		

	</script>
<style>
textarea#description {
    max-width: 100%;
    width: 100%;
    height: 100px;
	color: #000!important;
}
h2.legend.upload-picture {
    margin-bottom: 0px;
    margin-top: 20px;
    padding-left: 10px;
}
</style>
</div>
<?php
}

// custom price management for custom user group and product attribute set
//add_action( 'woocommerce_add_to_cart', 'add_custom_price');
//add_action( 'woocommerce_before_calculate_totals', 'add_custom_price',99);

add_action( 'woocommerce_before_calculate_totals', 'add_custom_price', 10, 1);

function add_custom_price($cart_obj) {
	
	global $woocommerce,$product,$wpdb;
	
	 //  This is necessary for WC 3.0+
//    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
//        return;
		
	//$cart_obj  $cart_obj->cart_contents
	//$value['product_id'] == $target_product_id;	
	
//	echo "<pre>";
//	print_r(WC()->cart->get_cart());
//	echo "</pre>";
//	
//	echo "<pre>";
//	print_r($cart_obj->get_cart());
//	echo "</pre>";
	
		if(is_user_logged_in()){
			
				$current_user = wp_get_current_user();
				$i = 1;
				
				//$cart_obj->get_cart()
				foreach (WC()->cart->get_cart() as $key => $value ) {
					
					//echo "<br>".$value['product_id']."===============".$value['_gform_total'];
					//===========replace product title with product sub title
					$product_subtitle = get_post_meta( $value['product_id'], 'wc_ps_subtitle', true );
					
					$value['data']->post->post_title = $product_subtitle;
					
					//Minimum Advertised Pricing Options
					//$advr_price =  get_post_meta( $value['product_id'], '_minimum_advertised_price', true );
						
									
									
									$k = 0;
									if(($key = array_search('administrator', $current_user->roles)) !== false) {
										//unset($current_user->roles[$key]);
										$k +=1;
									}
									
									$attribute_set = get_post_meta( $value['product_id'], '_attribute_set_key', true );
									
									//echo "select * from {$wpdb->prefix}product_attribute_price_rules where prodcut_attribute_set_id='".$attribute_set."' and user_role_key='".$current_user->roles[$k]."' and status=1";
									
									
									$price_discount_arr = $wpdb->get_results("select * from {$wpdb->prefix}product_attribute_price_rules where prodcut_attribute_set_id='".$attribute_set."' and user_role_key='".$current_user->roles[$k]."' and status=1");
							
										if(count($price_discount_arr) > 0){
											
											
												$product_price = get_post_meta( $value['product_id'], '_price', true );
												
												//$product_price = floatval( $value['data']->get_price() );
												//$final_disount_price = $value['data']->price  * $price_discount_arr[0]->discount_price_rules;
												$final_disount_price = $product_price * $price_discount_arr[0]->discount_price_rules;
												//$value['data']->price = $final_disount_price;
													
												if(isset($value['_gform_total']) && !empty($value['_gform_total'])){
													
													$gravity_discount = $value['_gform_total'];
													
												}else{
													
													$gravity_discount = 0;
												}
												 
												 $value['data']->set_price($final_disount_price + $gravity_discount);
										}
							 
					
				}
				
								if(is_user_logged_in() && (in_array('dealer', wp_get_current_user()->roles) || in_array('dealer_40', wp_get_current_user()->roles) || in_array('dealer_50', wp_get_current_user()->roles) || in_array('dealer_60', wp_get_current_user()->roles))){
					
					if(WC()->cart->has_discount( "mygears19" )){
						
							WC()->cart->remove_coupon( "mygears19" );
							wc_print_notice( 'Sorry, this coupon is not valid.', 'error' );
							?>
							<style>.woocommerce-message{display: none;}</style>
							
							<?php
					}
				}
		
		}
}


// dealer locator function 
function searchDealor(){

		global $wpdb;
		$countries_obj   = new WC_Countries();
		$countries   = $countries_obj->__get('countries');
		
	if(!(isset($_GET['id']) && !empty($_GET['id']))){	
	?>
        <div id="container">
          <div style="margin:20px auto; text-align: center;">
           <h2 class="scp">Strange Dealer Locator</h2>
             <div class="sc_profile">
            	<div class="sc_profile_inner">
                <div class="sc_profile_left"><p><img src="<?php echo get_stylesheet_directory_uri();?>/images/strangecar_banner.png" alt="dealer_ocator"/></p></div>
                  <div class="search_profile">
                  <h3>Search Dealer</h3>
                  <div class="block-content">
                    <form action="" method="post">
                        <select name="category" id="category" class="input-text2">
                             <option value="">Please select Category</option>
                                <?php
                                 $table_category = $wpdb->prefix.'user_category';
                                 $all_category = $wpdb->get_results("SELECT * FROM $table_category");
                                 
                                 foreach($all_category as $category):
                        
                    
                                if(isset($_POST['category']) && ($_POST['category']==$category->id)){
                                
                                $selected = 'selected="selected"';
                            }else{
                                
                                $selected = '';
                            }
                        
                            echo '<option '.$selected.' value="'.$category->id.'">'.$category->category_name.'</option>'; 
                            endforeach;
                        ?>
                        </select> 
                          <select id="country" name="country" class="input-text2">
                         <!-- <option value="">Select Country</option>-->
                          <?php
/*                            foreach($countries as $key => $value):
                    
                        if(isset($_POST['country']) && $_POST['country']==$key){
                            
                            $selected = 'selected="selected"';
                        }else{
                            
                            if($key == 'US'){
                                
                                $selected = 'selected="selected"';
                            }else{
                                
                                $selected = '';
                            }
                    
                        }
                    
                        echo '<option '.$selected.' value="'.$key.'">'.$value.'</option>'; 
                    endforeach;*/
                          ?>
                                <option value="US">United States</option>
                                <option value="AE">United Arab Emirates</option>
                                <option value="AU">Australia</option>
                                <option value="CA">Canada</option>
                                <option value="DK">Denmark</option>
                                <option value="FI">Finland</option>
                                <option value="GB">United Kingdom</option>
                                <option value="JP">Japan</option>
                                <option value="MT">Malta</option>
                                <option value="MX">Mexico</option>
                                <option value="NO">Norway</option>
                                <option value="NZ">New Zealand</option>
                                <option value="PR">Puerto Rico</option>
                                <option value="SE">Sweden</option>
                                <option value="TH">Thailand</option>
                                <option value="TT">Trinidad and Tobago</option>
                                <option value="VG">British Virgin Islands</option>
                                <option value="ZA">South Africa</option>
                                <option value="">Other</option>
                          </select>
                          <input type="text" name="zipcode" id="zipcode" value="" placeholder="Enter Zip Code" class="deal">
                          <input type="button" onclick="mloactor();" name="submit" value="Search"/>
                          <br>
                        </form>
                    </div>
                  </div>
                  </div>
                  </div>
		     <script>
				function mloactor(){
					var category = document.getElementById('category').value;
					var country = document.getElementById('country').value;
					var zipcode = document.getElementById('zipcode').value;
					//alert(category);
					jQuery('#results').html('<img style="text-align:center;" src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif">');
					jQuery.ajax({
						type: 'POST', 
						url: '../wp-admin/admin-ajax.php', 
						data: {'action': 'dealer_locator_list', 'category':category,'country':country,'zipcode':zipcode},
						success: function(data){ 
						//alert(data);
						jQuery('#results').html(data);
					}
				});
				}
				
		</script>
            <div id="results" class="profile_area"></div>	
      </div>
    </div>
	<?php
	}else{
		$wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
		$dealer_id = $_GET['id'];
		$dealer_exists = get_user_by('id', $dealer_id);

		if($dealer_exists){
				$dealer_profile_main_image = $wpdb->get_results("select user_profile_image from ".$wpdb->prefix."user_profile_image where user_id=$dealer_id and image_no=1");
				
//				echo "<pre>";
//				print_r($dealer_exists);
//				echo "</pre>";	
	
				$cat_ID = get_user_meta($dealer_id,'category',true);
		
				$categories_info = $wpdb->get_results("select * from ".$wpdb->prefix."user_category where id in($cat_ID)");
		?>		
	<div class="col-main">
        <div class="page-title"><h1>Dealer Profile</h1></div>
        
        <div class="std">
				<div class="title"><?php if(get_user_meta($dealer_id,'billing_company',true)) {echo get_user_meta($dealer_id,'billing_company',true);}else{echo get_user_meta($dealer_id,'shipping_company',true);}?></div>
		
				<div class="top_car">
					
                     <?php if(count($dealer_profile_main_image) > 0 && file_exists($wp_root_path.$dealer_profile_main_image[0]->user_profile_image)){ ?>
                     
                        <img src="<?php echo get_site_url()."/".$dealer_profile_main_image[0]->user_profile_image;?>">
                      <?php }else{?>
                      
                        <img src="<?php echo get_stylesheet_directory_uri();?>/images/dealerprofilepicmain.jpg"/>
                      <?php }?>
				</div>
				
				<div class="general">
				
					<ul id="leftside">
					<?php foreach($categories_info as $category){?>
						<li><?php echo $category->category_name?></li>
					<?php }?>    
					</ul>
					<div style="clear:both"></div>
			
						<p class="discription"><?php echo get_user_meta($dealer_id,'description',true);?></p>
						
					<div class="clear"></div>
					<?php
					$dealer_profile_image = $wpdb->get_results("select user_profile_image from ".$wpdb->prefix."user_profile_image where user_id=$dealer_id and image_no in(2,3,4,5)");
					
					if(count($dealer_profile_image) < 0){
					?>
					<!-- Start Additioanal image-->
					<div class="myimgparent">
					<?php foreach($dealer_profile_image as $dprofile){
						
							if(file_exists($wp_root_path.$dprofile->user_profile_image)){
						?>  <div class="left">
								<img src="<?php echo get_site_url()."/".$dprofile->user_profile_image;?>">
							</div>
						<?php } }?>        
					</div>
                    <?php }?>  
			
					<!-- End Additioanal image-->
					<div class="clear"></div>
                    
                    <div class="add_outer">
                    <?php if( get_user_meta($dealer_id,'website',true)){?>
                        <div class="left"><a href="<?php echo get_user_meta($dealer_id,'website',true);?>" target="_blank"><?php echo get_user_meta($dealer_id,'website',true);?></a></div><br>
                       <?php }?>
                        <?php 
						$email_option = get_user_meta($dealer_id,'email_option',true);
						
						echo get_user_meta($dealer_id,'billing_phone',true);?><br><?php echo get_user_meta($dealer_id,'billing_address_1',true)." ".get_user_meta($dealer_id,'billing_address_2',true);?><div>
                        <?php echo get_user_meta($dealer_id,'billing_city',true);?>, <?php echo get_user_meta($dealer_id,'billing_state',true);?><br><?php echo get_user_meta($dealer_id,'billing_postcode',true);?><br><?php if($email_option == 'yes') echo $dealer_exists->data->user_email;?>
                         </div>
                        <?php
						$address = get_user_meta($dealer_id,'billing_address_1',true)."+".get_user_meta($dealer_id,'billing_address_2',true)."+".get_user_meta($dealer_id,'billing_city',true)."+".get_user_meta($dealer_id,'billing_state',true)."+".get_user_meta($dealer_id,'billing_country',true);
						$postcode = get_user_meta($dealer_id,'billing_postcode',true);

						if(empty($latitude) && empty($latitude) && !empty($postcode)){
							
							$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$postcode ."&sensor=false";
							$details=file_get_contents($url);
							$result = json_decode($details,true);
							
							$latitude = $result['results'][0]['geometry']['location']['lat'];
							$longitude = $result['results'][0]['geometry']['location']['lng'];
						}else{
							
							$latitude = get_user_meta($dealer_id,'latitude',true);
							$longitude = get_user_meta($dealer_id,'longitude',true);
						}
						
						?>
                        <div class="right2">
                       <iframe width="300" height="200" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.co.in/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=<?php echo $address;?>&amp;aq=&amp;sll=27.141237,80.883382&amp;sspn=11.093745,21.643066&amp;ie=UTF8&amp;hq=&amp;hnear=<?php echo $address;?>&amp;t=m&amp;ll=<?php echo $latitude;?>,<?php echo $longitude;?>&amp;spn=0.019673,0.051413&amp;z=14&amp;output=embed"></iframe>
                       
                       <div style="padding-top:10px;">
                   <?php /*?>     <input type="hidden" value="<?php echo $dealer_id;?>" name="cid">
                        <input type="hidden" value="<?php echo $dealer_exists->data->user_email;?>" name="email"><?php */?>
                        <a href="<?php echo home_url()."/flag-form/";?>"><img  class="btn" title="Flag Inappropriate Content" alt="Flag Inappropriate Content" src="<?php echo get_stylesheet_directory_uri();?>/images/flag.png"></a>
                        </div>
                        </div>
                        
                        <div style="clear:both"></div>
                    </div>
				</div>               
                
		</div>
   </div>

 <?php      }
 
       }
}
// dealer locator shortcode
add_shortcode('dealer_locator', 'searchDealor' );

// customer profile function 
function searchCustomer(){
global $wpdb;
	if(!(isset($_GET['id']) && !empty($_GET['id']))){
	?>
      <div id="container">
        <div style="margin:20px auto; text-align: center;">
           <h2 class="scp">Strange Customer Profiles</h2>
             <div class="sc_profile">
            	<div class="sc_profile_inner">
                <div class="sc_profile_left"><p><img src="<?php echo get_stylesheet_directory_uri();?>/images/strangecar.png" alt="customer_profiles"/></p></div>
                  <div class="search_profile">
                  <h3>Search Profiles</h3>
                  <div class="block-content">
                    <form action="" method="post">
                        <select name="modelclass" id="modelclass" class="search_select">
                                <option value="">Search Class</option>';
                                <?php 
                                $model_class = $wpdb->get_results("select * from ".$wpdb->prefix."racing_class");
                                
                                foreach($model_class as $mclass){
                                    
                                    if((isset($_POST['modelclass']) && ($_POST['modelclass'] == $mclass->model_class))){
                                        
                                        $selected = 'selected="selected"';
                                    }else{
                                        
                                        $selected = '';
                                    }
                                    
                                    echo '<option '.$selected.' value="'.$mclass->model_class.'">'.$mclass->model_class.'</option>';
                                        
                                }
                                ?>
                                </select>
                                <select name="modelmake" id="modelmake" class="search_select">
                                <option value="">Search Make</option>
                                <?php	
                                    $model_make = $wpdb->get_results("select * from ".$wpdb->prefix."vehicle_make");
                                
                                    foreach($model_make as $mmake){
                                        
                                        if((isset($_POST['modelmake']) && ($_POST['modelmake'] == $mmake->model_make))){
                                            
                                            $selected = 'selected="selected"';
                                        }else{
                                            
                                            $selected = '';
                                        }
                                        
                                        echo '<option '.$selected.' value="'.$mmake->model_make.'">'.$mmake->model_make.'</option>';
                                            
                                    }
                                ?>	
                        </select>
                          <input type="text" name="zipcode" id="zipcode" value="" placeholder="Enter Zip Code" class="deal">
                          <input type="button" onclick="ploactor();" name="submit" value="Submit"/><br>
                        </form>
                    </div>
                  </div>
                  </div>
                  </div>
				<script>
                function ploactor(){
                    
                    var modelclass = document.getElementById('modelclass').value;
                    var modelmake = document.getElementById('modelmake').value;
                    var zipcode = document.getElementById('zipcode').value;
                    //alert(category);
                    jQuery('#listOfCustomer').html('<img style="text-align:center;" src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif">');
                    jQuery.ajax({
                        type: 'POST', 
                        url: '../wp-admin/admin-ajax.php', 
                        data: {'action': 'customer_profile_list', 'modelclass':modelclass,'modelmake':modelmake,'zipcode':zipcode},
                        success: function(data){ 
                        //alert(data);
                        jQuery('#listOfCustomer').html(data);
                    }
                });
                }
        </script>
            <div id="listOfCustomer" class="profile_area"></div>	
      </div>
    </div>
	<?php
	}else{
		
		$wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
		$customer_id = $_GET['id'];
		$customer_exists = get_user_by('id', $customer_id);
		
//		echo "<pre>";
//		print_r($customer_exists);
//		echo "<pre>";		
		if($customer_exists){
			
				$customer_profile_main_image = $wpdb->get_results("select user_profile_image from ".$wpdb->prefix."user_profile_image where user_id=$customer_id and image_no=1");
				
				$cat_ID = get_user_meta($customer_id,'category',true);
		
				$categories_info = $wpdb->get_results("select * from ".$wpdb->prefix."user_category where id in($cat_ID)");
		?>	
        	<div class="col-main">
                  <div class="page-title">
                    <h1>Member Profile</h1>
                </div>
                <div class="std">
                <div class="title"><?php echo $customer_exists->data->user_nicename;?></div>
                
                <div class="top_car">
				  <?php if(count($customer_profile_main_image) > 0 && file_exists($wp_root_path.$customer_profile_main_image[0]->user_profile_image)){ ?>
                 
                    <img src="<?php echo get_site_url()."/".$customer_profile_main_image[0]->user_profile_image;?>">
                  <?php }else{?>
                  
                    <img src="<?php echo get_stylesheet_directory_uri();?>/images/profilephotoplaceholder.gif"/>
                  <?php }?>
                </div>
             
                <div class="general">
			
                    <ul>
                    <?php if(get_user_meta($customer_id,'modelclass',true)){?>

                        <li><?php echo get_user_meta($customer_id,'modelclass',true);?></li>
                        <!--<li>|</li>-->
					 <?php } if(get_user_meta($customer_id,'modelmake',true)){?>                          
                        <li><?php echo get_user_meta($customer_id,'modelmake',true);?></li>
                     <?php } ?>
                    </ul>
                  
                    <div style="clear:both"></div>
                 
                     <p class="discription"><?php echo get_user_meta($customer_id,'discription',true)?></p>
                    <div class="clear"></div>
                
                
                    <!-- Additioanal image-->
                    
                    <?php
					$customer_profile_image = $wpdb->get_results("select user_profile_image from ".$wpdb->prefix."user_profile_image where user_id=$customer_id and image_no in(2,3,4,5)");
					
					if(count($dealer_profile_image) > 0){
					?>
					<!-- Start Additioanal image-->
					<div class="myimgparent">
					<?php foreach($customer_profile_image as $cprofile){
						
							if(file_exists($wp_root_path.$cprofile->user_profile_image)){
						?>  <div class="left">
								<img src="<?php echo get_site_url()."/".$cprofile->user_profile_image;?>">
							</div>
						<?php } }?>        
					</div>
                    <?php } ?>  
                <!-- Additioanal image-->
                    <div class="clear"></div>
            
                </div>
            
                <div class="add_outer">
                    <div class="left"><?php echo (get_user_meta($customer_id,'website',true) ? '<a href="'.get_user_meta($customer_id,'website',true).'" target="_blank">'.get_user_meta($customer_id,'website',true).'</a><br>' : ''); echo $customer_exists->data->user_email;?><br>
                </div>
                <div class="right2"></div>
                <div style="clear:both"></div>
<?php /*?>       <div style="float:right; padding-top:10px;">
                    <form method="post" action="http://www.strangeengineering.net/news/index/flag/">
                    <input type="hidden" value="1817" name="cid">
                    <input type="hidden" value="tommy.langolf@ntebb.no" name="email">
                    <input type="image" alt="Flag inappropriate content" title="Flag inappropriate content" src="http://www.strangeengineering.net/skin/frontend/default/shopper/images/flag.png">
                    </form>
                </div><?php */?>
                </div>
            
                <div class="clr"></div>
                </div>
            </div>	
 <?php      }else{
             	
 			}
	}
}
// customer profile shortcode
add_shortcode('customer_profile', 'searchCustomer' );

 // custom featured products listing
    function wc_custom_related_products() {
			   $args = array(  
					'post_type' => 'product',  
					'meta_key' => '_featured',  
					'orderby' => 'menu_order',
					'order' => 'ASC',
					'meta_value' => 'yes',  
					'posts_per_page' => 5  
				);  
				  

				$featured_query = new WP_Query( $args );  
					  
				if ($featured_query->have_posts()) :   
				  
				 	echo '<ul class="list-group">';
					while ($featured_query->have_posts()) :   
					  
						$featured_query->the_post();  
						  
						$product = get_product( $featured_query->post->ID ); 
//						 echo $product->post->post_name;
//						 echo "<pre>";
//						 print_r($product); 
//						 echo "</pre>";
						// Output product information here 
						
							echo '<li class="list-group-item">
									 <span class="list-thum"><a class="product-image" title="'.$product->post->post_title.'" href="'.home_url().'/'.$product->post->post_name.'">'.get_the_post_thumbnail( $product->get_id(), array(61, 61) ).'</a></span>
			<a title="'.$product->post->post_title.'" href="'.home_url().'/'.$product->post->post_name.'">'.$product->post->post_title.'</a>
									<div class="clearfix"></div>
								 </li>';
						
						  
					endwhile; 
					wp_reset_query(); // Remember to reset   
					echo '</ul>';  
				endif;  
				  
				
    }
	
 add_shortcode( 'wc_related_products','wc_custom_related_products' );

//////////////Start //////////////// 
 /**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function myplugin_add_meta_box() {

	$screens = array( 'product' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'myplugin_sectionid',
			__( 'Attribute Set Name', 'myplugin_textdomain' ),
			'myplugin_meta_box_callback',
			$screen
		);
	}
}
add_action( 'add_meta_boxes', 'myplugin_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function myplugin_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'myplugin_meta_box', 'myplugin_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$value = get_post_meta( $post->ID, '_attribute_set_key', true );
	//$pro_arr = explode(",",$value);

		?>	
		<table  cellspacing="0" cellpadding="0" width="80%">
		<?php
		global $wpdb;
		$tbl = $wpdb->prefix . "product_attribute_sets"; //Good practice
		$result = $wpdb->get_results("SELECT * FROM $tbl where status=1 order by page_order" );	
			
		?>
                <tr>
                    <td><strong>Attribute Set</strong></td>
                     <td><select name="myplugin_new_field" style="width:180px">
                     	 <option value="">-Please Select-</option>	
                     <?php foreach($result as $res_s_prod){ 
					 
					 	if($value==$res_s_prod->id){
						 
						 	$selected = 'selected="selected"';
						}else{
							
							$selected = '';
						}
					 ?>
                     
                     		<option <?php echo $selected;?> value="<?php echo $res_s_prod->id;?>"><?php echo $res_s_prod->set_name;?></option>	
                     <?php } ?>
                     </select></td>
                </tr>
		</table>
		<?php
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function myplugin_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['myplugin_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['myplugin_meta_box_nonce'], 'myplugin_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'product' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */
	
	// Make sure that it is set.
	if ( ! isset( $_POST['myplugin_new_field'] ) ) {
		return;
	}

	// Sanitize user input.
	//$my_data = sanitize_text_field( implode(",",$_POST['myplugin_new_field']));
	$my_data = sanitize_text_field( $_POST['myplugin_new_field']);

	// Update the meta field in the database.
	update_post_meta( $post_id, '_attribute_set_key', $my_data );
}
add_action( 'save_post', 'myplugin_save_meta_box_data' );	

//////////////End ////////////////

// get products details by part no
add_shortcode('product_price','get_woocommerce_product_price');

function get_woocommerce_product_price($atr){
	ob_start();
	get_woocommerce_product_price_method($atr);
	$output_string=ob_get_contents();;
	ob_end_clean();
	return $output_string;
}

function get_woocommerce_product_price_method($atr){

global $wpdb;


	if(!empty($atr['partno'])){
		
		$id = wc_get_product_id_by_sku( $atr['partno'] );
		
		if(!empty($id)){
			
			$product = wc_get_product( $id );
			
			echo '<div class="part_'.$product->get_sku().'" style="display:none;">';
			echo clean_spaces($product->get_title());
			echo '<input type="hidden" id="price_'.$product->get_sku().'" value="'.intval($product->get_price()).'">';
			echo $product->get_price_html();
			echo "<br>";
			echo $product->get_sku(); 
			echo '</div>';
		}
	}

}

// upload file set in product by usint CSV
add_shortcode('EXPORTATTRSETS','upload_woocommerce_product_attributes');

function upload_woocommerce_product_attributes(){
	ob_start();
	upload_woocommerce_product_attributes_sets();
	$output_string=ob_get_contents();;
	ob_end_clean();
	return $output_string;
}

function upload_woocommerce_product_attributes_sets(){

global $wpdb;
global $parent_cat;
global $post;
	

	if (isset($_POST['submit'])) {
		
		echo "Have a Nice Day...";
		exit;
//=====================import csv file for gear set product weights=======================================

	if (is_uploaded_file($_FILES['filename']['tmp_name'])) {
			
			echo "<h1>" . "File ". $_FILES['filename']['name'] ." uploaded successfully." . "</h1>";
			// echo "<h2>Displaying contents:</h2>";
			 //readfile($_FILES['filename']['tmp_name']);
		}

		//Import uploaded file to Database
		$handle = fopen($_FILES['filename']['tmp_name'], "r");

/*		$i=0;
		$j = 1;
		while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
		
			if($i!=0 && trim($data[2]) != ''){

				 if(!(trim($data[0]) == '' || trim($data[0]) == 0)){
				 
					$parent_cat = trim($data[0]);
					
					// ==================insert category for level 1====================
					
							$first_parent = 600;
							$term_data_1 =  $wpdb->get_results("SELECT * FROM wp_terms WHERE term_id = $parent_cat");
							
							$wpdb->insert( 
								'wp_terms', 
								array( 
									'name' => $term_data_1[0]->name, 
									'slug' => $term_data_1[0]->slug."-3-2-1",
									'term_group' => $term_data_1[0]->term_group, 
								) 
							);
							
							$first_id = $wpdb->insert_id;
							
							$term_data_2 =  $wpdb->get_results("SELECT * FROM wp_term_taxonomy WHERE term_id = $parent_cat");
							
							$wpdb->insert( 
								'wp_term_taxonomy', 
								array( 
									'term_id' => $first_id, 
									'taxonomy' => $term_data_2[0]->taxonomy,
									'description' => $term_data_2[0]->description,
									'parent' => $first_parent,
									'count' => $term_data_2[0]->count,
									 
								) 
							);
							
							$term_taxonomy_id = $wpdb->insert_id;
							
							$term_data_3 =  $wpdb->get_results("SELECT * FROM wp_term_relationships WHERE term_taxonomy_id = ".$term_data_2[0]->term_taxonomy_id);
							
							foreach($term_data_3 as $res){
								
								
									$wpdb->insert( 
										'wp_term_relationships', 
										array( 
											'object_id' => $res->object_id, 
											'term_taxonomy_id' => $term_taxonomy_id,
											'term_order' => $res->term_order,
										) 
									);
							}
							
							
							$term_data_4 =  $wpdb->get_results("SELECT * FROM wp_termmeta WHERE term_id = $parent_cat");
							
							foreach($term_data_4 as $res){
								
								
									$wpdb->insert( 
										'wp_termmeta', 
										array( 
											'term_id' => $first_id,
											'meta_key' => $res->meta_key,
											'meta_value' => $res->meta_value,
										) 
									);
							}										
					
				 }
				 
				 
				// ==================insert category for level 2===================
				
				$parent_middle_cat = trim($data[1]);
				
							$term_data_1 =  $wpdb->get_results("SELECT * FROM wp_terms WHERE term_id = $parent_middle_cat");
							
							$wpdb->insert( 
								'wp_terms', 
								array( 
									'name' => $term_data_1[0]->name, 
									'slug' => $term_data_1[0]->slug."-3-2-1",
									'term_group' => $term_data_1[0]->term_group, 
								) 
							);
							
							$second_id = $wpdb->insert_id;
							
							$term_data_2 =  $wpdb->get_results("SELECT * FROM wp_term_taxonomy WHERE term_id = $parent_middle_cat");
							
							$wpdb->insert( 
								'wp_term_taxonomy', 
								array( 
									'term_id' => $second_id, 
									'taxonomy' => $term_data_2[0]->taxonomy,
									'description' => $term_data_2[0]->description,
									'parent' => $first_id,
									'count' => $term_data_2[0]->count,
									 
								) 
							);
							
							$term_taxonomy_id = $wpdb->insert_id;
							
							$term_data_3 =  $wpdb->get_results("SELECT * FROM wp_term_relationships WHERE term_taxonomy_id = ".$term_data_2[0]->term_taxonomy_id);
							
							foreach($term_data_3 as $res){
								
								
									$wpdb->insert( 
										'wp_term_relationships', 
										array( 
											'object_id' => $res->object_id, 
											'term_taxonomy_id' => $term_taxonomy_id,
											'term_order' => $res->term_order,
										) 
									);
							}
							
							
							$term_data_4 =  $wpdb->get_results("SELECT * FROM wp_termmeta WHERE term_id = $parent_middle_cat");
							
							foreach($term_data_4 as $res){
								
								
									$wpdb->insert( 
										'wp_termmeta', 
										array( 
											'term_id' => $second_id,
											'meta_key' => $res->meta_key,
											'meta_value' => $res->meta_value,
										) 
									);
							}	

				// ==================insert category for level 3===================
				
				
				$last_child_arr = explode("#",trim($data[2]));
				
				if(count($last_child_arr) == 0){
					
					$last_child_arr[0] = trim($data[2]);
				}
				
				foreach($last_child_arr as $id){
					
					
							$term_data_1 =  $wpdb->get_results("SELECT * FROM wp_terms WHERE term_id = $id");
							
							$wpdb->insert( 
								'wp_terms', 
								array( 
									'name' => $term_data_1[0]->name, 
									'slug' => $term_data_1[0]->slug."-3-2-1",
									'term_group' => $term_data_1[0]->term_group, 
								) 
							);
							
							$third_id = $wpdb->insert_id;
							
							$term_data_2 =  $wpdb->get_results("SELECT * FROM wp_term_taxonomy WHERE term_id = $id");
							
							$wpdb->insert( 
								'wp_term_taxonomy', 
								array( 
									'term_id' => $third_id, 
									'taxonomy' => $term_data_2[0]->taxonomy,
									'description' => $term_data_2[0]->description,
									'parent' => $second_id,
									'count' => $term_data_2[0]->count,
									 
								) 
							);
							
							$term_taxonomy_id = $wpdb->insert_id;
							
							$term_data_3 =  $wpdb->get_results("SELECT * FROM wp_term_relationships WHERE term_taxonomy_id = ".$term_data_2[0]->term_taxonomy_id);
							
							foreach($term_data_3 as $res){
								
								
									$wpdb->insert( 
										'wp_term_relationships', 
										array( 
											'object_id' => $res->object_id, 
											'term_taxonomy_id' => $term_taxonomy_id,
											'term_order' => $res->term_order,
										) 
									);
							}
							
							
							$term_data_4 =  $wpdb->get_results("SELECT * FROM wp_termmeta WHERE term_id = $id");
							
							foreach($term_data_4 as $res){
								
								
									$wpdb->insert( 
										'wp_termmeta', 
										array( 
											'term_id' => $third_id,
											'meta_key' => $res->meta_key,
											'meta_value' => $res->meta_value,
										) 
									);
							}
					
				}
							



					
//								$wpdb->update( 
//									'wp_user_crop_image', 
//									array( 
//										'user_id' => $cust_id,  // string
//										'user_crop_image' => $fileName,  // integer (number) 
//										'user_crop_image_no' => $baseAttID,
//									), 
//									array( 'id' => $myrows[0]->id )
//								);	
//								
//								
//							 echo "<pre>";	
//							 print_r($last_child_arr);
				 
				 echo $i."-----------------".$parent_cat."-----------".trim($data[1]);
				 echo "<br>";
				
			}
			
			$i++;
			
		}
	
		fclose($handle);*/	

//===========================export spreadsheet Part number and image URL of product==============================
		$res = $wpdb->get_results("select * from {$wpdb->prefix}posts where post_type='product' and post_status='publish' limit 0,10");	
		$count = count($res);

		if($count > 0){
			
				$i=1;
				$file = "S NO,Product ID, Part number,  Image URL\r";
				
				foreach($res as $row){
					
					$attached_file = get_post_meta(get_post_thumbnail_id( $row->ID ),'_wp_attached_file',true);
					$image_info = wp_get_attachment_image_src( get_post_thumbnail_id( $row->ID ), 'single-post-thumbnail' );

					$image = wp_get_image_editor( $image_info[0] );
					$product_sku = get_post_meta($row->ID,'_sku',true);
					
					$image_arr = explode(".",$attached_file);
					$count = count($image_arr);
					$image_type = $image_arr[$count-1];
					
					if ( ! is_wp_error( $image ) ) {
						
							if($product_sku){
							
									$final_image_name = $product_sku.".".$image_type;
									$image->resize( 500, 500, false );
									$image->save('/home/strangeeng/public_html/products_catalog/'.$final_image_name);
									
									$file.=  	 "\n".$i.','.
													$row->ID.','.
													$product_sku.','.
													get_site_url()."/products_catalog/".$final_image_name.',';
														
									$i++;
							}
					}
					
						
				}
				
				//echo "Total image ALT tag update ====>".$i;
				
				header("Content-Type: application/csv");
				header("Content-Disposition: attachment; filename=\"products_catalog.csv\";" );
				header("Expires: 0");
				echo $file;	
				exit;
	}
		
/*		$i=0;
		$j = 0;
		while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
		
			if(trim($data[0]) != ''){

				$product_id = wc_get_product_id_by_sku( trim($data[0]) );
					
						if(!empty($product_id)){
									
								//echo trim($data[0]) ."--->";	
								$price = number_format(trim($data[1]), 2, '.', '');
								
								update_post_meta($product_id,'_regular_price',$price);	
								update_post_meta($product_id,'_price',$price);
								
								echo trim($data[0])."&nbsp;&nbsp;&nbsp;&nbsp;".$price;
								echo "<br>";	
								$j++;															 
						}else{
							
							//echo "<br>".trim($data[0]).",".trim($data[1]);
							$i++;
						}	
				}
			
		}
	
		echo "<br>";	
		echo $i." found sku ".$j;
		fclose($handle);*/
		

		
		
//	export csv for gear set product	
/*		$category_array = array('standard-gear-sets','gear-sets-1-2','9-1-2-10-inch-pro-gear-sets','gear-sets-installation-kits','ford-9in-standard-gear-sets','ford-9in-9-5in','ford-9-1-2-10-pro-gears','gear-sets-1-4','gear-sets-2','gear-sets-1','gear-sets','gear-sets-installation-kits-1','standard-gear-sets-1','gear-sets-1-3','9-1-2-10-pro-gear-sets','gear-sets-1-6','gear-sets-1-7','gear-sets-1-5','mopar-8-3-4','mopar-8-75in');
				
			$csv_file = "S NO,Product ID,SKU,Weight,\r";
				
			foreach($category_array as $cat){
				
				
				$arg = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1000, 'product_cat' => $cat, 'orderby' => 'rand' );	
				 
					$loop = new WP_Query( $arg );
				
					$i = 1;
					while ( $loop->have_posts() ) : $loop->the_post(); ; 
			
						$weights = get_post_meta($loop->post->ID,'_weight',true);
						
						if($weights == '' || $weights == '0.01'){
						
								$sku = get_post_meta($loop->post->ID,'_sku',true);
								
								$csv_file.=  	 "\n".$i.','.
																	$loop->post->ID.','.
																	$sku.','.
																	$weights.',';
																	
								$i++;									
						}
						
						
					endwhile; 
					wp_reset_query();
			}



			header("Content-Type: application/csv");
			header("Content-Disposition: attachment; filename=\"products_weight.csv\";" );
			header("Expires: 0");
			echo $csv_file;	
			exit; */
	
		
// export csv for product has no images		
/*		$res = $wpdb->get_results("select id,post_title from {$wpdb->prefix}posts where post_type='product' and post_status='publish'");	
		$count = count($res);
		
		if($count > 0){
				$i=1;
				$file = "S NO,Product ID,SKU,Quantity\r";
				foreach($res as $row){
						
						$product_stock = get_post_meta($row->id,'_stock_status',true);
						
						$product_qty = get_post_meta($row->id,'_stock',true);
						
						$product_sku = get_post_meta($row->id,'_sku',true);
						
						if($product_stock == 'outofstock'){
							// get new product ID
								
								$file.=  	 "\n".$i.','.
												$row->id.','.
												$product_sku.','.
												$product_qty.',';
												
							$i++;
						}
						
						
				}
				
				header("Content-Type: application/csv");
				header("Content-Disposition: attachment; filename=\"product_list.csv\";" );
				header("Expires: 0");
				echo $file;	
				exit;
		}*/
		
		
	//=======================================update seo title, description and keyword from project supermacy plugin to yoast plugin
		/*
		$res = $wpdb->get_results("select id,post_title from {$wpdb->prefix}posts where post_type='post'");	// and post_status='publish'
		
		if(count($res) > 0){
			
				
				foreach($res as $row){
						
						//$ps_seo_title = get_post_meta($row->id,'ps_seo_title',true);
						$ps_seo_keyword = get_post_meta($row->id,'ps_seo_keyword',true);
						//$ps_seo_description = get_post_meta($row->id,'ps_seo_description',true);
						
						
						// update data to yoasr plugin
						//update_post_meta($row->id,'_yoast_wpseo_linkdex','');
						//update_post_meta($row->id,'_yoast_wpseo_metadesc',$ps_seo_description);
						//update_post_meta($row->id,'_yoast_wpseo_title',$ps_seo_title);
						update_post_meta($row->id,'_yoast_wpseo_focuskw ',$ps_seo_keyword);
						update_post_meta($row->id,'_yoast_wpseo_focuskw_text_input',$ps_seo_keyword);
						//update_post_meta($row->id,'_yoast_wpseo_focuskeywords','[]');
						//update_post_meta($row->id,'_yoast_wpseo_content_score',30);
						//update_post_meta($row->id,'_yoast_wpseo_primary_product_cat','');
						
						echo "Product seo data update for ID --->".$row->id;
						echo "<br>";
						//exit;
						
				}
				

		}	
		*/
		
				
// replace out of stock products with instock
		/*$res = $wpdb->get_results("select id from {$wpdb->prefix}posts where post_type='product' and post_status='publish'");	
		$count = count($res);
		
		if($count > 0){
			
				foreach($res as $row){
						
					
						//$regular_price = get_post_meta($row->id,'_regular_price',true);
						
						$_product = wc_get_product( $row->id );
						
						if( $_product->is_type( 'simple' ) ) {
							
							//update_post_meta($row->id,'_stock_status','outofstock');
							//update_post_meta($row->id,'_manage_stock','no');
							echo "Stock status update of Product ".$row->id; 
							echo "<br>";
						}
						
						//die("end");
						
				}
				

//				update_option( 'ps_seo', $seo_option_value );
//				echo "<pre>";
//				print_r($seo_option_value);
		}*/
		
		
	// export csv for product weight not in correct format
				
			//$res = $wpdb->get_results("select * from {$wpdb->prefix}postmeta where meta_key='_weight'");
			
//			$res = $wpdb->get_results("select * from {$wpdb->prefix}posts where post_type='attachment'");	
//			$count = count($res);
//			
//			if($count > 0){
//				
//				$i=1;
//				$file = "S NO,Product ID, SKU, Weight\r";
//				foreach($res as $row){
//						
////						$product_sku = get_post_meta($row->ID,'_sku',true);
////						$product_weight = get_post_meta($row->ID,'_weight',true);
//						
//						
///*						$image_alttag = get_post_meta($row->ID,'_wp_attachment_image_alt',true);
//						$attached_file = get_post_meta($row->ID,'_wp_attached_file',true);
//						
//						if(empty($image_alttag)){
//							
//							if (strpos($attached_file, ".pdf") !== false || strpos($attached_file, ".PDF") !== false) {
//								
//								echo $row->ID.'------The PDF exists in given string.'.$attached_file;
//								echo "<br>";
//								
//							}else{
//								
//								echo "===================>".$row->post_title."<br>";
//								update_post_meta($row->ID,'_wp_attachment_image_alt',$row->post_title);
//								$i++;
//							}
//							
//						}*/
//						
//						
//						//if($row->meta_value){
//						
//								//$weight_arr = explode('.',$row->meta_value);
//								
//								//if($weight_arr[1] == ''){
//									
//									// get new product ID
////										$weight = number_format_i18n($weight_arr[0], 2);
////										echo $row->post_id."-----".$weight_arr[0]."-----new value--->".$weight;
////										echo "<br>";
//										
//										//update_post_meta( $row->post_id, '_weight', $weight );
//										
//										//$file.=  	 "\n".$i.','.
////														$row->ID.','.
////														$product_sku.','.
////														$product_weight.',';
////														
////									$i++;
//								//}
//						//}
//						
//				}
//				
//				echo "Total image ALT tag update ====>".$i;
//				
///*				header("Content-Type: application/csv");
//				header("Content-Disposition: attachment; filename=\"product_list.csv\";" );
//				header("Expires: 0");
//				echo $file;	
//				exit;*/
//	
//			}
		
		
		
	}else {
	
		//print "Upload new csv by browsing to file and clicking on Upload<br />\n";
		
		print "Execute Code to submit below button<br />\n";
	
		print "<form enctype='multipart/form-data' action='' method='post'>";
	
		print "File name to import:<br />\n";
	
		print "<input size='50' type='file' name='filename'><br />\n";
	
		print "<input type='submit' name='submit' value='Upload'>";
		
		print "</form>";
	
	}

}

// strange news for home by shortcode
add_shortcode('STRANGE_NEWS','strange_news_listing_on_home');

function strange_news_listing_on_home(){
	
	$categories = get_categories( array ('orderby' => 'count', 'order' => 'desc' ) );

	$i=1;
     // for dev server
	$category_arr = array(381,384,385,394);  
	// for local system
	//$category_arr = array(382,385,386,395); 
		 
	foreach ($categories as $category) :
	
	if(in_array($category->term_id,$category_arr)){
		
		query_posts( array ( 'category_name' => $category->slug, 'showposts' => '1', 'orderby' => 'date' ) );
		
		if ( have_posts() ):
		
			while ( have_posts() ) :
			
				the_post();
				?>
                    <div class="row st-news">
                     <div class="col-lg-2 col-sm-6">
                            <a href="<?php the_permalink(); ?>">
                            <?php  $size= array(300,300);
                                $default_attr = array(
                                'class'	=> "none",
                                'alt'	=> trim(strip_tags( $post->post_title )),
                                'title'	=> trim(strip_tags( $post->post_title )),
                                );
                                $src=wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) ); if($src) {?>
                                <img src="<?php echo $src[0];  ?>"  width="80" />
                                <?php } else{ ?>
                                <img src="<?php echo get_stylesheet_directory_uri();  ?>/images/no-image.png"  height="80" width="80" />
                                <?php } ?>
                            </a>
                    </div>
                <?php
				$content = get_the_content(); // save entire content in variable
				$content =strip_tags($content);
				$content = apply_filters( 'the_content', (strip_tags($content)) ); // WP bug fix
				$content = str_replace( ']]>', ']]>', $content ); // ...as well
				$out = trim_the_content( $content, __( " Read More", "" ), $perma_link,40); // trim to 55 words
				//the_excerpt(); 
			?>
            <div class="col-lg-10 col-sm-6"><p><?php echo substr(strip_tags($out),0,200).".."; ?><br /><a href="<?php the_permalink(); ?>" style="text-decoration:none;color:#fff;float:right;">Read more</a></p></div>
			 </div>
            <?php	
			
			endwhile; 
		
		 wp_reset_query();

 		endif; 


	 	$i++; 
	  }
	 endforeach; 
	
}

function trim_the_content( $the_contents = '', $read_more_tag = '...READ MORE', $perma_link_to = '', $all_words = 45 ) {

	// make the list of allowed tags

	$allowed_tags = array( 'a', 'abbr', 'b', 'blockquote', 'b', 'cite', 'code', 'div', 'em', 'fon', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'label', 'i', 'p', 'pre', 'span', 'strong', 'title', 'ul', 'ol', 'li', 'object', 'embed', 'param' );

	if( $the_contents != '' && $all_words > 0 ) {

		// process allowed tags

		$allowed_tags = '<' . implode( '><', $allowed_tags ) . '>';

		$the_contents = str_replace( ' ]]>', ' ]]>', $the_contents );

		//$the_contents = strip_tags( $the_contents, $allowed_tags );

		// exclude HTML from counting words

		if( $all_words > count( preg_split( '/[\s]+/', strip_tags( $the_contents ), -1 ) ) ) return $the_contents;

		// count all

		$all_chunks = preg_split( '/([\s]+)/', $the_contents, -1, PREG_SPLIT_DELIM_CAPTURE );

		$the_contents = '';

		$count_words = 0;

		$enclosed_by_tag = false;

		foreach( $all_chunks as $chunk ) {

			// is tag opened?

			if( 0 < preg_match( '/<[^>]*$/s', $chunk ) ) $enclosed_by_tag = true;

			elseif( 0 < preg_match( '/>[^<]*$/s', $chunk ) ) $enclosed_by_tag = false; 			// get entire word

			// get sub str word

			if( !$enclosed_by_tag && '' != trim( $chunk ) && substr( $chunk, -1, 1 ) != '>' ) $count_words ++;

			$the_contents .= $chunk;

			if( $count_words >= $all_words && !$enclosed_by_tag ) break;

		}

                // note the class named 'more-link'. style it on your own

		

		 if($perma_link_to=='nolink') $the_contents = $the_contents . $read_more_tag . '';

		else if($perma_link_to!='') $the_contents = $the_contents . '<div class="register_now" style=" margin:10px 0 0 0;"><a href="'.$perma_link_to.'">' . $read_more_tag . '</a></div>';

		else $the_contents = $the_contents;

		// native WordPress check for unclosed tags

		$the_contents = force_balance_tags( $the_contents );

	}

	return $the_contents;
	
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );

}

// category image listing on category page
add_action( 'woocommerce_archive_description_custom', 'woocommerce_category_image', 2 );
function woocommerce_category_image() {
    if ( is_product_category() ){
	    global $wp_query;
	    $cat = $wp_query->get_queried_object();
	    $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
	    $image = wp_get_attachment_url( $thumbnail_id );

		$description = wc_format_content( term_description() );
		if ( $description ) {
			echo '<div class="term-description">' . $description . '</div>';
		
		}
		
		if ( $image ) {
			
			echo '<img src="' . $image . '" />';
		}
	   
	}
}

/////////////////// start adding custom field(extra filed) to category add/edit////////////////////////


add_action( 'init', 'wpm_product_cat_register_meta' );
/**
 * Register details product_cat meta.
 *
 * Register the details metabox for WooCommerce product categories.
 *
 */
function wpm_product_cat_register_meta() {
	register_meta( 'term', 'category_static_block', 'wpm_sanitize_details' );
}
/**
 * Sanitize the details custom meta field.
 *
 * @param  string $details The existing details field.
 * @return string          The sanitized details field
 */
function wpm_sanitize_details( $details ) {
	return wp_kses_post( $details );
}

add_action( 'product_cat_add_form_fields', 'wpm_product_cat_add_details_meta' );
/**
 * Add a details metabox to the Add New Product Category page.
 *
 * For adding a details metabox to the WordPress admin when
 * creating new product categories in WooCommerce.
 *
 */
function wpm_product_cat_add_details_meta() {
	wp_nonce_field( basename( __FILE__ ), 'wpm_product_cat_details_nonce' );
	?>
	<div class="form-field">
		<label for="category_static_block"><?php esc_html_e( 'Category Static Block', 'wpm' ); ?></label>
		<textarea name="category_static_block" id="category_static_block" rows="5" cols="40"></textarea>
		<p class="description"><?php esc_html_e( 'Detailed category info to appear below the product list', 'wpm' ); ?></p>
	</div>
	<?php
}

/*function woocommerce_category_image_function(){
    global $wp_query;

    // get the query object
    $cat = $wp_query->get_queried_object();

    // get the thumbnail id using the queried category term_id
    $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true ); 

    // get the image URL
    $image = wp_get_attachment_url( $thumbnail_id ); 

    // print the IMG HTML
    echo "<img src='{$image}' alt='' width='762' height='365' />";
}


add_action( 'woocommerce_category_image', 'woocommerce_category_image_function' );*/

add_action( 'product_cat_edit_form_fields', 'wpm_product_cat_edit_details_meta' );
/**
 * Add a details metabox to the Edit Product Category page.
 *
 * For adding a details metabox to the WordPress admin when
 * editing an existing product category in WooCommerce.
 *
 * @param  object $term The existing term object.
 */
function wpm_product_cat_edit_details_meta( $term ) {
	$product_cat_details = get_post_meta( $term->term_id, 'category_static_block', true );
	if ( ! $product_cat_details ) {
		$product_cat_details = '';
	}
	$settings = array( 'textarea_name' => 'category_static_block' );
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="category_static_block"><?php esc_html_e( 'Category Static Block', 'wpm' ); ?></label></th>
		<td>
			<?php wp_nonce_field( basename( __FILE__ ), 'wpm_product_cat_details_nonce' ); ?>
			<?php wp_editor( wpm_sanitize_details( $product_cat_details ), 'product_cat_details', $settings ); ?>
			<p class="description"><?php esc_html_e( 'Detailed category info to appear below the product list','wpm' ); ?></p>
		</td>
	</tr>
	<?php
}

add_action( 'create_product_cat', 'wpm_product_cat_details_meta_save' );
add_action( 'edit_product_cat', 'wpm_product_cat_details_meta_save' );
/**
 * Save Product Category details meta(extra filed).
 *
 * Save the product_cat details meta POSTed from the
 * edit product_cat page or the add product_cat page.
 *
 * @param  int $term_id The term ID of the term to update.
 */
function wpm_product_cat_details_meta_save( $term_id ) {
	if ( ! isset( $_POST['wpm_product_cat_details_nonce'] ) || ! wp_verify_nonce( $_POST['wpm_product_cat_details_nonce'], basename( __FILE__ ) ) ) {
		return;
	}
	$old_details = get_post_meta( $term_id, 'category_static_block', true );
	$new_details = isset( $_POST['category_static_block'] ) ? $_POST['category_static_block'] : '';
	if ( $old_details && '' === $new_details ) {
		update_post_meta( $term_id, 'category_static_block', '' );
	} else if ( $old_details !== $new_details ) {
		update_post_meta(
			$term_id,
			'category_static_block',
			wpm_sanitize_details( $new_details )
		);
	}
}

add_action( 'woocommerce_before_shop_loop', 'wpm_product_cat_display_details_meta' );

// Display details meta(extra filed) on Product Category archives. woocommerce_after_shop_loop

function wpm_product_cat_display_details_meta() {
	
	if ( ! is_tax( 'product_cat' ) ) {
		return;
	}
	
	$t_id = get_queried_object()->term_id;
	$details = get_post_meta( $t_id, 'category_static_block', true );
	//print_r($details);
	if ( '' !== $details ) {
		?>
		<div class="product-cat-details">
			<?php echo apply_filters( 'the_content', wp_kses_post( $details ) ); ?>
		</div>
		<?php
	}
}


/* Display WooCommerce product category description on all category archive pages */
function woocommerce_taxonomy_archive_description() {
	
 /*   if ( is_tax( array( 'product_cat', 'product_tag' ) ) && get_query_var( 'paged' ) != 0 ) {
        $description = wc_format_content( term_description() );
        if ( $description ) {
            echo '<div class="term-description">' . $description . '</div>';
        }
    }*/
	
	$t_id = get_queried_object()->term_id;
	$details = get_post_meta( $t_id, 'category_static_block', true );
	//print_r($details);
	if ( '' !== $details ) {
		?>
		<div class="product-cat-details">
			<?php echo apply_filters( 'the_content', wp_kses_post( $details ) ); ?>
		</div>
		<?php
	}
}

//remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

//add_action( 'woocommerce_after_main_content', 'woocommerce_taxonomy_archive_description', 100 );

//add_action( 'woocommerce_before_shop_loop', 'woocommerce_taxonomy_archive_description', 100 );

/////////////////// END adding custom field to category add/edit////////////////////////

// show category image on category page
add_action( 'woocommerce_category_image', 'woocommerce_category_image_function' );

function woocommerce_category_image_function(){
	
	if ( is_product_category() ){
		
		global $wp_query;
		// get the query object
		$cat = $wp_query->get_queried_object();
		//print_r($cat);
		// get the thumbnail id using the queried category term_id
		$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true ); 
	
		// get the image URL
		$image = wp_get_attachment_url( $thumbnail_id ); 
		
		if(!empty($image)){
			
			// print the IMG HTML
			echo "<img src='{$image}' alt='".$cat->name."' />";
		}
	}
}


// add .html in product url 

function wpse_178112_permastruct_html( $post_type, $args ) {
    if ( $post_type === 'product' )
	// add_permastruct( $post_type, "{$args->rewrite['slug']}/%$post_type%.html", $args->rewrite );
        add_permastruct( $post_type, "{$args->rewrite['slug']}/%$post_type%.html", $args->rewrite );
}
add_action( 'registered_post_type', 'wpse_178112_permastruct_html', 10, 2 );


/**
 * Adds product SKUs above the "Add to Cart" buttons
 * Tutorial: http://www.skyverge.com/blog/add-information-to-woocommerce-shop-page/
**/
function skyverge_shop_display_skus() {

	global $product;
	
	if(isset($_REQUEST['product_view']) && $_REQUEST['product_view'] == 'list'){
		
		$class_v = "product-details grid-view";
		
	}else{
		
		$class_v = "list-view";
	}
	
	if ( $product->get_sku() ) {
		echo '<div class="product-meta1 '.$class_v.'">PN : ' . $product->get_sku() . '</div>';
	}
	
	if ( $product->get_description() && ($_REQUEST['product_view'] == 'grid' || !isset($_REQUEST['product_view']))) {
		echo '<div class="product-excerpt '.$class_v.'">' . $product->get_description() . '</div>';
	}
}
add_action( 'woocommerce_after_product_details_item', 'skyverge_shop_display_skus', 9 );

/*
 * wc_remove_related_products
 * 
 * Clear the query arguments for related products so none show.
 * Add this code to your theme functions.php file.  
 */
 
/*function wc_remove_related_products( $args ) {
	return array();
}
add_filter('woocommerce_related_products_args','wc_remove_related_products', 10); */

//search sku in ascending order by using relevanssi plugin
add_filter('relevanssi_hits_filter', 'sku_hits_first');

function sku_hits_first($hits) {
	$sku_hits = array();
	$everything_else = array();
	global $wp_query;
	
	foreach ($hits[0] as $hit) {
		$sku = get_post_meta($hit->ID, '_sku', true);

		if (($sku == $wp_query->query_vars['s']) || ($sku == ucwords($wp_query->query_vars['s']))) {
			$sku_hits[] = $hit;
		}
		else {
			$everything_else[] = $hit;
		}
	}
	$hits[0] = array_merge($sku_hits, $everything_else);
	//echo "relevanssi search";
	return $hits;
}

//===================== adding custom field  profile page in admin=================================
add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );

function my_show_extra_profile_fields( $user ) { 
	global $wpdb;
	//echo $user->ID;
//	echo "<pre>";
//	print_r($user);
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri();?>/crop/example.css">
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri();?>/crop/crop.css">
<script src="<?php echo get_stylesheet_directory_uri();?>/crop/crop.js"></script>
<?php /*?><table class="form-table">
<tbody>
	<tr class="user-author_custom-wrap">
	<th><label for="author_custom">Phone No</label></th>
	<td><input type="text" name="phone_no" id="phone_no" value="<?php echo get_user_meta($user->ID,'_phone', true);?>" class="regular-text"></td>
	</tr>
</tbody>
</table><?php */?>
			<div class="fieldset">  
        	<h2 class="legend">Upload Profile Picture</h2>
            <ul class="form-list1">
                    <li style="clear:both; margin:0 0 0 0;">
                        <div class="newupload" data-id="1">Upload an image</div> 
                        <div id="example1" class="example" style="clear:both;">
                        
                        <div id="example1" class="example" style="clear:both;">
                         <?php
						 $wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
						 $table_name1 = $wpdb->prefix.'user_profile_image';
						 $showProfileImageone =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user->ID and image_no =1");
						 //echo "SELECT * FROM $table_name1 WHERE user_id = $user->ID and image_no =1";
						 //echo $wp_root_path.$showProfileImageone[0]->user_profile_image;
						 if(count($showProfileImageone) > 0 && file_exists($wp_root_path.$showProfileImageone[0]->user_profile_image)){ ?>
                        
                        <div class="default1" id="default1">
                        <div class="cropMain custom-crop" id="cropMain1">
                        <div class="crop-container" style="width: 800px; height: 330px;">
                        	<div class="crop-overlay" style="z-index: 6000; top: 0px; left: 0px;"></div>
                             <img class="crop-img" src="<?php echo get_site_url().'/'.$showProfileImageone[0]->user_profile_image;?>" style="z-index: 5999; top: -73px; left: 40px; width: 720px; height: 540px;">
                        </div>
                        </div>

                        </div>
                                                
                         <?php }else{ ?>
						 
						 
                         <div class="preview-wrapper">
                            <img src="<?php echo get_stylesheet_directory_uri();?>/images/profilephotoplaceholder.gif" width="700" height="250">
                        </div>
                     <?php }?> 
                        
                       </div>
                       </div>
                    </li>
                    <div class="clear"></div>
            </ul>
            <div id="crop_loader" style="margin-top: -13px;display:none;"><img src="<?php echo get_stylesheet_directory_uri();?>/images/ajax-loader.gif" alt="Loader" /></div>
   		</div>
      <input type="hidden" id="user_cropimage" name="user_cropimage" value="" />
    <input type="file" class="uploadfile" id="uploadfile">
    <script>

		//  cropper settings
		// --------------------------------------------------------------------------

		// create new object crop
		// you may change the "one" variable to anything
		var one = new CROP();

		// link the .default class to the crop function
		//one.init('.default1');
		//one.init('.default2');
		//one.init('#default4');

		// load image into crop
		//one.loadImg('img/one.jpg');


		//  on click of button, crop the image
		// --------------------------------------------------------------------------

		//jQuery('body').on("click", "button", function() {
		//jQuery(".cropbutton").click(function() {

		var productImageNo =0 ;		
		
		function cropimageTosave()
		{	
	          
			// grab width and height of .crop-img for canvas
			jQuery("#crop_loader").show();
			var	width = jQuery('#cropMain'+productImageNo+' .crop-container').width() - 80,  // new image width
				height = jQuery('#cropMain'+productImageNo+' .crop-container').height() - 80;  // new image height

			jQuery('#canvas'+productImageNo).remove();
			jQuery('#default'+productImageNo).after('<canvas width="'+width+'" height="'+height+'" id="canvas'+productImageNo+'"/>');
           
			var ctx = document.getElementById('canvas'+productImageNo).getContext('2d'),
				img = new Image,
				w = coordinates(one).w,
			    h = coordinates(one).h,
			    x = coordinates(one).x,
			    y = coordinates(one).y;
   
			img.src = coordinates(one).image;
       
			img.onload = function() {
				
				// draw image
			    ctx.drawImage(img, x, y, w, h, 0, 0, width, height);

			    // display canvas image
				jQuery('#canvas'+productImageNo).addClass('output').show().delay('4000').fadeOut('slow');
               // jQuery('#canvas'+productImageNo).addClass('output').show();  
			  
			  var canvas  = document.getElementById('canvas'+productImageNo);
				var dataUrl = canvas.toDataURL();
			 	//alert(dataUrl+"-test-"+productImageNo);
				// save the image to server
//				jQuery.ajax({
//					type: "post",
//					dataType: "json",
//					url: "save.php",
//					data: { image: dataUrl , imgno: productImageNo}
//				})
//				.done(function(data) {
//                	//alert("success"+data["url"]);
//					// You can pull the image URL using data.url, e.g.:
//					// jQuery('body').append('<img src="'+data.url+'" />');
//
//				});
//				
				
			jQuery.ajax({
				type: 'POST', 
				url: '<?php echo home_url();?>/wp-admin/admin-ajax.php', 
				data: {'action': 'save_user_crop_image_admin', 'image': dataUrl , 'imgno':productImageNo, 'userID': <?php echo $user->ID;?>},
				success: function(data){ 
					
					crop_image = jQuery('#user_cropimage').val();
					//alert(data+"---"+crop_image);
					
					if(crop_image == ''){
						//alert("if");
						jQuery('#user_cropimage').val(data);
						jQuery("#crop_loader").hide();
						//jQuery(".button-default").show();
					}else{
						alert("else");
						jQuery('#user_cropimage').val(crop_image+","+data);
						jQuery("#crop_loader").hide();
						//jQuery(".button-default").show();
					}
				}
			});

			}

		}


		//  on click of .upload class, open .uploadfile (input file)
		// --------------------------------------------------------------------------

//		jQuery('body').on("click", ".newupload", function() 
//		{
//		
//		    jQuery('.uploadfile').click();
//		});
		
		jQuery( ".newupload" ).each(function(index)
		 {
		    
			
			jQuery(this).click(function()
			{
				var upid= jQuery(this).attr("data-id");
				productImageNo = upid;
				productImageNo = index+1;
				jQuery('.uploadfile').click();
		    });		  
		});

		// on input[type="file"] change
		jQuery('.uploadfile').change(function() { 		
		    loadImageFile();			
		    // resets input file
		    jQuery('.uploadfile').wrap('<form>').closest('form').get(0).reset();
		    jQuery('.uploadfile').unwrap();

		 });


		//  get input type=file IMG through base64 and send it to the cropper
		// --------------------------------------------------------------------------

		oFReader = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
		
		
		function loadImageFile()
		 {

		    if(document.getElementById("uploadfile").files.length === 0) return

		    var oFile = document.getElementById("uploadfile").files[0];

		    if(!rFilter.test(oFile.type)) {
		        return;
		    }

		  oFReader.readAsDataURL(oFile);
		}

		oFReader.onload = function (oFREvent) 
		{
		
		    jQuery('#example'+productImageNo).html('<div class="default'+productImageNo+'" id="default'+productImageNo+'"><div class="cropMain" id="cropMain'+productImageNo+'"></div><div class="cropSlider" id="cropSlider'+productImageNo+'"></div><input style="margin-top:0px" type="button" value="Crop" class="cropbutton" onclick="cropimageTosave();"><span style="font-size:12px; font-style:italic;margin-top:3px">(Crop button must be used to set the photo.)</span></div>');
			one = new CROP();
			one.init('.default'+productImageNo);
			one.loadImg(oFREvent.target.result);

		};
		
		

	</script>
<style>
.newupload {
    background: #4672c8 none repeat scroll 0 0 !important;
    border: 3px dashed rgba(0, 0, 0, 0.04) !important;
    border-radius: 7px !important;
    box-sizing: border-box !important;
    color: #fff !important;
    cursor: pointer !important;
    display:block !important;
    font-family: arial !important;
    text-align: center !important;
    width: 167px !important;
}
.cropMain.custom-crop .crop-container::after {
  box-shadow: 0 0 0 40px white inset, 0 0 0 41px rgba(0, 0, 0, 0.1) inset, 0 0 20px 41px rgba(0, 0, 0, 0.2) inset;
  content: "";
  height: 100%;
  left: 0;
  opacity: 1 !important;
  position: absolute;
  top: 0;
  width: 100%;
  z-index: 5999;
}
.example .default1 {
    z-index: 9;

}
.cropMain.custom-crop .crop-img {
    top: 40px!important;
    left: 40px;
    width: 720px;
    height: auto!important;
}
</style>    
<?php }

add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );

function my_save_extra_profile_fields( $user_id ) {

	global $wpdb;
	
/*	if ( !current_user_can( 'edit_user', $user_id ) ){
		//return false;
		//echo "=================================>New user addded";
		update_usermeta( $user_id, 'is_activated', 1 );
		update_usermeta( $user_id, 'activationcode', '' );
		//update_usermeta( $user_id, '_phone', $_POST['phone_no'] );
	}else{
		//echo "=================================>New user EDIT------";
		//update_usermeta( $user_id, '_phone', $_POST['phone_no'] );
		//echo "=============>".$user_id;
	}*/
	
	
	if(!empty($_POST['user_cropimage'])){
		    
			$user_profile_image = explode(',',$_POST['user_cropimage']);
			
			
			$user_cropimage_unique_id = array_unique($user_profile_image,SORT_REGULAR);
			$implodeIdWithComma = implode(',',$user_cropimage_unique_id);
			
			$table_name = $wpdb->prefix.'user_crop_image';
			
			$rec = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user_id and user_crop_image_no IN($implodeIdWithComma) group by user_crop_image_no");

			$table_name1 = $wpdb->prefix.'user_profile_image';
			$wp_root_path = str_replace('wp-content/themes', '', get_theme_root());
			
			foreach($rec as $data){
					
				$select =  $wpdb->get_results("SELECT * FROM $table_name1 WHERE user_id = $user_id and image_no = $data->user_crop_image_no");
				
				 if(count($select) > 0){
						$wpdb->query("UPDATE $table_name1 SET user_id=$user_id,
						user_profile_image = 'wp-content/uploads/user_crop_image/$data->user_crop_image',
						image_no = '$data->user_crop_image_no'
						WHERE user_id = $user_id and image_no = $data->user_crop_image_no");
						
						@copy($wp_root_path.'wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, $wp_root_path.'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
						
						@unlink($wp_root_path.'wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
			
				}else{
				
				 	$wpdb->insert("$table_name1", array(
   								'user_id' => $user_id,  
   								 'user_profile_image' =>  'wp-content/uploads/user_crop_image/'.$data->user_crop_image,
   								 "image_no" => $data->user_crop_image_no
));	
				
					@copy($wp_root_path.'wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image, $wp_root_path.'wp-content/uploads/user_crop_image/'.$data->user_crop_image);
			
					@unlink($wp_root_path.'wp-content/uploads/user_crop_image/temp/'.$data->user_crop_image);
				 }
				
			}
			//exit;
			
		}		
	
}

// shortcode for application chart
add_shortcode( 'application_chart', 'html_application_chart' );

function html_application_chart($id){
	ob_start();
	html_application_chart_content_clear($id);
	$output_string=ob_get_contents();;
	ob_end_clean();
	return $output_string;
}


function html_application_chart_content_clear($id){
		
	if($id['id'] != ''){
		
		$content_post = get_post($id['id']);
		$content = $content_post->post_content;
		$content = apply_filters('the_content', $content);
		//$content = str_replace(']]>', ']]&gt;', $content);
		
			if (class_exists('SEOLinksPRO')){
		
				$ob = new SEOLinksPRO();
				
				echo $html = $ob->SEOLinks_the_content_filter($content);
			}else{	
		
			 echo $html = $content;
			}
	}
	

	
}



// ===============start======Add TinyMce Editor To Category Description
// remove the html filtering
remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'term_description', 'wp_kses_data' );

add_filter('product_cat_edit_form_fields', 'cat_description');
function cat_description($tag)
{
    ?>
<style>
.term-description-wrap{
	display:none!important;
}
</style> 
           <tr class="form-field">
                <th scope="row" valign="top"><label for="description"><?php _ex('Description', 'Taxonomy Description'); ?></label></th>
                <td>
                <?php
                    $settings = array('wpautop' => true, 'media_buttons' => false, 'quicktags' => false, 'textarea_rows' => '15', 'textarea_name' => 'description' );
                    wp_editor(wp_kses_post($tag->description , ENT_QUOTES, 'UTF-8'), 'cat_description', $settings);
					//wp_editor(html_entity_decode( wp_kses_post($tag->description , ENT_QUOTES, 'UTF-8') ), 'cat_description', $settings);
                ?>
                <br />
                <span class="description"><?php _e('The description is not prominent by default; however, some themes may show it.'); ?></span>
                </td>
            </tr>
    <?php
}
add_action('admin_head', 'remove_default_category_description');
function remove_default_category_description()
{
    global $current_screen;
    if ( $current_screen->id == 'edit-category' )
    {
    ?>
        <script type="text/javascript">
        jQuery(function($) {
            $('textarea#description').closest('tr.form-field').remove();
        });
        </script>
    <?php
    }
}

// Add action to return new placeholder image URL. avada_placeholder_image
/*add_action( 'woocommerce_placeholder_img_src', 'growdev_custom_woocommerce_placeholder', 10 );

function growdev_custom_woocommerce_placeholder( $image_url ) {
	
	$image_url = home_url().'/wp-content/uploads/2016/09/placeholder.png';
  	return $image_url;
}*/

// to stop plugin updation there is no availabe pugin update
function my_filter_plugin_updates( $value ) {
	
	if ( is_admin() ) {
	unset( $value->response['wc-product-subtitle/wc-product-subtitle.php'] );
    unset( $value->response['seo-smart-links-pro/seo-smart-links-pro.php'] );
	unset( $value->response['custom-title/custom_heading.php'] );
	//unset( $value->response['woocommerce-minimum-advertised-price/woocommerce-minimum-advertised-price.php'] );
	}
	return $value;
}
add_filter( 'site_transient_update_plugins', 'my_filter_plugin_updates' );

// change out of stock text Our hooked in function $availablity is passed via the filter!
/*function custom_get_availability( $availability, $_product ) {
	global $product;
	
if ( !$_product->is_in_stock() && $product->product_type == 'simple') $availability['availability'] = _e("<strong>This product is only available from an Authorized Strange Dealer or Factory Direct
Call - 847.663.1701 or <a href='http://www.strangeengineering.net/contact-strange/'>Email Us</a></strong>", "woocommerce");
	return $availability;
}
add_filter( 'woocommerce_get_availability', 'custom_get_availability', 1, 2);*/


/*function custom_woocommerce_template_single_excerpt(){
	
	global $product;

//	echo "<pre>";
//	print_r($product);
//	echo "</pre>";
	
	if ( $product->stock_status == 'outofstock' && $product->product_type == 'simple'){
	
	echo '<strong>This product is only available from an Authorized Strange Dealer or Factory Direct
Call - 847.663.1701 or <a href="http://www.strangeengineering.net/contact-strange/">Email Us</a>
</strong>';

	}
}

add_action( 'woocommerce_single_product_summary', 'custom_woocommerce_template_single_excerpt', 20);*/

function woocommerce_output_cart_products(){
	
	global $woocommerce;
	global $wpdb;
    $items = WC()->cart->get_cart();//$woocommerce->cart->get_cart();
//	echo "<pre>";
//	print_r($items);
//	echo "</pre>";
		
if(count($items) > 0){		
	//Your cart is currently empty.
	?>
    <div class="cart_main_item" style="left: auto; right: 0px;">
    <strong style="display:block; padding:10px 0px">Cart Summery</strong>
    <div class="clearfix"></div>
<?php
    foreach($items as $item => $values) { 
	
		$_product = $values['data']->post; 
		
				if(!isset($_POST['add-to-cart'])){
		
							if(is_user_logged_in()){
							
										$current_user = wp_get_current_user();
										
										$k = 0;
										if(($key = array_search('administrator', $current_user->roles)) !== false) {
											//unset($current_user->roles[$key]);
											$k +=1;
										}
										$attribute_set = get_post_meta( $values['product_id'], '_attribute_set_key', true );
										$price_discount_arr = $wpdb->get_results("select * from {$wpdb->prefix}product_attribute_price_rules where prodcut_attribute_set_id='".$attribute_set."' and user_role_key='".$current_user->roles[$k]."' and status=1");
								
											if(count($price_discount_arr) > 0){
												
												$final_disount_price = wc_price(($values['data']->price  * $price_discount_arr[0]->discount_price_rules), $args);
												
												
											}else{
												
													$final_disount_price = wc_price($values['data']->price, $args);
											}
							 }else{
								 
								 $final_disount_price = wc_price($values['data']->price, $args);
							 }
							 
				}else{
					
					 $final_disount_price = wc_price($values['data']->price, $args);
				}
		
		 $thumb_image =  wp_get_attachment_image_src(get_post_thumbnail_id( $values['product_id']),'thumbnail');
?>    
	<div class="fusion-menu-cart-item">
            <a href="<?php echo get_permalink($_product->id);?>">
                     <img width="66" height="66" src="<?php echo $thumb_image[0];?>" sizes="(max-width: 66px) 100vw, 66px">
                    <div class="fusion-menu-cart-item-details">
                                <span class="fusion-menu-cart-item-title"><?php echo clean_spaces($_product->post_title);?></span>
                                <span class="fusion-menu-cart-item-quantity"><?php echo $values['quantity'];?> x <span class="woocommerce-Price-amount amount">
                                <!--<span class="woocommerce-Price-currencySymbol">$10.00</span>--><?php echo $final_disount_price;?></span></span>
                    </div>
    		</a>
    </div>
<?php }?>    
    <div class="fusion-menu-cart-checkout">
            <div class="fusion-menu-cart-link"><a href="<?php echo $woocommerce->cart->get_cart_url();?>"> View Cart</a></div>
            <div class="fusion-menu-cart-checkout-link"><a href="<?php echo $woocommerce->cart->get_checkout_url();?>">Checkout</a></div>
    </div>
</div>
	<?php
	}
}

add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_cart_products', 10 );

// change meta (alt and title) in catalog thumbnail , product thumbnail
add_filter('wp_get_attachment_image_attributes', 'change_attachement_image_attributes', 20, 2);

function change_attachement_image_attributes( $attr, $attachment ){
    // Get post parent
    $parent = get_post_field( 'post_parent', $attachment);

    // Get post type to check if it's product
    $type = get_post_field( 'post_type', $parent);
    if( $type != 'product' ){
        return $attr;
    }

    /// Get title
    $title = get_post_field( 'post_title', $parent);

   // $attr['alt'] = $title;
    $attr['title'] = "";

    return $attr;
}

// Add Continue Shopping Button on Cart Page
/*add_action( 'woocommerce_before_cart_table', 'woo_add_continue_shopping_button_to_cart' );
function woo_add_continue_shopping_button_to_cart() {
 
 //$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
 global $woocommerce;
 $prev_url = (isset($_SERVER['HTTP_REFERER']) && ($_SERVER['HTTP_REFERER'] != $woocommerce->cart->get_cart_url())) ? $_SERVER['HTTP_REFERER'] : 'http://www.strangeengineering.net/product-category/apparel/';
 
 //echo '<div class="woocommerce-message">';
 //echo ' <a href="'.$shop_page_url.'" class="button">Continue Shopping →</a> Would you like some more goods?';
 //echo '</div>';
 	echo '<div class="woocommerce-message woocommerce-cotinue-shopping">';
	echo ' <a href="'.$prev_url.'" class="button">Continue Shopping?</a>';
	echo '</div>';
}*/

// Put sticky posts first in results
add_filter( 'relevanssi_hits_filter', 'rlv_sticky_first' );
function rlv_sticky_first( $hits ) {
	$sticky = array();
	$nonsticky = array();
	$sticky_post_ids = get_option( 'sticky_posts' );
	foreach( $hits[0] as $hit ) {
		if ( in_array( $hit->ID, $sticky_post_ids ) ) {
			$sticky[] = $hit;
		}
		else {
			$nonsticky[] = $hit;
		}
	}
	$hits[0] = array_merge( $sticky, $nonsticky );
	return $hits;
}


// remove spacess from text
function clean_spaces($str){ 
      
    //$str = utf8_decode($str);
	$str = str_replace("&nbsp;", " ", $str);
	$str = str_replace(" ", " ", $str);
    $str = preg_replace('/\s+/', ' ',$str);
    $str = trim($str);
    return $str;
}

// Edit "successfully added to your cart"
add_filter( 'wc_add_to_cart_message', 'bbloomer_custom_add_to_cart_message' );
 
function bbloomer_custom_add_to_cart_message($message) {
 
	global $woocommerce;
	//$return_to  = get_permalink(woocommerce_get_page_id('shop'));
	//$message    = sprintf('<a href="%s" class="button wc-forwards">%s</a> %s', $return_to, __('Continue Shopping', 'woocommerce'), __('Product successfully added to your cart.', 'woocommerce') );
	
	$message = clean_spaces($message);

	$message    = sprintf('%s', $message, __('has been added to your cart.', 'woocommerce') );
	return $message;
}

//cURL error 52: Empty reply from server
function preempt_expect_header($r) {
	$r['headers']['Expect'] = '';
	return $r;
}

add_filter( 'http_request_args', 'preempt_expect_header' );

// Disable WordPress AutoSave
add_action( 'admin_init', 'disable_autosave' );
function disable_autosave() {
	wp_deregister_script( 'autosave' );
}	

// show product name when mouse hover on prodcut sku in yith tab plugin
if ( !function_exists( 'yith_customization_show_sku_in_search_products' ) ) {
 function yith_customization_show_sku_in_search_products( $title, $post_id, $request ) {
	 
 if ( !empty( $request[ 'post_type' ] ) && 'product' === $request[ 'post_type' ] ) {
 $product = wc_get_product( $post_id );
 $title = rawurldecode( clean_spaces(get_the_title( $post_id )) );
 $sku     = $product->get_sku();
 $title   .= " [$sku]";
 }

 return $title;
 }

 add_filter( 'yith_plugin_fw_json_search_found_post_title', 'yith_customization_show_sku_in_search_products', 10, 3 );
}

function custom_my_account_menu_items( $items ) {
    //unset($items['downloads']);
	unset($items['coupons']);
	unset($items['payment-methods']);
	
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'custom_my_account_menu_items' );

function show_content($arr){
	
	if(isset($arr['id']) && count($arr['id']) > 0){
		
		$content_post = get_post($arr['id']);
		$content = $content_post->post_content;
		$content = apply_filters('the_content', $content);
		//$content = str_replace(']]>', ']]&gt;', $content);
		echo $content;
	}
	
}

function custom_show_content($arr) {
    ob_start();
    show_content($arr);
    return ob_get_clean();
}

add_shortcode('page_content','custom_show_content');



// ======================== Create a column Registration date on user list page. And maybe remove some of the default ones==========================

 /* @param array $columns Array of all user table columns {column ID} => {column Name} 
 */
add_filter( 'manage_users_columns', 'rudr_modify_user_table' );
 
function rudr_modify_user_table( $columns ) {
 
	// unset( $columns['posts'] ); // maybe you would like to remove default columns
	$columns['registration_date'] = 'Registration date'; // add new
 
	return $columns;
 
}
 
/*
 * Fill our new column with the registration dates of the users
 * @param string $row_output text/HTML output of a table cell
 * @param string $column_id_attr column ID
 * @param int $user user ID (in fact - table row ID)
 */
add_filter( 'manage_users_custom_column', 'rudr_modify_user_table_row', 10, 3 );
 
function rudr_modify_user_table_row( $row_output, $column_id_attr, $user ) {
 
	$date_format = 'j M, Y H:i';
 
	switch ( $column_id_attr ) {
		case 'registration_date' :
			return date( $date_format, strtotime( get_the_author_meta( 'registered', $user ) ) );
			break;
		default:
	}
 
	return $row_output;
 
}
 
/*
 * Make our "Registration date" column sortable
 * @param array $columns Array of all user sortable columns {column ID} => {orderby GET-param} 
 */
add_filter( 'manage_users_sortable_columns', 'rudr_make_registered_column_sortable' );
 
function rudr_make_registered_column_sortable( $columns ) {
	return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
}

//===========How to prevent items to be added in cart for specific condition in wordpress
function filter_woocommerce_add_to_cart_validation( $passed, $product_id, $quantity ) { 

	$cart_val = array_values($_POST);
//	echo "<pre>";
//	print_r($cart_val);
	
    if ( in_array("I Don\'t See Mine",$cart_val)) {
       	 
		 wc_add_notice( __('You are not allowing to add this product because you have selected "I dont see mine" product option.', 'woocommerce' ), 'error' );
         $passed = false;
    }
    return $passed; 
}

add_filter( 'woocommerce_add_to_cart_validation', 'filter_woocommerce_add_to_cart_validation', 10, 3 );


//WooCommerce Custom Checkout Validation 
function custom_checkout_validation( $fields, $errors ){
 
	// if any validation errors
	if( !empty( $errors->get_error_codes() ) ) {
 
		// remove all of them
		/*foreach( $errors->get_error_codes() as $code ) {
			$errors->remove( $code );
		}*/
 
		// add our custom one
		$errors->add( 'validation', 'Please fill the all fields!' );
 
	}/*else{
		
		$errors->add( 'validation', 'OK' );
	}*/
 
}

add_action( 'woocommerce_after_checkout_validation', 'custom_checkout_validation', 9999, 2);
/*
// ============Contactform7 prevent duplicate field value submission===================

function is_already_submitted($formName, $fieldName, $fieldValue) {
	
    require_once(ABSPATH . 'wp-content/plugins/contact-form-7-to-database-extension/CFDBFormIterator.php');
    $exp = new CFDBFormIterator();
    $atts = array();
    $atts['show'] = $fieldName;
    $atts['filter'] = "$fieldName=$fieldValue";
    $atts['unbuffered'] = 'true';
    $exp->export($formName, $atts);
    $found = false;
	
    while ($row = $exp->nextRow()) {
		
        $found = true;
    }
    return $found;
	
}

// 
function my_validate_email($result, $tag) {
    $formName = 'Order Catalog'; // Change to name of the form containing this field
    $fieldName = 'email-853'; // Change to your form's unique field name
    $errorMessage = 'Email has already been submitted'; // Change to your error message
    $name = $tag['name'];
    if ($name == $fieldName) {
        if (is_already_submitted($formName, $fieldName, $_POST[$name])) {
			
            $result->invalidate($tag, $errorMessage);
        }
    }
    return $result;
}

// use the next line if your field is a **required email** field on your form
add_filter('wpcf7_validate_email*', 'my_validate_email', 10, 2);
// use the next line if your field is an **email** field not required on your form
add_filter('wpcf7_validate_email', 'my_validate_email', 10, 2);

// use the next line if your field is a **required text** field
add_filter('wpcf7_validate_text*', 'my_validate_email', 10, 2);
// use the next line if your field is a **text** field field not required on your form 
add_filter('wpcf7_validate_text', 'my_validate_email', 10, 2);

*/

/*// Customize WooCommerce Products Search Form
add_filter( 'get_product_search_form' , 'woo_custom_product_searchform' );

function woo_custom_product_searchform( $form ) {
	
	$ps_echo = true ; 
	//$form = do_shortcode('[yith_woocommerce_ajax_search]');
	//$form = dynamic_sidebar( 'avada-footer-widget-2' );
	$form = woo_predictive_search_widget($ps_echo);
	return $form;
}*/


//Hide/remove/disable Add to cart button for a specific product Only
/*add_filter('woocommerce_is_purchasable', 'specific_product_woocommerce_is_purchasable', 10, 2);
function specific_product_woocommerce_is_purchasable($is_purchasable, $product) {
	
		$adr_price =  get_post_meta( $product->get_id(), '_minimum_advertised_price', true );
		
		if(empty($adr_price)){
			
        	//return ($product->get_id() == whatever_mambo_jambo_id_you_want ? false : $is_purchasable);
			return $is_purchasable;
		}else{
			
			return false;
		}
}*/