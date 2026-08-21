<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function () {
	// filemtime, not the parent theme's static version string, so every CSS edit
	// actually busts the browser cache instead of silently serving a stale copy.
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_uri(),
		[],
		filemtime(get_stylesheet_directory() . '/style.css')
	);
	wp_enqueue_script(
		'ldc-frontend',
		get_stylesheet_directory_uri() . '/assets/ldc-frontend.js',
		[],
		'1.0.0',
		true
	);

	// header.php/footer.php render on every page (blog and homepage alike) outside
	// Elementor's own asset pipeline, so Font Awesome is enqueued here site-wide
	// (reusing Elementor's already-bundled local copy — no CDN dependency).
	wp_enqueue_style(
		'ldc-font-awesome',
		plugins_url('assets/lib/font-awesome/css/all.min.css', WP_PLUGIN_DIR . '/elementor/elementor.php'),
		[],
		'5.0'
	);
});

// Elementor pages/posts print their own H1 via the builder; suppress the theme's default page title.
add_filter('hello_elementor_page_title', '__return_false');

/* =========================================================
 * Nav menu locations — manageable at Appearance → Menus, exactly
 * like any other WordPress site. "pyxis" (mobile off-canvas) is
 * registered by the Pyxis Mobile Menu plugin itself.
 * ========================================================= */
add_action('after_setup_theme', function () {
	register_nav_menus([
		'header-menu' => 'Header Menu',
		'footer-about-menu' => 'Footer: About',
		'footer-lending-menu' => 'Footer: Lending',
		'footer-resources-menu' => 'Footer: Resources',
		'footer-legal-menu' => 'Footer: Legal',
	]);
});

/* =========================================================
 * Footer widget area — wp-admin editable via Appearance → Widgets —
 * for the legal/copyright paragraph only (prose, not a link list;
 * the four footer link columns are real nav menus, see above).
 * ========================================================= */
add_action('widgets_init', function () {
	register_sidebar([
		'id' => 'footer-legal-text',
		'name' => 'Footer: Legal / Copyright Text',
		'description' => 'The disclaimer + copyright paragraph shown at the very bottom of the footer, on every page.',
		'before_widget' => '<div class="ldc-footer-widget">',
		'after_widget' => '</div>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	]);
});

/* =========================================================
 * FAQ custom post type + taxonomy — a real add/delete list at
 * FAQs → All FAQs in wp-admin (like Posts), instead of repeater
 * rows buried inside a Page/Post edit screen. Each FAQ (title =
 * question, content = answer) is tagged with the "FAQ Group" it
 * belongs to — Homepage, or one group per blog article — via a
 * normal taxonomy checkbox picker in the FAQ editor's sidebar.
 * ========================================================= */
add_action('init', function () {
	register_post_type('ldc_faq', [
		'labels' => [
			'name' => 'FAQs',
			'singular_name' => 'FAQ',
			'add_new_item' => 'Add New FAQ',
			'edit_item' => 'Edit FAQ',
			'new_item' => 'New FAQ',
			'all_items' => 'All FAQs',
			'menu_name' => 'FAQs',
			'featured_image' => 'FAQ Image',
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-editor-help',
		'menu_position' => 30,
		'supports' => ['title', 'editor', 'page-attributes'],
		'labels_title_placeholder' => 'Enter the question',
	]);

	register_taxonomy('ldc_faq_group', 'ldc_faq', [
		'labels' => [
			'name' => 'FAQ Groups',
			'singular_name' => 'FAQ Group',
			'menu_name' => 'FAQ Groups',
		],
		'hierarchical' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_menu' => true,
		'public' => false,
		'rewrite' => false,
	]);
});

// Custom placeholder text for the FAQ title field ("Enter the question" instead of "Add title").
add_filter('enter_title_here', function ($title, $post) {
	return $post->post_type === 'ldc_faq' ? 'Enter the question' : $title;
}, 10, 2);

/* =========================================================
 * ACF field on the FAQ editor: a real, functional field (not just a
 * demo) — "Featured FAQ" moves that question to the top of its group,
 * ahead of manual ordering. This is a code-registered ("local") field
 * group, which is why it won't appear in Custom Fields → Field Groups
 * — that list only shows field groups created through the ACF admin
 * UI itself. It still fully works: open any FAQ to edit it and the
 * checkbox is right there.
 * ========================================================= */
add_action('acf/init', function () {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}
	acf_add_local_field_group([
		'key' => 'group_ldc_faq_settings',
		'title' => 'FAQ Settings',
		'fields' => [
			[
				'key' => 'field_ldc_faq_featured',
				'label' => 'Featured FAQ',
				'name' => 'featured_faq',
				'type' => 'true_false',
				'ui' => 1,
				'instructions' => 'Show this question first, ahead of the others in its FAQ Group.',
			],
		],
		'location' => [
			[
				['param' => 'post_type', 'operator' => '==', 'value' => 'ldc_faq'],
			],
		],
	]);
});

function ldc_faq_group_slug_for_post($post_id) {
	return ((int) $post_id === (int) get_option('page_on_front')) ? 'homepage' : get_post_field('post_name', $post_id);
}

/**
 * Fetch the FAQs belonging to a given page/post, by its FAQ Group term.
 * Used for the homepage, and for every blog article.
 */
function ldc_get_faqs($post_id = null) {
	$post_id = $post_id ?: get_the_ID();
	$slug = ldc_faq_group_slug_for_post($post_id);
	if (!$slug) {
		return [];
	}
	$term = get_term_by('slug', $slug, 'ldc_faq_group');
	if (!$term) {
		return [];
	}
	$q = new WP_Query([
		'post_type' => 'ldc_faq',
		'posts_per_page' => -1,
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'tax_query' => [
			['taxonomy' => 'ldc_faq_group', 'field' => 'term_id', 'terms' => $term->term_id],
		],
	]);
	$posts = $q->posts;
	// Featured FAQs float to the top, ahead of menu_order — but a post with no
	// featured_faq meta value at all (never touched) must still sort as "not
	// featured" here, so this is done in PHP rather than a meta_key SQL filter
	// (which would silently exclude every post that lacks the meta row).
	usort($posts, function ($a, $b) {
		$fa = function_exists('get_field') ? (bool) get_field('featured_faq', $a->ID) : false;
		$fb = function_exists('get_field') ? (bool) get_field('featured_faq', $b->ID) : false;
		if ($fa === $fb) {
			return $a->menu_order <=> $b->menu_order;
		}
		return $fa ? -1 : 1;
	});

	$faqs = [];
	foreach ($posts as $p) {
		$answer = trim(wp_strip_all_tags($p->post_content));
		if ($p->post_title && $answer) {
			$faqs[] = ['question' => $p->post_title, 'answer' => $answer];
		}
	}
	return $faqs;
}

function ldc_get_home_faqs() {
	$front_page_id = (int) get_option('page_on_front');
	return $front_page_id ? ldc_get_faqs($front_page_id) : [];
}

/**
 * One-time setup: create the FAQ Group terms + seed each with its FAQ posts,
 * so the CPT isn't empty out of the box. Runs once (checked via an option flag).
 */
add_action('init', function () {
	if (get_option('ldc_faqs_seeded')) {
		return;
	}
	if (!post_type_exists('ldc_faq') || !taxonomy_exists('ldc_faq_group')) {
		return;
	}

	$groups = [
		'homepage' => [
			'name' => 'Homepage',
			'faqs' => [
				['q' => 'Is P2P lending legal in India?', 'a' => 'Yes. P2P lending is regulated by the Reserve Bank of India (RBI) under the NBFC-P2P framework introduced in 2017 and updated in August 2024. Only RBI-registered NBFC-P2P platforms are legally permitted to operate this service.'],
				['q' => 'What is P2P lending and how does it work?', 'a' => 'Peer-to-peer lending lets individuals lend money directly to other individuals or small businesses through an RBI-regulated digital platform, instead of routing it through a traditional bank.'],
				['q' => 'Is my money safe with LenDenClub?', 'a' => 'LenDenClub is regulated by the RBI under the NBFC-P2P Master Directions. That said, P2P lending carries credit risk — returns depend on borrowers repaying their loans and are not deposit-insured or guaranteed.'],
				['q' => 'How does LenDenClub verify borrowers?', 'a' => 'Every borrower goes through a multi-point credit assessment before being listed, and LenDenClub maintains an in-house collections process to support recovery if repayments are missed.'],
				['q' => "What if a borrower doesn't repay?", 'a' => "LenDenClub's in-house collections team, together with recovery agencies, follows up on overdue repayments. As with any lending, recovery is not guaranteed, which is why diversifying across many borrowers is recommended."],
			],
		],
		'what-makes-lendenclub-different-from-other-p2p-lending-platforms' => [
			'name' => 'What Makes LenDenClub Different...',
			'faqs' => [
				['q' => 'Is LenDenClub regulated by RBI?', 'a' => 'Yes, it operates as an RBI-registered NBFC-P2P platform following the prescribed regulatory framework.'],
				['q' => 'Does LenDenClub guarantee returns?', 'a' => 'No. Earnings depend entirely on borrower repayments. No P2P platform is permitted to guarantee returns.'],
				['q' => 'How is my money handled?', 'a' => 'All transactions are routed through bank-operated escrow accounts to ensure transparency and compliance.'],
				['q' => 'How is it different from other platforms?', 'a' => 'Differentiation lies in regulatory alignment, transparent communication, structured fund flows, and conservative product design.'],
			],
		],
		'common-questions-new-lenders-ask-before-starting-on-lendenclub' => [
			'name' => 'Common Questions New Lenders Ask...',
			'faqs' => [
				['q' => 'Is my money completely safe?', 'a' => 'No. While the platform is regulated, lending inherently carries delay and default risk.'],
				['q' => 'Who pays the earnings?', 'a' => 'Earnings originate from borrower repayments, not from platform funds.'],
				['q' => 'Can I withdraw any time?', 'a' => 'Funds return according to EMI schedules; this is not an instant-liquidity product.'],
				['q' => 'What happens if a borrower defaults?', 'a' => 'Recovery processes are initiated, though complete recovery is never guaranteed.'],
				['q' => 'How much should I start with?', 'a' => 'Begin with an amount you are comfortable with, and expand gradually as you get familiar with the platform.'],
			],
		],
		'where-lendenclub-fits-in-a-long-term-wealth-strategy' => [
			'name' => 'Where LenDenClub Fits...',
			'faqs' => [
				['q' => 'Can LenDenClub replace my fixed deposits?', 'a' => 'No — fixed deposits target stability and liquidity, while P2P lending carries credit risk and serves a distinct role.'],
				['q' => 'Are earnings guaranteed in P2P lending?', 'a' => 'No — earnings depend on borrower repayments, without guarantees.'],
				['q' => 'Does RBI regulation make P2P lending risk-free?', 'a' => "No — regulation ensures transparency and structure but doesn't eliminate credit risk."],
				['q' => 'Where should P2P lending sit in my portfolio?', 'a' => 'Typically within the income/debt portion, as a diversification layer.'],
				['q' => 'Should I allocate a large portion to P2P lending?', 'a' => 'Most lenders use measured allocations based on their own risk comfort and financial goals.'],
			],
		],
	];

	$order = 0;
	foreach ($groups as $slug => $group) {
		$term = get_term_by('slug', $slug, 'ldc_faq_group');
		if (!$term) {
			$result = wp_insert_term($group['name'], 'ldc_faq_group', ['slug' => $slug]);
			$term_id = is_wp_error($result) ? null : $result['term_id'];
		} else {
			$term_id = $term->term_id;
		}
		if (!$term_id) {
			continue;
		}
		foreach ($group['faqs'] as $faq) {
			$order++;
			$faq_id = wp_insert_post([
				'post_type' => 'ldc_faq',
				'post_title' => $faq['q'],
				'post_content' => $faq['a'],
				'post_status' => 'publish',
				'menu_order' => $order,
			]);
			if ($faq_id && !is_wp_error($faq_id)) {
				wp_set_object_terms($faq_id, [(int) $term_id], 'ldc_faq_group');
			}
		}
	}

	update_option('ldc_faqs_seeded', 1);
}, 20);

/* =========================================================
 * Reading time
 * ========================================================= */
function ldc_reading_minutes($post_id = null) {
	$post_id = $post_id ?: get_the_ID();
	$words = str_word_count(wp_strip_all_tags(get_post_field('post_content', $post_id)));
	return max(1, (int) round($words / 200));
}
add_shortcode('ldc_reading_time', function () {
	if (!is_singular('post')) {
		return '';
	}
	return ldc_reading_minutes() . ' min read';
});

/* =========================================================
 * Heading extraction (shared by TOC + heading-ID injection)
 * ========================================================= */
function ldc_heading_slug($text, &$used) {
	$slug = sanitize_title(wp_strip_all_tags($text));
	if ($slug === '') {
		$slug = 'section';
	}
	$base = $slug;
	$i = 2;
	while (in_array($slug, $used, true)) {
		$slug = $base . '-' . $i;
		$i++;
	}
	return $slug;
}

function ldc_extract_headings($content) {
	$headings = [];
	$used = [];
	if (preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $m) {
			$text = trim(wp_strip_all_tags($m[2]));
			if ($text === '') {
				continue;
			}
			$slug = ldc_heading_slug($text, $used);
			$used[] = $slug;
			$headings[] = ['level' => (int) $m[1], 'id' => $slug, 'text' => $text];
		}
	}
	return $headings;
}

// Inject matching id="" attributes into the rendered H2/H3 tags so TOC links resolve.
add_filter('the_content', function ($content) {
	if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
		return $content;
	}
	$used = [];
	return preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function ($m) use (&$used) {
		$level = $m[1];
		$attrs = $m[2];
		$inner = $m[3];
		$text = trim(wp_strip_all_tags($inner));
		if ($text === '') {
			return $m[0];
		}
		$slug = ldc_heading_slug($text, $used);
		$used[] = $slug;
		$attrs = preg_replace('/\sid="[^"]*"/i', '', $attrs);
		return "<h{$level}{$attrs} id=\"{$slug}\">{$inner}</h{$level}>";
	}, $content);
}, 20);

/* =========================================================
 * Table of Contents shortcode
 * ========================================================= */
add_shortcode('ldc_toc', function () {
	if (!is_singular('post')) {
		return '';
	}
	$headings = ldc_extract_headings(get_post_field('post_content', get_the_ID()));
	if (empty($headings)) {
		return '';
	}
	$html = '<nav class="ldc-toc" aria-label="Table of contents">';
	$html .= '<button type="button" class="ldc-toc-toggle" aria-expanded="true">On This Page <span class="ldc-toc-chevron">&minus;</span></button>';
	$html .= '<ul class="ldc-toc-list">';
	foreach ($headings as $h) {
		$class = $h['level'] === 3 ? ' class="ldc-toc-sub"' : '';
		$html .= "<li{$class}><a href=\"#{$h['id']}\">" . esc_html($h['text']) . '</a></li>';
	}
	$html .= '</ul></nav>';
	return $html;
});

/* =========================================================
 * Breadcrumb shortcode
 * ========================================================= */
add_shortcode('ldc_breadcrumb', function () {
	if (!is_singular('post')) {
		return '';
	}
	$cats = get_the_category();
	$cat = $cats ? $cats[0] : null;
	$blog_page_id = (int) get_option('page_for_posts');

	$html = '<nav class="ldc-breadcrumb" aria-label="Breadcrumb"><ol>';
	$html .= '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
	if ($blog_page_id) {
		$html .= '<li><a href="' . esc_url(get_permalink($blog_page_id)) . '">Blog</a></li>';
	}
	if ($cat) {
		$html .= '<li><a href="' . esc_url(get_category_link($cat)) . '">' . esc_html($cat->name) . '</a></li>';
	}
	$html .= '<li aria-current="page">' . esc_html(get_the_title()) . '</li>';
	$html .= '</ol></nav>';
	return $html;
});

/* =========================================================
 * Author box shortcode
 * ========================================================= */
add_shortcode('ldc_author_box', function () {
	if (!is_singular('post')) {
		return '';
	}
	$author_id = get_the_author_meta('ID');
	$name = get_the_author_meta('display_name', $author_id);
	$bio = get_the_author_meta('description', $author_id);
	$avatar = get_avatar($author_id, 72);
	$archive_link = get_author_posts_url($author_id);

	$html = '<div class="ldc-author-box">';
	$html .= '<div class="ldc-author-avatar">' . $avatar . '</div>';
	$html .= '<div class="ldc-author-info">';
	$html .= '<p class="ldc-author-label">About the Author</p>';
	$html .= '<h3 class="ldc-author-name">' . esc_html($name) . '</h3>';
	$html .= '<p class="ldc-author-bio">' . esc_html($bio) . '</p>';
	$html .= '<a class="ldc-author-link" href="' . esc_url($archive_link) . '">View all articles &rarr;</a>';
	$html .= '</div></div>';
	return $html;
});

/* =========================================================
 * Related articles shortcode (same category, excludes current post)
 * ========================================================= */
add_shortcode('ldc_related', function () {
	if (!is_singular('post')) {
		return '';
	}
	$cats = wp_get_post_categories(get_the_ID());
	$q = new WP_Query([
		'post_type' => 'post',
		'posts_per_page' => 3,
		'post__not_in' => [get_the_ID()],
		'category__in' => $cats ?: null,
		'ignore_sticky_posts' => true,
	]);
	if (!$q->have_posts()) {
		return '';
	}
	$html = '<div class="ldc-related-grid">';
	while ($q->have_posts()) {
		$q->the_post();
		$html .= '<a class="ldc-related-card" href="' . esc_url(get_permalink()) . '">';
		if (has_post_thumbnail()) {
			$html .= '<div class="ldc-related-thumb">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
		} else {
			$html .= '<div class="ldc-related-thumb ldc-related-thumb--placeholder"></div>';
		}
		$html .= '<div class="ldc-related-body"><h3>' . esc_html(get_the_title()) . '</h3><span class="ldc-related-cta">Read &rarr;</span></div>';
		$html .= '</a>';
	}
	wp_reset_postdata();
	$html .= '</div>';
	return $html;
});

/* =========================================================
 * In-content CTA shortcode (used inside post body between H2 sections)
 * ========================================================= */
add_shortcode('ldc_cta', function ($atts) {
	$atts = shortcode_atts([
		'title' => 'Ready to take the next step?',
		'text' => 'Explore your options with LenDenClub.',
		'button' => 'Get Started',
		'url' => home_url('/#lead-form'),
	], $atts);
	return '<div class="ldc-incontent-cta"><h3>' . esc_html($atts['title']) . '</h3><p>' . esc_html($atts['text']) . '</p>'
		. '<a class="ldc-cta-btn" href="' . esc_url($atts['url']) . '">' . esc_html($atts['button']) . '</a></div>';
});

/* =========================================================
 * FAQ shortcode (renders the ldc_faq CPT as an accordion; same
 * data feeds the FAQPage schema below)
 * ========================================================= */
function ldc_render_faq_html($faqs, $id_prefix = 'ldc-faq-a') {
	if (empty($faqs)) {
		return '';
	}
	$html = '<div class="ldc-faq">';
	foreach ($faqs as $i => $faq) {
		$n = $i + 1;
		$html .= '<div class="ldc-faq-item">';
		$html .= '<button type="button" class="ldc-faq-question" aria-expanded="false" aria-controls="' . $id_prefix . '-' . $n . '">' . esc_html($faq['question']) . '<span class="ldc-faq-icon">+</span></button>';
		$html .= '<div class="ldc-faq-answer" id="' . $id_prefix . '-' . $n . '" hidden><p>' . esc_html($faq['answer']) . '</p></div>';
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

add_shortcode('ldc_faq', function () {
	if (!is_singular('post')) {
		return '';
	}
	return ldc_render_faq_html(ldc_get_faqs(), 'ldc-faq-a');
});

/* =========================================================
 * Homepage FAQ (ldc_faq CPT, "Homepage" group) — same rendering
 * + accordion behaviour as the blog's [ldc_faq].
 * ========================================================= */
add_shortcode('ldc_home_faq', function () {
	return ldc_render_faq_html(ldc_get_home_faqs(), 'ldc-home-faq-a');
});

/* =========================================================
 * Sticky CTA (site-wide, shows on singular posts only)
 * ========================================================= */
add_action('wp_footer', function () {
	if (!is_singular('post')) {
		return;
	}
	echo '<div class="ldc-sticky-cta" id="ldc-sticky-cta">'
		. '<span class="ldc-sticky-cta-text">Ready to get started?</span>'
		. '<a href="' . esc_url(home_url('/#lead-form')) . '" class="ldc-cta-btn">Get Started</a>'
		. '</div>';
});

/* =========================================================
 * SEO: meta description, canonical, Open Graph, JSON-LD schema
 * (No SEO plugin installed — hand-rolled to avoid duplicate schema
 * and avoid a heavier plugin's own network calls.)
 * ========================================================= */
function ldc_meta_description() {
	if (is_singular('post')) {
		$excerpt = get_the_excerpt();
		if (!$excerpt) {
			$excerpt = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', get_the_ID())), 30);
		}
		return wp_strip_all_tags($excerpt);
	}
	if (is_front_page()) {
		return "LenDenClub is India's largest RBI-registered NBFC-P2P lending platform. Diversify your capital across thousands of verified borrowers and earn interest.";
	}
	return get_bloginfo('description');
}

// Avoid duplicate tags: Hello Elementor (parent theme) and WP core both output their
// own basic description/canonical tags; ours below is the single source of truth.
add_action('wp_head', function () {
	remove_action('wp_head', 'hello_elementor_add_description_meta_tag');
	remove_action('wp_head', 'rel_canonical');
}, 1);

add_action('wp_head', function () {
	$description = ldc_meta_description();
	echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";

	$canonical = is_singular() ? get_permalink() : (is_front_page() ? home_url('/') : get_pagenum_link());
	echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";

	echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr(is_singular() ? get_the_title() : get_bloginfo('name')) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
	if (is_singular('post') && has_post_thumbnail()) {
		echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')) . '">' . "\n";
	}

	// Article schema (only on single posts — avoids duplicating a site-wide Organization/WebSite schema).
	if (is_singular('post')) {
		$author_id = get_the_author_meta('ID');
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'headline' => get_the_title(),
			'description' => $description,
			'datePublished' => get_the_date('c'),
			'dateModified' => get_the_modified_date('c'),
			'author' => [
				'@type' => 'Organization',
				'name' => get_the_author_meta('display_name', $author_id),
			],
			'publisher' => [
				'@type' => 'Organization',
				'name' => 'LenDenClub',
			],
			'mainEntityOfPage' => [
				'@type' => 'WebPage',
				'@id' => get_permalink(),
			],
		];
		if (has_post_thumbnail()) {
			$schema['image'] = get_the_post_thumbnail_url(get_the_ID(), 'large');
		}
		echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";

		// BreadcrumbList schema (mirrors the visible [ldc_breadcrumb] output — single source, no duplication).
		$cats = get_the_category();
		$cat = $cats ? $cats[0] : null;
		$items = [
			['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
		];
		$blog_page_id = (int) get_option('page_for_posts');
		if ($blog_page_id) {
			$items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => get_permalink($blog_page_id)];
		}
		$pos = count($items) + 1;
		if ($cat) {
			$items[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $cat->name, 'item' => get_category_link($cat)];
			$pos++;
		}
		$items[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => get_the_title(), 'item' => get_permalink()];
		$breadcrumb_schema = [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $items,
		];
		echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb_schema) . '</script>' . "\n";

		// FAQPage schema — only emitted when the FAQ accordion is actually visible on the page (same ACF data).
		$faqs_for_schema = ldc_get_faqs();
		if ($faqs_for_schema) {
			$faq_items = array_map(function ($faq) {
				return [
					'@type' => 'Question',
					'name' => $faq['question'],
					'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
				];
			}, $faqs_for_schema);
			if ($faq_items) {
				$faq_schema = [
					'@context' => 'https://schema.org',
					'@type' => 'FAQPage',
					'mainEntity' => $faq_items,
				];
				echo '<script type="application/ld+json">' . wp_json_encode($faq_schema) . '</script>' . "\n";
			}
		}
	}

	// FAQPage schema for the homepage's own FAQ accordion — sourced from the same
	// ACF Options Page repeater that [ldc_home_faq] renders, so there is exactly
	// one place to edit FAQ content and no risk of visible/schema content drifting apart.
	if (is_front_page()) {
		$home_faqs = ldc_get_home_faqs();
		if ($home_faqs) {
			$home_faq_schema = [
				'@context' => 'https://schema.org',
				'@type' => 'FAQPage',
				'mainEntity' => array_map(function ($f) {
					return ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]];
				}, $home_faqs),
			];
			echo '<script type="application/ld+json">' . wp_json_encode($home_faq_schema) . '</script>' . "\n";
		}
	}
}, 5);
