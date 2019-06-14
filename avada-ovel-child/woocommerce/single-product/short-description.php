<?php
/**
 * Single product short description
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version	 3.4.0
 */

 if ( ! defined( 'ABSPATH' ) ) {
 	exit; // Exit if accessed directly
 }

global $post;

if ( ! $post->post_excerpt ) {
	return;
}
global $act;
?>
<div class="post-content woocommerce-product-details__short-description">

<?php 
$pid = 41246;
//$pid = 610;
// || $post->ID == 599
if($post->ID == 41246){

$tabs = apply_filters( 'woocommerce_product_tabs', array() );


	if ( ! empty( $tabs ) ) : ?>

	<!--<div class="woocommerce-tabs wc-tabs-wrapper">-->
    <div class="tab-main">
		<?php /*?><ul class="tabs wc-tabs" role="tablist">
			<?php foreach ( $tabs as $key => $tab ) : ?>
            
            <?php if(esc_attr( $key ) != 'description'){?>
				<li class="<?php echo esc_attr( $key ); ?>_tab" id="tab-title-<?php echo esc_attr( $key ); ?>" role="tab" aria-controls="tab-<?php echo esc_attr( $key ); ?>">
					<a href="#tab-<?php echo esc_attr( $key ); ?>"><?php echo apply_filters( 'woocommerce_product_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				</li>
                
              <?php } ?>  
			<?php endforeach; ?>
		</ul><?php */?>
		<?php foreach ( $tabs as $key => $tab ) : ?>
        
         <?php if(esc_attr( $key ) == 'description'){?>
         
			<?php /*?><div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?> panel entry-content wc-tab" id="tab-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr( $key ); ?>">
				<?php call_user_func( $tab['callback'], $key, $tab ); ?>
			</div> <?php */?>
            		
            		
	        		<div id="descrip_tab" class="tab-con active" data-binder="description"> <?php echo apply_filters( 'woocommerce_short_description', $post->post_excerpt ); ?></div>
		 
		 <?php }else{ ?>
			 
			 <div class="tab-con" data-binder="<?php echo esc_attr( $key ); ?>">
				<?php call_user_func( $tab['callback'], $key, $tab ); ?>
			</div>
			 
		<?php }
		 
		 
		 endforeach; ?>
         
     <?php /*?>      <div class="tab-con" data-binder="tab-1"><p>When it comes to radical, it’s hard to beat Late Model dirt. These cars range in horsepower from a 400HP crate engine to an 850HP engine that rivals NASCAR Sprint Cup. Many popular NASCAR drivers have a team that runs one or 2 cars on tour with the World of Outlaw Late Model Series or the Lucas Oil Late Model Series.</p>
<p>Who supplies the teams with the Late Model racing parts they need to win? You guessed it, Strange Engineering. Everything from our revolutionary Wrap Axles (KERA), to our bullet-proof rear axle Wide 5 Hub Drive Flanges, Strange’s oval track racing parts help turn any racing team into a winning one. For the best drive plates and race axles on the market, don’t just race, RACE STRANGE!</p></div>
					 <div class="tab-con" data-binder="tab-2"><p>With wide variations in rules, these chassis’ are very similar and have the greatest number of cars running at various tracks throughout the country. Mainly run on dirt tracks with a “crate motor” based rules package, these cars are both economical to run and offer a wide range of car classes (A-Mod, B-Mod & Sport Mod based) that makes running these cars attractive to a racer’s budget from the hobby racer to a semi-pro effort.</p>
			<p>Right there by your side is Strange Oval, supplying the best in dirt track racing products found anywhere on the market. From our legendary wrap axles and drive plates, Strange Oval often makes the difference between winning and losing. If you’re not running Strange axles, take a look at the guy next to you, he probably is. When you want to stay in the winner’s circle, don’t just race… RACE STRANGE!</p></div>
					 <div class="tab-con" data-binder="tab-3"><p>With wide variations in rules, the modified UMP, USMTS and Renegade sanctioned series are very similar and have the greatest number of cars running at various tracks throughout the country. Mainly run on dirt tracks, these cars are both economical to run and offer a wide range of rules that makes running these cars attractive to a racer’s budget from the hobby racer to a semi-pro effort.</p>
			<p>Right there tearing up dirt tracks across the county is Strange Oval, supplying the best in oval track racing parts found anywhere on the market. From our legendary twist axles and drive plates, Strange Oval often makes the difference between winning and losing. When you want to stay in the winner’s circle, don’t just race… RACE STRANGE!</p></div>
		<?php */?>
	</div>
<?php endif; 
}else{

 echo apply_filters( 'woocommerce_short_description', $post->post_excerpt );
 
}
?>
</div>