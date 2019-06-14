<?php

// Render the content of the portfolio page
//while ( have_posts() ): the_post();
//
//	ob_start();
//	post_class( 'fusion-portfolio-page-content' );
//	$post_classes = ob_get_clean();
//
//	echo sprintf( '<div id="post-%s" %s>', get_the_ID(), $post_classes );
//		// Render the rich snippets
//		echo avada_render_rich_snippets_for_pages();
//
//		// Render the featured images
//		echo avada_featured_images_for_pages();

		// Portfolio page content
//		echo '<div class="container custom-cms-shopper-home">';
//			the_content();
//			avada_link_pages();
//		echo '</div>';
//	echo '</div>';
//
//endwhile;

echo sprintf( '<div id="post-%s" %s>', get_the_ID(), $post_classes );
echo '<div class="container custom-cms-shopper-home">';
?>
<div class="container custom-cms-shopper-home">
<div class="row">
<div class="col-lg-8 col-sm-6 slider"><?php echo do_shortcode('[layerslider id="1"]');?></div>
<div class="col-lg-4 col-sm-6 list-top"><?php echo do_shortcode('[wc_related_products]');?></div>
</div>
<div class="row">
	<div class="col-lg-12"><div class="hr"></div></div>
</div>
<div class="row">
<?php the_content();?>
</div>
<div class="row">
	<div class="col-lg-12"><div class="hr"></div></div>
</div>
<div class="row">
<div class="col-lg-8 col-sm-6 strange-news">
<h2>Strange News</h2>
<?php echo do_shortcode('[STRANGE_NEWS]');?>
</div>
<div class="col-lg-4 col-sm-6 specials">
<h2>Ford, GM, Mopar Easy Find Charts</h2>
<p class="banner-right">
<a href="<?php echo home_url();?>/product-category/dragrace/brake-kits-components/"><img class="alignnone size-full wp-image-11882" src="<?php echo home_url();?>/wp-content/uploads/2016/01/homepagespecial2.jpg" alt="homepagespecial2" width="370" height="430" /></a></p>

</div>
</div>
<div class="row">
	<div class="col-lg-12"><div class="hr"></div></div>
</div>
<div class="row">
<div class="col-lg-12">
<h2>Recent videos</h2>
</div>
<div class="col-lg-4 home-video"><iframe src="//www.youtube.com/embed/7bvVMR66TN0" width="295" height="164" frameborder="0"></iframe></div>
<div class="col-lg-4 home-video-center"><iframe src="//www.youtube.com/embed/Gq_br5yTFZM" width="295" height="164" frameborder="0"></iframe></div>
<div class="col-lg-4 home-video-right"><iframe src="//www.youtube.com/embed/8MFNlC69XKI" width="295" height="164" frameborder="0"></iframe></div>
</div>
</div>
<?php
	echo '</div>';
	echo '</div>';
?>