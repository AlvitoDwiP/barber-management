const hasFilledValue = (value) => value !== null && value !== undefined && String(value).trim() !== '';
const toPositiveInteger = (value, fallback = 1) => {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};
const normalizeSelectValue = (value) => (hasFilledValue(value) ? String(value) : '');
const normalizeCopiedItemType = (item) => (item?.item_type === 'product' ? 'product' : 'service');

const cloneEntryItemSeed = (item = {}) => {
    const itemType = normalizeCopiedItemType(item);

    return {
        item_type: itemType,
        service_id: itemType === 'service' ? normalizeSelectValue(item?.service_id) : '',
        product_id: itemType === 'product' ? normalizeSelectValue(item?.product_id) : '',
        employee_id: normalizeSelectValue(item?.employee_id),
        qty: itemType === 'product' ? toPositiveInteger(item?.qty, 1) : 1,
    };
};

export const buildAutofillEntrySeed = (entry = null) => {
    if (! entry) {
        return {};
    }

    return {
        employee_id: normalizeSelectValue(entry?.employee_id),
        payment_method: ['cash', 'qr'].includes(entry?.payment_method) ? entry.payment_method : 'cash',
        notes: '',
    };
};

export const buildDuplicatedEntrySeed = (entry = null) => {
    if (! entry) {
        return {};
    }

    const items = Array.isArray(entry?.items) ? entry.items.map((item) => cloneEntryItemSeed(item)) : [];

    return {
        employee_id: normalizeSelectValue(entry?.employee_id),
        payment_method: ['cash', 'qr'].includes(entry?.payment_method) ? entry.payment_method : 'cash',
        notes: '',
        items,
    };
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
