
 //Hàm lọc danh sách vật tư dựa trên thanh tìm kiếm.

function filterInventoryTable() {
    var textFilter = removeAccents((document.getElementById('search-inventory').value || '').toLowerCase().trim());
    
    var rows       = document.querySelectorAll('#table-body tr[data-sort-name]');
    var noResults  = document.getElementById('no-results');
    
    var visible = 0;

    // Ẩn/Hiện dòng dựa trên tìm kiếm
    rows.forEach(function(row) {
        var text     = removeAccents((row.textContent || row.innerText).toLowerCase());
        var textOk = !textFilter || text.includes(textFilter);

        if (textOk) {
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
