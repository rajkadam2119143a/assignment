<?php
defined('ABSPATH') || exit;
get_header();
?>

<div class="ldc-blog-wrap">
	<nav class="ldc-breadcrumb" aria-label="Breadcrumb">
		<ol>
			<li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
			<li aria-current="page">Blog</li>
		</ol>
	</nav>

	<header class="ldc-blog-header">
		<h1>LenDenClub Blog</h1>
		<p style="color:#5B6472;font-size:17px;max-width:640px;">Regulation, platform mechanics, and portfolio strategy for lenders on India's largest RBI-registered NBFC-P2P platform.</p>
	</header>

	<?php if (have_posts()) : ?>
		<div class="ldc-archive-grid">
			<?php while (have_posts()) : the_post(); ?>
				<a class="ldc-related-card" href="<?php the_permalink(); ?>">
					<?php if (has_post_thumbnail()) : ?>
						<div class="ldc-related-thumb"><?php the_post_thumbnail('medium_large'); ?></div>
					<?php else : ?>
						<div class="ldc-related-thumb ldc-related-thumb--placeholder"></div>
					<?php endif; ?>
					<div class="ldc-related-body">
						<h3><?php the_title(); ?></h3>
						<p style="color:#5B6472;font-size:14.5px;margin:0 0 10px;"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
						<span class="ldc-related-cta">Read &rarr;</span>
					</div>
				</a>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p>No articles published yet.</p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
