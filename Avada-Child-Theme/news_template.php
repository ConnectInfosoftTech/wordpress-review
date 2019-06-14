<?php
/*
Template Name: News and Events Page


*/?>
<?php get_header(); ?>
<script type="text/javascript">
jQuery( document ).ready(function() {
jQuery( ".news-box" ).mouseover(function() {
jQuery(this ).find( ".newsarrow a img" ).attr("src"," ");
jQuery(this ).find( ".newsarrow a img" ).attr("src","<?php echo get_stylesheet_directory_uri();?>/images/news-arrow_dark.jpg ");
});
jQuery( ".news-box" ).mouseout(function() {
jQuery(this ).find( ".newsarrow a img" ).attr("src"," ");
jQuery(this ).find( ".newsarrow a img" ).attr("src","<?php echo get_stylesheet_directory_uri();?>/images/news-arrow.jpg ");
});
});
</script>
	<div id="content" <?php Avada()->layout->add_class( 'content_class' ); ?> <?php Avada()->layout->add_style( 'content_style' ); ?>>

    <?php get_template_part( 'templates/news-layout', 'layout' ); ?>
    </div>
	<?php do_action( 'fusion_after_content' ); ?>
<?php get_footer();

// Omit closing PHP tag to avoid "Headers already sent" issues.
