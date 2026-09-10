document.addEventListener('DOMContentLoaded', function () {
    var loanProductSelector = document.getElementById('loan_product_version_id');
    if (loanProductSelector) {
        loanProductSelector.classList.remove('enhanced-select');

        if (loanProductSelector.options.length && loanProductSelector.options[0].value === '') {
            loanProductSelector.options[0].textContent = 'Select a configured loan product';
        }

        if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(loanProductSelector).hasClass('select2-hidden-accessible')) {
            window.jQuery(loanProductSelector).select2('destroy');
        }
    }

    document.querySelectorAll('[onclick*="window.print"]').forEach(function (printButton) {
        printButton.removeAttribute('onclick');
        printButton.addEventListener('click', function () {
            window.location.href = window.location.pathname.replace(/\/$/, '') + '/pdf';
        });
    });

    document.querySelectorAll('form[action$="/guarantors"], form[action$="/collaterals"]').forEach(function (securityForm) {
        if (!securityForm.action.includes('/loan-applications/')) {
            var notice = document.createElement('div');
            notice.className = 'alert alert-info mt-3 mb-0';
            notice.textContent = 'Create and verify this security from the Security section of a specific loan application.';
            securityForm.replaceWith(notice);
        }
    });

    initializeSidebarToggle();

    var userMenuButton = document.querySelector('[data-user-menu]');
    var userMenuDropdown = document.querySelector('[data-user-menu-dropdown]');

    if (userMenuButton && userMenuDropdown) {
        userMenuButton.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = userMenuDropdown.classList.toggle('show');
            userMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.user-menu')) {
                userMenuDropdown.classList.remove('show');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    initializeSelects(document);

    initializeCustomerTypeFields();
    initializeLoanQuotation();
    initializeLocationSelectors();
});

/**
 * Sidebar open/close + collapse.
 *
 * - On screens < 768px the sidebar behaves as an off-canvas panel:
 *   the toggle button slides it in/out, a dark backdrop appears behind it,
 *   and clicking the backdrop or pressing Escape closes it.
 * - On screens >= 768px the same toggle button instead collapses the
 *   sidebar down to an icon-only rail (rather than doing nothing, which
 *   was the previous behaviour). The collapsed state is remembered
 *   across page loads via localStorage.
 */
function initializeSidebarToggle() {
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    var STORAGE_KEY = 'microfinance:sidebar-collapsed';
    var MOBILE_QUERY = '(max-width: 767px)';

    if (!toggle) {
        return;
    }

    function isMobile() {
        return window.matchMedia(MOBILE_QUERY).matches;
    }

    function setExpanded(expanded) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    function openMobile() {
        document.body.classList.add('sidebar-open');
        setExpanded(true);
    }

    function closeMobile() {
        document.body.classList.remove('sidebar-open');
        setExpanded(false);
    }

    function toggleMobile() {
        if (document.body.classList.contains('sidebar-open')) {
            closeMobile();
        } else {
            openMobile();
        }
    }

    function toggleDesktopCollapse() {
        var collapsed = document.body.classList.toggle('sidebar-collapsed');
        setExpanded(!collapsed);

        try {
            window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            // localStorage may be unavailable (private browsing, etc). Non-fatal.
        }
    }

    // Restore the desktop collapsed preference on load.
    try {
        if (!isMobile() && window.localStorage.getItem(STORAGE_KEY) === '1') {
            document.body.classList.add('sidebar-collapsed');
            setExpanded(false);
        }
    } catch (error) {
        // Ignore storage errors.
    }

    toggle.addEventListener('click', function () {
        if (isMobile()) {
            toggleMobile();
        } else {
            toggleDesktopCollapse();
        }
    });

    if (backdrop) {
        backdrop.addEventListener('click', closeMobile);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            closeMobile();
        }
    });

    // If the viewport crosses the mobile breakpoint while a state is
    // active, clear it so we don't end up stuck half-open / collapsed.
    window.addEventListener('resize', debounce(function () {
        if (isMobile()) {
            document.body.classList.remove('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-open');
        }
    }, 150));
}

function debounce(fn, wait) {
    var timer;
    return function () {
        var context = this;
        var args = arguments;
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            fn.apply(context, args);
        }, wait);
    };
}

function initializeCustomerTypeFields() {
    var customerType = document.querySelector('[data-customer-type]');
    var identificationType = document.querySelector('[data-identification-type]');

    if (!customerType || !identificationType) {
        return;
    }

    function filterIdentificationTypes() {
        var selectedType = customerType.value;

        Array.prototype.forEach.call(identificationType.options, function (option) {
            var appliesTo = option.dataset.customerType;
            option.hidden = Boolean(appliesTo && appliesTo !== selectedType);
            option.disabled = option.hidden;
        });

        if (identificationType.selectedOptions[0] && identificationType.selectedOptions[0].disabled) {
            identificationType.value = '';
        }

        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(identificationType).trigger('change.select2');
        }
    }

    customerType.addEventListener('change', filterIdentificationTypes);
    filterIdentificationTypes();
}

function initializeSelects(container) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        return;
    }

    window.jQuery(container).find('select.form-select').each(function () {
        if (this.id === 'loan_product_version_id' || this.dataset.nativeSelect === 'true') {
            if (window.jQuery(this).hasClass('select2-hidden-accessible')) {
                window.jQuery(this).select2('destroy');
            }

            return;
        }

        var select = window.jQuery(this);

        if (select.hasClass('select2-hidden-accessible')) {
            select.select2('destroy');
        }

        var modal = select.closest('.modal');
        var dropdownParent = modal.length ? modal : window.jQuery(document.body);

        select.select2({
            width: '100%',
            placeholder: select.data('placeholder') || 'Select an option',
            allowClear: !this.required,
            dropdownParent: dropdownParent
        });
    });
}

function initializeLocationSelectors() {
    document.querySelectorAll('[data-location-form]').forEach(function (form) {

        var region = form.querySelector('[data-region]');
        var district = form.querySelector('[data-district]');
        var ward = form.querySelector('[data-ward]');
        var street = form.querySelector('[data-street]');

        if (!region || !district || !ward || !street) {
            console.error('Location fields missing');
            return;
        }

        var districtsUrl = form.dataset.districtsUrl;
        var wardsUrl = form.dataset.wardsUrl;
        var streetsUrl = form.dataset.streetsUrl;

        function clearSelect(select, placeholder) {
            select.innerHTML = '';

            var option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;

            select.appendChild(option);

            if (window.jQuery) {
                window.jQuery(select).val(null).trigger('change.select2');
            }
        }

        function fillSelect(select, items, placeholder) {
            select.innerHTML = '';

            var first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);

            items.forEach(function (item) {
                var option = document.createElement('option');

                option.value = item.id;
                option.textContent = item.name;

                select.appendChild(option);
            });

            if (window.jQuery) {
                window.jQuery(select).trigger('change.select2');
            }
        }

        async function requestData(url, parameter, value) {
            var requestUrl = new URL(url, window.location.origin);

            requestUrl.searchParams.set(parameter, value);

            var response = await fetch(requestUrl.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                var text = await response.text();
                console.error('SERVER ERROR:', text);
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        }

        async function loadDistricts() {
            var regionId = region.value;

            clearSelect(district, 'Select district');
            clearSelect(ward, 'Select ward');
            clearSelect(street, 'Select street or village');

            if (!regionId) {
                return;
            }

            try {
                var districts = await requestData(districtsUrl, 'region_id', regionId);

                fillSelect(
                    district,
                    districts,
                    districts.length ? 'Select district' : 'No districts found'
                );
            } catch (error) {
                console.error('DISTRICT ERROR:', error);
                clearSelect(district, 'Unable to load districts');
            }
        }

        async function loadWards() {
            var districtId = district.value;

            clearSelect(ward, 'Select ward');
            clearSelect(street, 'Select street or village');

            if (!districtId) {
                return;
            }

            try {
                var wards = await requestData(wardsUrl, 'district_id', districtId);

                fillSelect(
                    ward,
                    wards,
                    wards.length ? 'Select ward' : 'No wards found'
                );
            } catch (error) {
                console.error('WARD ERROR:', error);
                clearSelect(ward, 'Unable to load wards');
            }
        }

        async function loadStreets() {
            var wardId = ward.value;

            clearSelect(street, 'Select street or village');

            if (!wardId) {
                return;
            }

            try {
                var streets = await requestData(streetsUrl, 'ward_id', wardId);

                fillSelect(
                    street,
                    streets,
                    streets.length ? 'Select street or village' : 'No streets found'
                );
            } catch (error) {
                console.error('STREET ERROR:', error);
                clearSelect(street, 'Unable to load streets');
            }
        }

        /*
         * Use jQuery events because these fields use Select2.
         */
        if (window.jQuery) {
            window.jQuery(region).off('change.location').on('change.location', loadDistricts);
            window.jQuery(district).off('change.location').on('change.location', loadWards);
            window.jQuery(ward).off('change.location').on('change.location', loadStreets);
        } else {
            region.addEventListener('change', loadDistricts);
            district.addEventListener('change', loadWards);
            ward.addEventListener('change', loadStreets);
        }

        // If a region is already selected when the page loads, load its districts immediately.
        if (region.value) {
            loadDistricts();
        }
    });
}

function initializeLoanQuotation() {
    var form = document.querySelector('[data-loan-application-form]');

    if (!form) {
        return;
    }

    var customer = form.querySelector('[name="customer_id"]');
    var product = form.querySelector('[name="loan_product_version_id"]');
    var amount = form.querySelector('[name="requested_amount"]');
    var term = form.querySelector('[name="requested_term"]');
    var income = form.querySelector('[name="declared_monthly_income"]');
    var expenses = form.querySelector('[name="declared_monthly_expenses"]');
    var debt = form.querySelector('[name="existing_debt_payment"]');
    var frequency = form.querySelector('input[data-frequency]');
    var summary = form.querySelector('[data-loan-quote]');
    var policy = form.querySelector('[data-product-policy]');
    var timer;

    function hydrateCustomer() {
        var option = customer.selectedOptions[0];

        if (!option) {
            return;
        }

        income.value = option.dataset.income || income.value;
        expenses.value = option.dataset.expenses || expenses.value;
        debt.value = option.dataset.debt || debt.value;
        requestQuote();
    }

    function hydrateProduct() {
        var option = product.selectedOptions[0];

        if (!option) {
            return;
        }

        amount.min = option.dataset.minimumAmount || '';
        amount.max = option.dataset.maximumAmount || '';
        term.min = option.dataset.minimumTerm || '';
        term.max = option.dataset.maximumTerm || '';
        frequency.value = option.dataset.frequency || '';
        form.querySelector('.input-group-text[data-term-unit]').textContent = option.dataset.termUnit || 'units';
        form.querySelector('[data-amount-rule]').textContent = 'Allowed: ' + formatMoney(option.dataset.minimumAmount || 0) + ' to ' + formatMoney(option.dataset.maximumAmount || 0);
        form.querySelector('[data-term-rule]').textContent = 'Allowed: ' + (option.dataset.minimumTerm || 0) + ' to ' + (option.dataset.maximumTerm || 0) + ' ' + (option.dataset.termUnit || 'units').toLowerCase();
        policy.hidden = false;
        policy.querySelector('[data-policy-interest]').textContent = (option.dataset.rate || 0) + '% ' + (option.dataset.rateBasis || '') + ' · ' + (option.dataset.method || '');
        policy.querySelector('[data-policy-ratio]').textContent = (option.dataset.ratio || 0) + '% of disposable income';
        policy.querySelector('[data-policy-guarantors]').textContent = option.dataset.guarantors || '0';
        policy.querySelector('[data-policy-collateral]').textContent = option.dataset.collateral || 'Not required';
        requestQuote();
    }

    function requestQuote() {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            if (!product.value || !amount.value || !term.value || !amount.checkValidity() || !term.checkValidity()) {
                return;
            }

            var payload = new FormData();
            payload.append('loan_product_version_id', product.value);
            payload.append('requested_amount', amount.value);
            payload.append('requested_term', term.value);
            payload.append('monthly_income', income.value || 0);
            payload.append('monthly_expenses', expenses.value || 0);
            payload.append('existing_debt_payment', debt.value || 0);

            fetch(form.dataset.quoteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: payload
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to calculate quote');
                }

                return response.json();
            }).then(function (quote) {
                summary.hidden = false;
                summary.querySelector('[data-principal]').textContent = formatMoney(quote.principal);
                summary.querySelector('[data-net-disbursement]').textContent = formatMoney(quote.estimated_net_disbursement);
                summary.querySelector('[data-installment]').textContent = formatMoney(quote.installment);
                summary.querySelector('[data-periods]').textContent = quote.periods;
                summary.querySelector('[data-interest]').textContent = formatMoney(quote.total_interest);
                summary.querySelector('[data-fees]').textContent = formatMoney(quote.total_fees);
                summary.querySelector('[data-total]').textContent = formatMoney(quote.total_repayment);
                summary.querySelector('[data-customer-cost]').textContent = formatMoney(quote.total_customer_cost);
                summary.querySelector('[data-maximum-installment]').textContent = formatMoney(quote.maximum_installment);
                summary.querySelector('[data-recommended]').textContent = formatMoney(quote.recommended_amount);
                summary.querySelector('[data-affordability]').textContent = quote.affordable ? 'Within affordability policy' : 'Exceeds affordability policy';
                summary.querySelector('[data-affordability]').className = quote.affordable ? 'text-success fw-semibold' : 'text-danger fw-semibold';
                summary.querySelector('[data-quote-status]').textContent = quote.affordable ? 'Eligible' : 'Review required';
                summary.querySelector('[data-quote-status]').className = quote.affordable ? 'badge bg-success' : 'badge bg-danger';
                summary.querySelector('[data-formula]').textContent = quote.formula;
                summary.querySelector('[data-affordability-formula]').textContent = quote.affordability_formula;
                summary.querySelector('[data-fee-breakdown]').innerHTML = quote.fees.length
                    ? '<p class="mb-2"><strong>Fee breakdown</strong></p><ul>' + quote.fees.map(function (fee) { return '<li>' + escapeHtml(fee.name) + ': ' + formatMoney(fee.amount) + ' (' + escapeHtml(fee.collection_point) + ')</li>'; }).join('') + '</ul>'
                    : '<p class="text-muted">No configured product fees.</p>';
            }).catch(function () {
                summary.hidden = true;
            });
        }, 250);
    }

    function formatMoney(value) {
        return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
    }

    function escapeHtml(value) {
        var element = document.createElement('div');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
    }

    customer.addEventListener('change', hydrateCustomer);
    product.addEventListener('change', hydrateProduct);

    amount.addEventListener('input', function () {
        var maximum = Number(amount.max || 0);

        if (maximum > 0 && Number(amount.value) > maximum) {
            amount.setCustomValidity('Requested amount cannot exceed the selected product maximum of ' + formatMoney(maximum) + '.');
        } else {
            amount.setCustomValidity('');
        }
    });

    amount.addEventListener('blur', function () {
        var maximum = Number(amount.max || 0);

        if (maximum > 0 && Number(amount.value) > maximum) {
            amount.value = maximum.toFixed(2);
            amount.setCustomValidity('');
            requestQuote();
        }
    });

    term.addEventListener('input', function () {
        var maximum = Number(term.max || 0);

        if (maximum > 0 && Number(term.value) > maximum) {
            term.setCustomValidity('Requested term cannot exceed the selected product maximum of ' + maximum + '.');
        } else {
            term.setCustomValidity('');
        }
    });

    [amount, term, income, expenses, debt].forEach(function (field) {
        field.addEventListener('input', requestQuote);
    });
    hydrateCustomer();
    hydrateProduct();
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="hidden"][name="rate_basis"]').forEach(function (hiddenInput) {
        var select = document.createElement('select');
        select.className = 'form-select';
        select.name = 'rate_basis';
        select.innerHTML = '<option value="Annual">Annual rate</option><option value="Whole Term">Whole-term rate</option>';
        select.value = hiddenInput.value || 'Annual';
        hiddenInput.replaceWith(select);

        var container = select.closest('.col-md-3');
        var label = container ? container.querySelector('label') : null;
        if (label) {
            label.textContent = 'Interest rate basis';
        }
    });
});
