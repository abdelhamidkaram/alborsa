<?php

/**
 * Selected static page presentation.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$static_page = get_post();

if (! $static_page instanceof WP_Post) {
	return;
}

$static_page_model = Alborsa\Theme\StaticPages\page_model($static_page);
?>
<article <?php post_class('static-page'); ?>>
	<header class="static-page__header">
		<div class="site-container static-page__header-inner">
			<nav class="static-page__breadcrumbs" aria-label="<?php esc_attr_e('مسار التنقل', 'alborsa'); ?>">
				<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('الرئيسية', 'alborsa'); ?></a>
				<span aria-hidden="true">‹</span>
				<span aria-current="page"><?php echo esc_html(get_the_title($static_page)); ?></span>
			</nav>
			<h1><?php echo esc_html(get_the_title($static_page)); ?></h1>
			<p class="static-page__updated">
				<?php
				printf(
					/* translators: %s: page last-modified date. */
					esc_html__('آخر تحديث: %s', 'alborsa'),
					esc_html(get_the_modified_date('j F Y', $static_page))
				);
				?>
			</p>
		</div>
	</header>

	<div class="site-container static-page__layout">

		<aside class="static-page__toc static-page__rail" aria-labelledby="static-page-toc-heading">
			<h2 id="static-page-toc-heading"><?php esc_html_e('في هذه الصفحة', 'alborsa'); ?></h2>
			<?php if (array() !== $static_page_model['headings']) : ?>
				<nav aria-label="<?php esc_attr_e('فهرس محتوى الصفحة', 'alborsa'); ?>">
					<ol class="static-page__toc-list" data-static-page-toc>
						<?php foreach ($static_page_model['headings'] as $heading) : ?>
							<li><a href="#<?php echo esc_attr($heading['id']); ?>"><?php echo esc_html($heading['label']); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php else : ?>
				<p class="static-page__toc-empty"><?php esc_html_e('لا توجد عناوين فرعية في هذه الصفحة.', 'alborsa'); ?></p>
			<?php endif; ?>
		</aside>
		<div class="static-page__content entry-content">
			<?php echo wp_kses_post($static_page_model['content']); ?>
		</div>
		<aside class="static-page__pages static-page__rail" aria-labelledby="static-pages-heading">
			<div class="static-page__pages-panel">
				<h2 id="static-pages-heading"><?php esc_html_e('صفحات البورصة', 'alborsa'); ?></h2>
				<nav aria-label="<?php esc_attr_e('الصفحات الثابتة', 'alborsa'); ?>">
					<ul class="static-page__page-list">
						<?php foreach ($static_page_model['pages'] as $page_item) : ?>
							<li>
								<a
									href="<?php echo esc_url($page_item['url']); ?>"
									<?php if ($page_item['id'] === $static_page->ID) : ?>
									aria-current="page"
									<?php endif; ?>>
									<?php echo esc_html($page_item['label']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</div>
		</aside>

	</div>
</article>