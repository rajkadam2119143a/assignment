<?php
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="ldc-header" id="top">
	<div class="ldc-header-inner">
		<a href="<?php echo esc_url(home_url('/')); ?>" class="ldc-logo">
			<span class="ldc-logo-mark"><i class="fas fa-bolt" aria-hidden="true"></i></span>
			<span class="ldc-logo-text">LenDenClub</span>
		</a>
		<nav class="ldc-nav-desktop" aria-label="Primary">
			<?php
			wp_nav_menu([
				'theme_location' => 'header-menu',
				'container' => false,
				'menu_class' => 'ldc-nav-menu',
				'fallback_cb' => false,
			]);
			?>
		</nav>
		<div class="ldc-header-actions">
			<a href="#" class="ldc-login-link">Login</a>
			<a href="<?php echo esc_url(home_url('/#lead-form')); ?>" class="ldc-cta-btn ldc-cta-btn--sm">Get Started</a>
			<?php echo do_shortcode('[pyxis-toggle]'); ?>
		</div>
	</div>
</header>
