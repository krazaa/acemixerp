(function () {
    'use strict';

    const element = document.getElementById('vendor-statement-table');
    if (!element) {
        return;
    }

    const DataTable = window.DataTable || (window.jQuery && window.jQuery.fn.dataTable);
    if (!DataTable || !DataTable.Buttons) {
        const error = document.getElementById('statement-export-error');
        if (error) {
            error.textContent = 'Export tools could not load. Please reload the page and try again.';
            error.hidden = false;
        }
        return;
    }

    // DataTables supplies its own empty row; body colspans are not supported.
    const emptyCell = element.querySelector('tbody td[colspan]');
    if (emptyCell) {
        emptyCell.parentElement.remove();
    }

    const table = new DataTable(element, {
        paging: false,
        searching: false,
        ordering: false,
        info: false,
        autoWidth: false,
        layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
        language: { emptyTable: 'No transactions in the selected period.' }
    });

    const exportOptions = {
        columns: [0, 1, 2, 3, 4, 5, 6, 7],
        modifier: { selected: null },
        format: {
            body: function (data, row, column, node) {
                return node.dataset.exportNumber ?? node.textContent.replace(/\s+/g, ' ').trim();
            },
            footer: function (data, column, node) {
                return node.dataset.exportNumber ?? node.textContent.trim();
            }
        }
    };

    const common = {
        title: element.dataset.exportTitle,
        messageTop: [element.dataset.exportCompany, element.dataset.exportPeriod, element.dataset.exportContact,
            element.dataset.exportCurrency ? 'Currency: ' + element.dataset.exportCurrency : ''].filter(Boolean).join(' | '),
        messageBottom: 'Outstanding = Opening + Invoice Amount - Payment - WHT + Adjustment. Invoice Amount includes tax before withholding. Positive balances are payable to the vendor; negative balances represent advances or credits. Only posted entries are included.', 
        filename: element.dataset.exportFilename,
        footer: true,
        exportOptions: exportOptions
    };

    new DataTable.Buttons(table, {
        buttons: [
            {
                ...common,
                extend: 'pdfHtml5',
                text: 'Export PDF',
                className: 'btn btn-sm btn-outline-danger',
                pageSize: 'A4',
                orientation: 'landscape',
                download: 'download',
                customize: function (document) {
                    const report = document.content.find(function (section) { return section.table; });
                    report.table.widths = [55, 90, '*', 75, 70, 65, 70, 85];
                    report.table.body.forEach(function (row) {
                        row.forEach(function (cell, column) {
                            if (column >= 3 && Object.prototype.hasOwnProperty.call(cell, 'text')) {
                                cell.alignment = 'right';
                            }
                        });
                    });
                    document.defaultStyle.fontSize = 9;
                    document.footer = function (page, pages) {
                        return { text: page + ' / ' + pages, alignment: 'right', margin: [40, 0, 40, 0], fontSize: 8 };
                    };
                }
            },
            {
                ...common,
                extend: 'excelHtml5',
                text: 'Export Excel',
                className: 'btn btn-sm btn-outline-success',
                sheetName: 'Vendor Statement',
                exportOptions: {
                    ...exportOptions,
                    customizeData: function (data) {
                        data.body.forEach(function (row) {
                            row.forEach(function (value, column) {
                                if (column < 3 && /^[=+\-@\t\r]/.test(value)) {
                                    row[column] = "'" + value;
                                }
                            });
                        });
                    }
                }
            }
        ]
    });

    table.buttons().container().appendTo('#vendor-statement-exports');
})();
