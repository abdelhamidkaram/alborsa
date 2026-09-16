(function () {
	'use strict';

	const config = window.alborsaPlatform || {};
	const restBase = 'string' === typeof config.restBase ? config.restBase.replace(/\/+$/, '') + '/' : '';

	if (!restBase) {
		return;
	}

	const text = Object.assign(
		{
			unavailable: 'غير متاح',
			fresh: 'محدّث من المصدر',
			delayed: 'بيانات متأخرة',
			stale: 'بيانات قديمة',
			loading: 'جارٍ التحميل…',
			requestError: 'تعذر إكمال الطلب الآن. حاول مرة أخرى.',
			invalidCalculator: 'راجع القيم المدخلة ثم أعد المحاولة.',
			chooseBrokers: 'اختر وسيطين أو ثلاثة للمقارنة.',
			maximumBrokers: 'يمكن مقارنة ثلاثة وسطاء كحد أقصى.',
			noData: 'لم تُنشر بيانات كافية بعد.',
		},
		config.strings || {}
	);

	const resultLabels = {
		pip_value: 'قيمة النقطة',
		risk_amount: 'قيمة المخاطرة',
		position_units: 'حجم المركز بالوحدات',
		gross_profit_loss: 'الربح أو الخسارة قبل الرسوم',
		net_profit_loss: 'الربح أو الخسارة بعد الرسوم',
		fees: 'إجمالي الرسوم',
		side: 'نوع المركز',
		total_contributions: 'إجمالي المساهمات',
		estimated_growth: 'النمو التقديري',
		future_value: 'القيمة المستقبلية التقديرية',
	};

	const sideLabels = {
		long: 'شراء',
		short: 'بيع',
	};

	const calculatorAssumptions = {
		'pip-value': [
			'استخدم سعر تحويل يساوي 1 عندما تكون عملة التسعير هي نفسها عملة الحساب.',
			'الحاسبة لا تجلب سعر الصرف ولا تستنتجه تلقائياً.',
		],
		'position-size': [
			'قيمة النقطة لكل وحدة يجب أن تكون محسوبة مسبقاً بعملة الحساب.',
			'التقدير لا يشمل الانزلاق أو فجوات السعر أو العمولات أو قيود الهامش.',
		],
		'profit-loss': [
			'تُدخل الرسوم كمبلغ إجمالي وتُخصم مرة واحدة.',
			'الضرائب وتكاليف التمويل والانزلاق وتحويل العملة غير مشمولة.',
		],
		'compound-growth': [
			'يفترض عائداً سنوياً اسمياً ثابتاً يُركّب شهرياً.',
			'تُضاف المساهمات في نهاية كل شهر.',
			'الضرائب والرسوم والتضخم وتغيرات السوق غير مشمولة.',
		],
	};

	const brokerRows = [
		['arabic_support', 'الدعم العربي', (item) => (item.arabic_support ? 'متاح' : 'غير موثّق')],
		['minimum_deposit', 'الحد الأدنى للإيداع', (item) => joinDeposit(item)],
		['platforms', 'المنصات', (item) => joinList(item.platforms)],
		['deposit_methods', 'طرق الإيداع', (item) => joinList(item.deposit_methods)],
		['currencies', 'عملات الحساب', (item) => joinList(item.currencies)],
		['fee_summary', 'ملخص الرسوم', (item) => item.fee_summary || 'غير منشور'],
		['licenses', 'تراخيص موثقة المصدر', (item) => String(Array.isArray(item.licenses) ? item.licenses.length : 0)],
		['user_rating', 'تقييم المستخدمين', (item) => ratingLabel(item.user_rating)],
		['editorial_rating', 'التقييم التحريري', () => 'ينتظر اعتماد المنهجية'],
	];

	async function fetchJson(path, options) {
		const response = await fetch(restBase + path.replace(/^\/+/, ''), Object.assign({ credentials: 'same-origin' }, options));
		let payload = null;

		try {
			payload = await response.json();
		} catch (error) {
			payload = null;
		}

		if (!response.ok) {
			const requestError = new Error(text.requestError);
			requestError.status = response.status;
			throw requestError;
		}

		return payload;
	}

	function queryRole(container, role) {
		return container.querySelector('[data-quote-' + role + ']');
	}

	function quoteStatus(quote) {
		if ('fresh' === quote.status) {
			return text.fresh;
		}

		if ('delayed' === quote.status) {
			const minutes = Math.max(1, Math.ceil(Number(quote.delay_seconds || 0) / 60));
			return text.delayed + ' (' + minutes.toLocaleString('ar-SA') + ' د)';
		}

		return 'stale' === quote.status ? text.stale : text.unavailable;
	}

	function formatDecimal(value, maximumFractionDigits) {
		if (null === value || '' === value || 'undefined' === typeof value) {
			return '';
		}

		const decimal = String(value).trim();

		if (!/^-?\d+(?:\.\d+)?$/.test(decimal)) {
			return String(value);
		}

		const negative = decimal.startsWith('-');
		const parts = (negative ? decimal.slice(1) : decimal).split('.');
		const integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '٬');
		const fraction = (parts[1] || '').slice(0, maximumFractionDigits).replace(/0+$/, '');
		const localized = (negative ? '-' : '') + integer + (fraction ? '٫' + fraction : '');

		return localized.replace(/[0-9]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'[Number(digit)]);
	}

	function renderQuote(container, quote) {
		const price = queryRole(container, 'price');
		const change = queryRole(container, 'change');
		const status = queryRole(container, 'status');
		const source = queryRole(container, 'source');
		const asOf = queryRole(container, 'as-of');
		const currency = quote.currency ? ' ' + quote.currency : '';

		container.dataset.quoteState = quote.status || 'unavailable';
		container.setAttribute('aria-busy', 'false');

		if (price) {
			price.textContent = quote.price ? formatDecimal(quote.price, 6) + currency : text.unavailable;
		}

		if (change) {
			change.classList.remove('quote-value--up', 'quote-value--down');
			change.textContent = null === quote.percent_change || 'undefined' === typeof quote.percent_change
				? '—'
				: formatDecimal(quote.percent_change, 2) + '%';

			if (Number(quote.percent_change) > 0) {
				change.classList.add('quote-value--up');
			} else if (Number(quote.percent_change) < 0) {
				change.classList.add('quote-value--down');
			}
		}

		if (status) {
			status.textContent = quoteStatus(quote);
		}

		if (asOf) {
			asOf.textContent = quote.as_of ? formatDate(quote.as_of) : '—';
		}

		if (source) {
			source.replaceChildren();
			const sourceLabel = quote.source && quote.source.label ? quote.source.label : '';
			const sourceUrl = quote.source && quote.source.url ? safeUrl(quote.source.url) : '';

			if (sourceLabel && sourceUrl) {
				const link = document.createElement('a');
				link.href = sourceUrl;
				link.rel = 'noopener noreferrer';
				link.textContent = sourceLabel;
				source.append(link);
			} else {
				source.textContent = sourceLabel || '—';
			}
		}
	}

	function markQuoteUnavailable(container) {
		container.dataset.quoteState = 'unavailable';
		container.setAttribute('aria-busy', 'false');
		const status = queryRole(container, 'status');

		if (status) {
			status.textContent = text.unavailable;
		}
	}

	async function initializeQuotes() {
		const grouped = new Map();

		document.querySelectorAll('[data-alborsa-quote]').forEach((container) => {
			const assetId = container.getAttribute('data-alborsa-quote');

			if (!/^\d+$/.test(assetId || '')) {
				return;
			}

			container.setAttribute('aria-busy', 'true');
			grouped.set(assetId, (grouped.get(assetId) || []).concat(container));
		});

		const jobs = Array.from(grouped.entries());
		let nextJob = 0;
		const worker = async () => {
			while (nextJob < jobs.length) {
				const job = jobs[nextJob++];
				const assetId = job[0];
				const containers = job[1];

				try {
					const quote = await fetchJson('assets/' + assetId + '/quote');
					containers.forEach((container) => renderQuote(container, quote));
				} catch (error) {
					containers.forEach(markQuoteUnavailable);
				}
			}
		};

		await Promise.all(Array.from({ length: Math.min(5, jobs.length) }, worker));
	}

	function formatDate(value) {
		const date = new Date(value);

		return Number.isNaN(date.getTime())
			? String(value)
			: new Intl.DateTimeFormat('ar-SA', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
	}

	function safeUrl(value) {
		if ('string' !== typeof value || '' === value.trim()) {
			return '';
		}

		try {
			const url = new URL(value, window.location.origin);
			return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
		} catch (error) {
			return '';
		}
	}

	function resultValue(key, value) {
		if ('side' === key) {
			return sideLabels[value] || String(value);
		}

		return formatDecimal(value, 6);
	}

	function renderCalculatorResult(output, payload) {
		output.replaceChildren();
		const heading = document.createElement('h3');
		heading.textContent = 'النتيجة التقديرية';
		output.append(heading);

		const values = document.createElement('dl');
		Object.entries(payload.result || {}).forEach(([key, value]) => {
			const row = document.createElement('div');
			const term = document.createElement('dt');
			const description = document.createElement('dd');
			term.textContent = resultLabels[key] || key;
			description.textContent = resultValue(key, value);
			row.append(term, description);
			values.append(row);
		});
		output.append(values);

		const assumptionsList = calculatorAssumptions[payload.tool] || payload.assumptions;
		if (Array.isArray(assumptionsList) && assumptionsList.length) {
			const assumptionsHeading = document.createElement('h4');
			assumptionsHeading.textContent = 'الافتراضات المستخدمة';
			const assumptions = document.createElement('ul');
			assumptionsList.forEach((assumption) => {
				const item = document.createElement('li');
				item.textContent = String(assumption);
				assumptions.append(item);
			});
			output.append(assumptionsHeading, assumptions);
		}

		const disclaimer = document.createElement('p');
		disclaimer.className = 'calculator-result__disclaimer';
		disclaimer.textContent = 'هذه نتيجة تعليمية تقديرية وليست نصيحة مالية أو استثمارية أو ضريبية أو شرعية.';
		output.append(disclaimer);
	}

	function initializeCalculators() {
		document.querySelectorAll('[data-alborsa-calculator]').forEach((form) => {
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const slug = form.getAttribute('data-alborsa-calculator');
				const output = form.parentElement ? form.parentElement.querySelector('[data-calculator-result]') : null;
				const submit = form.querySelector('button[type="submit"]');

				if (!slug || !(output instanceof HTMLElement)) {
					return;
				}

				const values = {};
				new FormData(form).forEach((value, key) => {
					values[key] = String(value).trim();
				});

				output.textContent = text.loading;
				output.setAttribute('aria-busy', 'true');
				if (submit instanceof HTMLButtonElement) {
					submit.disabled = true;
				}

				try {
					const payload = await fetchJson('tools/' + encodeURIComponent(slug) + '/calculate', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(values),
					});
					renderCalculatorResult(output, payload);
				} catch (error) {
					output.textContent = error.status && error.status < 500 ? text.invalidCalculator : text.requestError;
				} finally {
					output.setAttribute('aria-busy', 'false');
					if (submit instanceof HTMLButtonElement) {
						submit.disabled = false;
					}
				}
			});
		});
	}

	function joinList(value) {
		return Array.isArray(value) && value.length ? value.join('، ') : 'غير منشور';
	}

	function joinDeposit(item) {
		if (!item.minimum_deposit) {
			return 'غير منشور';
		}

		return item.minimum_deposit + (item.deposit_currency ? ' ' + item.deposit_currency : '');
	}

	function ratingLabel(rating) {
		if (!rating || !rating.count || !rating.average) {
			return 'لا توجد تقييمات منشورة';
		}

		return formatDecimal(rating.average, 2) + ' / 5 (' + Number(rating.count).toLocaleString('ar-SA') + ')';
	}

	function createCell(tag, content) {
		const cell = document.createElement(tag);
		cell.textContent = content;
		return cell;
	}

	function renderBrokerComparison(output, items) {
		output.replaceChildren();
		const tableWrapper = document.createElement('div');
		tableWrapper.className = 'broker-comparison__table-wrap';
		const table = document.createElement('table');
		const head = document.createElement('thead');
		const headRow = document.createElement('tr');
		headRow.append(createCell('th', 'عنصر المقارنة'));

		items.forEach((item) => {
			const cell = document.createElement('th');
			const link = document.createElement('a');
			link.href = safeUrl(item.url) || '#';
			link.textContent = item.name || 'وسيط';
			cell.append(link);
			headRow.append(cell);
		});
		head.append(headRow);
		table.append(head);

		const body = document.createElement('tbody');
		brokerRows.forEach((definition) => {
			const row = document.createElement('tr');
			row.append(createCell('th', definition[1]));
			items.forEach((item) => row.append(createCell('td', definition[2](item))));
			body.append(row);
		});
		table.append(body);
		tableWrapper.append(table);
		output.append(tableWrapper);
	}

	function initializeBrokerComparison() {
		document.querySelectorAll('[data-alborsa-broker-comparison]').forEach((form) => {
			const output = form.querySelector('[data-broker-comparison-result]');
			const message = form.querySelector('[data-broker-comparison-message]');
			const inputs = Array.from(form.querySelectorAll('input[name="broker_ids[]"]'));

			inputs.forEach((input) => {
				input.addEventListener('change', () => {
					const selected = inputs.filter((candidate) => candidate.checked);
					if (selected.length > 3) {
						input.checked = false;
						if (message) {
							message.textContent = text.maximumBrokers;
						}
					} else if (message) {
						message.textContent = '';
					}
				});
			});

			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const selected = inputs.filter((input) => input.checked).map((input) => input.value);

				if (selected.length < 2 || selected.length > 3) {
					if (message) {
						message.textContent = text.chooseBrokers;
					}
					return;
				}

				if (!(output instanceof HTMLElement)) {
					return;
				}

				output.textContent = text.loading;
				output.setAttribute('aria-busy', 'true');
				try {
					const payload = await fetchJson('brokers/compare?ids=' + selected.join(','));
					renderBrokerComparison(output, Array.isArray(payload.items) ? payload.items : []);
					const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
					output.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
				} catch (error) {
					output.textContent = text.requestError;
				} finally {
					output.setAttribute('aria-busy', 'false');
				}
			});
		});
	}

	function appendDefinitionList(container, entries) {
		const list = document.createElement('dl');
		entries.forEach(([label, value]) => {
			if (!value || 'غير منشور' === value) {
				return;
			}
			const row = document.createElement('div');
			row.append(createCell('dt', label), createCell('dd', value));
			list.append(row);
		});

		if (list.children.length) {
			container.append(list);
		}
	}

	function appendTextList(container, headingText, values) {
		if (!Array.isArray(values) || !values.length) {
			return;
		}
		const section = document.createElement('section');
		section.append(createCell('h2', headingText));
		const list = document.createElement('ul');
		values.forEach((value) => list.append(createCell('li', String(value))));
		section.append(list);
		container.append(section);
	}

	function renderBrokerDetail(container, broker) {
		container.replaceChildren();
		const facts = document.createElement('section');
		facts.append(createCell('h2', 'بيانات الوسيط المنشورة'));
		appendDefinitionList(facts, [
			['الاسم القانوني', broker.legal_name],
			['الدعم العربي', broker.arabic_support ? 'متاح' : 'غير موثّق'],
			['الحد الأدنى للإيداع', joinDeposit(broker)],
			['الخدمات', joinList(broker.services)],
			['المنصات', joinList(broker.platforms)],
			['أنواع الحساب', joinList(broker.account_types)],
			['طرق الإيداع', joinList(broker.deposit_methods)],
			['ملخص الرسوم', broker.fee_summary],
			['تقييم المستخدمين', ratingLabel(broker.user_rating)],
		]);
		const officialWebsite = safeUrl(broker.official_website || '');
		if (officialWebsite) {
			const website = document.createElement('p');
			website.className = 'broker-detail__official';
			const link = document.createElement('a');
			link.href = officialWebsite;
			link.rel = 'noopener noreferrer';
			link.textContent = 'زيارة الموقع الرسمي للوسيط';
			website.append(link);
			facts.append(website);
		}
		container.append(facts);

		if (Array.isArray(broker.licenses) && broker.licenses.length) {
			const licenses = document.createElement('section');
			licenses.append(createCell('h2', 'التراخيص الموثقة بالمصدر'));
			const list = document.createElement('ul');
			broker.licenses.forEach((license) => {
				const item = document.createElement('li');
				const label = [license.regulator, license.license_number, license.country].filter(Boolean).join(' — ');
				const url = safeUrl(license.source_url);
				if (url) {
					const link = document.createElement('a');
					link.href = url;
					link.rel = 'noopener noreferrer';
					link.textContent = label;
					item.append(link);
				} else {
					item.textContent = label;
				}
				if (license.verified_at) {
					item.append(document.createTextNode(' — تحقق: ' + license.verified_at));
				}
				list.append(item);
			});
			licenses.append(list);
			container.append(licenses);
		}

		const strengths = document.createElement('div');
		strengths.className = 'broker-detail__pros-cons';
		appendTextList(strengths, 'نقاط القوة المنشورة', broker.pros);
		appendTextList(strengths, 'ملاحظات يجب الانتباه لها', broker.cons);
		if (strengths.children.length) {
			container.append(strengths);
		}

		if (Array.isArray(broker.reviews) && broker.reviews.length) {
			const reviews = document.createElement('section');
			reviews.className = 'broker-user-reviews';
			reviews.append(createCell('h2', 'آراء المستخدمين المنشورة'));
			const explanation = document.createElement('p');
			explanation.textContent = 'هذه الآراء مقبولة للنشر، لكنها لا تحمل شارة «موثقة» قبل اعتماد سياسة التحقق.';
			reviews.append(explanation);
			const list = document.createElement('div');
			list.className = 'broker-user-reviews__list';
			broker.reviews.forEach((review) => {
				const entry = document.createElement('article');
				const heading = document.createElement('h3');
				heading.textContent = (review.author || 'مستخدم') + ' — ' + formatDecimal(review.rating, 0) + ' / ٥';
				entry.append(heading);
				if (review.created_at) {
					const date = document.createElement('time');
					date.dateTime = review.created_at;
					date.textContent = formatDate(review.created_at);
					entry.append(date);
				}
				entry.append(createCell('p', review.content || ''));
				list.append(entry);
			});
			reviews.append(list);
			container.append(reviews);
		}

		const notice = document.createElement('p');
		notice.className = 'broker-methodology-notice';
		notice.textContent = 'التقييم التحريري وروابط فتح الحساب بالعمولة غير متاحة حتى اعتماد المنهجية وسياسة الإفصاح.';
		container.append(notice);
	}

	async function initializeBrokerDetails() {
		const containers = Array.from(document.querySelectorAll('[data-alborsa-broker-detail]'));
		await Promise.all(containers.map(async (container) => {
			const brokerId = container.getAttribute('data-alborsa-broker-detail');
			if (!/^\d+$/.test(brokerId || '')) {
				return;
			}
			container.setAttribute('aria-busy', 'true');
			try {
				const broker = await fetchJson('brokers/' + brokerId);
				renderBrokerDetail(container, broker);
			} catch (error) {
				container.textContent = text.noData;
			} finally {
				container.setAttribute('aria-busy', 'false');
			}
		}));
	}

	initializeQuotes();
	initializeCalculators();
	initializeBrokerComparison();
	initializeBrokerDetails();
}());
