import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildAutofillEntrySeed,
    buildDuplicatedEntrySeed,
    summarizeDailyBatchEntries,
} from '../../resources/js/transactions/daily-batch-reconciliation.js';

const buildSummary = (entries) => summarizeDailyBatchEntries(entries, {
    lineSubtotal: (item) => item.subtotal_minor_units ?? 0,
});

test('autofill blok baru mewarisi pegawai utama dan metode bayar terakhir tanpa menyalin catatan', () => {
    const seed = buildAutofillEntrySeed({
        employee_id: '7',
        payment_method: 'qr',
        notes: 'Jangan ikut',
        items: [
            { item_type: 'service', service_id: '10', qty: 1 },
        ],
    });

    assert.deepEqual(seed, {
        employee_id: '7',
        payment_method: 'qr',
        notes: '',
    });
});

test('duplikat blok menyalin field penting dan membuat nested item yang independen', () => {
    const sourceEntry = {
        employee_id: '4',
        payment_method: 'cash',
        notes: 'Catatan lama',
        items: [
            { item_type: 'service', service_id: '10', product_id: '', employee_id: '4', qty: 1 },
            { item_type: 'product', service_id: '', product_id: '20', employee_id: '4', qty: 3 },
        ],
    };
    const duplicatedSeed = buildDuplicatedEntrySeed(sourceEntry);

    assert.deepEqual(duplicatedSeed, {
        employee_id: '4',
        payment_method: 'cash',
        notes: '',
        items: [
            { item_type: 'service', service_id: '10', product_id: '', employee_id: '4', qty: 1 },
            { item_type: 'product', service_id: '', product_id: '20', employee_id: '4', qty: 3 },
        ],
    });
    assert.notStrictEqual(duplicatedSeed.items, sourceEntry.items);
    assert.notStrictEqual(duplicatedSeed.items[0], sourceEntry.items[0]);
    assert.notStrictEqual(duplicatedSeed.items[1], sourceEntry.items[1]);
});

test('summary menghitung metrik dasar batch harian', () => {
    const summary = buildSummary([
        {
            employee_id: '1',
            payment_method: 'cash',
            items: [
                { item_type: 'service', service_id: '10', qty: 1, subtotal_minor_units: 15000000 },
                { item_type: 'product', product_id: '20', qty: 2, subtotal_minor_units: 10000000 },
            ],
        },
        {
            employee_id: '2',
            payment_method: 'qr',
            items: [
                { item_type: 'service', service_id: '11', qty: 1, subtotal_minor_units: 12000000 },
            ],
        },
    ]);

    assert.equal(summary.totalBlocks, 2);
    assert.equal(summary.filledEntries, 2);
    assert.equal(summary.readyEntries, 2);
    assert.equal(summary.attentionEntries, 0);
    assert.equal(summary.grossCashIn, 37000000);
    assert.equal(summary.cash, 25000000);
    assert.equal(summary.qr, 12000000);
    assert.equal(summary.serviceRevenue, 27000000);
    assert.equal(summary.productRevenue, 10000000);
    assert.equal(summary.productItemCount, 2);
});

test('perubahan metode bayar memindahkan total antara cash dan qr', () => {
    const cashSummary = buildSummary([
        {
            employee_id: '1',
            payment_method: 'cash',
            items: [
                { item_type: 'service', service_id: '10', qty: 1, subtotal_minor_units: 10000000 },
            ],
        },
    ]);
    const qrSummary = buildSummary([
        {
            employee_id: '1',
            payment_method: 'qr',
            items: [
                { item_type: 'service', service_id: '10', qty: 1, subtotal_minor_units: 10000000 },
            ],
        },
    ]);

    assert.equal(cashSummary.cash, 10000000);
    assert.equal(cashSummary.qr, 0);
    assert.equal(qrSummary.cash, 0);
    assert.equal(qrSummary.qr, 10000000);
});

test('perubahan komposisi service dan product memengaruhi ringkasan yang sesuai', () => {
    const summary = buildSummary([
        {
            employee_id: '1',
            payment_method: 'cash',
            items: [
                { item_type: 'service', service_id: '10', qty: 1, subtotal_minor_units: 8000000 },
                { item_type: 'product', product_id: '20', qty: 3, subtotal_minor_units: 13500000 },
            ],
        },
    ]);

    assert.equal(summary.serviceRevenue, 8000000);
    assert.equal(summary.productRevenue, 13500000);
    assert.equal(summary.grossCashIn, 21500000);
    assert.equal(summary.productItemCount, 3);
});

test('blok yang belum lengkap tetap terhitung sebagai perlu dicek tanpa mengganggu total terisi', () => {
    const summary = buildSummary([
        {
            employee_id: '1',
            payment_method: 'cash',
            items: [
                { item_type: 'service', service_id: '10', qty: 1, subtotal_minor_units: 10000000 },
            ],
        },
        {
            employee_id: '',
            payment_method: 'cash',
            notes: 'Masih draft',
            items: [
                { item_type: 'product', product_id: '20', qty: 2, subtotal_minor_units: 7000000 },
            ],
        },
    ]);

    assert.equal(summary.totalBlocks, 2);
    assert.equal(summary.filledEntries, 2);
    assert.equal(summary.readyEntries, 1);
    assert.equal(summary.attentionEntries, 1);
    assert.equal(summary.grossCashIn, 17000000);
});
