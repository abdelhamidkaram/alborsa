<?php
/**
 * Financial tools landing page.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$calculators = array(
	array(
		'slug'        => 'pip-value',
		'name'        => __( 'حاسبة قيمة النقطة', 'alborsa' ),
		'topic'       => __( 'الفوركس', 'alborsa' ),
		'description' => __( 'احسب قيمة حركة النقطة في عملة الحساب من مدخلات صريحة، من دون جلب سعر صرف تلقائي.', 'alborsa' ),
		'fields'      => array(
			array(
				'name'        => 'position_units',
				'label'       => __( 'حجم المركز بالوحدات', 'alborsa' ),
				'placeholder' => '100000',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
			array(
				'name'        => 'pip_size',
				'label'       => __( 'حجم النقطة', 'alborsa' ),
				'placeholder' => '0.0001',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
			array(
				'name'        => 'quote_to_account_rate',
				'label'       => __( 'سعر تحويل عملة التسعير إلى عملة الحساب', 'alborsa' ),
				'placeholder' => '1',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
		),
	),
	array(
		'slug'        => 'position-size',
		'name'        => __( 'حاسبة حجم المركز', 'alborsa' ),
		'topic'       => __( 'إدارة المخاطر', 'alborsa' ),
		'description' => __( 'حوّل نسبة المخاطرة ومسافة وقف الخسارة إلى حجم مركز تقديري بالوحدات.', 'alborsa' ),
		'fields'      => array(
			array(
				'name'        => 'account_balance',
				'label'       => __( 'رصيد الحساب', 'alborsa' ),
				'placeholder' => '10000',
				'min'         => '0.01',
				'step'        => 'any',
			),
			array(
				'name'        => 'risk_percent',
				'label'       => __( 'نسبة المخاطرة %', 'alborsa' ),
				'placeholder' => '1',
				'min'         => '0.0001',
				'step'        => 'any',
			),
			array(
				'name'        => 'stop_loss_pips',
				'label'       => __( 'وقف الخسارة بالنقاط', 'alborsa' ),
				'placeholder' => '50',
				'min'         => '0.0001',
				'step'        => 'any',
			),
			array(
				'name'        => 'pip_value_per_unit',
				'label'       => __( 'قيمة النقطة لكل وحدة بعملة الحساب', 'alborsa' ),
				'placeholder' => '0.0001',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
		),
	),
	array(
		'slug'        => 'profit-loss',
		'name'        => __( 'حاسبة الربح والخسارة', 'alborsa' ),
		'topic'       => __( 'التداول', 'alborsa' ),
		'description' => __( 'قدّر نتيجة مركز شراء أو بيع بعد خصم إجمالي الرسوم المدخل.', 'alborsa' ),
		'fields'      => array(
			array(
				'name'        => 'entry_price',
				'label'       => __( 'سعر الدخول', 'alborsa' ),
				'placeholder' => '100',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
			array(
				'name'        => 'exit_price',
				'label'       => __( 'سعر الخروج', 'alborsa' ),
				'placeholder' => '110',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
			array(
				'name'        => 'quantity',
				'label'       => __( 'الكمية أو عدد الوحدات', 'alborsa' ),
				'placeholder' => '10',
				'min'         => '0.00000001',
				'step'        => 'any',
			),
			array(
				'name'        => 'fees',
				'label'       => __( 'إجمالي الرسوم', 'alborsa' ),
				'placeholder' => '0',
				'min'         => '0',
				'step'        => 'any',
			),
		),
		'side'        => true,
	),
	array(
		'slug'        => 'compound-growth',
		'name'        => __( 'حاسبة نمو الاستثمار', 'alborsa' ),
		'topic'       => __( 'التخطيط', 'alborsa' ),
		'description' => __( 'قدّر القيمة المستقبلية مع مساهمة شهرية وعائد سنوي اسمي مركب شهرياً.', 'alborsa' ),
		'fields'      => array(
			array(
				'name'        => 'initial_amount',
				'label'       => __( 'المبلغ الأولي', 'alborsa' ),
				'placeholder' => '10000',
				'min'         => '0',
				'step'        => 'any',
			),
			array(
				'name'        => 'monthly_contribution',
				'label'       => __( 'المساهمة الشهرية', 'alborsa' ),
				'placeholder' => '500',
				'min'         => '0',
				'step'        => 'any',
			),
			array(
				'name'        => 'annual_rate_percent',
				'label'       => __( 'العائد السنوي الاسمي %', 'alborsa' ),
				'placeholder' => '7',
				'min'         => '-99.99',
				'step'        => 'any',
			),
			array(
				'name'        => 'years',
				'label'       => __( 'المدة بالسنوات', 'alborsa' ),
				'placeholder' => '10',
				'min'         => '1',
				'step'        => '1',
				'max'         => '100',
			),
		),
	),
);

$pending_tools = array(
	array(
		'name'  => __( 'محوّل العملات', 'alborsa' ),
		'state' => __( 'ينتظر اعتماد مزود الأسعار D-008', 'alborsa' ),
	),
	array(
		'name'  => __( 'حاسبة الزكاة', 'alborsa' ),
		'state' => __( 'تنتظر اعتماد المنهج الشرعي D-006', 'alborsa' ),
	),
	array(
		'name'  => __( 'فلتر أسهم تداول', 'alborsa' ),
		'state' => __( 'ينتظر بيانات أساسيات مرخصة', 'alborsa' ),
	),
	array(
		'name'  => __( 'متتبع الاكتتابات', 'alborsa' ),
		'state' => __( 'ضمن المرحلة التالية P1', 'alborsa' ),
	),
	array(
		'name'  => __( 'تنبيهات الأسعار', 'alborsa' ),
		'state' => __( 'ضمن مرحلة حسابات المستخدمين P2', 'alborsa' ),
	),
);

get_header();
?>
<section class="section-hero section-hero--tools">
	<div class="site-container section-hero__inner section-hero__inner--split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'قرارات تبدأ من مدخلات واضحة', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'الأدوات والحاسبات', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'أربع حاسبات عربية تعمل بخدمات اختبارية داخل الإضافة، وتعرض المعادلة والافتراضات من دون حفظ مدخلاتك.', 'alborsa' ); ?></p>
		</div>
		<div class="hero-state hero-state--available">
			<span class="status-dot status-dot--available" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'الحاسبات الأساسية متاحة', 'alborsa' ); ?></strong>
			<p><?php esc_html_e( 'النتائج تقديرية وتعليمية، ولا تحل محل المشورة المتخصصة.', 'alborsa' ); ?></p>
		</div>
	</div>
</section>

<div class="site-container section-content section-stack">
	<section aria-labelledby="calculators-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'احسب الآن', 'alborsa' ); ?></p>
				<h2 id="calculators-title"><?php esc_html_e( 'حاسبات تعمل من دون تخزين المدخلات', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'أدخل الأرقام باستخدام النقطة للفاصل العشري. كل حاسبة ترسل المدخلات إلى خدمة حساب عديمة الحالة داخل الإضافة.', 'alborsa' ); ?></p>
			</div>
		</header>

		<div class="calculator-grid">
			<?php foreach ( $calculators as $calculator ) : ?>
				<article class="calculator-card">
					<header>
						<p class="calculator-card__topic"><?php echo esc_html( $calculator['topic'] ); ?></p>
						<h3><?php echo esc_html( $calculator['name'] ); ?></h3>
						<p><?php echo esc_html( $calculator['description'] ); ?></p>
					</header>
					<form data-alborsa-calculator="<?php echo esc_attr( $calculator['slug'] ); ?>">
						<div class="calculator-fields">
							<?php foreach ( $calculator['fields'] as $field ) : ?>
								<label>
									<span><?php echo esc_html( $field['label'] ); ?></span>
									<input
										type="number"
										name="<?php echo esc_attr( $field['name'] ); ?>"
										min="<?php echo esc_attr( $field['min'] ); ?>"
										<?php
										if ( isset( $field['max'] ) ) :
											?>
											max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
										step="<?php echo esc_attr( $field['step'] ); ?>"
										placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
										inputmode="decimal"
										required
									>
								</label>
							<?php endforeach; ?>
							<?php if ( ! empty( $calculator['side'] ) ) : ?>
								<label>
									<span><?php esc_html_e( 'نوع المركز', 'alborsa' ); ?></span>
									<select name="side" required>
										<option value="long"><?php esc_html_e( 'شراء', 'alborsa' ); ?></option>
										<option value="short"><?php esc_html_e( 'بيع', 'alborsa' ); ?></option>
									</select>
								</label>
							<?php endif; ?>
						</div>
						<button type="submit"><?php esc_html_e( 'احسب النتيجة', 'alborsa' ); ?></button>
					</form>
					<div class="calculator-result" data-calculator-result aria-live="polite">
						<?php esc_html_e( 'ستظهر النتيجة والافتراضات هنا.', 'alborsa' ); ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section aria-labelledby="pending-tools-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'حدود الإصدار الحالي', 'alborsa' ); ?></p>
				<h2 id="pending-tools-title"><?php esc_html_e( 'أدوات لن تُفعّل قبل اكتمال متطلباتها', 'alborsa' ); ?></h2>
			</div>
		</header>
		<div class="tool-grid">
			<?php foreach ( $pending_tools as $tool ) : ?>
				<article class="tool-card tool-card--pending">
					<h3><?php echo esc_html( $tool['name'] ); ?></h3>
					<p><?php echo esc_html( $tool['state'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="principles-panel" aria-labelledby="tool-principles-title">
		<div><p class="eyebrow"><?php esc_html_e( 'معيار التنفيذ', 'alborsa' ); ?></p><h2 id="tool-principles-title"><?php esc_html_e( 'النتيجة وحدها لا تكفي', 'alborsa' ); ?></h2></div>
		<ol>
			<li><span>01</span><strong><?php esc_html_e( 'مدخلات محددة', 'alborsa' ); ?></strong><p><?php esc_html_e( 'وحدات العملة والنسب والفترات ظاهرة ولا تُستنتج بصمت.', 'alborsa' ); ?></p></li>
			<li><span>02</span><strong><?php esc_html_e( 'افتراضات مشروحة', 'alborsa' ); ?></strong><p><?php esc_html_e( 'التقريب والصيغة والحدود جزء من نتيجة الأداة.', 'alborsa' ); ?></p></li>
			<li><span>03</span><strong><?php esc_html_e( 'خصوصية افتراضية', 'alborsa' ); ?></strong><p><?php esc_html_e( 'لا تُحفظ مدخلات الزائر ولا تُرسل إلى مزود خارجي.', 'alborsa' ); ?></p></li>
		</ol>
	</section>
</div>
<?php
get_footer();
