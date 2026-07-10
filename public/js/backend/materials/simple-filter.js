// ============================================================
//  filterSimpleTable(inputId)
//  Hàm tìm kiếm chung (bằng chữ) cho các bảng đơn giản (như Hàng sắp hết hạn, Tồn kho thấp)
// ============================================================
function filterSimpleTable(inputId) {
    var textFilter = removeAccents((document.getElementById(inputId).value || '').toLowerCase().trim());
    var rows       = document.querySelectorAll('#table-body tr');
    var noResults  = document.getElementById('no-results');
    var visible    = 0;

    rows.forEach(function(row) {
        var text = removeAccents((row.textContent || row.innerText).toLowerCase());
        if (!textFilter || text.includes(textFilter)) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    // Cập nhật số bản ghi đang hiển thị
    var cnt = document.getElementById('visible-count');
    if (cnt) cnt.textContent = visible;

    if (noResults) noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
}
