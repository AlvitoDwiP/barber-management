const normalizeSelectValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value);
};

const normalizeOptionIds = (options = []) => (
    Array.isArray(options)
        ? options.map((option) => ({
            ...option,
            id: normalizeSelectValue(option?.id),
        }))
        : []
);

export const getItemMasterValue = (item = {}) => (
    item?.item_type === 'product'
        ? normalizeSelectValue(item?.product_id)
        : normalizeSelectValue(item?.service_id)
);

export const getItemMasterPlaceholder = (item = {}) => (
    item?.item_type === 'product' ? 'Pilih produk' : 'Pilih layanan'
);

export const getItemMasterOptions = ({
    item = {},
    serviceOptions = [],
    productOptions = [],
} = {}) => (
    item?.item_type === 'product'
        ? normalizeOptionIds(productOptions)
        : normalizeOptionIds(serviceOptions)
);

export const getResolvedItemMasterValue = ({
    item = {},
    serviceOptions = [],
    productOptions = [],
} = {}) => {
    const selectedValue = getItemMasterValue(item);

    if (selectedValue === '') {
        return '';
    }

    const hasMatchingOption = getItemMasterOptions({
        item,
        serviceOptions,
        productOptions,
    }).some((option) => option.id === selectedValue);

    return hasMatchingOption ? selectedValue : '';
};

export const applyItemMasterSelection = (item = {}, nextValue = '') => {
    const normalizedValue = normalizeSelectValue(nextValue);

    if (item?.item_type === 'product') {
        return {
            ...item,
            service_id: '',
            product_id: normalizedValue,
        };
    }

    return {
        ...item,
        service_id: normalizedValue,
        product_id: '',
    };
};
