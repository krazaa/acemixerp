import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/assets/js/custom/vendor-statement-exports.js', import.meta.url), 'utf8');

function initialize(empty = false) {
    let buttons;
    let options;
    let removed = false;
    let destination;
    const element = {
        dataset: {
            exportTitle: 'Vendor Statement — Acme',
            exportPeriod: 'V001 | 01 Sep 2026 – 28 Sep 2026',
            exportFilename: 'vendor-statement-1-2026-09-01-2026-09-28'
        },
        querySelector: () => empty ? { parentElement: { remove: () => { removed = true; } } } : null
    };
    function DataTable(node, configuration) {
        options = configuration;
        this.buttons = () => ({ container: () => ({ appendTo: (selector) => { destination = selector; } }) });
    }
    DataTable.Buttons = function (table, configuration) { buttons = configuration.buttons; };
    vm.runInNewContext(source, { document: { getElementById: () => element }, window: { DataTable } });

    return { buttons, options, removed, destination };
}

test('both downloads include the vendor, applied period, and outstanding balance footer', () => {
    const { buttons, options, destination } = initialize();
    assert.equal(buttons.length, 2);
    for (const button of buttons) {
        assert.equal(button.title, 'Vendor Statement — Acme');
        assert.equal(button.messageTop, 'V001 | 01 Sep 2026 – 28 Sep 2026');
        assert.equal(button.filename, 'vendor-statement-1-2026-09-01-2026-09-28');
        assert.equal(button.footer, true);
        assert.equal(button.exportOptions.columns.length, 8);
        assert.equal(button.exportOptions.modifier.selected, null);
    }
    assert.equal(options.paging, false);
    assert.equal(options.ordering, false);
    assert.equal(destination, '#vendor-statement-exports');
});

test('exports numeric values without thousands separators and preserves text references', () => {
    const format = initialize().buttons[1].exportOptions.format;
    assert.equal(format.body('', 0, 3, { dataset: { exportNumber: '1234.5678' }, textContent: '1,234.5678' }), '1234.5678');
    assert.equal(format.body('', 0, 1, { dataset: {}, textContent: '  0000123  ' }), '0000123');
    assert.equal(format.body('', 0, 2, { dataset: {}, textContent: 'Invoice\n   Memo' }), 'Invoice Memo');
    assert.equal(format.footer('', 5, { dataset: { exportNumber: '-1234.0000' } }), '-1234.0000');
});

test('spreadsheet exports neutralize formulas in text without altering negative amounts', () => {
    const excel = initialize().buttons[1];
    for (const formula of ['=1+1', '+SUM(A1)', '-cmd', '@SUM(A1)']) {
        const data = { body: [['28/09/2026', 'REF1', formula, '-25.0000', '0.0000', '-25.0000']] };
        excel.exportOptions.customizeData(data);
        assert.equal(data.body[0][2], "'" + formula);
        assert.equal(data.body[0][3], '-25.0000');
    }
});

test('PDF exports fit the ledger columns and show page numbers', () => {
    const pdf = initialize().buttons[0];
    const document = { content: [{ text: 'Title' }, { table: { body: [[{ text: 'Date' }, { text: 'Reference' }, { text: 'Description' }, { text: 'Invoice Amount' }, { text: 'Payment' }, { text: 'WHT' }, { text: 'Adjustment' }, { text: 'Outstanding' }], [{ text: 'Closing', colSpan: 7 }, {}, {}, {}, {}, {}, {}, { text: '100' }]] } }], defaultStyle: {} };
    pdf.customize(document);
    assert.equal(pdf.download, 'download');
    assert.equal(pdf.pageSize, 'A4');
    assert.equal(document.content[1].table.widths[2], '*');
    assert.equal(document.content[1].table.body[0][5].alignment, 'right');
    assert.equal(document.footer(2, 3).text, '2 / 3');
    assert.equal(Object.keys(document.content[1].table.body[1][3]).length, 0);
});

test('empty statements remove the incompatible body colspan and retain a readable empty message', () => {
    const { removed, options } = initialize(true);
    assert.equal(removed, true);
    assert.equal(options.language.emptyTable, 'No transactions in the selected period.');
});

test('the PDF renderer generates a document with the merged balance footer', async () => {
    const context = { module: { exports: {} }, exports: {}, console, setTimeout, clearTimeout, Uint8Array, ArrayBuffer, Buffer };
    vm.createContext(context);
    vm.runInContext(readFileSync(new URL('../../public/xassets/plugins/datatable/js/pdfmake.min.js', import.meta.url), 'utf8'), context);
    context.pdfMake = context.module.exports;
    vm.runInContext(readFileSync(new URL('../../public/xassets/plugins/datatable/js/vfs_fonts.js', import.meta.url), 'utf8'), context);
    const document = {
        content: [{ table: { headerRows: 1, body: [
            ['Date', 'Reference', 'Description', 'Invoice Amount', 'Payment', 'WHT', 'Adjustment', 'Outstanding'].map(text => ({ text })),
            [{ text: 'Period totals', colSpan: 3 }, {}, {}, { text: '100.0000' }, { text: '20.0000' }, { text: '10.0000' }, { text: '0.0000' }, { text: '70.0000' }]
        ] } }], defaultStyle: {}
    };
    initialize().buttons[0].customize(document);
    const buffer = await new Promise(resolve => context.pdfMake.createPdf(document).getBuffer(resolve));
    assert.equal(Buffer.from(buffer).subarray(0, 5).toString(), '%PDF-');
    assert.ok(buffer.length > 1000);
});
