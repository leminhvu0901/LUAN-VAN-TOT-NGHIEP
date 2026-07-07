<?php
$dir = __DIR__ . '/app/Http/Controllers';
$dest = $dir . '/Frontend';
if (!is_dir($dest)) {
    mkdir($dest, 0777, true);
}
$files = ['ReviewController.php', 'ProfileController.php', 'ProductController.php', 'OrderController.php', 'MomoController.php', 'CartController.php', 'AuthController.php'];

foreach ($files as $file) {
    $path = $dir . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = str_replace('namespace App\Http\Controllers;', 'namespace App\Http\Controllers\Frontend;', $content);
        file_put_contents($path, $content);
        rename($path, $dest . '/' . $file);
    }
}

$routesPath = __DIR__ . '/routes/web.php';
$routesContent = file_get_contents($routesPath);
foreach ($files as $file) {
    $className = str_replace('.php', '', $file);
    $routesContent = str_replace(
        "[App\Http\Controllers\\$className::class", 
        "[App\Http\Controllers\Frontend\\$className::class", 
        $routesContent
    );
}
file_put_contents($routesPath, $routesContent);
echo "\n======================================================\n";
echo "Thanh cong! 7 file Controller da duoc di chuyen vao thu muc Frontend.\n";
echo "Namespace va Route cung da duoc cap nhat tu dong!\n";
echo "======================================================\n";
