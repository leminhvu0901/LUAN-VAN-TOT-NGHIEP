// ============================================================
//  Các hàm đẩy dữ liệu cụ thể vào Popup (Modal)
// ============================================================

// Đẩy dữ liệu vào Popup "Xuất / Huỷ Kho"
function disposeBatch(id, unit, maxStock) {
    var elUnit = document.getElementById('dispose-batch-unit');
    var elMax = document.getElementById('dispose-batch-max');
    var elId = document.getElementById('dispose-batch-id');
    var elQty = document.getElementById('dispose-batch-quantity');
    var elForm = document.getElementById('form-dispose-batch');
    
    if(elUnit) elUnit.innerText = unit;
    if(elMax) elMax.innerText = maxStock;
    if(elId) elId.innerText = 'LOT-' + String(id).padStart(4, '0');
    if(elQty) elQty.max = maxStock; // Chặn không cho nhập số lượng xuất quá tồn kho
    if(elForm) elForm.action = `/admin/materials/imports/${id}/dispose-batch`;
    
    var modal = document.getElementById('modal-dispose-batch');
    if(modal) modal.classList.remove('hidden');
}

// Đẩy dữ liệu vào Popup "Sửa thông tin Vật tư"
function editMaterial(id, name, unit, price) {
    var elName = document.getElementById('edit-name');
    var elUnit = document.getElementById('edit-unit');
    var elPrice = document.getElementById('edit-price');
    var elForm = document.getElementById('form-edit');
    
    if(elName) elName.value = name;
    if(elUnit) elUnit.value = unit;
    if(elPrice) elPrice.value = price;
    if(elForm) elForm.action = `/admin/materials/${id}`;
    
    var modal = document.getElementById('modal-edit');
    if(modal) modal.classList.remove('hidden');
}

// Đẩy dữ liệu vào Popup "Sửa Phiếu nhập"
function editImport(id, qty, price, expDate, note) {
    var elIdText = document.getElementById('edit-import-id-text');
    var elQty = document.getElementById('edit-import-quantity');
    var elPrice = document.getElementById('edit-import-total-price');
    var elExp = document.getElementById('edit-import-expiration-date');
    var elNote = document.getElementById('edit-import-note');
    var elForm = document.getElementById('form-edit-import');
    
    if(elIdText) elIdText.innerText = 'LOT-' + id;
    if(elQty) elQty.value = qty;
    if(elPrice) elPrice.value = price;
    if(elExp) elExp.value = expDate;
    if(elNote) elNote.value = note;
    if(elForm) elForm.action = `/admin/materials/imports/${id}`;
    
    var modal = document.getElementById('modal-edit-import');
    if(modal) modal.classList.remove('hidden');
}
