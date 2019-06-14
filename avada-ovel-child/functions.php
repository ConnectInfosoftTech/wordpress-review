<?php

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

function theme_enqueue_styles() {

   // wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'avada-stylesheet' ) );



}



function woocommerce_2nd_title() {

global $post;

global $wpdb;

$tabs = apply_filters( 'woocommerce_product_tabs', array() );



$pid = 41246;

//$pid = 610;

// || $post->ID == 599

$getslugid = wp_get_post_terms( $post->ID, 'product_cat' ); 



$cat_slug = array();

foreach( $getslugid as $thisslug ) {



    $cat_slug[] = $thisslug->slug; 



}

//	echo "<pre>";

//	print_r($cat_slug);



if($post->ID == 41246){

	

/*	echo "<pre>";

	print_r($tabs);*/

	?>

      <ul class="tabs tab-vertical-list" role="tablist">

			<?php 

			$i = 0;

			foreach ( $tabs as $key => $tab ) : 

			

				if(esc_attr( $key ) != 'description'){

					

					if(in_array($key,$cat_slug)){

						

						$act = 'class="act-tab"';

					}else{

						

						if($i == 0){

							

							$act = 'class="act-tab"';

						}else{

							

							$act = '';

						}

					}

            	?>

				<li><a href="<?php echo esc_attr( $key ); ?>" <?php //echo $act;?>><?php echo apply_filters( 'woocommerce_product_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a></li>

                

			<?php  $i++;

				}

			

			endforeach; ?>

            <!--Asphalt-->

        <!-- <li><a href="late-model">dirt</a></li>   

        <li><a href="asphalt-late-model">dirt</a></li>

        <li><a href="asphalt">imca</a></li>

		<li><a href="tab-3">ump, usmts</a></li>	-->

      </ul>

        <script>

        jQuery(document).ready(function(){

		

		// get current menu id

		var elements = document.getElementsByClassName("current-menu-parent");

		var names = '';

		var slug = '';

		for(var i=0; i < elements.length; i++) {

			//names += elements[i].id;

			//alert(elements[i].id);

			var ar = jQuery("#"+elements[i].id+" > a").attr('href').split('/');

			var slug = ar[ar.length-2];

			//alert(ar+"----"+slug);

			

			//jQuery('div[data-binder="'+tabs+'"]').addClass('active');

			//jQuery('.tabs li a').addClass('act-tab');

		}

		

		if(slug != ''){

				

				//alert(slug);

				jQuery('.tabs li a').removeClass('act-tab');

				jQuery('[href*="'+slug+'"]').addClass('act-tab');

				

				jQuery('[data-binder="'+slug+'"]').remove();

				jQuery('#descrip_tab').attr('data-binder',slug);

				

			}

			

			

		 jQuery('.tabs li a').click(function(e){

			 

			 e.preventDefault();

		

			 var tabs=jQuery(this).attr('href');

			 

			  jQuery('div[data-binder]').removeClass('active');

			   jQuery('div[data-binder="'+tabs+'"]').addClass('active');

			   jQuery('.tabs li a').removeClass('act-tab');

			   jQuery(this).addClass('act-tab');

			 });	

		});

        </script>

		<style>

		.summary-container h2.product_title.entry-title {

			margin-top: 15px !important;

		}

        ul.tabs.tab-vertical-list {

        padding: 0;

        margin: 0;

        list-style: none;

        }

        ul.tabs.tab-vertical-list li {

        padding: 0;

        margin: 0;

        display:inline-block;

        

        }

        .tab-con{

        

        display:none;

        }

        

        .tab-con.active{

        

        display:block;

        }

		

		.tabs.tab-vertical-list li a {

    border: 4px solid #fcaf17;

    display: block;

    padding: 3px 24px 0;

    border-radius: 10px 10px 0 0;

    border-bottom: 0;

	color:#fcaf17;

	text-transform:uppercase;

	min-width: 150px;

    text-align: center;

	font-weight: 800;

    font-size: 18px;

}

.tabs.tab-vertical-list li a.act-tab {

    border: 4px solid #fff;

	color:#fff;

	border-bottom: 0;

}

        </style>

<?php /*?>    <ul class="tabs wc-tabs" role="tablist">

			<?php foreach ( $tabs as $key => $tab ) : ?>

            

            <?php //if(esc_attr( $key ) != 'description'){?>

				<li class="<?php echo esc_attr( $key ); ?>_tab" id="tab-title-<?php echo esc_attr( $key ); ?>" role="tab" aria-controls="tab-<?php echo esc_attr( $key ); ?>">

					<a href="#tab-<?php echo esc_attr( $key ); ?>"><?php echo apply_filters( 'woocommerce_product_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>

				</li>

                

              <?php //} ?>  

			<?php endforeach; ?>

            

            <li class="gopal_tab" id="tab-title-gopal" role="tab" aria-controls="tab-gopal">

					<a href="#tab-gopal">Gopal</a>

				</li>

      </ul><?php */?> 

	<?php

}

	

}

add_action( 'woocommerce_single_product_summary', 'woocommerce_2nd_title', 4 );

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
	

	if (isset($_POST['submit'])) {
		
		echo "Hello";
		exit;
//=====================import csv file for gear set product weights=======================================

	if (is_uploaded_file($_FILES['filename']['tmp_name'])) {
			
			echo "<h1>" . "File ". $_FILES['filename']['name'] ." uploaded successfully." . "</h1>";
			// echo "<h2>Displaying contents:</h2>";
			// readfile($_FILES['filename']['tmp_name']);
		}
		
	//=======================================update seo title, description and keyword from project supermacy plugin to yoast plugin
		
				$res = $wpdb->get_results("select id,post_title from {$wpdb->prefix}posts where post_type='page'");	// and post_status='publish'
				
				if(count($res) > 0){
					
						
						foreach($res as $row){
								
								$ps_seo_title = get_post_meta($row->id,'ps_seo_title',true);
								$ps_seo_keyword = get_post_meta($row->id,'ps_seo_keyword',true);
								$ps_seo_description = get_post_meta($row->id,'ps_seo_description',true);
								
								
								// update data to yoasr plugin
								update_post_meta($row->id,'_yoast_wpseo_linkdex','');
								update_post_meta($row->id,'_yoast_wpseo_metadesc',$ps_seo_description);
								update_post_meta($row->id,'_yoast_wpseo_title',$ps_seo_title);
								update_post_meta($row->id,'_yoast_wpseo_focuskw ',$ps_seo_keyword);
								update_post_meta($row->id,'_yoast_wpseo_focuskw_text_input','');
								update_post_meta($row->id,'_yoast_wpseo_focuskeywords','[]');
								update_post_meta($row->id,'_yoast_wpseo_content_score',30);
								update_post_meta($row->id,'_yoast_wpseo_primary_product_cat','');
								
								echo "Product seo data update for ID --->".$row->id;
								echo "<br>";
								//exit;
								
						}
						
		
				}	
		
				
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

?>