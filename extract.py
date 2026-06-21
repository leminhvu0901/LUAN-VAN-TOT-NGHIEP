import os
import re

base_dir = r"c:\Users\ADMIN\Desktop\DA_LVTN\LUAN-VAN-TOT-NGHIEP"

mapping = [
    ("resources/views/admin/layouts/app.blade.php", "public/js/admin/layout.js", "js/admin/layout.js"),
    ("resources/views/pages/products/show.blade.php", "public/js/frontend/product-show.js", "js/frontend/product-show.js"),
    ("resources/views/pages/products/review.blade.php", "public/js/frontend/product-review.js", "js/frontend/product-review.js"),
    ("resources/views/pages/products/index.blade.php", "public/js/frontend/product-index.js", "js/frontend/product-index.js"),
    ("resources/views/pages/orders/checkout.blade.php", "public/js/frontend/checkout.js", "js/frontend/checkout.js"),
    ("resources/views/pages/profile.blade.php", "public/js/frontend/profile-inline.js", "js/frontend/profile-inline.js"),
    ("resources/views/components/user-profile-modal.blade.php", "public/js/frontend/user-profile-modal.js", "js/frontend/user-profile-modal.js"),
    ("resources/views/components/navbar.blade.php", "public/js/frontend/navbar.js", "js/frontend/navbar.js"),
    ("resources/views/components/head.blade.php", "public/js/frontend/tailwind-config.js", "js/frontend/tailwind-config.js"),
    ("resources/views/components/footer.blade.php", "public/js/frontend/footer.js", "js/frontend/footer.js"),
    ("resources/views/auth/verify-otp.blade.php", "public/js/frontend/verify-otp.js", "js/frontend/verify-otp.js"),
    ("resources/views/auth/reset-password.blade.php", "public/js/frontend/reset-password.js", "js/frontend/reset-password.js"),
    ("resources/views/auth/register.blade.php", "public/js/frontend/register.js", "js/frontend/register.js"),
    ("resources/views/auth/login.blade.php", "public/js/frontend/login.js", "js/frontend/login.js"),
    ("resources/views/auth/forgot-password.blade.php", "public/js/frontend/forgot-password.js", "js/frontend/forgot-password.js")
]

# regex to find <script>...</script> but ignoring <script src="...">
script_pattern = re.compile(r'<script>(.*?)</script>', re.DOTALL)

for rel_view_path, rel_js_path, asset_path in mapping:
    view_path = os.path.join(base_dir, rel_view_path.replace('/', '\\'))
    js_path = os.path.join(base_dir, rel_js_path.replace('/', '\\'))
    
    if not os.path.exists(view_path):
        print(f"Skipping {view_path}, does not exist.")
        continue
        
    with open(view_path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    matches = script_pattern.findall(content)
    
    if matches:
        # Create JS file directory if not exists
        os.makedirs(os.path.dirname(js_path), exist_ok=True)
        
        js_content = ""
        for m in matches:
            js_content += m.strip() + "\n\n"
            
        # Append to JS file if it exists, otherwise create
        with open(js_path, 'w', encoding='utf-8') as f:
            f.write(js_content)
            
        # Replace script tags with asset link
        replacement = f'<script src="{{{{ asset(\'{asset_path}\') }}}}"></script>'
        new_content = script_pattern.sub(replacement, content)
        
        with open(view_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
            
        print(f"Processed {view_path} -> {js_path}")
    else:
        print(f"No inline scripts found in {view_path}")

