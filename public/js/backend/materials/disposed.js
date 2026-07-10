
//  Chứa các hàm lọc và tính toán riêng cho trang Lịch sử xuất huỷ


function filterDisposedTable() {
    var textFilter = removeAccents((document.getElementById('search-disposed').value || '').toLowerCase().trim());
    var dateFrom   = document.getElementById('date-from').value;
    var dateTo     = document.getElementById('date-to').value;
    
    var rows       = document.querySelectorAll('#table-body tr[data-date]');
    var noResults  = document.getElementById('no-results');
    var btnClear   = document.getElementById('btn-clear-date');
    
    var visible = 0;

    if (btnClear) {
        (dateFrom || dateTo) ? btnClear.classList.remove('hidden') : btnClear.classList.add('hidden');
    }

    rows.forEach(function(row) {
        var text     = removeAccents((row.textContent || row.innerText).toLowerCase());
        var rowDate  = row.getAttribute('data-date') || '';

        var textOk = !textFilter || text.includes(textFilter);
        var fromOk = !dateFrom  || rowDate >= dateFrom;
        var toOk   = !dateTo    || rowDate <= dateTo;

        if (textOk && fromOk && toOk) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
            // Bỏ check nếu dòng bị ẩn đi
            var cb = row.querySelector('.row-checkbox');
            if(cb) cb.checked = false;
        }
    });

    if (noResults) noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
    
    // Tính toán lại tổng sau khi lọc
    calculateSelected();
}

function clearDateFilter() {
    var dateFrom = document.getElementById('date-from');
    var dateTo   = document.getElementById('date-to');
    if (dateFrom) dateFrom.value = '';
    if (dateTo)   dateTo.value   = '';
    
    filterDisposedTable();
}
