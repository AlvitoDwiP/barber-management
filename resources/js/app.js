import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';

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

const singularType = (type) => (type === 'services' ? 'service' : 'product');

const createEmptyLineItem = (type) => (
    type === 'service'
        ? {
            key: createTransactionFormKey(type),
            service_id: '',
        }
        : {
            key: createTransactionFormKey(type),
            product_id: '',
            qty: 1,
        }
);

const normalizeLineItems = (rows, type) => {
    if (! Array.isArray(rows) || rows.length === 0) {
        return [createEmptyLineItem(type)];
    }

    const idKey = `${type}_id`;

    return rows.map((row) => (
        type === 'service'
            ? {
                key: createTransactionFormKey(type),
                [idKey]: normalizeSelectValue(row?.[idKey]),
            }
            : {
                key: createTransactionFormKey(type),
                [idKey]: normalizeSelectValue(row?.[idKey]),
                qty: toPositiveInteger(row?.qty, 1),
            }
    ));
};

const normalizeMoneyInput = (value) => {
    if (value === null || value === undefined) {
        return '0';
    }

    const normalized = String(value).trim();

    return normalized === '' ? '0' : normalized;
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

const normalizePricedOptions = (options) => (
    Array.isArray(options)
        ? options.map((option) => ({
            ...option,
            price: normalizeMoneyInput(option?.price),
            price_minor_units: parseDecimalToMinorUnits(option?.price),
        }))
        : []
);

const findOptionById = (options, id) => options.find((option) => String(option.id) === String(id));

const resolveLineSubtotal = (options, id, qty) => {
    const option = findOptionById(options, id);

    return (option?.price_minor_units ?? 0) * qty;
};

const createDailyBatchEntry = (entry = {}) => ({
    key: createTransactionFormKey('entry'),
    notes: entry.notes ?? '',
    payment_method: entry.payment_method ?? 'cash',
    services: normalizeLineItems(entry.services ?? [], 'service'),
    products: normalizeLineItems(entry.products ?? [], 'product'),
});

document.addEventListener('alpine:init', () => {
    Alpine.data('transactionFormEditor', (config = {}) => ({
        errors: config.errors ?? {},
        serviceOptions: normalizePricedOptions(config.serviceOptions ?? []),
        productOptions: normalizePricedOptions(config.productOptions ?? []),
        services: normalizeLineItems(config.initialServices ?? [], 'service'),
        products: normalizeLineItems(config.initialProducts ?? [], 'product'),
        addRow(type) {
            this[type].push(createEmptyLineItem(singularType(type)));
        },
        removeRow(type, index) {
            this[type].splice(index, 1);

            if (this[type].length === 0) {
                this[type].push(createEmptyLineItem(singularType(type)));
            }
        },
        option(type, id) {
            return findOptionById(type === 'service' ? this.serviceOptions : this.productOptions, id);
        },
        lineSubtotal(type, row) {
            return resolveLineSubtotal(
                type === 'service' ? this.serviceOptions : this.productOptions,
                row[`${type}_id`],
                type === 'service' ? 1 : toPositiveInteger(row.qty, 1)
            );
        },
        serviceSubtotal() {
            return this.services.reduce((total, row) => total + this.lineSubtotal('service', row), 0);
        },
        productSubtotal() {
            return this.products.reduce((total, row) => total + this.lineSubtotal('product', row), 0);
        },
        grandTotal() {
            return this.serviceSubtotal() + this.productSubtotal();
        },
        formatCurrency,
        fieldErrors(path) {
            return this.errors[path] ?? [];
        },
    }));

    Alpine.data('dailyBatchTransactionForm', (config = {}) => ({
        errors: config.errors ?? {},
        serviceOptions: normalizePricedOptions(config.serviceOptions ?? []),
        productOptions: normalizePricedOptions(config.productOptions ?? []),
        entries: [],
        init() {
            const initialEntries = Array.isArray(config.initialEntries) && config.initialEntries.length > 0
                ? config.initialEntries
                : [createDailyBatchEntry()];

            this.entries = initialEntries.map((entry) => createDailyBatchEntry(entry));
        },
        addTransaction() {
            this.entries.push(createDailyBatchEntry());
        },
        removeTransaction(index) {
            this.entries.splice(index, 1);

            if (this.entries.length === 0) {
                this.entries.push(createDailyBatchEntry());
            }
        },
        addRow(entryIndex, type) {
            this.entries[entryIndex][type].push(createEmptyLineItem(singularType(type)));
        },
        removeRow(entryIndex, type, rowIndex) {
            this.entries[entryIndex][type].splice(rowIndex, 1);

            if (this.entries[entryIndex][type].length === 0) {
                this.entries[entryIndex][type].push(createEmptyLineItem(singularType(type)));
            }
        },
        option(type, id) {
            return findOptionById(type === 'service' ? this.serviceOptions : this.productOptions, id);
        },
        lineSubtotal(type, row) {
            return resolveLineSubtotal(
                type === 'service' ? this.serviceOptions : this.productOptions,
                row[`${type}_id`],
                type === 'service' ? 1 : toPositiveInteger(row.qty, 1)
            );
        },
        entryServiceSubtotal(entry) {
            return entry.services.reduce((total, row) => total + this.lineSubtotal('service', row), 0);
        },
        entryProductSubtotal(entry) {
            return entry.products.reduce((total, row) => total + this.lineSubtotal('product', row), 0);
        },
        entryGrandTotal(entry) {
            return this.entryServiceSubtotal(entry) + this.entryProductSubtotal(entry);
        },
        formatCurrency,
        fieldErrors(path) {
            return this.errors[path] ?? [];
        },
    }));
});

Alpine.start();
