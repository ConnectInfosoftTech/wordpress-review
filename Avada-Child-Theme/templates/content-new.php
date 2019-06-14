<?php
/**
 * The default template for displaying content. Used for both single and index/archive/search.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
*/

 //if ( is_singular() && current_theme_supports( 'post-thumbnails' )  ) { 

if (is_category( )) {
  $cat = get_query_var('cat');
  $current_cat = get_category ($cat);
  $slug = $current_cat->slug;
 }
?>
 		<div class="news-box <?php if($slug=='strangenews'){ ?> blue_footer <?php } else if($slug=='strangeracing'){ ?> red_footer <?php } else if($slug=='newproducts'){ ?> green_footer <?php } else if($slug=='strange-life'){ ?>  simple_green_footer <?php }?>" style="margin:5px 0 0 0">
<div class="news-thum"><a href="<?php the_permalink(); ?>"><?php 
			$size= array(300,300);
			$default_attr = array(
			'class'	=> "none",
			'alt'	=> trim(strip_tags( $post->post_title )),
			'title'	=> trim(strip_tags( $post->post_title )),
			);
			$src=wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) ); if($src) {?>
			<img src="<?php echo $src[0];  ?>"  height="150" width="180" />
			<?php } else{ ?>
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/no-image.png"  height="150" width="180" />
			<?php } ?>
			</a></div>
<h3 class="newshead"><a href="<?php the_permalink(); ?>"><?php $title=get_the_title(); $lenght=strlen($title);if($lenght <= 55){echo $title;}else { echo  ucwords(strtolower(substr($title,0,55)))."..";} ?></a></h3>
<?php 
$content = get_the_content(); // save entire content in variable
$content =strip_tags($content);
$content = apply_filters( 'the_content', (strip_tags($content)) ); // WP bug fix
$content = str_replace( ']]>', ']]>', $content ); // ...as well
$out =trim_the_content( $content, __( " Read More", "" ), $perma_link,40); // trim to 55 words
//the_excerpt(); 


?>
<div class="content-news"><?php echo substr($out,0,120).".."; ?></div>
<span class="newsarrow">
<a href="<?php the_permalink(); ?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/news-arrow.jpg" alt="" /></a></span>
</div>
<?php //} ?>