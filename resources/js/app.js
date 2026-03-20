import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';
import {
    compareDailyBatchSummaryWithManualRecap,
    entryHasMeaningfulInput,
    isBatchEntryReady,
    normalizeManualRecap,
    parseOptionalCurrencyInputToMinorUnits,
    parseOptionalIntegerInput,
    summarizeDailyBatchEntries,
} from './transactions/daily-batch-reconciliation';

window.Alpine = Alpine;

const initDatePickers = () => {
    const dateInputs = document.querySelectorAll('[data-flatpickr="date"], input[type="date"]');

    dateInputs.forEach((input) => {
        if (input.dataset.fpInitialized === 'true') {
            return;
        }

        if (input.type === 'date') {
            input.type = 'text';
            input.autocomplete = 'off';
        }

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
        });

        input.dataset.fpInitialized = 'true';
    });
};

document.addEventListener('DOMContentLoaded', initDatePickers);

const currencyFormatter = new Intl.NumberFormat('id-ID');
let transactionFormRowCounter = 0;
const DECIMAL_MONEY_PATTERN = /^-?\d+(?:\.\d{1,2})?$/;

const createTransactionFormKey = (prefix = 'row') => {
    transactionFormRowCounter += 1;

    return `${prefix}-${Date.now()}-${transactionFormRowCounter}`;
};

const toPositiveInteger = (value, fallback = 1) => {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

const normalizeSelectValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value);
};

const normalizeMoneyInput = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value).trim();
};

const parseDecimalToMinorUnits = (value) => {
    const normalized = normalizeMoneyInput(value);

    if (! DECIMAL_MONEY_PATTERN.test(normalized)) {
        return 0;
    }

    const isNegative = normalized.startsWith('-');
    const absoluteValue = isNegative ? normalized.slice(1) : normalized;
    const [wholePart, fractionPart = ''] = absoluteValue.split('.');
    const paddedFractionPart = `${fractionPart}00`.slice(0, 2);
    const minorUnits = (Number.parseInt(wholePart, 10) * 100) + Number.parseInt(paddedFractionPart, 10);

    return isNegative ? -minorUnits : minorUnits;
};

const roundMinorUnitsToWholeRupiah = (minorUnits) => {
    const isNegative = minorUnits < 0;
    const absoluteAmount = isNegative ? -minorUnits : minorUnits;
    const fractionPart = absoluteAmount % 100;
    let wholePart = (absoluteAmount - fractionPart) / 100;

    if (fractionPart >= 50) {
        wholePart += 1;
    }

    return isNegative ? -wholePart : wholePart;
};

const formatCurrency = (minorUnits) => `Rp ${currencyFormatter.format(roundMinorUnitsToWholeRupiah(minorUnits))}`;
const formatWholeNumber = (value) => currencyFormatter.format(value ?? 0);

const normalizePricedOptions = (options) => (
    Array.isArray(options)
        ? options.map((option) => ({
            ...option,
            price: normalizeMoneyInput(option?.price),
            price_minor_units: parseDecimalToMinorUnits(option?.price),
            commission_value: option?.commission_value === null ? null : normalizeMoneyInput(option?.commission_value),
            commission_value_minor_units: option?.commission_value === null ? 0 : parseDecimalToMinorUnits(option?.commission_value),
        }))
        : []
);

const normalizeCommissionDefaults = (defaults = {}) => ({
    service: {
        commission_type: defaults?.service?.commission_type ?? '',
        commission_value: normalizeMoneyInput(defaults?.service?.commission_value ?? ''),
        commission_value_minor_units: parseDecimalToMinorUnits(defaults?.service?.commission_value ?? ''),
    },
    product: {
        commission_type: defaults?.product?.commission_type ?? '',
        commission_value: normalizeMoneyInput(defaults?.product?.commission_value ?? ''),
        commission_value_minor_units: parseDecimalToMinorUnits(defaults?.product?.commission_value ?? ''),
    },
});

const findOptionById = (options, id) => options.find((option) => String(option.id) === String(id));

const applyPercentageToMinorUnits = (amountMinorUnits, basisPoints) => {
    const numerator = amountMinorUnits * basisPoints;
    const quotient = Math.trunc(numerator / 10000);
    const remainder = Math.abs(numerator % 10000);

    if (remainder * 2 < 10000) {
        return quotient;
    }

    return quotient + (numerator >= 0 ? 1 : -1);
};

const itemTypeKey = (item) => (item?.item_type === 'product' ? 'product' : 'service');

const createEmptyTransactionItem = (type = 'service', employeeId = '') => ({
    key: createTransactionFormKey(type),
    item_type: type,
    service_id: '',
    product_id: '',
    employee_id: normalizeSelectValue(employeeId),
    qty: type === 'service' ? 1 : 1,
});

const normalizeTransactionItem = (item, fallbackEmployeeId = '') => {
    const itemType = item?.item_type === 'product' ? 'product' : 'service';

    return {
        key: createTransactionFormKey(itemType),
        item_type: itemType,
        service_id: itemType === 'service' ? normalizeSelectValue(item?.service_id) : '',
        product_id: itemType === 'product' ? normalizeSelectValue(item?.product_id) : '',
        employee_id: normalizeSelectValue(item?.employee_id ?? fallbackEmployeeId),
        qty: itemType === 'service' ? 1 : toPositiveInteger(item?.qty, 1),
    };
};

const normalizeTransactionItems = (items, defaultEmployeeId = '') => {
    if (! Array.isArray(items) || items.length === 0) {
        return [createEmptyTransactionItem('service', defaultEmployeeId)];
    }

    return items.map((item) => normalizeTransactionItem(item, defaultEmployeeId));
};

const normalizeDailyBatchEntry = (entry = {}) => {
    const initialItems = Array.isArray(entry?.items) && entry.items.length > 0
        ? entry.items
        : [];

    return {
        key: createTransactionFormKey('entry'),
        employee_id: normalizeSelectValue(entry?.employee_id),
        notes: entry?.notes ?? '',
        payment_method: entry?.payment_method ?? 'cash',
        items: normalizeTransactionItems(initialItems, entry?.employee_id),
    };
};

const normalizeSingleTransactionPayload = (transaction = {}) => ({
    transaction_date: transaction?.transaction_date ?? '',
    employee_id: normalizeSelectValue(transaction?.employee_id),
    payment_method: transaction?.payment_method ?? 'cash',
    notes: transaction?.notes ?? '',
    items: normalizeTransactionItems(transaction?.items ?? [], transaction?.employee_id),
});

const selectedItemId = (item) => (item?.item_type === 'product' ? item?.product_id : item?.service_id);

const resolveLineSubtotal = (item, serviceOptions, productOptions) => {
    const option = item?.item_type === 'product'
        ? findOptionById(productOptions, item?.product_id)
        : findOptionById(serviceOptions, item?.service_id);

    const qty = item?.item_type === 'product' ? toPositiveInteger(item?.qty, 1) : 1;

    return (option?.price_minor_units ?? 0) * qty;
};

const resolveCommissionRule = (item, serviceOptions, productOptions, commissionDefaults) => {
    const currentItemType = itemTypeKey(item);
    const option = currentItemType === 'product'
        ? findOptionById(productOptions, item?.product_id)
        : findOptionById(serviceOptions, item?.service_id);

    if (option?.commission_type && option?.commission_value !== null && option?.commission_value !== '') {
        return {
            source: 'master',
            type: option.commission_type,
            value: option.commission_value,
            value_minor_units: option.commission_value_minor_units,
            option,
        };
    }

    const defaultRule = commissionDefaults[currentItemType];

    return {
        source: 'global',
        type: defaultRule?.commission_type ?? '',
        value: defaultRule?.commission_value ?? '',
        value_minor_units: defaultRule?.commission_value_minor_units ?? 0,
        option,
    };
};

const resolveCommissionAmount = (item, serviceOptions, productOptions, commissionDefaults) => {
    const subtotalMinorUnits = resolveLineSubtotal(item, serviceOptions, productOptions);
    const qty = item?.item_type === 'product' ? toPositiveInteger(item?.qty, 1) : 1;
    const rule = resolveCommissionRule(item, serviceOptions, productOptions, commissionDefaults);

    if (! rule.type) {
        return 0;
    }

    if (rule.type === 'percent') {
        return applyPercentageToMinorUnits(subtotalMinorUnits, rule.value_minor_units);
    }

    return rule.value_minor_units * qty;
};

const filterSelectedItems = (items = []) => items.filter((item) => selectedItemId(item));

const transactionFormMixin = (config = {}) => ({
    errors: config.errors ?? {},
    employeeOptions: config.employeeOptions ?? [],
    serviceOptions: normalizePricedOptions(config.serviceOptions ?? []),
    productOptions: normalizePricedOptions(config.productOptions ?? []),
    commissionDefaults: normalizeCommissionDefaults(config.commissionDefaults ?? {}),
    selectedOption(item) {
        return item?.item_type === 'product'
            ? findOptionById(this.productOptions, item?.product_id)
            : findOptionById(this.serviceOptions, item?.service_id);
    },
    unitPrice(item) {
        return this.selectedOption(item)?.price_minor_units ?? 0;
    },
    lineSubtotal(item) {
        return resolveLineSubtotal(item, this.serviceOptions, this.productOptions);
    },
    commissionRule(item) {
        return resolveCommissionRule(item, this.serviceOptions, this.productOptions, this.commissionDefaults);
    },
    commissionSourceLabel(item) {
        const source = this.commissionRule(item).source;

        return {
            master: item?.item_type === 'product' ? 'Master produk' : 'Master layanan',
            global: 'Global',
        }[source] ?? 'Global';
    },
    commissionTypeDisplay(item) {
        const rule = this.commissionRule(item);

        if (! selectedItemId(item)) {
            return 'Pilih item terlebih dulu.';
        }

        if (! rule.type) {
            return 'Belum ada aturan komisi.';
        }

        if (rule.type === 'percent') {
            return `${rule.value || '0'}% dari subtotal`;
        }

        return `${formatCurrency(rule.value_minor_units)} per item`;
    },
    commissionAmount(item) {
        return resolveCommissionAmount(item, this.serviceOptions, this.productOptions, this.commissionDefaults);
    },
    fieldErrors(path) {
        return this.errors[path] ?? [];
    },
});

document.addEventListener('alpine:init', () => {
    Alpine.data('dailyBatchTransactionForm', (config = {}) => ({
        ...transactionFormMixin(config),
        entries: [],
        manualRecap: normalizeManualRecap(config.initialManualRecap ?? {}),
        init() {
            const initialEntries = Array.isArray(config.initialEntries) && config.initialEntries.length > 0
                ? config.initialEntries
                : [{}];

            this.entries = initialEntries.map((entry) => normalizeDailyBatchEntry(entry));
            this.manualRecap = normalizeManualRecap(config.initialManualRecap ?? {});
        },
        addTransaction() {
            this.entries.push(normalizeDailyBatchEntry({}));
        },
        removeTransaction(index) {
            this.entries.splice(index, 1);

            if (this.entries.length === 0) {
                this.entries.push(normalizeDailyBatchEntry({}));
            }
        },
        addItem(entryIndex) {
            this.entries[entryIndex].items.push(createEmptyTransactionItem());
        },
        removeItem(entryIndex, rowIndex) {
            this.entries[entryIndex].items.splice(rowIndex, 1);

            if (this.entries[entryIndex].items.length === 0) {
                this.entries[entryIndex].items.push(createEmptyTransactionItem());
            }
        },
        changeItemType(entryIndex, rowIndex, nextType) {
            const currentItem = this.entries[entryIndex]?.items?.[rowIndex] ?? {};
            this.entries[entryIndex].items[rowIndex] = normalizeTransactionItem({
                ...createEmptyTransactionItem(nextType, currentItem.employee_id || this.entries[entryIndex]?.employee_id),
                key: currentItem.key,
                item_type: nextType,
                employee_id: currentItem.employee_id || this.entries[entryIndex]?.employee_id,
            });
        },
        entryItems(entry) {
            return filterSelectedItems(entry?.items ?? []);
        },
        entrySelectedItemCount(entry) {
            return this.entryItems(entry).length;
        },
        entryServiceSubtotal(entry) {
            return this.entryItems(entry)
                .filter((item) => item.item_type === 'service')
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        entryProductSubtotal(entry) {
            return this.entryItems(entry)
                .filter((item) => item.item_type === 'product')
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        entryGrandTotal(entry) {
            return this.entryItems(entry)
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        batchGrandTotal() {
            return this.entries.reduce((total, entry) => total + this.entryGrandTotal(entry), 0);
        },
        batchSummary() {
            return summarizeDailyBatchEntries(this.entries, {
                lineSubtotal: (item) => this.lineSubtotal(item),
            });
        },
        entryNeedsAttention(entryIndex) {
            const entry = this.entries[entryIndex] ?? {};

            return entryHasMeaningfulInput(entry) && ! isBatchEntryReady(entry);
        },
        entryStatus(entryIndex) {
            if (this.entryHasErrors(entryIndex)) {
                return {
                    label: 'Perlu cek',
                    badgeClass: 'bg-rose-100 text-rose-700',
                };
            }

            if (this.entryNeedsAttention(entryIndex)) {
                return {
                    label: 'Belum lengkap',
                    badgeClass: 'bg-amber-100 text-amber-700',
                };
            }

            if (isBatchEntryReady(this.entries[entryIndex] ?? {})) {
                return {
                    label: 'Siap input',
                    badgeClass: 'bg-[#FAF3EF] text-[#7D4026]',
                };
            }

            return {
                label: 'Draft kosong',
                badgeClass: 'bg-slate-100 text-slate-600',
            };
        },
        updateManualRecapField(field, value) {
            this.manualRecap = {
                ...this.manualRecap,
                ...normalizeManualRecap({ [field]: value }),
            };
        },
        manualRecapHint(field) {
            if (field === 'transaction_count') {
                const parsed = parseOptionalIntegerInput(this.manualRecap[field]);

                return parsed === null ? '' : `${formatWholeNumber(parsed)} transaksi`;
            }

            const parsed = parseOptionalCurrencyInputToMinorUnits(this.manualRecap[field]);

            return parsed === null ? '' : formatCurrency(parsed);
        },
        reconciliationStatus() {
            return compareDailyBatchSummaryWithManualRecap(this.batchSummary(), this.manualRecap);
        },
        reconciliationStatusClass() {
            return {
                idle: 'border-slate-200 bg-slate-50 text-slate-700',
                match: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                mismatch: 'border-amber-200 bg-amber-50 text-amber-800',
            }[this.reconciliationStatus().status] ?? 'border-slate-200 bg-slate-50 text-slate-700';
        },
        comparisonCardClass(comparison) {
            if (! comparison.provided) {
                return 'border-slate-200 bg-slate-50';
            }

            return comparison.matches
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-amber-200 bg-amber-50';
        },
        comparisonStatusLabel(comparison) {
            if (! comparison.provided) {
                return 'Belum diisi';
            }

            return comparison.matches ? 'Cocok' : 'Selisih';
        },
        formatComparisonValue(comparison, key = 'system') {
            const value = comparison[key];

            if (value === null) {
                return 'Belum diisi';
            }

            if (comparison.kind === 'currency') {
                return formatCurrency(value);
            }

            return formatWholeNumber(value);
        },
        formatComparisonDelta(comparison) {
            if (comparison.delta === null) {
                return '-';
            }

            if (comparison.kind === 'currency') {
                if (comparison.delta === 0) {
                    return formatCurrency(0);
                }

                return `${comparison.delta > 0 ? '+' : '-'}${formatCurrency(Math.abs(comparison.delta))}`;
            }

            if (comparison.delta === 0) {
                return formatWholeNumber(0);
            }

            return `${comparison.delta > 0 ? '+' : '-'}${formatWholeNumber(Math.abs(comparison.delta))}`;
        },
        entryHasErrors(entryIndex) {
            return Object.keys(this.errors).some((key) => key.startsWith(`entries.${entryIndex}.`));
        },
        itemHasErrors(entryIndex, rowIndex) {
            return Object.keys(this.errors).some((key) => key.startsWith(`entries.${entryIndex}.items.${rowIndex}.`));
        },
        formatCurrency,
        formatWholeNumber,
    }));

    Alpine.data('singleTransactionEditForm', (config = {}) => ({
        ...transactionFormMixin(config),
        transaction: normalizeSingleTransactionPayload(config.initialTransaction ?? {}),
        init() {
            this.transaction = normalizeSingleTransactionPayload(config.initialTransaction ?? {});
        },
        addItem() {
            this.transaction.items.push(createEmptyTransactionItem('service', this.transaction.employee_id));
        },
        removeItem(rowIndex) {
            this.transaction.items.splice(rowIndex, 1);

            if (this.transaction.items.length === 0) {
                this.transaction.items.push(createEmptyTransactionItem('service', this.transaction.employee_id));
            }
        },
        changeItemType(rowIndex, nextType) {
            const currentItem = this.transaction.items?.[rowIndex] ?? {};

            this.transaction.items[rowIndex] = normalizeTransactionItem({
                ...createEmptyTransactionItem(nextType, currentItem.employee_id || this.transaction.employee_id),
                key: currentItem.key,
                item_type: nextType,
                employee_id: currentItem.employee_id || this.transaction.employee_id,
            }, this.transaction.employee_id);
        },
        selectedItems() {
            return filterSelectedItems(this.transaction.items ?? []);
        },
        selectedItemCount() {
            return this.selectedItems().length;
        },
        serviceSubtotal() {
            return this.selectedItems()
                .filter((item) => item.item_type === 'service')
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        productSubtotal() {
            return this.selectedItems()
                .filter((item) => item.item_type === 'product')
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        grandTotal() {
            return this.selectedItems()
                .reduce((total, item) => total + this.lineSubtotal(item), 0);
        },
        itemHasErrors(rowIndex) {
            return Object.keys(this.errors).some((key) => key.startsWith(`items.${rowIndex}.`));
        },
        applyTransactionEmployeeToItems() {
            this.transaction.items = this.transaction.items.map((item) => ({
                ...item,
                employee_id: this.transaction.employee_id,
            }));
        },
        formatCurrency,
    }));
});

Alpine.start();
