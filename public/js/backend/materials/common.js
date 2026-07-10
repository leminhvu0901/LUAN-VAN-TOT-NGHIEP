// ============================================================
//  Hàm hỗ trợ: Loại bỏ dấu tiếng Việt để tìm kiếm chính xác hơn
//  (Ví dụ: "Đường" sẽ thành "Duong", giúp gõ không dấu vẫn ra kết quả)
// ============================================================
function removeAccents(str) {
    if(!str) return '';
    // Chuẩn hóa chuỗi và dùng RegEx để xóa các ký tự dấu, đổi đ/Đ thành d/D
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D');
}

//  Hàm sắp xếp các dòng trong bảng.
var _sortState = { col: -1, asc: true }; // Lưu trạng thái hiện tại đang sắp xếp cột nào và chiều nào (tăng/giảm)
var _attrMap = ['sort-name', 'sort-qty', 'sort-value', 'sort-date', 'sort-exp']; // Danh sách các thuộc tính tương ứng với từng cột

function sortTable(colIndex) {
    var tbody = document.getElementById('table-body');
    if (!tbody) return;

    // Chỉ lấy các dòng dữ liệu (tránh lấy nhầm dòng thông báo)
    var rows = Array.from(tbody.querySelectorAll('tr[data-sort-name]'));

    // Nếu ấn lại vào cột đang được sắp xếp thì đổi chiều (tăng thành giảm, giảm thành tăng)
    _sortState.asc = (_sortState.col === colIndex) ? !_sortState.asc : true;
    _sortState.col = colIndex;

    // Cập nhật icon mũi tên trên tiêu đề cột
    for (var i = 0; i < _attrMap.length; i++) {
        var icon = document.getElementById('sort-icon-' + i);
        if (!icon) continue;
        if (i === colIndex) {
            icon.textContent = _sortState.asc ? 'arrow_upward' : 'arrow_downward';
            icon.className = 'material-symbols-outlined text-[14px] text-red-500';
        } else {
            icon.textContent = 'unfold_more';
            icon.className = 'material-symbols-outlined text-[14px] text-gray-300';
        }
    }

    // Thuật toán sắp xếp mảng các dòng (rows)
    rows.sort(function (a, b) {
        var attr = _attrMap[colIndex];
        var vA = a.getAttribute('data-' + attr) || '';
        var vB = b.getAttribute('data-' + attr) || '';

        // Các cột số lượng, giá trị, ngày tháng (cột số 1 trở lên) -> so sánh theo toán học (trừ nhau)
        if (colIndex >= 1) {
            vA = parseFloat(vA) || 0;
            vB = parseFloat(vB) || 0;
            return _sortState.asc ? vA - vB : vB - vA;
        }
        // Cột chữ (tên vật tư) -> so sánh theo bảng chữ cái tiếng Việt
        return _sortState.asc ? vA.localeCompare(vB, 'vi') : vB.localeCompare(vA, 'vi');
    });

    // Đẩy các dòng đã sắp xếp trở lại vào trong bảng (tbody)
    rows.forEach(function (row) { tbody.appendChild(row); });
}

//  Hàm bật/tắt (hiện/ẩn) các hộp thoại popup chung.
function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('hidden');
}
function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('hidden');
}

// ============================================================
//  Hàm tính toán lại "Tổng số mặt hàng" và "Tổng giá trị" dùng chung cho các bảng có tính năng check chọn.
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('check-all');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            var isChecked = this.checked;
            // Chỉ chọn những dòng đang hiển thị
            var visibleCheckboxes = Array.from(document.querySelectorAll('.row-checkbox')).filter(cb => cb.closest('tr').style.display !== 'none');
            visibleCheckboxes.forEach(function(cb) {
                cb.checked = isChecked;
            });
            if (typeof calculateSelected === 'function') {
                calculateSelected();
            }
        });
    }
});

function calculateSelected() {
    var table = document.querySelector('table[data-total-id]');
    if (!table) return;

    var totalId = table.getAttribute('data-total-id');
    var highlightClass = table.getAttribute('data-highlight') || 'bg-gray-50';

    var checkboxes = document.querySelectorAll('.row-checkbox:checked');
    var allVisibleCheckboxes = Array.from(document.querySelectorAll('.row-checkbox')).filter(cb => cb.closest('tr').style.display !== 'none');
    
    var totalValue = 0;
    var count = 0;
    
    if (checkboxes.length > 0) {
        checkboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            if (row.style.display !== 'none') {
                var val = parseFloat(row.getAttribute('data-value') || 0);
                totalValue += val;
                count++;
                row.classList.add(highlightClass);
            }
        });
        
        var lblCount = document.getElementById('label-count-suffix');
        if(lblCount) lblCount.textContent = '(đã chọn)';
        var lblVal = document.getElementById('label-val-suffix');
        if(lblVal) lblVal.textContent = '(đã chọn)';
        
        var checkAll = document.getElementById('check-all');
        if (checkAll) {
            checkAll.checked = (checkboxes.length === allVisibleCheckboxes.length && allVisibleCheckboxes.length > 0);
        }
    } else {
        allVisibleCheckboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            var val = parseFloat(row.getAttribute('data-value') || 0);
            totalValue += val;
            count++;
        });
        
        var lblCount = document.getElementById('label-count-suffix');
        if(lblCount) lblCount.textContent = '(theo bộ lọc)';
        var lblVal = document.getElementById('label-val-suffix');
        if(lblVal) lblVal.textContent = '(theo bộ lọc)';
        
        var checkAll = document.getElementById('check-all');
        if (checkAll) checkAll.checked = false;
    }
    
    document.querySelectorAll('.row-checkbox:not(:checked)').forEach(function(cb) {
        cb.closest('tr').classList.remove(highlightClass);
    });

    var elVal = document.getElementById(totalId);
    if (elVal) elVal.textContent = new Intl.NumberFormat('vi-VN').format(totalValue) + 'đ';

    var statCount = document.getElementById('stat-count');
    if (statCount) statCount.textContent = count;
}
