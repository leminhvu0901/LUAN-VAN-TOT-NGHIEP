$baseDir = "c:\Users\ADMIN\Desktop\DA_LVTN\LUAN-VAN-TOT-NGHIEP"

$mapping = @(
    @("resources/views/pages/products/index.blade.php", "public/js/frontend/product-index.js", "js/frontend/product-index.js"),
    @("resources/views/pages/orders/checkout.blade.php", "public/js/frontend/checkout.js", "js/frontend/checkout.js"),
    @("resources/views/components/user-profile-modal.blade.php", "public/js/frontend/user-profile-modal.js", "js/frontend/user-profile-modal.js"),
    @("resources/views/components/navbar.blade.php", "public/js/frontend/navbar.js", "js/frontend/navbar.js"),
    @("resources/views/components/head.blade.php", "public/js/frontend/tailwind-config-head.js", "js/frontend/tailwind-config-head.js"),
    @("resources/views/components/footer.blade.php", "public/js/frontend/footer.js", "js/frontend/footer.js"),
    @("resources/views/auth/verify-otp.blade.php", "public/js/frontend/verify-otp.js", "js/frontend/verify-otp.js"),
    @("resources/views/auth/reset-password.blade.php", "public/js/frontend/reset-password.js", "js/frontend/reset-password.js"),
    @("resources/views/auth/register.blade.php", "public/js/frontend/register.js", "js/frontend/register.js"),
    @("resources/views/auth/login.blade.php", "public/js/frontend/login.js", "js/frontend/login.js"),
    @("resources/views/auth/forgot-password.blade.php", "public/js/frontend/forgot-password.js", "js/frontend/forgot-password.js"),
    @("resources/views/pages/profile.blade.php", "public/js/frontend/profile-inline.js", "js/frontend/profile-inline.js")
)

foreach ($item in $mapping) {
    $viewPath = Join-Path $baseDir $item[0]
    $jsPath = Join-Path $baseDir $item[1]
    $assetPath = $item[2]

    if (Test-Path $viewPath) {
        $content = Get-Content -Path $viewPath -Raw -Encoding UTF8
        $matches = [regex]::Matches($content, '(?s)<script>(.*?)</script>')
        
        if ($matches.Count -gt 0) {
            $jsContent = ""
            foreach ($match in $matches) {
                $jsContent += $match.Groups[1].Value.Trim() + "`r`n`r`n"
            }
            
            $jsDir = Split-Path $jsPath
            if (-not (Test-Path $jsDir)) {
                New-Item -ItemType Directory -Force -Path $jsDir | Out-Null
            }
            
            Set-Content -Path $jsPath -Value $jsContent -Encoding UTF8
            
            $newContent = [regex]::Replace($content, '(?s)<script>(.*?)</script>', "<script src=`"{{ asset('$assetPath') }}`"></script>")
            Set-Content -Path $viewPath -Value $newContent -Encoding UTF8
            
            Write-Host "Processed $viewPath -> $jsPath"
        } else {
            Write-Host "No inline scripts found in $viewPath"
        }
    }
}
