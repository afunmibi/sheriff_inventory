<?php
$files = ["dashboard.html", "ecommerce_dashboard.html", "products.html", "inventory.html", "categories.html", "sales.html", "returns.html", "suppliers.html", "purchase-orders.html", "reports.html", "settings.html"];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Add logout button to topbar-right if it doesn't have one
    if (strpos($content, 'topbar-right') !== false && strpos($content, 'headerLogoutBtn') === false) {
        $content = str_replace('<div class="topbar-right">', '<div class="topbar-right"><button id="headerLogoutBtn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;" onmouseover="this.style.background=\'#ef4444\'; this.style.color=\'white\'" onmouseout="this.style.background=\'rgba(239, 68, 68, 0.1)\'; this.style.color=\'#ef4444\'"><span>🚪</span> Sign Out</button>', $content);
        
        // Add JS handler if not already present
        if (strpos($content, 'headerLogoutBtn') !== false) {
            $js_handler = "
            const hLogout = document.getElementById('headerLogoutBtn');
            if (hLogout) hLogout.onclick = () => { if(confirm('Are you sure you want to sign out?')) new AuthService().logout().then(() => window.location.href = 'login.php'); };";
            
            // Inject into the end of DOMContentLoaded or before </body>
            if (strpos($content, 'DOMContentLoaded') !== false) {
                $content = str_replace('// Initial UI Update', $js_handler . "\n// Initial UI Update", $content);
                // Also handle cases like dashboard.html which might not have 'Initial UI Update' comment
                if (strpos($content, 'loadDashboard();') !== false) {
                   $content = str_replace('loadDashboard();', $js_handler . "\n        loadDashboard();", $content);
                }
            }
        }
    }
    
    file_put_contents($file, $content);
    echo "Added topbar logout to $file\n";
}
?>
