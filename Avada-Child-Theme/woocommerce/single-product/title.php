<?php
/**
 * Single Product title
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version	 1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//<h2 itemprop="name" class="product_title entry-title"><?php the_title(); ? ></h2>
?>
<h1 itemprop="name" class="product_title entry-title"><?php strip_tags(the_title()); ?></h1>