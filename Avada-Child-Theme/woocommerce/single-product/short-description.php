<?php
/**
 * Single product short description
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/short-description.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post;
if(file_exists(ABSPATH."wp-content/plugins/seo-smart-links-pro/seo-smart-links-pro.php")){
	require(ABSPATH."wp-content/plugins/seo-smart-links-pro/seo-smart-links-pro.php");
}

if ( ! $post->post_excerpt ) {
	return;
} ?>
<div class="post-content" itemprop="description">
<?php if (class_exists('SEOLinksPRO')){
	
	$obj = new SEOLinksPRO();
	$shor_description = $obj->SEOLinks_the_content_filter($post->post_excerpt);
	echo apply_filters( 'woocommerce_short_description', $shor_description );
}else{	

 echo apply_filters( 'woocommerce_short_description', $post->post_excerpt );
}
 ?>
</div>