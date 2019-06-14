<?php



// Do not allow directly accessing this file.

if ( ! defined( 'ABSPATH' ) ) {

	exit( 'Direct script access denied.' );

}

?>

<!DOCTYPE html>

<?php global $woocommerce; ?>

<html class="<?php echo ( Avada()->settings->get( 'smooth_scrolling' ) ) ? 'no-overflow-y' : ''; ?>" <?php language_attributes(); ?>>

<head>

	<meta http-equiv="X-UA-Compatible" content="IE=edge" />



	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<meta name="google-site-verification" content="Psx9dhUjI-Ad-cF_K5FXqR7UdOKcw1DioCDYHkDsDkI" />

	<?php $is_ipad = (bool) ( isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== strpos( $_SERVER['HTTP_USER_AGENT'],'iPad' ) ); ?>



	<?php

	$viewport = '';

	if ( Avada()->settings->get( 'responsive' ) && $is_ipad ) {

		$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';

	} elseif ( Avada()->settings->get( 'responsive' ) ) {

		if ( Avada()->settings->get( 'mobile_zoom' ) ) {

			$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1" />';

		} else {

			$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';

		}

	}



	$viewport = apply_filters( 'avada_viewport_meta', $viewport );

	echo $viewport;

	?>

	<?php wp_head(); ?>



	<?php



	$object_id = get_queried_object_id();

	$c_page_id  = Avada()->get_page_id();

	?>



	<script type="text/javascript">

		var doc = document.documentElement;

		doc.setAttribute('data-useragent', navigator.userAgent);

	</script>



	<?php echo Avada()->settings->get( 'google_analytics' ); ?>



	<?php echo Avada()->settings->get( 'space_head' ); ?>

</head>

<?php

$wrapper_class = '';





if ( is_page_template( 'blank.php' ) ) {

	$wrapper_class  = 'wrapper_blank';

}



if ( 'modern' == Avada()->settings->get( 'mobile_menu_design' ) ) {

	$mobile_logo_pos = strtolower( Avada()->settings->get( 'logo_alignment' ) );

	if ( 'center' == strtolower( Avada()->settings->get( 'logo_alignment' ) ) ) {

		$mobile_logo_pos = 'left';

	}

}



?>

<body <?php body_class(); ?>>

	<?php do_action( 'avada_before_body_content' ); ?>

	<?php $boxed_side_header_right = false; ?>

	<?php if ( ( ( 'Boxed' == Avada()->settings->get( 'layout' ) && ( 'default' == get_post_meta( $c_page_id, 'pyre_page_bg_layout', true ) || '' == get_post_meta( $c_page_id, 'pyre_page_bg_layout', true ) ) ) || 'boxed' == get_post_meta( $c_page_id, 'pyre_page_bg_layout', true ) ) && 'Top' != Avada()->settings->get( 'header_position' ) ) : ?>

		<?php if ( Avada()->settings->get( 'slidingbar_widgets' ) && ! is_page_template( 'blank.php' ) && ( 'Right' == Avada()->settings->get( 'header_position' ) || 'Left' == Avada()->settings->get( 'header_position' ) ) ) : ?>

			<?php get_template_part( 'slidingbar' ); ?>

			<?php $boxed_side_header_right = true; ?>

		<?php endif; ?>

		<div id="boxed-wrapper">

	<?php endif; ?>

	<div id="wrapper" class="<?php echo $wrapper_class; ?>">

		<div id="home" style="position:relative;top:1px;"></div>

		<?php if ( Avada()->settings->get( 'slidingbar_widgets' ) && ! is_page_template( 'blank.php' ) && ! $boxed_side_header_right ) : ?>

			<?php get_template_part( 'slidingbar' ); ?>

		<?php endif; ?>

		<?php if ( false !== strpos( Avada()->settings->get( 'footer_special_effects' ), 'footer_sticky' ) ) : ?>

			<div class="above-footer-wrapper">

		<?php endif; ?>



		<?php avada_header_template( 'Below' ); ?>

		<?php if ( 'Left' == Avada()->settings->get( 'header_position' ) || 'Right' == Avada()->settings->get( 'header_position' ) ) : ?>

			<?php avada_side_header(); ?>

		<?php endif; ?>



		<div id="sliders-container">

			<?php

			if ( is_search() ) {

				$slider_page_id = '';

			} else {

				$slider_page_id = '';

				if ( ! is_home() && ! is_front_page() && ! is_archive() && isset( $object_id ) ) {

					$slider_page_id = $object_id;

				}

				if ( ! is_home() && is_front_page() && isset( $object_id ) ) {

					$slider_page_id = $object_id;

				}

				if ( is_home() && ! is_front_page() ) {

					$slider_page_id = get_option( 'page_for_posts' );

				}

				if ( class_exists( 'WooCommerce' ) && is_shop() ) {

					$slider_page_id = get_option( 'woocommerce_shop_page_id' );

				}



				if ( ( 'publish' == get_post_status( $slider_page_id ) && ! post_password_required() ) || ( current_user_can( 'read_private_pages' ) && in_array( get_post_status( $slider_page_id ), array( 'private', 'draft', 'pending' ) ) ) ) {

					avada_slider( $slider_page_id );

				}

			} ?>

		</div>

		<?php if ( get_post_meta( $slider_page_id, 'pyre_fallback', true ) ) : ?>

			<div id="fallback-slide">

				<img src="<?php echo get_post_meta( $slider_page_id, 'pyre_fallback', true ); ?>" alt="" />

			</div>

		<?php endif; ?>

		<?php avada_header_template( 'Above' ); ?>



		<?php if ( has_action( 'avada_override_current_page_title_bar' ) ) : ?>

			<?php do_action( 'avada_override_current_page_title_bar', $c_page_id ); ?>

		<?php else : ?>

			<?php avada_current_page_title_bar( $c_page_id ); ?>

		<?php endif; ?>



		<?php if ( is_page_template( 'contact.php' ) && Avada()->settings->get( 'recaptcha_public' ) && Avada()->settings->get( 'recaptcha_private' ) ) : ?>

			<script type="text/javascript">var RecaptchaOptions = { theme : '<?php echo Avada()->settings->get( 'recaptcha_color_scheme' ); ?>' };</script>

		<?php endif; ?>



		<?php if ( is_page_template( 'contact.php' ) && Avada()->settings->get( 'gmap_address' ) && Avada()->settings->get( 'status_gmap' ) ) : ?>

			<?php

			$map_popup             = ( ! Avada()->settings->get( 'map_popup' ) )          ? 'yes' : 'no';

			$map_scrollwheel       = ( Avada()->settings->get( 'map_scrollwheel' ) )    ? 'yes' : 'no';

			$map_scale             = ( Avada()->settings->get( 'map_scale' ) )          ? 'yes' : 'no';

			$map_zoomcontrol       = ( Avada()->settings->get( 'map_zoomcontrol' ) )    ? 'yes' : 'no';

			$address_pin           = ( Avada()->settings->get( 'map_pin' ) )            ? 'yes' : 'no';

			$address_pin_animation = ( Avada()->settings->get( 'gmap_pin_animation' ) ) ? 'yes' : 'no';

			?>

			<div id="fusion-gmap-container">

				<?php echo Avada()->google_map->render_map( array(

					'address'                  => Avada()->settings->get( 'gmap_address' ),

					'type'                     => Avada()->settings->get( 'gmap_type' ),

					'address_pin'              => $address_pin,

					'animation'                => $address_pin_animation,

					'map_style'                => Avada()->settings->get( 'map_styling' ),

					'overlay_color'            => Avada()->settings->get( 'map_overlay_color' ),

					'infobox'                  => Avada()->settings->get( 'map_infobox_styling' ),

					'infobox_background_color' => Avada()->settings->get( 'map_infobox_bg_color' ),

					'infobox_text_color'       => Avada()->settings->get( 'map_infobox_text_color' ),

					'infobox_content'          => htmlentities( Avada()->settings->get( 'map_infobox_content' ) ),

					'icon'                     => Avada()->settings->get( 'map_custom_marker_icon' ),

					'width'                    => Avada()->settings->get( 'gmap_dimensions', 'width' ),

					'height'                   => Avada()->settings->get( 'gmap_dimensions', 'height' ),

					'zoom'                     => Avada()->settings->get( 'map_zoom_level' ),

					'scrollwheel'              => $map_scrollwheel,

					'scale'                    => $map_scale,

					'zoom_pancontrol'          => $map_zoomcontrol,

					'popup'                    => $map_popup,

				) ); ?>

			</div>

		<?php endif; ?>



		<?php if ( is_page_template( 'contact-2.php' ) && Avada()->settings->get( 'gmap_address' ) && Avada()->settings->get( 'status_gmap' ) ) : ?>

			<?php

			$map_popup             = ( ! Avada()->settings->get( 'map_popup' ) )          ? 'yes' : 'no';

			$map_scrollwheel       = ( Avada()->settings->get( 'map_scrollwheel' ) )    ? 'yes' : 'no';

			$map_scale             = ( Avada()->settings->get( 'map_scale' ) )          ? 'yes' : 'no';

			$map_zoomcontrol       = ( Avada()->settings->get( 'map_zoomcontrol' ) )    ? 'yes' : 'no';

			$address_pin_animation = ( Avada()->settings->get( 'gmap_pin_animation' ) ) ? 'yes' : 'no';

			?>

			<div id="fusion-gmap-container">

				<?php echo Avada()->google_map->render_map( array(

					'address'                  => Avada()->settings->get( 'gmap_address' ),

					'type'                     => Avada()->settings->get( 'gmap_type' ),

					'map_style'                => Avada()->settings->get( 'map_styling' ),

					'animation'                => $address_pin_animation,

					'overlay_color'            => Avada()->settings->get( 'map_overlay_color' ),

					'infobox'                  => Avada()->settings->get( 'map_infobox_styling' ),

					'infobox_background_color' => Avada()->settings->get( 'map_infobox_bg_color' ),

					'infobox_text_color'       => Avada()->settings->get( 'map_infobox_text_color' ),

					'infobox_content'          => htmlentities( Avada()->settings->get( 'map_infobox_content' ) ),

					'icon'                     => Avada()->settings->get( 'map_custom_marker_icon' ),

					'width'                    => Avada()->settings->get( 'gmap_dimensions', 'width' ),

					'height'                   => Avada()->settings->get( 'gmap_dimensions', 'height' ),

					'zoom'                     => Avada()->settings->get( 'map_zoom_level' ),

					'scrollwheel'              => $map_scrollwheel,

					'scale'                    => $map_scale,

					'zoom_pancontrol'          => $map_zoomcontrol,

					'popup'                    => $map_popup,

				) ); ?>

			</div>

		<?php endif; ?>

		<?php

		$main_css   = '';

		$row_css    = '';

		$main_class = '';



		if ( Avada()->layout->is_hundred_percent_template() ) {

			$main_css = 'padding-left:0px;padding-right:0px;';

			if ( Avada()->settings->get( 'hundredp_padding' ) && ! get_post_meta( $c_page_id, 'pyre_hundredp_padding', true ) ) {

				$main_css = 'padding-left:' . Avada()->settings->get( 'hundredp_padding' ) . ';padding-right:' . Avada()->settings->get( 'hundredp_padding' );

			}

			if ( get_post_meta( $c_page_id, 'pyre_hundredp_padding', true ) ) {

				$main_css = 'padding-left:' . get_post_meta( $c_page_id, 'pyre_hundredp_padding', true ) . ';padding-right:' . get_post_meta( $c_page_id, 'pyre_hundredp_padding', true );

			}

			$row_css    = 'max-width:100%;';

			$main_class = 'width-100';

		}

		do_action( 'avada_before_main_container' );

		?>

		<div id="main" class="clearfix <?php echo $main_class; ?>" style="<?php echo $main_css; ?>">

			<div class="fusion-row" style="<?php echo $row_css; ?>">

