import test from 'node:test';
import assert from 'node:assert/strict';

import { buildDuplicatedEntrySeed } from '../../resources/js/transactions/daily-batch-reconciliation.js';
import {
    applyItemMasterSelection,
    getItemMasterOptions,
    getItemMasterPlaceholder,
    getResolvedItemMasterValue,
    getItemMasterValue,
} from '../../resources/js/transactions/daily-batch-item-master.js';

test('service_id valid dibaca sebagai layanan terpilih untuk select item master', () => {
    assert.equal(getItemMasterValue({
        item_type: 'service',
        service_id: 15,
        product_id: '',
    }), '15');
    assert.equal(getItemMasterPlaceholder({ item_type: 'service' }), 'Pilih layanan');
});

test('product_id valid dibaca sebagai produk terpilih untuk select item master', () => {
    assert.equal(getItemMasterValue({
        item_type: 'product',
        service_id: '',
        product_id: 22,
    }), '22');
    assert.equal(getItemMasterPlaceholder({ item_type: 'product' }), 'Pilih produk');
});

test('opsi item master mengikuti item_type aktif dengan id yang sudah dinormalisasi ke string', () => {
    const serviceOptions = getItemMasterOptions({
        item: { item_type: 'service' },
        serviceOptions: [{ id: 10, name: 'Cukur' }],
        productOptions: [{ id: 20, name: 'Pomade' }],
    });
    const productOptions = getItemMasterOptions({
        item: { item_type: 'product' },
        serviceOptions: [{ id: 10, name: 'Cukur' }],
        productOptions: [{ id: 20, name: 'Pomade' }],
    });

    assert.equal(serviceOptions[0].id, '10');
    assert.equal(productOptions[0].id, '20');
});

test('nilai select item master ikut state internal saat opsi yang cocok tersedia', () => {
    assert.equal(getResolvedItemMasterValue({
        item: { item_type: 'service', service_id: 10, product_id: '' },
        serviceOptions: [{ id: 10, name: 'Cukur' }],
        productOptions: [],
    }), '10');
    assert.equal(getResolvedItemMasterValue({
        item: { item_type: 'product', service_id: '', product_id: 20 },
        serviceOptions: [],
        productOptions: [{ id: 20, name: 'Pomade' }],
    }), '20');
});

test('nilai select item master dikosongkan jika opsi aktif belum memuat id yang sesuai', () => {
    assert.equal(getResolvedItemMasterValue({
        item: { item_type: 'service', service_id: 10, product_id: '' },
        serviceOptions: [{ id: 11, name: 'Creambath' }],
        productOptions: [],
    }), '');
    assert.equal(getResolvedItemMasterValue({
        item: { item_type: 'product', service_id: '', product_id: 20 },
        serviceOptions: [],
        productOptions: [{ id: 21, name: 'Wax' }],
    }), '');
});

test('duplikat blok mempertahankan item master yang benar untuk setiap item', () => {
    const duplicatedSeed = buildDuplicatedEntrySeed({
        employee_id: '4',
        payment_method: 'cash',
        items: [
            { item_type: 'service', service_id: '10', product_id: '', qty: 1 },
            { item_type: 'product', service_id: '', product_id: '20', qty: 2 },
        ],
    });

    assert.equal(getItemMasterValue(duplicatedSeed.items[0]), '10');
    assert.equal(getItemMasterValue(duplicatedSeed.items[1]), '20');
});

test('ganti item_type tidak meninggalkan selected value palsu dari tipe sebelumnya', () => {
    assert.equal(getItemMasterValue({
        item_type: 'product',
        service_id: '10',
        product_id: '',
    }), '');
    assert.equal(getItemMasterValue({
        item_type: 'service',
        service_id: '',
        product_id: '20',
    }), '');
});

test('perubahan item master hanya mengisi field aktif dan membersihkan field lawannya', () => {
    assert.deepEqual(applyItemMasterSelection({
        item_type: 'service',
        service_id: '',
        product_id: '99',
    }, 10), {
        item_type: 'service',
        service_id: '10',
        product_id: '',
    });
    assert.deepEqual(applyItemMasterSelection({
        item_type: 'product',
        service_id: '88',
        product_id: '',
    }, 20), {
        item_type: 'product',
        service_id: '',
        product_id: '20',
    });
});
