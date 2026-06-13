import sys

filepath = r'c:\Users\ADMIN\Desktop\DA_LVTN\LUAN-VAN-TOT-NGHIEP\resources\views\pages\checkout.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace empty-address block to add ID
content = content.replace(
    '<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-xl flex flex-col items-center text-center">',
    '<div id="empty-address-block" class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-xl flex flex-col items-center text-center">'
)

# Replace active address info block to add ID
content = content.replace(
    '<div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60 relative">',
    '<div id="address-info-block" class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60 relative">'
)

# Replace address action buttons to add ID
content = content.replace(
    '<div class="mt-3 flex items-center gap-4">',
    '<div id="address-action-buttons" class="mt-3 flex items-center gap-4">'
)

# Insert the new form right before </section> of shipping address
new_form = """
                            <!-- New Address Form (Hidden by default) -->
                            <div id="addressModal" class="hidden mt-4 pt-4 border-t border-outline-variant/60">
                                <div class="border-b border-outline-variant pb-4 mb-4 flex items-center justify-between">
                                    <h2 id="addressModalTitle" class="font-headline-md text-lg text-on-surface font-bold">Thêm địa chỉ mới</h2>
                                    <button type="button" onclick="closeAddressModal()" class="text-on-surface-variant hover:bg-surface-container p-2 rounded-full transition-colors active:scale-95">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Left: Form Inputs -->
                                    <div class="space-y-4">
                                        <input type="hidden" id="addr_id">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Họ và tên</label>
                                                <input type="text" id="addr_fullname" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập họ tên">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Số điện thoại</label>
                                                <input type="tel" id="addr_phone" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập SĐT">
                                            </div>
                                        </div>

                                        <!-- Location Picker -->
                                        <div class="space-y-1 relative" id="locPickerContainer">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Khu vực</label>
                                            <div class="relative">
                                                <input type="text" id="locPickerInputText" readonly class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm cursor-pointer hover:bg-surface-container-low focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Chọn Tỉnh/Thành, Quận/Huyện, Phường/Xã" onclick="toggleLocPanel()">
                                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                                            </div>
                                            
                                            <!-- Dropdown Panel -->
                                            <div id="locPanel" class="loc-panel hidden absolute z-50 w-full mt-1 bg-white border border-outline-variant rounded-xl shadow-lg overflow-hidden" style="display:none;">
                                                <div class="flex border-b border-outline-variant bg-surface-container-lowest">
                                                    <button type="button" id="tab_province" onclick="switchLocTab('province')" class="flex-1 py-3 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">Tỉnh/Thành</button>
                                                    <button type="button" id="tab_district" onclick="switchLocTab('district')" class="flex-1 py-3 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">Quận/Huyện</button>
                                                    <button type="button" id="tab_ward" onclick="switchLocTab('ward')" class="flex-1 py-3 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">Phường/Xã</button>
                                                </div>
                                                <div id="locList" class="max-h-60 overflow-y-auto text-sm"></div>
                                            </div>
                                            
                                            <input type="hidden" id="addr_province">
                                            <input type="hidden" id="addr_district">
                                            <input type="hidden" id="addr_ward">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Địa chỉ cụ thể</label>
                                            <textarea id="addr_specific" rows="2" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all resize-none" placeholder="Số nhà, tên đường..."></textarea>
                                        </div>

                                        <!-- Types & Default -->
                                        <div class="space-y-3 pt-2">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Loại địa chỉ</label>
                                            <div class="flex gap-3">
                                                <button type="button" id="btnTypeHome" onclick="setAddrType('home')" class="flex-1 py-2 rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">home</span> Nhà riêng
                                                </button>
                                                <button type="button" id="btnTypeOffice" onclick="setAddrType('office')" class="flex-1 py-2 rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">domain</span> Công ty
                                                </button>
                                            </div>
                                            <input type="hidden" id="addr_type" value="home">
                                        </div>
                                        
                                        <label class="flex items-center gap-3 cursor-pointer p-3 border border-outline-variant rounded-xl hover:bg-surface-container-lowest transition-colors mt-2">
                                            <input type="checkbox" id="addr_default" class="w-4 h-4 text-primary focus:ring-primary rounded border-outline-variant">
                                            <span class="text-sm font-medium text-on-surface">Đặt làm địa chỉ mặc định</span>
                                        </label>
                                    </div>

                                    <!-- Right: Map -->
                                    <div class="flex flex-col h-full space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Vị trí trên bản đồ</label>
                                            <button type="button" onclick="getCurrentLocation(this)" class="text-primary hover:text-green-800 font-bold text-xs flex items-center gap-1 bg-primary/10 px-3 py-1.5 rounded-full transition-colors active:scale-95">
                                                <span class="material-symbols-outlined text-[16px]">my_location</span>
                                                <span id="gps-btn-text">Định vị GPS</span>
                                            </button>
                                        </div>
                                        <div id="leafletMap" class="w-full flex-1 min-h-[250px] rounded-xl border border-outline-variant z-10"></div>
                                        <input type="hidden" id="addr_lat">
                                        <input type="hidden" id="addr_lng">
                                        
                                        <!-- Actions -->
                                        <div class="grid grid-cols-2 gap-3 pt-4">
                                            <button type="button" onclick="closeAddressModal()" class="py-3 rounded-xl border border-outline-variant text-on-surface font-bold hover:bg-surface-container-low transition-colors">
                                                Hủy
                                            </button>
                                            <button type="button" onclick="saveAddress()" class="py-3 rounded-xl bg-primary text-white font-bold hover:opacity-90 transition-opacity shadow-sm">
                                                Hoàn thành
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
"""
# Insert new form
section_end = '</section>\n\n\n\n                    <!-- 3. Payment Method Section -->'
if section_end in content:
    content = content.replace(section_end, new_form + '\n' + section_end)
else:
    print('Failed to find insertion point')

# Remove old modal
old_modal_start = content.find('<style>\n@media (max-width: 640px) {')
old_modal_end = content.find('</div>\n\n<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>')
if old_modal_start != -1 and old_modal_end != -1:
    content = content[:old_modal_start] + content[old_modal_end + 7:]
else:
    print('Failed to remove old modal')

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print('Success')
