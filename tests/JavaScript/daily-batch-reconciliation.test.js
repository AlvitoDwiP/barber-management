import test from 'node:test';
import assert from 'node:assert/strict';

import {
    compareDailyBatchSummaryWithManualRecap,
    summarizeDailyBatchEntries,
} from '../../resources/js/transactions/daily-batch-reconciliation.js';

const buildSummary = (entries) => summarizeDailyBatchEntries(entries, {
    lineSubtotal: (item) => item.subtotal_minor_units ?? 0,
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

test('rekap manual yang cocok menghasilkan status match', () => {
    const comparison = compareDailyBatchSummaryWithManualRecap(
        {
            filledEntries: 2,
            cash: 25000000,
            qr: 12000000,
        },
        {
            transaction_count: '2',
            cash: '250000',
            qr: '120000',
        },
    );

    assert.equal(comparison.status, 'match');
    assert.equal(comparison.label, 'Sudah cocok');
    assert.equal(comparison.mismatches.length, 0);
});

test('rekap manual yang berbeda menghasilkan status mismatch dan delta yang jelas', () => {
    const comparison = compareDailyBatchSummaryWithManualRecap(
        {
            filledEntries: 3,
            cash: 30000000,
            qr: 10000000,
        },
        {
            transaction_count: '2',
            cash: '250000',
            qr: '100000',
        },
    );

    assert.equal(comparison.status, 'mismatch');
    assert.equal(comparison.label, 'Ada selisih');
    assert.deepEqual(
        comparison.mismatches.map((item) => [item.key, item.delta]),
        [
            ['transaction_count', 1],
            ['cash', 5000000],
        ],
    );
});
