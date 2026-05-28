<?php
$files = ["dashboard.html", "products.html", "inventory.html", "categories.html", "sales.html", "returns.html", "suppliers.html", "purchase-orders.html", "reports.html", "settings.html"];

// Operations Sidebar
$ops_nav = '        <nav class="sidebar-nav">
          <a href="gateway.html" class="nav-item" style="background: rgba(34, 211, 238, 0.1); border-bottom: 2px solid rgba(34, 211, 238, 0.1); margin-bottom: 10px; border-radius: 0;"><span>🏠</span> Hub Selection</a>
          <a href="dashboard.html" class="nav-item {{DASH_ACTIVE}}"><span>📊</span> Ops Dashboard</a>
          <div style="margin: 15px 20px; border-top: 1px solid rgba(255,255,255,0.05);"></div>
          <a href="products.html?hub=ops" class="nav-item {{PROD_ACTIVE}}"><span>📦</span> Full Inventory</a>
          <a href="inventory.html" class="nav-item {{INV_ACTIVE}}"><span>📋</span> Stock Audit</a>
          <a href="categories.html" class="nav-item {{CAT_ACTIVE}}"><span>🏷️</span> Categories</a>
          <a href="sales.html?hub=ops" class="nav-item {{SALE_ACTIVE}}"><span>💰</span> POS Sales</a>
          <a href="suppliers.html" class="nav-item {{SUP_ACTIVE}}"><span>🚚</span> Suppliers</a>
          <a href="settings.html?hub=ops" class="nav-item {{SET_ACTIVE}}"><span>⚙️</span> Admin Settings</a>
        </nav>';

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $current_file_active = "";
    if ($file === "dashboard.html") $current_file_active = "DASH";
    if ($file === "products.html") $current_file_active = "PROD";
    if ($file === "inventory.html") $current_file_active = "INV";
    if ($file === "categories.html") $current_file_active = "CAT";
    if ($file === "sales.html") $current_file_active = "SALE";
    if ($file === "suppliers.html") $current_file_active = "SUP";
    if ($file === "settings.html") $current_file_active = "SET";

    $specific_nav = str_replace(['{{DASH_ACTIVE}}', '{{PROD_ACTIVE}}', '{{INV_ACTIVE}}', '{{CAT_ACTIVE}}', '{{SALE_ACTIVE}}', '{{SUP_ACTIVE}}', '{{SET_ACTIVE}}'], '', $ops_nav);
    if ($current_file_active) $specific_nav = str_replace('{{' . $current_file_active . '_ACTIVE}}', 'active', $specific_nav);
    
    $pattern = '/<nav class="sidebar-nav">.*?<\/nav>/s';
    $content = preg_replace($pattern, $specific_nav, $content);
    file_put_contents($file, $content);
}

// Storefront Sidebar
$ecomm_nav = '        <nav class="sidebar-nav">
          <a href="gateway.html" class="nav-item" style="background: rgba(251, 191, 36, 0.1); border-bottom: 2px solid rgba(251, 191, 36, 0.1); margin-bottom: 10px; border-radius: 0;"><span>🏠</span> Hub Selection</a>
          <a href="ecommerce_dashboard.html" class="nav-item active"><span>🌐</span> Store Dashboard</a>
          <div style="margin: 20px 20px; border-top: 1px solid rgba(255,255,255,0.05);"></div>
          <a href="products.html?hub=store" class="nav-item"><span>✨</span> Web Products</a>
          <a href="sales.html?hub=store" class="nav-item"><span>🛒</span> Web Orders</a>
          <a href="settings.html?hub=store" class="nav-item"><span>⚙️</span> Front Settings</a>
        </nav>';
$econtent = file_get_contents("ecommerce_dashboard.html");
$econtent = preg_replace($pattern, $ecomm_nav, $econtent);
file_put_contents("ecommerce_dashboard.html", $econtent);
?>
