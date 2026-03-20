const hasFilledValue = (value) => value !== null && value !== undefined && String(value).trim() !== '';

export const normalizeDigitInput = (value) => String(value ?? '').replace(/[^\d]/g, '');

export const normalizeManualRecap = (manualRecap = {}) => ({
    transaction_count: normalizeDigitInput(manualRecap?.transaction_count),
    cash: normalizeDigitInput(manualRecap?.cash),
    qr: normalizeDigitInput(manualRecap?.qr),
});

export const parseOptionalIntegerInput = (value) => {
    const normalized = normalizeDigitInput(value);

    if (normalized === '') {
        return null;
    }

    return Number.parseInt(normalized, 10);
};

export const parseOptionalCurrencyInputToMinorUnits = (value) => {
    const wholeRupiah = parseOptionalIntegerInput(value);

    if (wholeRupiah === null) {
        return null;
    }

    return wholeRupiah * 100;
};

export const hasSelectedTransactionItem = (item) => {
    if (item?.item_type === 'product') {
        return hasFilledValue(item?.product_id);
    }

    return hasFilledValue(item?.service_id);
};

export const isTransactionItemReady = (item) => {
    if (! hasSelectedTransactionItem(item)) {
        return false;
    }

    if (item?.item_type !== 'product') {
        return true;
    }

    const qty = Number.parseInt(item?.qty, 10);

    return Number.isFinite(qty) && qty > 0;
};

export const entryHasMeaningfulInput = (entry = {}) => {
    const items = Array.isArray(entry?.items) ? entry.items : [];

    return hasFilledValue(entry?.employee_id)
        || hasFilledValue(entry?.notes)
        || items.some((item) => hasSelectedTransactionItem(item) || (item?.item_type === 'product' && hasFilledValue(item?.qty)));
};

export const isBatchEntryReady = (entry = {}) => {
    const items = Array.isArray(entry?.items) ? entry.items : [];

    if (! hasFilledValue(entry?.employee_id)) {
        return false;
    }

    if (! ['cash', 'qr'].includes(entry?.payment_method)) {
        return false;
    }

    if (items.length === 0) {
        return false;
    }

    return items.every((item) => isTransactionItemReady(item));
};

export const summarizeDailyBatchEntries = (entries = [], { lineSubtotal } = {}) => {
    const resolveSubtotal = typeof lineSubtotal === 'function' ? lineSubtotal : () => 0;

    return entries.reduce((summary, entry) => {
        const items = Array.isArray(entry?.items) ? entry.items : [];
        const selectedItems = items.filter((item) => hasSelectedTransactionItem(item));
        const serviceRevenue = selectedItems
            .filter((item) => item?.item_type === 'service')
            .reduce((total, item) => total + resolveSubtotal(item), 0);
        const productRevenue = selectedItems
            .filter((item) => item?.item_type === 'product')
            .reduce((total, item) => total + resolveSubtotal(item), 0);
        const grossCashIn = serviceRevenue + productRevenue;
        const productItemCount = selectedItems
            .filter((item) => item?.item_type === 'product')
            .reduce((total, item) => total + Math.max(0, Number.parseInt(item?.qty, 10) || 0), 0);
        const hasSelectedItems = selectedItems.length > 0;
        const isReady = isBatchEntryReady(entry);
        const needsAttention = entryHasMeaningfulInput(entry) && ! isReady;

        return {
            totalBlocks: summary.totalBlocks + 1,
            filledEntries: summary.filledEntries + (hasSelectedItems ? 1 : 0),
            readyEntries: summary.readyEntries + (isReady ? 1 : 0),
            attentionEntries: summary.attentionEntries + (needsAttention ? 1 : 0),
            grossCashIn: summary.grossCashIn + grossCashIn,
            cash: summary.cash + (entry?.payment_method === 'cash' ? grossCashIn : 0),
            qr: summary.qr + (entry?.payment_method === 'qr' ? grossCashIn : 0),
            serviceRevenue: summary.serviceRevenue + serviceRevenue,
            productRevenue: summary.productRevenue + productRevenue,
            productItemCount: summary.productItemCount + productItemCount,
        };
    }, {
        totalBlocks: 0,
        filledEntries: 0,
        readyEntries: 0,
        attentionEntries: 0,
        grossCashIn: 0,
        cash: 0,
        qr: 0,
        serviceRevenue: 0,
        productRevenue: 0,
        productItemCount: 0,
    });
};

const createComparison = ({ key, label, kind, system, manual }) => ({
    key,
    label,
    kind,
    system,
    manual,
    provided: manual !== null,
    matches: manual !== null && system === manual,
    delta: manual === null ? null : system - manual,
});

export const compareDailyBatchSummaryWithManualRecap = (summary = {}, manualRecap = {}) => {
    const comparisons = [
        createComparison({
            key: 'transaction_count',
            label: 'Jumlah transaksi',
            kind: 'count',
            system: summary.filledEntries ?? 0,
            manual: parseOptionalIntegerInput(manualRecap?.transaction_count),
        }),
        createComparison({
            key: 'cash',
            label: 'Total cash',
            kind: 'currency',
            system: summary.cash ?? 0,
            manual: parseOptionalCurrencyInputToMinorUnits(manualRecap?.cash),
        }),
        createComparison({
            key: 'qr',
            label: 'Total QR',
            kind: 'currency',
            system: summary.qr ?? 0,
            manual: parseOptionalCurrencyInputToMinorUnits(manualRecap?.qr),
        }),
    ];

    const providedComparisons = comparisons.filter((comparison) => comparison.provided);
    const mismatches = providedComparisons.filter((comparison) => ! comparison.matches);
    const hasAnyComparison = providedComparisons.length > 0;
    const status = ! hasAnyComparison
        ? 'idle'
        : mismatches.length === 0
            ? 'match'
            : 'mismatch';

    return {
        status,
        label: {
            idle: 'Belum dibandingkan',
            match: 'Sudah cocok',
            mismatch: 'Ada selisih',
        }[status],
        message: {
            idle: 'Isi angka buku yang ingin dicek. Sistem akan langsung membandingkan total transaksi, cash, dan QR.',
            match: 'Angka buku yang sudah diisi saat ini sama dengan hitungan sistem.',
            mismatch: 'Masih ada perbedaan antara input sistem dan catatan buku. Cek baris yang bertanda selisih.',
        }[status],
        hasAnyComparison,
        providedComparisons,
        mismatches,
        comparisons,
    };
};
