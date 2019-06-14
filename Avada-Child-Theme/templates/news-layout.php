<?php ////Render the content of the portfolio page
//		while ( have_posts() ): the_post();
//		
//			ob_start();
//			post_class( 'fusion-portfolio-page-content' );
//			$post_classes = ob_get_clean();
//		
//			echo sprintf( '<div id="post-%s" %s>', get_the_ID(), $post_classes );
//				// Render the rich snippets
//				echo avada_render_rich_snippets_for_pages();
//		
//				// Render the featured images
//				echo avada_featured_images_for_pages();
//		
//				 //Portfolio page content
//				echo '<div class="post-content">';
//					the_content();
//					avada_link_pages();
//				echo '</div>';
//			echo '</div>';
//		
//		endwhile;

			
			$categories = get_categories( array ('orderby' => 'count', 'order' => 'desc' ) ); 
           // for dev server
		    $category_arr = array(381,384,385,394);  
			// for local system
		    //$category_arr = array(382,385,386,395); 
      

	 		$i=1;
	 
			 foreach ($categories as $category) : 
	 
					if(in_array($category->term_id,$category_arr)){
				
					 query_posts( array ( 'category_name' => $category->slug, 'showposts' => '4', 'orderby' => 'date' ) ); ?>
			<div class="news-collum <?php if($i%4==0){ ?> last-collum <?php } ?>">
			<h2 class="snews"> <a href="<?php echo esc_url(get_category_link( $category->term_id)); ?>"><?php single_cat_title(); ?></a></h2>
		 
		<?php if ( have_posts() ): ?>
		 
				<?php while ( have_posts() ) : ?>
		 
					<?php the_post(); ?>
		 <div class="news-box <?php if($i%4==0){ ?> simple_green_footer <?php } else if($i%3==0){ ?> green_footer <?php } else if($i%2==0){ ?> red_footer <?php } else{ ?>  blue_footer <?php }?>">
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
					<img src="<?php echo get_stylesheet_directory_uri();  ?>/images/no-image.png"  height="150" width="180" />
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
				 
		 
				<?php endwhile; 
				
				wp_reset_query();?> 
				 
				<?php endif; ?>
				</div>
				 
			<?php 
			
			$i++; 
			}
		
		 
		 	
		 	endforeach;