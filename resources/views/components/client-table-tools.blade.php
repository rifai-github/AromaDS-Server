{{--
    Sort & filter sisi klien untuk tabel di halaman DETAIL bertab (Kontrak, SQ).

    Pak Hengky 27 Nov 2026: di tab Room & Rental kontrak dan tab Detail SQ, klik judul kolom
    tidak mengurutkan dan Enter di kotak filter melompat kembali ke tab Basic Info. Penyebabnya
    skrip global di layouts/app.blade.php: sort (th[data-column]) dan filter (Enter) keduanya
    me-reload halaman dengan ?sort= / ?filter[...] untuk diurutkan/disaring SERVER - pola
    halaman list. Controller halaman detail tidak membaca parameter itu, jadi tabel tidak
    berubah, dan reload membuka tab default.

    Tabel yang diberi atribut data-client-table diurutkan & disaring di browser:
    - judul kolom th[data-column] diberi data-no-sort agar skrip sort global melewatinya;
      klik judul mengurutkan baris tbody (naik/turun);
    - Enter / ketikan di input filter-row disaring langsung tanpa reload (listener capture
      menghentikan handler Enter global yang me-reload);
    - th[data-row-number] (kolom "No") dinomori ulang mengikuti urutan & saringan.
--}}
<script>
(function () {
    'use strict';

    if (window.__clientTableToolsLoaded) {
        return;
    }
    window.__clientTableToolsLoaded = true;

    var TABLE_SELECTOR = 'table[data-client-table]';

    function headerRow(table) {
        return table.tHead && table.tHead.rows.length ? table.tHead.rows[0] : null;
    }

    // Baris kosong ("No rooms or rentals found.") memakai colspan - jangan ikut diurutkan/disaring.
    function dataRows(table) {
        var body = table.tBodies[0];
        if (!body) {
            return [];
        }
        return Array.prototype.filter.call(body.rows, function (row) {
            return !row.querySelector('td[colspan]');
        });
    }

    function cellText(row, index) {
        var cell = row.cells[index];
        return cell ? (cell.textContent || '').replace(/\s+/g, ' ').trim() : '';
    }

    // "Rp 1.250.000" / "12,5" / "3" -> angka; selain itu null (dibandingkan sebagai teks).
    function numericValue(text) {
        var cleaned = text.replace(/^[^\d\-]+/, '').replace(/\s/g, '');
        if (!/^-?[\d.,]+$/.test(cleaned)) {
            return null;
        }
        cleaned = cleaned.replace(/\./g, '').replace(',', '.');
        var value = parseFloat(cleaned);
        return isNaN(value) ? null : value;
    }

    function compareText(a, b) {
        var numA = numericValue(a);
        var numB = numericValue(b);
        if (numA !== null && numB !== null) {
            return numA - numB;
        }
        if (a === '-' || a === '') { return b === '-' || b === '' ? 0 : 1; }
        if (b === '-' || b === '') { return -1; }
        return a.localeCompare(b, 'id', { numeric: true, sensitivity: 'base' });
    }

    function renumber(table) {
        var header = headerRow(table);
        if (!header) {
            return;
        }
        var numberCell = header.querySelector('th[data-row-number]');
        if (!numberCell) {
            return;
        }
        var index = numberCell.cellIndex;
        var counter = 0;
        dataRows(table).forEach(function (row) {
            if (row.style.display === 'none' || !row.cells[index]) {
                return;
            }
            counter += 1;
            row.cells[index].textContent = counter;
        });
    }

    function applyFilters(table) {
        var filters = [];
        table.querySelectorAll('thead .filter-row input').forEach(function (input) {
            var value = input.value.trim().toLowerCase();
            var cell = input.closest('th, td');
            if (value && cell) {
                filters.push({ index: cell.cellIndex, value: value });
            }
        });

        dataRows(table).forEach(function (row) {
            var visible = filters.every(function (filter) {
                return cellText(row, filter.index).toLowerCase().indexOf(filter.value) !== -1;
            });
            row.style.display = visible ? '' : 'none';
        });
        renumber(table);
    }

    function sortBy(table, th) {
        var index = th.cellIndex;
        var direction = th.getAttribute('data-client-sort') === 'asc' ? 'desc' : 'asc';
        var header = headerRow(table);

        header.querySelectorAll('th[data-client-sort]').forEach(function (other) {
            other.removeAttribute('data-client-sort');
            var otherIcon = other.querySelector('.client-sort-icon');
            if (otherIcon) { otherIcon.textContent = '↕'; otherIcon.style.color = '#adb5bd'; }
        });
        th.setAttribute('data-client-sort', direction);
        var icon = th.querySelector('.client-sort-icon');
        if (icon) { icon.textContent = direction === 'asc' ? '▲' : '▼'; icon.style.color = ''; }

        var rows = dataRows(table);
        var body = table.tBodies[0];
        rows.map(function (row, position) {
            return { row: row, text: cellText(row, index), position: position };
        }).sort(function (a, b) {
            var result = compareText(a.text, b.text);
            if (result === 0) {
                return a.position - b.position;
            }
            return direction === 'asc' ? result : -result;
        }).forEach(function (item) {
            body.appendChild(item.row);
        });
        renumber(table);
    }

    function enhance(table) {
        if (table.dataset.clientTableEnhanced === '1') {
            return;
        }
        var header = headerRow(table);
        if (!header) {
            return;
        }
        table.dataset.clientTableEnhanced = '1';

        Array.prototype.forEach.call(header.cells, function (th) {
            if (!th.hasAttribute('data-column')) {
                return;
            }
            // Skrip sort global melewati header ber-data-no-sort (tidak reload ?sort=).
            th.setAttribute('data-no-sort', '');
            th.style.cursor = 'pointer';
            th.title = 'Klik untuk mengurutkan';
            if (!th.querySelector('.client-sort-icon')) {
                var icon = document.createElement('span');
                icon.className = 'client-sort-icon ms-1';
                icon.style.fontSize = '10px';
                icon.style.color = '#adb5bd';
                icon.textContent = '↕';
                th.appendChild(icon);
            }
            th.addEventListener('click', function (event) {
                if (event.target.closest('input, label, select, button, a')) {
                    return;
                }
                sortBy(table, th);
            });
        });
    }

    function enhanceAll() {
        document.querySelectorAll(TABLE_SELECTOR).forEach(enhance);
    }

    // Tandai data-no-sort secepat mungkin (sebelum skrip sort global berjalan pada
    // DOMContentLoaded bila skrip ini dimuat lebih dulu) dan sekali lagi setelahnya.
    enhanceAll();
    document.addEventListener('DOMContentLoaded', enhanceAll);

    // Capture: berjalan sebelum listener keydown Enter dari injector filter global (yang
    // me-reload halaman) dan sebelum submit form apa pun.
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || !event.target.closest) {
            return;
        }
        var input = event.target.closest(TABLE_SELECTOR + ' .filter-row input');
        if (!input) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        applyFilters(input.closest('table'));
    }, true);

    document.addEventListener('input', function (event) {
        if (!event.target.closest) {
            return;
        }
        var input = event.target.closest(TABLE_SELECTOR + ' .filter-row input');
        if (input) {
            applyFilters(input.closest('table'));
        }
    }, true);

    // Filter tanggal dari injector global memicu reload lewat 'change'; saring di sini saja.
    document.addEventListener('change', function (event) {
        if (!event.target.closest) {
            return;
        }
        var input = event.target.closest(TABLE_SELECTOR + ' .filter-row input');
        if (input) {
            event.stopImmediatePropagation();
            applyFilters(input.closest('table'));
        }
    }, true);
})();
</script>
