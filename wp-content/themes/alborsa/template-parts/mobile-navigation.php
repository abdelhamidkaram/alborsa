<?php
/**
 * Primary destinations fixed to the bottom edge on mobile screens.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\navigation_items;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$navigation   = navigation_items();
$account_url  = is_user_logged_in() ? get_edit_profile_url( get_current_user_id() ) : wp_login_url();
$destinations = array(
	'home'    => array(
		'label'  => $navigation['home']['label'],
		'url'    => $navigation['home']['url'],
		'active' => is_front_page(),
	),
	'markets' => array(
		'label'  => $navigation['markets']['label'],
		'url'    => $navigation['markets']['url'],
		'active' => is_page( 'aswaq' ) || is_page_template( 'page-aswaq.php' ) || is_singular( 'alb_asset' ),
	),
	'brokers' => array(
		'label'  => $navigation['brokers']['label'],
		'url'    => $navigation['brokers']['url'],
		'active' => is_page( 'brokers' ) || is_page_template( 'page-brokers.php' ) || is_post_type_archive( 'alb_broker' ) || is_singular( 'alb_broker' ),
	),
	'tools'   => array(
		'label'  => $navigation['tools']['label'],
		'url'    => $navigation['tools']['url'],
		'active' => is_page( 'adawat' ) || is_page_template( 'page-adawat.php' ),
	),
	'account' => array(
		'label'  => __( 'الحساب', 'alborsa' ),
		'url'    => $account_url,
		'active' => false,
	),
);
?>
<nav class="mobile-navigation" aria-label="<?php esc_attr_e( 'التنقل الرئيسي على الهاتف', 'alborsa' ); ?>">
	<ul class="mobile-navigation__list">
		<?php foreach ( $destinations as $destination_key => $destination ) : ?>
			<li>
				<a class="mobile-navigation__link" href="<?php echo esc_url( $destination['url'] ); ?>" aria-current="<?php echo esc_attr( $destination['active'] ? 'page' : 'false' ); ?>">
					<svg class="mobile-navigation__icon" aria-hidden="true" width="21" height="21" viewBox="0 0 24 24" fill="none">
						<?php if ( 'home' === $destination_key ) : ?>
							<path d="m3.5 10.25 8.5-7 8.5 7v9.25a1.25 1.25 0 0 1-1.25 1.25h-4.5V14h-5.5v6.75h-4.5A1.25 1.25 0 0 1 3.5 19.5v-9.25Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
						<?php elseif ( 'markets' === $destination_key ) : ?>
							<path d="M3.75 19.75h16.5M5.25 16.25l4.25-4.5 3.25 2.25 5.75-7.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M15.5 6.75h3v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
						<?php elseif ( 'brokers' === $destination_key ) : ?>
							<path d="M12 3.25 19 6v5.1c0 4.25-2.75 7.75-7 9.65-4.25-1.9-7-5.4-7-9.65V6l7-2.75Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
							<path d="m9 11.75 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
						<?php elseif ( 'tools' === $destination_key ) : ?>
							<rect x="6" y="2.75" width="12" height="18.5" rx="2" stroke="currentColor" stroke-width="1.7" />
							<path d="M8.75 6.25h6.5M9 10.25h.01M12 10.25h.01M15 10.25h.01M9 13.5h.01M12 13.5h.01M15 13.5h.01M9 16.75h.01M12 16.75h.01M15 16.75h.01" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
						<?php else : ?>
							<circle cx="12" cy="7.5" r="3.75" stroke="currentColor" stroke-width="1.7" />
							<path d="M4.75 20.25c.4-4.15 3.05-6.5 7.25-6.5s6.85 2.35 7.25 6.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
						<?php endif; ?>
					</svg>
					<span><?php echo esc_html( $destination['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
