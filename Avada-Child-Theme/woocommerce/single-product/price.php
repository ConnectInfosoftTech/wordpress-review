<?php
/**
 * Single Product Price, including microdata for SEO
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product,$wpdb;

$adr_price =  get_post_meta( get_the_ID(), '_minimum_advertised_price', true );

$reg_price =  get_post_meta( get_the_ID(), '_regular_price', true );

?>
<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">

<p class="price theme-active">
<?php 
$id = $product->get_id();
   
  	$cat = get_the_terms( $id, 'product_cat' );

	foreach ($cat as $categoria) {
		
		if($categoria->parent == 0 && $categoria->term_id == 600){
			
			  $cat_id = $categoria->term_id;	
			  break;
		}
	}      

	if((empty($adr_price) && !empty($reg_price)) || (!empty($adr_price) && !empty($reg_price)) || empty($reg_price)){
		if(is_user_logged_in()){

	
		
		$current_user = wp_get_current_user();

		$k = 0;
		if(($key = array_search('administrator', $current_user->roles)) !== false) {
			//unset($current_user->roles[$key]);
			$k +=1;
		}
//		echo "<pre>";
//		print_r($current_user->roles);
		
		$attribute_set = get_post_meta( get_the_ID(), '_attribute_set_key', true );
		//echo "select * from {$wpdb->prefix}product_attribute_price_rules where prodcut_attribute_set_id='".$attribute_set."' and user_role_key='".$current_user->roles[$k]."' and status=1";
		$price_discount_arr = $wpdb->get_results("select * from {$wpdb->prefix}product_attribute_price_rules where prodcut_attribute_set_id='".$attribute_set."' and user_role_key='".$current_user->roles[$k]."' and status=1");
	
		if(count($price_discount_arr) > 0){
		
		//$final_disount_price = $product->price - ($product->price * $price_discount_arr[0]->discount_price_rules / 100);
		$final_disount_price = $product->price * $price_discount_arr[0]->discount_price_rules; 
		
					
					echo '<span class="amount cross">';
		
					/*if(!empty($adr_price)){
						
						echo  'MAP: <span class="woocommerce-Price-amount amount">'.wc_price($adr_price, $args).'</span> ';
					}*/
							
					echo 'Reg: <span class="woocommerce-Price-amount amount">'.wc_price($reg_price, $args).'</span></span>';
					
				 //echo '<span class="amount cross">Reg: '.$product->get_price_html().'</span>';
				 echo '<span class="amount"><span class="red">Dealer Price:</span> '.wc_price($final_disount_price, $args).'</span>';
				 echo '<input type="hidden" id="final_price" value="'.$final_disount_price.'">';
			
		}else{
		
				echo $product->get_price_html();
		   }
	}else{
		
			echo $product->get_price_html();
	}
		
	}else{
		
		echo $product->get_price_html();
	}


	if($cat_id == 600){
		
	$expire_date = get_post_meta(31052,'expiry_date',true);
	
	if($expire_date >= date('Y-m-d')){
		
		echo '<span class="details-below" style="display:block; margin:10px 0 0 0;"><span class="red" style="margin-left:0px">* Free Shipping (Details Below)</span></span>';
		
			}
	}
	
	
	//'OutOfStock';
	?>
    </p>

	<meta itemprop="price" content="<?php echo (is_user_logged_in() ? get_post_meta( get_the_ID(), 'dealer_price', true ) : esc_attr( $product->get_price() )); ?>" />
	<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
	<link itemprop="availability" href="http://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'InStock'; ?>" />

</div>
<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

		<span class="sku_wrapper"><?php _e( 'Part Number:', 'woocommerce' ); ?> <span class="sku" itemprop="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : __( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>

	<?php //echo $product->get_categories( ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', $cat_count, 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php //echo $product->get_tags( ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>