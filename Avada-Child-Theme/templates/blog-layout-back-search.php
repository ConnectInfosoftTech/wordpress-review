<?php
/**
 * Render the blog layouts
 *
 * @author 		ThemeFusion
 * @package 	Avada/Templates
 * @version     1.0
 */
fusion_block_direct_access();

global $wp_query;
?>
<div class="col-main"><section id="primary" class="site-content">
<script type="text/javascript">
jQuery( document ).ready(function() {
jQuery( ".news-box" ).mouseover(function() {
jQuery(this ).find( ".newsarrow a img" ).attr("src"," ");
jQuery(this ).find( ".newsarrow a img" ).attr("src","<?php echo get_stylesheet_directory_uri(); ?>/images/news-arrow_dark.jpg ");
});
jQuery( ".news-box" ).mouseout(function() {
jQuery(this ).find( ".newsarrow a img" ).attr("src"," ");
jQuery(this ).find( ".newsarrow a img" ).attr("src","<?php echo get_stylesheet_directory_uri(); ?>/images/news-arrow.jpg ");
});
});
</script>

		<?php if ( have_posts() ) : ?>
			<?php /*?><header class="archive-header">
				<h1 class="archive-title"><?php
					if ( is_day() ) :
						printf( __( 'Daily Archives: %s', 'twentytwelve' ), '<span>' . get_the_date() . '</span>' );
					elseif ( is_month() ) :
						printf( __( 'Monthly Archives: %s', 'twentytwelve' ), '<span>' . get_the_date( _x( 'F Y', 'monthly archives date format', 'twentytwelve' ) ) . '</span>' );
					elseif ( is_year() ) :
						printf( __( 'Yearly Archives: %s', 'twentytwelve' ), '<span>' . get_the_date( _x( 'Y', 'yearly archives date format', 'twentytwelve' ) ) . '</span>' );
					else :
						_e( 'Archives', 'twentytwelve' );
					endif;
				?></h1>
			</header><?php */?><!-- .archive-header -->

			<?php  
		 
		 $i=1;
		?>

         <?php while ( have_posts() ) : the_post(); ?>
			<div class="news-collum <?php if($i%4==0){ ?> last-collum <?php } ?>">
					<?php include('content-new.php'); ?>
			</div>
		
			<?php 	$i++; 
			
			endwhile; ?>
<div class="pagination-template">
 			<?php // Get the pagination
				fusion_pagination( $pages = '', $range = 2 );
				
				wp_reset_query();
			?>
			</div>

		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>
		<?php endif; ?>

	</section><!-- #primary -->
    </div>