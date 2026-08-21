<?php
defined('ABSPATH') || exit;

function ldc_footer_column($theme_location, $title, $fallback_links) {
	echo '<div class="ldc-footer-col"><h4>' . esc_html($title) . '</h4>';
	if (has_nav_menu($theme_location)) {
		wp_nav_menu([
			'theme_location' => $theme_location,
			'container' => false,
			'menu_class' => 'ldc-footer-menu',
			'fallback_cb' => false,
		]);
	} else {
		foreach ($fallback_links as $label) {
			echo '<p><a href="#">' . esc_html($label) . '</a></p>';
		}
	}
	echo '</div>';
}
?>
<footer class="ldc-footer">
	<div class="ldc-footer-inner">
		<div class="ldc-footer-cols">
			<div class="ldc-footer-brand">
				<a href="<?php echo esc_url(home_url('/')); ?>" class="ldc-logo ldc-logo--footer">
					<span class="ldc-logo-mark ldc-logo-mark--footer"><i class="fas fa-bolt" aria-hidden="true"></i></span>
					<span class="ldc-logo-text ldc-logo-text--footer">LenDenClub</span>
				</a>
				<div class="ldc-footer-social">
					<a href="#" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
					<a href="#" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
					<a href="#" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
					<a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
				</div>
			</div>

			<?php
			ldc_footer_column('footer-about-menu', 'About', ['Peer to Peer Lending', 'About Us', 'Careers', 'Business Model']);
			ldc_footer_column('footer-lending-menu', 'Lending', ['Portfolio Performance', 'Credit Assessment', 'Who Can Lend']);
			ldc_footer_column('footer-resources-menu', 'Resources', ['Blogs', 'Contact Us', 'Grievance Redressal', 'Fair Practice Code']);
			ldc_footer_column('footer-legal-menu', 'Legal', ['Privacy Policy', 'Terms of Service']);
			?>
		</div>

		<?php if (is_active_sidebar('footer-legal-text')) : ?>
			<?php dynamic_sidebar('footer-legal-text'); ?>
		<?php else : ?>
			<p class="ldc-footer-legal">LenDenClub is a peer-to-peer (P2P) lending marketplace operated by a company registered with the Reserve Bank of India as a Non-Banking Financial Company &ndash; Peer to Peer Lending Platform (NBFC-P2P), under the RBI Master Directions, 2017 (updated August 2024). P2P lending is subject to risk; returns depend on borrower repayment and are not guaranteed or insured. <em>[Insert verified RBI Certificate of Registration No. and CIN here.]</em></p>
			<p class="ldc-footer-legal">&copy; <?php echo esc_html(date('Y')); ?> LenDenClub. All rights reserved.</p>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
