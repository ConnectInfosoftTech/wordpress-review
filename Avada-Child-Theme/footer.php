<?php
/**
 * The footer template.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>
					<?php do_action( 'avada_after_main_content' ); ?>

				</div>  <!-- fusion-row -->
			</main>  <!-- #main -->
			<?php do_action( 'avada_after_main_container' ); ?>

			<?php global $social_icons; ?>

			<?php
			/**
			 * Get the correct page ID.
			 */
			$c_page_id = Avada()->fusion_library->get_page_id();
			?>

			<?php
			/**
			 * Only include the footer.
			 */
			?>
			<?php if ( ! is_page_template( 'blank.php' ) ) : ?>
				<?php $footer_parallax_class = ( 'footer_parallax_effect' === Avada()->settings->get( 'footer_special_effects' ) ) ? ' fusion-footer-parallax' : ''; ?>

				<div class="fusion-footer<?php echo esc_attr( $footer_parallax_class ); ?>">
					<?php get_template_part( 'templates/footer-content' ); ?>
				</div> <!-- fusion-footer -->
			<?php endif; // End is not blank page check. ?>

			<?php
			/**
			 * Add sliding bar.
			 */
			?>
			<?php if ( Avada()->settings->get( 'slidingbar_widgets' ) && ! is_page_template( 'blank.php' ) ) : ?>
				<?php get_template_part( 'sliding_bar' ); ?>
			<?php endif; ?>
		</div> <!-- wrapper -->

		<?php
		/**
		 * Check if boxed side header layout is used; if so close the #boxed-wrapper container.
		 */
		$page_bg_layout = ( $c_page_id ) ? get_post_meta( $c_page_id, 'pyre_page_bg_layout', true ) : 'default';
		?>
		<?php if ( ( ( 'Boxed' === Avada()->settings->get( 'layout' ) && 'default' === $page_bg_layout ) || 'boxed' === $page_bg_layout ) && 'Top' !== Avada()->settings->get( 'header_position' ) ) : ?>
			</div> <!-- #boxed-wrapper -->
		<?php endif; ?>
		<?php if ( ( ( 'Boxed' === Avada()->settings->get( 'layout' ) && 'default' === $page_bg_layout ) || 'boxed' === $page_bg_layout ) && 'framed' === Avada()->settings->get( 'scroll_offset' ) && 0 !== intval( Avada()->settings->get( 'margin_offset', 'top' ) ) ) : ?>
			<div class="fusion-top-frame"></div>
			<div class="fusion-bottom-frame"></div>
			<?php if ( 'None' !== Avada()->settings->get( 'boxed_modal_shadow' ) ) : ?>
				<div class="fusion-boxed-shadow"></div>
			<?php endif; ?>
		<?php endif; ?>
		<a class="fusion-one-page-text-link fusion-page-load-link"></a>

		<?php wp_footer(); ?>
<?php 
global $product;

if(count($product) > 0){
	
//echo "--->".count($product);	
$id = $product->get_id();
$post_type = get_post_type( $id );

if($post_type == 'product'){
   
  	$cat = get_the_terms( $id, 'product_cat' );

	foreach ($cat as $categoria) {
		
		if($categoria->parent == 0 && $categoria->term_id == 600){
			
			  $cat_id = $categoria->term_id;	
			  break;
		}
	}      
 
if($cat_id == 600){
	
	$expire_date = get_post_meta(31052,'expiry_date',true);
	
	if($expire_date >= date('Y-m-d')){
?>	    
<script type="text/javascript">
(function($) {
   //jQuery(function(e) {
		var ex = jQuery('.woocommerce-product-gallery' );
ex.prepend('<div class="freeshiping">Order this item online and get free delivery and handling on your entire order!</div>');
	//alert('if');
	//});
	
})(jQuery);

</script>
<style>
.freeshiping {
    color: #fff;
    font-size: 17px;
    max-width: 360px;
    margin: 8px auto 10px;
    text-align: center;
    border: 2px solid #197eda!important;
    padding: 5px;
}
</style>
<?php } } } }

  if(is_shop()){
	  
        $page_id = woocommerce_get_page_id('shop');
		$post = get_post($page_id); 
		 $content = trim(apply_filters('the_content', $post->post_content));
		 echo '<div class="shop-page">'.$content.'</div>'
	?>
<script type="text/javascript">
(function($) {
    //jQuery(function(e) {
		var ex = jQuery('#content');
		var ax = jQuery('.shop-page');
		ax.clone().prependTo(ex);
		ax.remove();
	//});
	
})(jQuery);

</script>


<?php } 
	
/*if(is_product_category()){
	
	$t_id = get_queried_object()->term_id;
	$details = get_post_meta( $t_id, 'category_static_block', true );
	//print_r($details);
	if ( '' !== $details ) {
		?>
		<div class="product-cat-details">
			<?php echo apply_filters( 'the_content', wp_kses_post( $details ) ); ?>
		</div>
		<?php
	}*/
?>

<?php /*?><script type="text/javascript">
(function($) {
		var ex = jQuery('#content');
		var ax = jQuery('.product-cat-details');
		ax.clone().prependTo(ex);
		//ax.remove();
})(jQuery);
</script><?php */?>
<?php //}?>
<script type="text/javascript">
 function removeC(){
					jQuery(".submit-contact").removeClass("act");
					}
jQuery(window).load(function(){
	
	jQuery('.submit-contact').click(function(){
	
		//alert("test");
		 if( jQuery('.fusion-slider-loading').css('display') ==="block" ) {
			 // e.preventDefault();
			  //alert("in");
			  	jQuery(this).addClass("act");
	         //jQuery(this).find('input').attr("disabled",true);
			  //return false;
			  
			  

                setInterval(removeC, 5000);
		 }
		/* else if( jQuery('.wpcf7-form').hasClass('invalid') ){
			 jQuery(this).removeClass("act");
		  jQuery('.fusion-slider-loading').css("display","none");
		  }
 */

	});
});	
</script>

<style>
span.submit-contact {
    display: inline-block;
    position: relative;
}
span.submit-contact.act:after {
    content: "";
    display: block;
    background: transparent;
    z-index: 1;
    position: absolute;
    width: 100%;
    height: 100%;
    cursor: not-allowed;
    top: 0;
}
form.wpcf7-form.invalid{
	
}
</style>
</body>
</html>
