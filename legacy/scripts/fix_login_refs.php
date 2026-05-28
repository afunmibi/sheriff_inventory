<?php
$dir = __DIR__;
$files = glob($dir . "/*.html");
foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('login.html', 'login.php', $content);
    file_put_contents($file, $content);
    echo "Updated: $file\n";
}
?>
