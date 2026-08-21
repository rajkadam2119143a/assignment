<?php
defined('ABSPATH') || exit;
get_header();

while (have_posts()) :
	the_post();
	$cats = get_the_category();
	$cat = $cats ? $cats[0] : null;
	?>

<article class="ldc-blog-wrap">

	<?php echo do_shortcode('[ldc_breadcrumb]'); ?>

	<header class="ldc-blog-header">
		<?php if ($cat) : ?>
			<a class="ldc-blog-category" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a>
		<?php endif; ?>
		<h1><?php the_title(); ?></h1>
		<div class="ldc-blog-meta">
			<span>By <?php the_author(); ?></span>
			<span>Published <?php echo esc_html(get_the_date()); ?></span>
			<?php if (get_the_modified_date() !== get_the_date()) : ?>
				<span>Updated <?php echo esc_html(get_the_modified_date()); ?></span>
			<?php endif; ?>
			<span><?php echo esc_html(ldc_reading_minutes()); ?> min read</span>
		</div>
	</header>

	<?php if (has_post_thumbnail()) : ?>
		<div class="ldc-blog-featured-image">
			<?php the_post_thumbnail('large'); ?>
		</div>
	<?php endif; ?>

	<div class="ldc-blog-layout">
		<aside><?php echo do_shortcode('[ldc_toc]'); ?></aside>
		<div class="ldc-article-body">
			<?php the_content(); ?>
		</div>
	</div>

	<?php if (do_shortcode('[ldc_related]')) : ?>
		<h2 class="ldc-blog-section-heading">Related Articles</h2>
		<?php echo do_shortcode('[ldc_related]'); ?>
	<?php endif; ?>

	<?php echo do_shortcode('[ldc_author_box]'); ?>

	<?php if (ldc_get_faqs()) : ?>
		<h2 class="ldc-blog-section-heading">Frequently Asked Questions</h2>
		<?php echo do_shortcode('[ldc_faq]'); ?>
	<?php endif; ?>

	<?php echo do_shortcode('[ldc_cta title="Ready to take the next step?" text="Explore lending on LenDenClub and start diversifying today." button="Get Started"]'); ?>

</article>

<?php
endwhile;

get_footer();
