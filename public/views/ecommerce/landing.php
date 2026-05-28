<?php
$baseDir = dirname(__DIR__, 3);
require_once $baseDir . '/app/config/Config.php';
require_once $baseDir . '/app/config/DatabaseConnection.php';
require_once $baseDir . '/app/models/Product.php';

function format_currency($amount, $currency = 'NGN') {
    $symbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€'];
    $symbol = $symbols[$currency] ?? '₦';
    return $symbol . number_format($amount, 2);
}

Config::load();

// Compute correct app base URL for JS (ignores Config default which may point to wrong port)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$appRoot = str_replace('\\', '/', realpath($baseDir));
$basePath = str_replace($docRoot, '', $appRoot);
$storeBaseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/';

$productModel = new Product();
$products = $productModel->getProductsWithStock();

$db = DatabaseConnection::getInstance();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number' LIMIT 1");
$setting = $stmt->fetch_assoc();
$whatsapp_number_raw = $setting['setting_value'] ?? "+234 803 248 8020";
$whatsapp_number = preg_replace('/[^0-9]/', '', $whatsapp_number_raw); 

$stmt_msg = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'storefront_welcome' LIMIT 1");
$setting_msg = $stmt_msg->fetch_assoc();
$whatsapp_text = $setting_msg['setting_value'] ?? "Hi, I'm interested in purchasing: ";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHERIFF SHEVVY ENTERPRISES - Premium Laptops & Computers</title>
    <link rel="stylesheet" href="<?php echo $storeBaseUrl; ?>css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>var STORE_URL = '<?php echo $storeBaseUrl; ?>';
    var WHATSAPP_NUM = '<?php echo $whatsapp_number; ?>';
    var WHATSAPP_MSG = '<?php echo addslashes($whatsapp_text); ?>';</script>
    <style>
        :root {
            --primary: #f5c04a;
            --primary-dark: #d97706;
            --secondary: #9ca3af;
            --bg-light: #020617;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --white: #1e293b;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --glow: rgba(245, 192, 74, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #020617 !important;
            color: #f8fafc;
            line-height: 1.6;
            background-attachment: fixed;
        }

        header {
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo { display: flex; align-items: center; gap: 12px; }
        .logo .logo-icon {
            width: 40px; height: 40px;
            background: #f5c04a;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .logo .logo-icon svg { width: 22px; height: 22px; }
        .logo h1 {
            font-size: 1.6rem;
            font-weight: 950;
            color: #f5c04a !important;
            display: inline-block;
            letter-spacing: -1.5px;
            line-height: 1;
        }
        .logo .subtext {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }

        nav { display: flex; align-items: center; gap: 6px; }
        nav a {
            text-decoration: none;
            color: #ffffff;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 30px;
            transition: all 0.3s;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        nav a:hover { color: #fff; }
        nav a.active {
            background: #f5c04a;
            color: #020617;
        }

        .login-btn {
            background: #f5c04a !important;
            color: #020617 !important;
            padding: 12px 28px !important;
            border-radius: 14px !important;
            font-weight: 800;
            font-size: 0.85rem !important;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.3s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 192, 74, 0.3);
        }

        .currency-select {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            padding: 5px 8px;
            border-radius: 8px;
            font: inherit;
            font-size: 0.75rem;
            cursor: pointer;
            outline: none;
        }
        .currency-select option { background: #020617; color: #fff; }

        .hamburger {
            display: none; flex-direction: column; gap: 5px;
            background: none; border: none; cursor: pointer; padding: 8px;
        }
        .hamburger span {
            display: block; width: 22px; height: 2px;
            background: #94a3b8; border-radius: 2px;
            transition: all 0.3s;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        @media (max-width: 768px) {
            nav { display: none; flex-direction: column; position: absolute; top: 100%; left: 0; right: 0; background: rgba(2,6,23,0.98); backdrop-filter: blur(24px); border-bottom: 1px solid var(--glass-border); padding: 16px; gap: 4px; }
            nav.open { display: flex; }
            nav a { padding: 12px 16px; }
            .hamburger { display: flex; }
        }

        .hero-slider {
            position: relative; width: 100%; height: 500px; overflow: hidden;
            margin-top: 60px;
        }
        .slide {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease;
            display: flex; align-items: center; justify-content: center; text-align: center;
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }
        .slide.active { opacity: 1; z-index: 1; }
        .slide-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, rgba(2, 6, 23, 0.4) 0%, transparent 50%);
        }
        .slide-content { position: relative; z-index: 2; padding: 100px 5% 0; }
        .slide-content h2 { 
            font-size: 3.5rem; 
            font-weight: 900; 
            background: linear-gradient(to bottom, #fff 50%, #f5c04a);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 16px; 
            line-height: 1; 
            letter-spacing: -2px; 
            text-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .slide-content .btn-explore {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #f5c04a, #d97706);
            color: #020617;
            text-decoration: none;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 16px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            cursor: pointer;
        }
        .slide-content .btn-explore:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -10px rgba(245, 192, 74, 0.4);
            filter: brightness(1.08);
        }
        .slider-dots {
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 8px; z-index: 5;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: 0.3s; }
        .dot.active { background: var(--primary); width: 24px; border-radius: 8px; }

/* ── Shop Wrapper: Sidebar + Products ── */
.shop-wrapper {
    display: flex;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 5% 50px;
}
.shop-sidebar {
    width: 10%;
    min-width: 160px;
    flex-shrink: 0;
    align-self: flex-start;
    position: sticky;
    top: 100px;
}
.sidebar-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid rgba(255,255,255,0.06);
}
.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.cat-item {
    padding: 10px 14px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    border-radius: 10px;
    transition: all 0.2s;
    margin-bottom: 2px;
}
        .cat-item:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }
        .cat-item.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            font-weight: 700;
        }
.shop-main {
    flex: 1;
    min-width: 0;
}

        .product-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .product-card {
            background: #0b1222;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            transition: all 0.4s cubic-bezier(0.2, 1, 0.3, 1);
            display: flex;
            flex-direction: row;
            border: 1px solid rgba(255,255,255,0.05);
            padding: 30px;
            position: relative;
            gap: 30px;
        }
        .product-card:hover {
            border-color: rgba(245, 192, 74, 0.3);
            background: #0f172a;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .product-image {
            width: 380px;
            min-width: 380px;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }
        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 8px 16px 8px 4px;
        }
        
        .product-category {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            color: rgba(255,255,255,0.5);
            margin-bottom: 10px;
            letter-spacing: 1.5px;
        }

        .product-name {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: -0.5px;
            transition: color 0.3s;
        }
        .product-card:hover .product-name {
            color: #fff;
        }

        .product-description {
            font-size: 0.85rem;
            color: #fff;
            font-weight: 700;
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
        }

        .product-price span {
            font-size: 1.5rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .stock-badge {
            font-size: 0.65rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .stock-in { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.25); }
        .stock-out { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); }

        .card-actions {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            padding: 14px 32px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.2, 1, 0.2, 1);
            cursor: pointer;
            border: none;
        }

        .btn-details {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-details:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .btn-add-cart {
            flex: 1;
            background: #000000;
            color: #f5c04a;
            border: 1px solid #f5c04a;
            padding: 14px 32px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-add-cart:hover {
            background: #f5c04a;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 192, 74, 0.2);
        }
        .filter-chip.active { background: var(--primary); color: var(--bg-light); border-color: var(--primary);
            box-shadow: 0 10px 20px -5px rgba(107, 114, 128, 0.3);
        }

        .btn-share {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 16px;
            padding: 0;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .btn-share:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .share-popup {
            position: absolute;
            bottom: calc(100% + 8px);
            right: 0;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 8px;
            display: none;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            z-index: 20;
            min-width: 44px;
        }
        .share-popup.open { display: flex; }
        .share-popup a {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
            color: #fff;
        }
        .share-popup a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .share-popup a svg { width: 18px; height: 18px; }

        .cart-drawer {
            position: fixed; top: 0; right: -400px; width: 400px; height: 100%;
            background: #0f172a; border-left: 1px solid var(--glass-border);
            z-index: 1100; transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 40px; display: flex; flex-direction: column;
            box-shadow: -20px 0 50px rgba(0,0,0,0.5);
        }
        .cart-drawer.open { right: 0; }
        .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .cart-title { font-size: 1.5rem; font-weight: 800; color: white; }
        .cart-items { flex-grow: 1; overflow-y: auto; padding-right: 10px; }
        .cart-item { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--glass-border); position: relative; }
        .cart-item-img { width: 60px; height: 60px; background: #020617; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .cart-item-img img { max-width: 80%; max-height: 80%; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-size: 0.95rem; font-weight: 600; color: white; margin-bottom: 5px; }
        .cart-item-price { color: #fff; font-weight: 800; font-size: 0.9rem; }
        .cart-item-remove { cursor: pointer; color: #ef4444; font-size: 0.8rem; position: absolute; right: 0; bottom: 20px; }
        .cart-footer { margin-top: 30px; padding-top: 30px; border-top: 2px solid var(--glass-border); }
        .cart-total { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; color: white; margin-bottom: 25px; }
        .btn-checkout { width: 100%; padding: 18px; background: var(--primary); color: #020617; border: none; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }

        .cart-trigger {
            position: fixed; top: 120px; right: 30px; width: 60px; height: 60px;
            background: #0f172a; border: 1px solid var(--glass-border);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 500; cursor: pointer;
            transition: transform 0.3s;
        }
        .cart-trigger:hover { transform: scale(1.1); background: rgba(255,255,255,0.15); }
        .cart-trigger:hover svg { fill: #020617; }
        .cart-count { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 12px; font-weight: 800; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #0f172a; }
        .cart-trigger svg { width: 24px; height: 24px; fill: white; }

        .payment-option {
            background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 20px; border-radius: 15px;
            display: flex; align-items: center; gap: 20px; cursor: pointer; transition: all 0.3s; margin-bottom: 12px;
        }
        .payment-option:hover { background: rgba(255,255,255,0.05); }
        .payment-option.active { border-color: var(--primary); background: rgba(251,191,36,0.05); }
        .payment-radio { width: 20px; height: 20px; border: 2px solid #64748b; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .payment-option.active .payment-radio { border-color: var(--primary); }
        .payment-option.active .payment-radio::after { content: ''; width: 10px; height: 10px; background: var(--primary); border-radius: 50%; }
        .payment-info h4 { color: white; font-size: 0.9rem; margin-bottom: 2px; text-transform: uppercase; }
        .payment-info p { color: #fff; font-size: 0.75rem; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 6, 23, 0.9);
            backdrop-filter: blur(10px);
            display: none; justify-content: center; align-items: center;
            z-index: 1000;
        }

        .modal-close {
            position: absolute; top: 30px; right: 30px;
            background: rgba(255,255,255,0.05); border: none;
            color: white; width: 46px; height: 46px; border-radius: 50%;
            cursor: pointer; font-size: 28px; z-index: 100; display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.15); transform: rotate(90deg); }

        .modal-content {
            background: #0b1222 !important;
            width: 1000px; max-width: 95vw;
            border-radius: 40px;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex; overflow: hidden;
            position: relative;
            box-shadow: 0 50px 100px rgba(0,0,0,0.8);
        }
        .modal-left { flex: 1.1; background: #020617; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; position: relative; }
        .modal-right { flex: 0.9; padding: 60px; overflow-y: auto; background: #0b1222 !important; display: flex; flex-direction: column; }
        .modal-title { 
            font-size: 3.2rem; 
            font-weight: 900; 
            margin-bottom: 15px; 
            color: #ffffff;
            letter-spacing: -2px; 
            line-height: 1;
        }
        .modal-price { 
            font-size: 2.2rem; 
            font-weight: 900; 
            color: #fff; 
            margin-bottom: 40px; 
            letter-spacing: -1px; 
        }
        .modal-desc-title { font-size: 0.8rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; color: #fff; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .input-field { width: 100%; padding: 16px 20px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; color: #fff; font-size: 1rem; font-family: inherit; outline: none; transition: all 0.3s ease; }
        .input-field:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(251,191,36,0.08); background: rgba(255,255,255,0.06); }
        .input-field::placeholder { color: rgba(255,255,255,0.4); }
        .checkout-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .checkout-row { grid-template-columns: 1fr; } }

        .modal-contact-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-contact { padding: 10px 14px; border-radius: 10px; font-weight: 700; font-size: 0.78rem; text-align: center; cursor: pointer; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; }
        .btn-whatsapp { background: #25d366; color: #fff; }
        .btn-whatsapp:hover { background: #1ebe5d; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3); }
        .btn-call { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
        .btn-call:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
        .btn-checkout-modal { padding: 12px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; text-align: center; cursor: pointer; transition: all 0.3s; width: 100%; border: none; background: #f5c04a; color: #020617; text-transform: uppercase; letter-spacing: 1px; }
        .btn-checkout-modal:hover { background: #f59e0b; transform: translateY(-2px); }

        .payment-option { position: relative; }
        .payment-tooltip { position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 18px; font-size: 0.75rem; color: #fff; white-space: nowrap; opacity: 0; visibility: hidden; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.4); z-index: 20; pointer-events: none; }
        .payment-option:hover .payment-tooltip { opacity: 1; visibility: visible; }
        .payment-tooltip a { color: #25d366; font-weight: 700; text-decoration: none; pointer-events: auto; }
        .payment-tooltip a:hover { text-decoration: underline; }

        .checkout-summary-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .checkout-summary-item .cs-name { color: #fff; font-weight: 600; font-size: 0.95rem; }
        .checkout-summary-item .cs-qty { color: #fff; font-size: 0.8rem; margin-top: 4px; }
        .checkout-summary-item .cs-price { color: #fff; font-weight: 800; font-size: 0.95rem; }
        
        .cart-drawer {
            position: fixed; top: 0; right: -500px; width: 500px; height: 100%;
            background: #020617 !important; border-left: 1px solid rgba(255,255,255,0.05);
            z-index: 2000; transition: right 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            padding: 0; display: flex; flex-direction: column;
            box-shadow: -30px 0 60px rgba(0,0,0,0.8);
        }
        .checkout-modal {
            background: #020617;
            width: 1000px; max-width: 95vw;
            border-radius: 24px;
            display: flex; flex-direction: column;
            overflow: hidden; border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 50px 100px rgba(0,0,0,0.8);
        }
        .checkout-left { flex: 1.2; padding: 40px; background: #020617; }
        .checkout-right { flex: 0.8; padding: 40px; background: #020617; border-left: 1px solid rgba(255,255,255,0.05); }
        .cart-drawer.open { right: 0; }
        .cart-main-header { padding: 40px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .cart-items { padding: 30px; flex: 1; overflow-y: auto; }
        .cart-item-new { display: flex; gap: 20px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .cart-item-qty { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 5px 12px; border-radius: 10px; }
        .cart-item-qty button { background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; font-weight: 700; }
        .qty-val { font-weight: 800; min-width: 20px; text-align: center; }
        .cart-bottom { padding: 40px; background: rgba(2,6,23,0.4); border-top: 1px solid rgba(255,255,255,0.05); }
        .btn-proceed { width: 100%; padding: 20px; background: var(--primary); color: #020617; border-radius: 18px; font-weight: 800; font-size: 1.1rem; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }

        /* ── PAGE WRAP (sticky footer) ── */
        html, body { height: 100%; }
        .page-wrap { min-height: 100vh; display: flex; flex-direction: column; }
        .page-main { flex: 1; }

        .site-footer {
            background: #020617 !important;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 100px 5% 0;
            margin-top: 120px;
        }
        .footer-grid {
            max-width: 1400px; margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 60px;
            padding-bottom: 80px;
        }
        .footer-col h3 {
            font-size: 0.8rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 2px;
            color: #fff; margin-bottom: 30px;
        }
        .footer-col .brand { 
            font-size: 1.8rem; font-weight: 900; color: var(--primary); margin-bottom: 20px; 
            letter-spacing: -1px;
        }
        .footer-col p { color: #fff; font-size: 0.95rem; line-height: 1.8; }
        .footer-col a {
            display: block; color: #fff; text-decoration: none;
            font-size: 0.95rem; padding: 8px 0;
            transition: color 0.3s;
        }
        .footer-col a:hover { color: #fff; }
        .footer-social { display: flex; gap: 15px; margin-top: 30px; }
        .footer-social a {
            width: 44px; height: 44px;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
            background: rgba(255,255,255,0.02);
        }
        .footer-social a:hover { border-color: var(--primary); background: rgba(251,191,36,0.1); transform: translateY(-5px); }
        .footer-social a svg { width: 18px; height: 18px; fill: white; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 40px 5%;
            text-align: center;
            color: #fff;
            font-size: 0.9rem;
        }
        .footer-bottom strong { color: var(--primary); }
        .footer-signature {
            margin-top: 8px;
            color: #fff;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        .footer-signature span { color: var(--primary); }
        @media (max-width: 1024px) {
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 40px; }
        }
        @media (max-width: 600px) {
            .footer-grid { grid-template-columns: 1fr; gap: 40px; }
            .site-footer { padding: 60px 5% 0; }
        }

        .whatsapp-float {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; background: #25d366;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
            z-index: 500; transition: transform 0.3s;
            cursor: pointer;
        }
        .whatsapp-float:hover { transform: scale(1.1); }
        .whatsapp-float svg { width: 32px; height: 32px; fill: white; }

        .search-bar {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 60px; padding: 8px 8px 8px 24px;
            margin-bottom: 24px; backdrop-filter: blur(12px);
            transition: all 0.3s;
        }
        .search-bar:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(245, 192, 74, 0.1);
            background: rgba(255,255,255,0.08);
        }
        .search-bar input {
            flex: 1; padding: 18px 25px;
            background: transparent; border: none;
            color: #f8fafc; font-size: 1.1rem;
            outline: none; font-family: inherit;
        }
        .search-bar input::placeholder { color: rgba(255,255,255,0.4); }
        .search-bar button {
            padding: 0 30px;
            background: transparent; border: none;
            color: #fff; cursor: pointer;
            display: flex; align-items: center;
            transition: all 0.2s;
        }
        .search-bar button:hover { color: #fff; }
        .search-bar button svg { width: 24px; height: 24px; stroke-width: 2.5; }

        .scroll-top {
            position: fixed; bottom: 30px; left: 30px;
            width: 46px; height: 46px;
            border-radius: 50%;
            background: rgba(107,114,128,0.12);
            border: 1px solid rgba(107,114,128,0.2);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 99;
            opacity: 0; visibility: hidden;
            transform: translateY(20px);
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
            backdrop-filter: blur(12px);
        }
        .scroll-top.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .scroll-top:hover { background: rgba(107,114,128,0.2); transform: translateY(-3px); }
        .scroll-top svg { width: 20px; height: 20px; }

        .product-badge {
            position: absolute; top: 12px; left: 12px; z-index: 10;
            padding: 5px 14px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 900; text-transform: uppercase;
            letter-spacing: 0.8px; pointer-events: none;
        }
        .badge-featured {
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.3s ease;
        }
        .product-card:hover .badge-featured {
            background: var(--primary);
            color: #020617;
            border-color: var(--primary);
        }

        .low-stock-msg {
            font-size: 0.75rem; font-weight: 700; color: #f87171;
            margin-top: 4px;
        }

        .toast-container {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            z-index: 9999; pointer-events: none;
        }
        .toast {
            background: #1e293b; color: white;
            padding: 16px 30px; border-radius: 16px;
            font-weight: 700; font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            opacity: 0; transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.2,1,0.3,1);
            pointer-events: auto;
            display: flex; align-items: center; gap: 12px;
            backdrop-filter: blur(12px);
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast-icon { font-size: 1.4rem; }

        @media (max-width: 768px) {
            .hero-slider h2 { font-size: 2.2rem; }
            .product-card { flex-direction: column; }
            .product-image { width: 100%; min-width: auto; height: 340px; }
            .product-description { font-size: 0.8rem; }
            .shop-wrapper { flex-direction: column; }
            .shop-sidebar { width: 100%; min-width: auto; }
            .modal-content { flex-direction: column; }
            .modal-left { height: 300px; }
            .modal-right { padding: 40px 20px; }
            .cart-drawer { width: 100%; right: -100%; }
        }
    </style>
</head>
<body>

<div class="page-wrap">
<div class="page-main">

<header>
    <a href="./" style="text-decoration:none; display:flex; align-items:center;">
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#020617" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div>
            <h1>SHERIFF SHEVVY</h1>
            <div class="subtext">ENTERPRISE SUITE</div>
        </div>
    </div>
    </a>
    <button class="hamburger" id="hamburger" onclick="toggleNav()">
        <span></span><span></span><span></span>
    </button>
    <nav id="navLinks">
        <a href="./">Home</a>
        <a href="#categories">Categories</a>
        <a href="#products">Products</a>
    </nav>
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="login.php" class="login-btn">Admin Login</a>
        <div class="header-cart" onclick="toggleCart()" style="position:relative; cursor:pointer;">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:26px; height:26px;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span class="cart-count" id="cartCountHeader" style="position:absolute; top:-8px; right:-8px; background:#ef4444; color:white; font-size:10px; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">0</span>
        </div>
    </div>
</header>

<!-- Cart trigger removed as it is now in header -->

<div class="cart-drawer" id="cartDrawer">
    <div class="cart-main-header">
        <h2 class="cart-title" style="font-size: 2.2rem; font-weight: 900; color: #fff; letter-spacing: -1px;">Your Order</h2>
        <button class="modal-close" style="position: static;" onclick="toggleCart()">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-bottom">
        <div class="cart-total" style="display:flex; justify-content:space-between; margin-bottom:30px; font-size:1.4rem; font-weight:900;">
            <span style="color:#64748b;">Total Amount</span>
            <span id="cartTotal" data-ngn="0" style="color:white;">₦0.00</span>
        </div>
        <button class="btn-proceed" onclick="openCheckout()">Proceed to Checkout</button>
    </div>
</div>

<div class="modal-overlay" id="checkoutModal">
    <div class="modal-content" style="width: 1000px; max-width: 95vw; display: flex; flex-direction: row; padding: 0; overflow: hidden; height: 85vh;">
        <div class="checkout-left" style="flex: 1.2; padding: 60px; overflow-y: auto; background: #0f172a;">
            <h2 class="modal-title" style="font-size: 2.4rem;">Checkout Details</h2>
            <p style="color: #64748b; margin-bottom: 40px; font-size: 1rem;">Review your order and choose a payment method.</p>
            <form class="checkout-form" id="checkoutForm">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" class="input-field" name="customer_name" required placeholder="John Doe">
                </div>
                <div class="checkout-row">
                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="tel" class="input-field" name="customer_phone" required placeholder="080 123 4567">
                    </div>
                    <div class="input-group">
                        <label>Delivery Address</label>
                        <input type="text" class="input-field" name="customer_address" required placeholder="Street, City">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Email Address</label>
                    <input type="email" class="input-field" name="customer_email" id="customer_email" placeholder="you@example.com">
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Create Password (optional)</label>
                    <input type="password" class="input-field" name="customer_password" id="customer_password" placeholder="Leave blank to skip account creation">
                </div>
                
                <div style="margin-top: 30px;">
                    <label style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Payment Method</label>
                    <div style="display:grid; gap:12px; margin-top:20px;" id="paymentMethods">
                        <div class="payment-option active" data-value="Cash on Delivery" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>Cash on Delivery</h4><p>Pay cash when you receive your order</p></div>
                        </div>
                        <div class="payment-option" data-value="Access Bank" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>Access Bank</h4><p>Account: 1234567890 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                        <div class="payment-option" data-value="GTBank" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>GTBank</h4><p>Account: 0123456789 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                        <div class="payment-option" data-value="Moniepoint" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>Moniepoint</h4><p>Account: 2345678901 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                        <div class="payment-option" data-value="Opay" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>Opay</h4><p>Account: 3456789012 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                        <div class="payment-option" data-value="PalmPay" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>PalmPay</h4><p>Account: 4567890123 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                        <div class="payment-option" data-value="Kuda" onclick="selectPayment(this)">
                            <div class="payment-radio"></div>
                            <div class="payment-info"><h4>Kuda</h4><p>Account: 5678901234 | Name: Sheriff Shevvy</p></div>
                            <div class="payment-tooltip">Send proof of payment to <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank">WhatsApp</a></div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="selectedPayment" value="Cash on Delivery">
                
                <button type="submit" class="btn-proceed" style="margin-top:40px; padding:25px;">Place Order Now</button>
            </form>
        </div>
        <div class="checkout-right" style="flex: 0.8; background: #020617; padding: 60px 50px; display: flex; flex-direction: column; border-left: 1px solid rgba(255,255,255,0.05);">
            <button class="modal-close" onclick="closeCheckout()">&times;</button>
            <h2 style="font-size: 1.5rem; font-weight: 900; color: #fff; margin-bottom: 35px; letter-spacing: -0.5px;">Your Order</h2>
            <div id="checkoutSummaryItems" style="flex: 1; overflow-y: auto;"></div>
            <div class="checkout-total-bar" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 35px; margin-top: 15px;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:1.3rem; font-weight:900;">
                    <span style="color:#64748b;">Total</span>
                    <span id="checkoutTotalDisplay" style="color:#fff;">&#x20A6;0.00</span>
                </div>
                <div id="checkoutDiscountRow" style="display:none; justify-content:space-between; align-items:center; margin-top:12px; font-size:0.95rem;">
                    <span style="color:#059669; font-weight:600;">Discount</span>
                    <span id="checkoutDiscountDisplay" style="color:#059669; font-weight:700;">-&#x20A6;0</span>
                </div>
                <div style="margin-top: 20px; display:flex; gap:10px;">
                    <input type="text" id="promoCodeInput" placeholder="Promo code" style="flex:1; padding:12px 16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; color:#fff; font-size:0.85rem; outline:none; font-family:inherit;">
                    <button onclick="applyPromoCode()" style="padding:12px 20px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-weight:700; cursor:pointer; transition:0.3s;">Apply</button>
                </div>
                <div id="promoResult" style="margin-top:8px; font-size:0.8rem;"></div>
            </div>
        </div>
    </div>
</div>

<section class="hero-slider" id="heroSlider">
    <div id="slidesContainer"></div>
    <div class="slider-dots" id="sliderDots"></div>
</section>

<!-- Shop Section: Categories Sidebar + Products -->
<div class="shop-wrapper" id="products">
    <aside class="shop-sidebar" id="categories" style="scroll-margin-top:120px;">
        <h3 class="sidebar-title">Categories</h3>
        <ul class="category-list" id="categoryList">
            <li class="cat-item active" data-category="">All Products</li>
        </ul>
    </aside>
    <div class="shop-main">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search products..." oninput="filterProducts()">
            <button><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></button>
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $product): ?>
<div class="product-card"
                      data-product-id="<?php echo $product['product_id']; ?>"
                      data-product-uuid="<?php echo htmlspecialchars($product['uuid'] ?? ''); ?>"
                      data-product-price="<?php echo $product['selling_price']; ?>"
                      data-product-image="<?php echo htmlspecialchars(($product['image_url'] ? $storeBaseUrl . $product['image_url'] : $storeBaseUrl . 'uploads/products/dell/sheriff_login_3d.png')); ?>"
                      data-product-desc="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                      data-product-stock="<?php echo $product['stock_quantity']; ?>"
                      style="position:relative;">
                     <?php if (!empty($product['is_featured'])): ?>
                         <div class="product-badge badge-featured">Featured</div>
                     <?php endif; ?>
                     <div class="product-image" style="cursor:pointer;">
                         <img src="<?php echo $product['image_url'] ? $storeBaseUrl . $product['image_url'] : $storeBaseUrl . 'uploads/products/dell/sheriff_login_3d.png'; ?>" alt="Product">
                     </div>
                    <div class="product-info">
                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(mb_strimwidth($product['description'] ?? 'No description available.', 0, 200, '...')); ?></p>
                        <div class="product-price">
                            <div style="display:flex; flex-direction:column;">
                              <span data-ngn="<?php echo $product['selling_price']; ?>" style="font-size:1.3rem; font-weight:900;">
                                <?php 
                                if (!empty($product['min_price']) && !empty($product['max_price'])) {
                                    echo format_currency($product['min_price']) . ' - ' . format_currency($product['max_price']);
                                } else {
                                    echo format_currency($product['selling_price']);
                                }
                                ?>
                              </span>
                              <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] <= 5): ?>
                                <span class="low-stock-msg">Only <?php echo $product['stock_quantity']; ?> left</span>
                              <?php endif; ?>
                            </div>
                            <div class="card-actions">
                                <button class="btn-action btn-details">Details</button>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn-action btn-add-cart">Add to Cart</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <span class="stock-badge <?php echo $product['stock_quantity'] > 0 ? 'stock-in' : 'stock-out'; ?>" style="position:absolute; top:12px; right:12px; font-size:0.65rem; padding:4px 12px;">
                        <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <div class="modal-left" id="modalLeftContainer" style="padding: 30px;">
            <div id="modalImgBox" style="width:100%; height:100%; min-height:420px; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:20px;">
                <img id="modalImg" src="" alt="" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div id="modalGallery" style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;"></div>
        </div>
        <div class="modal-right">
            <div id="modalCat" style="color:rgba(255,255,255,0.5); font-size:0.85rem; font-weight:800; text-transform:uppercase; letter-spacing:2px; margin-bottom:12px;"></div>
            <h2 id="modalTitle" class="modal-title" style="color:white; font-size:2.8rem; font-weight:900; margin-bottom:10px; letter-spacing:-1.5px; line-height:1.1;"></h2>
            <div id="modalPrice" style="color:#fff; font-size:1.8rem; font-weight:900; margin-bottom:20px; letter-spacing:-1px;"></div>
            <div id="modalStock" style="margin-bottom:20px;"></div>
            <div class="modal-desc-title" style="color:rgba(255,255,255,0.4); font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom:15px;">Product Description</div>
            <p id="modalDesc" style="color:rgba(255,255,255,0.7); font-size:1.05rem; line-height:1.7; margin-bottom:25px;"></p>
            
            <div id="modalQtyRow" style="display:flex; align-items:center; gap:15px; margin-bottom:25px;">
                <span style="color:#64748b; font-size:0.8rem; font-weight:700; text-transform:uppercase;">Quantity:</span>
                <div class="cart-item-qty" style="display:flex; align-items:center; gap:12px; background:rgba(255,255,255,0.03); padding:8px 16px; border-radius:10px;">
                    <button id="modalQtyMinus" style="background:none; border:none; color:white; cursor:pointer; font-size:1.3rem; font-weight:700;">-</button>
                    <span id="modalQtyVal" class="qty-val" style="font-weight:800; min-width:24px; text-align:center; font-size:1.1rem;">1</span>
                    <button id="modalQtyPlus" style="background:none; border:none; color:white; cursor:pointer; font-size:1.3rem; font-weight:700;">+</button>
                </div>
            </div>
            
            <div class="modal-contact-buttons" style="margin-top:auto;">
                <div class="modal-contact-row">
                    <a id="modalWhatsAppBtn" href="#" target="_blank" class="btn-contact btn-whatsapp">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px; height:14px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Chat on WhatsApp
                    </a>
                    <a id="modalCallBtn" href="tel:<?php echo $whatsapp_number_raw; ?>" class="btn-contact btn-call">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Call Now
                    </a>
                </div>
                    <button id="modalAddCart" class="btn-checkout-modal" style="margin-top: 12px;">
                        <span style="display:flex; align-items:center; justify-content:center; gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px; height:14px;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Add to Cart &amp; Checkout
                        </span>
                    </button>
            </div>
            <div id="modalTotal" style="color:#fff; font-size:1.4rem; font-weight:900; margin-top:16px; text-align:center;"></div>
        </div>
    </div>
</div>

<div class="bot-float" style="position:fixed; bottom:100px; right:30px; width:50px; height:50px; background:#1e293b; border:1px solid rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 20px rgba(0,0,0,0.3); z-index:500; cursor:pointer;">
    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:24px; height:24px;">
        <path d="M12 2a10 10 0 0 1 10 10v1a3 3 0 0 1-3 3h-1.5a1 1 0 0 0-1 1v1.5a3 3 0 0 1-3 3h-1.5a1 1 0 0 1-1-1v-1.5a3 3 0 0 0-3-3h-1.5a3 3 0 0 1-3-3v-1a10 10 0 0 1 10-10z"></path>
        <circle cx="9" cy="11" r="1" fill="white"></circle>
        <circle cx="15" cy="11" r="1" fill="white"></circle>
    </svg>
</div>

<a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" class="whatsapp-float">
    <svg viewBox="0 0 448 512"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.7 19.3 70.1 29.5 106.2 29.5 122.4 0 222-99.6 222-222 0-59.3-23.1-115.1-65-157.1zM223.9 446.3c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.7-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.5-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18c-5.1-1.9-8.8-2.8-12.5 3.1-3.7 5.9-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.2-16.4-14.5-27.4-32.5-30.6-38.1-3.2-5.6-.3-8.6 2.5-11.4 2.5-2.5 5.5-6.5 8.3-9.7 2.8-3.2 3.7-5.5 5.6-9.2 1.9-3.7 1-7-0.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-0.2-6.9-0.2-10.6-0.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.9-19.1 19.1-19.1 46.5 0 27.4 20 53.9 22.8 57.6 2.8 3.7 39.4 63.9 98.9 87.4 60 23.8 80.5 19.8 94.6 18.5 14.1-1.3 45.4-18.5 51.9-36.5 6.4-18 6.5-33.3 4.6-36.5-1.9-3.2-6.9-5.2-12.4-8z"/></svg>
</a>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg>
</button>

</div>
</div>

<div class="toast-container" id="toastContainer"></div>

<footer class="site-footer">
    <div class="footer-grid" style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:2fr 1fr 1fr 1.5fr; gap:50px; padding-bottom:60px;">
        <div class="footer-col">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div style="width:38px; height:38px; background:#f5c04a; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#020617" stroke-width="3" style="width:20px; height:20px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div>
                    <div style="font-size:1.3rem; font-weight:950; color:#fff; letter-spacing:-1px; line-height:1;">SHERIFF SHEVVY</div>
                    <div style="font-size:0.6rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:2px; font-weight:700;">Enterprise Suite</div>
                </div>
            </div>
            <p style="color:rgba(255,255,255,0.45); font-size:0.9rem; line-height:1.7; margin-bottom:24px; max-width:340px;">
                Premium foreign-used laptops and enterprise computing solutions. Quality tested, reliable performance.
            </p>
            <div style="display:flex; gap:12px;">
                <a href="https://facebook.com" target="_blank" style="width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.6); transition:all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.color='rgba(255,255,255,0.6)';">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px; height:20px;"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="https://instagram.com" target="_blank" style="width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.6); transition:all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.color='rgba(255,255,255,0.6)';">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px; height:20px;"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5"/></svg>
                </a>
                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" style="width:46px; height:46px; border-radius:12px; background:rgba(37,211,102,0.08); border:1px solid rgba(37,211,102,0.15); display:flex; align-items:center; justify-content:center; color:#25D366; transition:all 0.3s;" onmouseover="this.style.background='#25D366'; this.style.color='#fff';" onmouseout="this.style.background='rgba(37,211,102,0.08)'; this.style.color='#25D366';">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px; height:20px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
            </div>
        </div>
        
        <div class="footer-col">
            <h3 style="color:#fff; font-size:0.85rem; font-weight:800; margin-bottom:24px; text-transform:uppercase; letter-spacing:1px;">Quick Links</h3>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="." style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Home</a>
                <a href="#categories" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Categories</a>
                <a href="#products" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Products</a>
                <a href="login.php" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Admin</a>
            </div>
        </div>

        <div class="footer-col">
            <h3 style="color:#fff; font-size:0.85rem; font-weight:800; margin-bottom:24px; text-transform:uppercase; letter-spacing:1px;">Support</h3>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="#" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Privacy Policy</a>
                <a href="#" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Terms of Service</a>
                <a href="#" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.9rem; font-weight:500; transition:color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">FAQ</a>
            </div>
        </div>

        <div class="footer-col">
            <h3 style="color:#fff; font-size:0.85rem; font-weight:800; margin-bottom:24px; text-transform:uppercase; letter-spacing:1px;">Contact</h3>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; align-items:flex-start; gap:10px;">
                    <div style="width:32px; height:32px; min-width:32px; background:rgba(255,255,255,0.06); border-radius:8px; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.6); font-size:14px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <span style="color:rgba(255,255,255,0.55); font-size:0.85rem; line-height:1.4;">Lalubu Street, Oke-Ilewo, Abeokuta, Ogun State</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; min-width:32px; background:rgba(255,255,255,0.06); border-radius:8px; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.6);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <span style="color:rgba(255,255,255,0.8); font-weight:600; font-size:0.9rem;"><?php echo $whatsapp_number_raw; ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; min-width:32px; background:rgba(37,211,102,0.08); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#25D366;">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px; height:14px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </div>
                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" style="color:#fff; font-weight:600; font-size:0.9rem; text-decoration:none; transition:color 0.3s;" onmouseover="this.style.color='#25D366'" onmouseout="this.style.color='#fff'">Chat on WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
    
    <div style="border-top:1px solid rgba(255,255,255,0.04); text-align:center; padding:32px 5% 24px;">
        <div style="color:rgba(255,255,255,0.35); font-size:0.8rem; font-weight:500;">
            &copy; <?php echo date('Y'); ?> <span style="color:#fff; font-weight:700;">SHERIFF SHEVVY</span>. All rights reserved.
        </div>
        <div class="footer-signature" style="margin-top:6px; color:rgba(255,255,255,0.25); font-size:0.8rem; letter-spacing:0.5px;">
            Designed &amp; Built with <span style="color:#ef4444;">&#9829;</span> by <a href="tel:+2348062328638" style="color:#fff;text-decoration:none;">Shevvy Technologies</a>
        </div>
    </div>
</footer>

<script src="<?php echo $storeBaseUrl; ?>js/core/utils.js"></script>
<script>
if (typeof Utils === 'undefined' || !Utils.formatCurrency) {
    if (typeof Utils !== 'object') window.Utils = {};
    Utils.formatCurrency = function(a, c) { var s = {NGN:'\u20A6',USD:'$',GBP:'\u00A3',EUR:'\u20AC'}; return (s[c]||'\u20A6') + Number(a).toLocaleString(); };
}

let exchangeRates = { NGN: 1, USD: 0.00067, GBP: 0.00052, EUR: 0.00061 };
let currentCurrency = localStorage.getItem('store_currency') || 'NGN';

document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('currencySelector');
    if (sel) sel.value = currentCurrency;
    fetch(STORE_URL + 'api/currency.php')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                exchangeRates = res.data.currencies || exchangeRates;
                convertAllPrices();
            }
        })
        .catch(function() {});
    convertAllPrices();
});

function switchCurrency(code) {
    currentCurrency = code;
    localStorage.setItem('store_currency', code);
    convertAllPrices();
}

function convertAllPrices() {
    var rate = exchangeRates[currentCurrency] || 1;
    document.querySelectorAll('[data-ngn]').forEach(function(el) {
        var ngn = parseFloat(el.dataset.ngn);
        if (!isNaN(ngn)) {
            var converted = ngn * rate;
            el.textContent = Utils.formatCurrency(converted, currentCurrency);
        }
    });
    var cartItems = document.querySelectorAll('.cart-item-price');
    if (cartItems.length) updateCartUI();
}

function toggleNav() {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('hamburger').classList.toggle('active');
}
document.querySelectorAll('.nav-links a, #navLinks a').forEach(function(a) { a.addEventListener('click', function() {
    document.getElementById('navLinks').classList.remove('open');
    document.getElementById('hamburger').classList.remove('active');
    document.querySelectorAll('#navLinks a').forEach(function(l) { l.classList.remove('active'); });
    this.classList.add('active');
}); });

// Active nav link on scroll for anchor links
function updateActiveNav() {
    var links = document.querySelectorAll('#navLinks a');
    var fromTop = window.scrollY + 120;
    var activeSet = false;
    links.forEach(function(a) {
        var href = a.getAttribute('href');
        if (href && href.startsWith('#')) {
            var target = document.querySelector(href);
            if (target && target.offsetTop <= fromTop && target.offsetTop + target.offsetHeight > fromTop) {
                links.forEach(function(l) { l.classList.remove('active'); });
                a.classList.add('active');
                activeSet = true;
            }
        }
    });
    if (!activeSet && window.scrollY < 100) {
        links.forEach(function(l) { l.classList.remove('active'); });
        document.querySelector('#navLinks a[href="."]')?.classList.add('active');
    }
}
window.addEventListener('scroll', updateActiveNav);
window.addEventListener('load', updateActiveNav);

function toggleShare(el, product) {
    var popup = el.querySelector('.share-popup') || createSharePopup(el, product);
    var isOpen = popup.classList.contains('open');
    document.querySelectorAll('.share-popup.open').forEach(function(p) { p.classList.remove('open'); });
    if (!isOpen) popup.classList.add('open');
}
function createSharePopup(el, product) {
    var div = document.createElement('div');
    div.className = 'share-popup';
    var url = encodeURIComponent(window.location.href.split('#')[0] + '?product=' + encodeURIComponent(product.product_name));
    var text = encodeURIComponent('Check out ' + product.product_name + ' at SHERIFF SHEVVY - \u20A6' + Number(product.selling_price).toLocaleString());
    div.innerHTML =
        '<a href="https://wa.me/?text=' + text + '%20' + url + '" target="_blank" title="WhatsApp" onclick="event.stopPropagation()">' +
            '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>' +
        '</a>' +
        '<a href="https://www.facebook.com/sharer/sharer.php?u=' + url + '" target="_blank" title="Facebook" onclick="event.stopPropagation()">' +
            '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>' +
        '</a>' +
        '<a href="https://twitter.com/intent/tweet?text=' + text + '&url=' + url + '" target="_blank" title="Twitter" onclick="event.stopPropagation()">' +
            '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>' +
        '</a>' +
        '<a href="#" onclick="event.stopPropagation();navigator.clipboard.writeText(decodeURIComponent(\'' + url + '\'));this.title=\'Copied!\';setTimeout(function(){this.title=\'Copy Link\';},1500);return false;" title="Copy Link">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>' +
        '</a>';
    el.appendChild(div);
    return div;
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-share')) {
        document.querySelectorAll('.share-popup.open').forEach(function(p) { p.classList.remove('open'); });
    }
});

var cart = JSON.parse(localStorage.getItem('sheriff_cart') || '[]');
var modalQty = 1;
var modalCurrentProduct = null;

function updateCartUI() {
    var cartItems = document.getElementById('cartItems');
    var cartCountHeader = document.getElementById('cartCountHeader');
    var cartTotal = document.getElementById('cartTotal');
    var rate = exchangeRates[currentCurrency] || 1;
    
    // Group cart by product ID to handle quantities
    var grouped = cart.reduce(function(acc, item) {
        var key = item.product_id;
        if (!acc[key]) acc[key] = { ...item, qty: 0 };
        acc[key].qty += 1;
        return acc;
    }, {});
    
    var cartArray = Object.values(grouped);
    if (cartCountHeader) cartCountHeader.textContent = cart.length;
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<div style="text-align: center; color: #64748b; margin-top: 50px;">Your cart is empty.</div>';
        cartTotal.textContent = Utils.formatCurrency(0, currentCurrency);
        cartTotal.dataset.ngn = '0';
    } else {
        var total = 0;
        cartItems.innerHTML = cartArray.map(function(item) {
            var itemTotal = Number(item.selling_price) * item.qty;
            total += itemTotal;
            return '<div class="cart-item-new">' +
                '<div style="width:70px;height:70px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                    '<img src="' + (item.image_url ? STORE_URL + item.image_url : STORE_URL + 'uploads/products/dell/sheriff_login_3d.png') + '" style="max-width:80%;max-height:80%;"></div>' +
                '<div style="flex:1;">' +
                    '<div style="color:white;font-weight:700;margin-bottom:5px;">' + item.product_name + '</div>' +
                    '<div style="color:var(--primary);font-weight:800;font-size:0.9rem;">' + Utils.formatCurrency(item.selling_price, currentCurrency) + '</div>' +
                '</div>' +
                '<div style="display:flex;flex-direction:column;align-items:end;gap:10px;">' +
                    '<div class="cart-item-qty">' +
                        '<button onclick="changeQty(' + item.product_id + ', -1)">-</button>' +
                        '<span class="qty-val">' + item.qty + '</span>' +
                        '<button onclick="changeQty(' + item.product_id + ', 1)">+</button>' +
                    '</div>' +
                    '<span style="color:#ef4444;font-size:0.75rem;cursor:pointer;font-weight:600;" onclick="removeFromCartById(' + item.product_id + ')">Remove</span>' +
                '</div>' +
            '</div>';
        }).join('');
        cartTotal.textContent = Utils.formatCurrency(total, currentCurrency);
        cartTotal.dataset.ngn = total;
    }
    localStorage.setItem('sheriff_cart', JSON.stringify(cart));
}

function changeQty(id, delta) {
    if (delta > 0) {
        var item = cart.find(i => i.product_id == id);
        if (item) cart.push(item);
    } else {
        var idx = cart.findLastIndex(i => i.product_id == id);
        if (idx !== -1) cart.splice(idx, 1);
    }
    updateCartUI();
}

function removeFromCartById(id) {
    cart = cart.filter(i => i.product_id != id);
    updateCartUI();
}

function showToast(msg, icon) {
    var container = document.getElementById('toastContainer');
    var t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = '<span class="toast-icon">' + (icon || '&#10003;') + '</span>' + msg;
    container.appendChild(t);
    requestAnimationFrame(function() { t.classList.add('show'); });
    setTimeout(function() {
        t.classList.remove('show');
        setTimeout(function() { t.remove(); }, 400);
    }, 2500);
}

function addToCart(product) {
    cart.push(product);
    updateCartUI();
    toggleCart(true);
    showToast(product.product_name + ' added to cart', '&#128722;');
}

function addToCartByUuid(uuid) {
    if (!uuid) return;
    var card = document.querySelector('[data-product-uuid="' + uuid.replace(/['"]/g, '') + '"]');
    if (!card) return;
    var stock = Number(card.getAttribute('data-product-stock'));
    if (stock <= 0) { showToast('Out of stock', '&#10060;'); return; }
    var product = {
        product_id: card.getAttribute('data-product-id'),
        product_name: card.querySelector('.product-name').textContent,
        selling_price: card.getAttribute('data-product-price'),
        image_url: card.getAttribute('data-product-image'),
        uuid: uuid
    };
    addToCart(product);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function toggleCart(forceOpen) {
    var drawer = document.getElementById('cartDrawer');
    if (forceOpen === true) drawer.classList.add('open');
    else if (forceOpen === false) drawer.classList.remove('open');
    else drawer.classList.toggle('open');
}

function openCheckout() {
    if (cart.length === 0) {
        alert('Please add items to your cart first.');
        return;
    }
    var grouped = cart.reduce(function(acc, item) {
        var key = item.product_id || item.uuid || item.product_name;
        if (!acc[key]) acc[key] = { ...item, qty: 0 };
        acc[key].qty += 1;
        return acc;
    }, {});
    var items = Object.values(grouped);
    var total = items.reduce(function(sum, item) { return sum + Number(item.selling_price) * item.qty; }, 0);

    var summaryHtml = items.map(function(item) {
        return '<div class="checkout-summary-item">' +
            '<div><div class="cs-name">' + item.product_name + '</div><div class="cs-qty">Qty: ' + item.qty + '</div></div>' +
            '<div class="cs-price">' + Utils.formatCurrency(Number(item.selling_price) * item.qty, currentCurrency) + '</div>' +
        '</div>';
    }).join('');
    document.getElementById('checkoutSummaryItems').innerHTML = summaryHtml;
    document.getElementById('checkoutTotalDisplay').textContent = Utils.formatCurrency(total, currentCurrency);
    document.getElementById('checkoutTotalDisplay').dataset.ngn = total;
    document.getElementById('checkoutDiscountRow').style.display = 'none';
    document.getElementById('promoCodeInput').value = '';
    document.getElementById('promoResult').innerHTML = '';
    appliedPromo = null;
    document.getElementById('checkoutModal').style.display = 'flex';
}

function closeCheckout() {
    document.getElementById('checkoutModal').style.display = 'none';
}

function selectPayment(el) {
    document.querySelectorAll('#paymentMethods .payment-option').forEach(function(o) { o.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('selectedPayment').value = el.getAttribute('data-value');
}

var appliedPromo = null;

function applyPromoCode() {
    var code = document.getElementById('promoCodeInput').value.trim().toUpperCase();
    if (!code) { document.getElementById('promoResult').innerHTML = '<span style="color:#ef4444;">Enter a promo code</span>'; return; }
    var total = cart.reduce(function(sum, item) { return sum + Number(item.selling_price); }, 0);
    fetch(STORE_URL + 'api/promo_codes.php?code=' + encodeURIComponent(code) + '&amount=' + total)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            var el = document.getElementById('promoResult');
            if (res.success) {
                appliedPromo = res.data;
                el.innerHTML = '<span style="color:#059669;font-weight:600;">✅ ' + code + ' applied! Discount: ' + Utils.formatCurrency(res.data.discount, currentCurrency) + '</span>';
                document.getElementById('checkoutDiscountRow').style.display = 'flex';
                document.getElementById('checkoutDiscountDisplay').textContent = '-' + Utils.formatCurrency(res.data.discount, currentCurrency);
                document.getElementById('checkoutDiscountDisplay').dataset.ngn = res.data.discount;
                document.getElementById('checkoutTotalDisplay').textContent = Utils.formatCurrency(res.data.total_after, currentCurrency);
                document.getElementById('checkoutTotalDisplay').dataset.ngn = res.data.total_after;
            } else {
                appliedPromo = null;
                el.innerHTML = '<span style="color:#ef4444;">❌ ' + res.message + '</span>';
                document.getElementById('checkoutDiscountRow').style.display = 'none';
                document.getElementById('checkoutTotalDisplay').textContent = Utils.formatCurrency(total, currentCurrency);
                document.getElementById('checkoutTotalDisplay').dataset.ngn = total;
            }
        })
        .catch(function() {
            document.getElementById('promoResult').innerHTML = '<span style="color:#ef4444;">Error validating code</span>';
        });
}

document.getElementById('checkoutForm').onsubmit = async function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    // Group cart items with quantities for accurate totals
    var grouped = cart.reduce(function(acc, item) {
        var key = item.product_id || item.uuid || item.product_name;
        if (!acc[key]) acc[key] = { ...item, qty: 0 };
        acc[key].qty += 1;
        return acc;
    }, {});
    var orderItems = Object.values(grouped);
    var subtotal = orderItems.reduce(function(sum, item) { return sum + Number(item.selling_price) * item.qty; }, 0);
    var discount = appliedPromo ? (appliedPromo.discount || 0) : 0;
    var total = Math.max(0, subtotal - discount);
    var customerEmail = formData.get('customer_email');
    var customerPassword = formData.get('customer_password');
    var paymentMethod = formData.get('payment_method');
    var orderData = {
        name: formData.get('customer_name'),
        phone: formData.get('customer_phone'),
        address: formData.get('customer_address'),
        email: customerEmail,
        payment_method: paymentMethod,
        items: cart,
        total: total,
        promo_code: appliedPromo ? appliedPromo.code : null,
        discount: discount
    };
    var summary = "*NEW ORDER*\n\n";
    summary += "*Customer:* " + orderData.name + "\n";
    summary += "*Phone:* " + orderData.phone + "\n";
    summary += "*Address:* " + orderData.address + "\n\n";
    summary += "*Payment:* " + paymentMethod + "\n\n";
    summary += "*Items:*\n";
    
    var idx = 1;
    orderItems.forEach(function(it) {
        summary += (idx++) + ". " + it.product_name + " x" + it.qty + " (\u20A6" + Number(it.selling_price).toLocaleString() + " each)\n";
    });
    
    if (paymentMethod !== 'Cash on Delivery') {
        summary += "\n*Proof of payment:* Send screenshot to this WhatsApp";
    }
    if (discount > 0) { summary += "\n*Promo:* " + (appliedPromo ? appliedPromo.code : '') + " (-" + Utils.formatCurrency(discount) + ")"; }
    summary += "\n*TOTAL: \u20A6" + Number(total).toLocaleString() + "*";
    var waUrl = "https://wa.me/<?php echo $whatsapp_number; ?>?text=" + encodeURIComponent(summary);
    
    try {
        await fetch(STORE_URL + 'api/web_order.php', {
            method: 'POST', body: JSON.stringify(orderData)
        });
    } catch (err) { console.error('DB preservation failed', err); }

    if (customerPassword) {
        try {
            await fetch(STORE_URL + 'api/storefront/register-customer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: customerEmail,
                    password: customerPassword,
                    name: orderData.name,
                    phone: orderData.phone,
                    address: orderData.address
                })
            });
        } catch (err) { console.error('Customer registration failed', err); }
    }

    try {
        await fetch(STORE_URL + 'api/storefront/send-order-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
    } catch (err) { console.error('Order email failed', err); }

    cart = [];
    appliedPromo = null;
    updateCartUI();
    closeCheckout();
    toggleCart(false);
    window.open(waUrl, '_blank');
    alert('Order placed successfully! Redirecting to WhatsApp for confirmation.');
};

updateCartUI();

function openProductModal(product) {
    var productUuid = product.uuid || product.product_uuid || '';
    if (!productUuid) {
        renderProductModal(product, []);
        return;
    }
    var apiUrl = STORE_URL + 'api/storefront/product/' + encodeURIComponent(productUuid);
    fetch(apiUrl)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.data) {
                renderProductModal(res.data, res.data.gallery || []);
            } else {
                renderProductModal(product, []);
            }
        })
        .catch(function() { renderProductModal(product, []); });
}

function renderProductModal(product, gallery) {
    modalCurrentProduct = product;
    modalQty = 1;
    document.getElementById('modalQtyVal').textContent = '1';

    document.getElementById('modalCat').textContent = product.category || 'Product';
    document.getElementById('modalTitle').textContent = product.product_name || 'Premium Device';
    
    var priceStr = Utils.formatCurrency(product.selling_price, currentCurrency);
    document.getElementById('modalPrice').textContent = priceStr;
    
    document.getElementById('modalDesc').style.cssText = 'color:#ffffff; font-size:1.05rem; line-height:1.7; margin-bottom:25px;';
    document.getElementById('modalDesc').innerHTML = (product.description || "No description available.").replace(/\n/g, '<br>');
    
    // Stock display
    var stockEl = document.getElementById('modalStock');
    var stockQty = Number(product.stock_quantity || product.available_stock) || 0;
    var isOutOfStock = stockQty <= 0;
    if (stockQty > 0) {
        stockEl.innerHTML = '<span class="stock-badge stock-in">In Stock (' + stockQty + ' available)</span>';
        if (stockQty <= 5) stockEl.innerHTML += '<span class="low-stock-msg" style="margin-left:10px;">Only ' + stockQty + ' left</span>';
    } else {
        stockEl.innerHTML = '<span class="stock-badge stock-out">Out of Stock</span>';
    }

    // Hide quantity selector and add-to-cart button when out of stock
    document.getElementById('modalQtyRow').style.display = isOutOfStock ? 'none' : '';
    document.getElementById('modalAddCart').style.display = isOutOfStock ? 'none' : '';

    function updateModalTotal() {
        var unitPrice = Number(product.selling_price) || 0;
        document.getElementById('modalTotal').textContent = 'Total: ' + Utils.formatCurrency(unitPrice * modalQty, currentCurrency);
    }
    updateModalTotal();

    // Main image
    var mainImg = document.getElementById('modalImg');
    mainImg.src = (gallery.length > 0 ? STORE_URL + gallery[0].image_url : product.image_url) || STORE_URL + 'uploads/products/dell/sheriff_login_3d.png';
    
    // Thumbnails
    var galleryContainer = document.getElementById('modalGallery');
    galleryContainer.innerHTML = '';
    
    var allImages = gallery.length > 0 ? gallery : (product.image_url ? [{image_url: product.image_url}] : []);
    
    allImages.forEach(function(img, i) {
        var thumb = document.createElement('div');
        thumb.style.cssText = 'width:80px;height:80px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;border:3px solid ' + (i === 0 ? '#fff' : 'transparent') + ';padding:4px;';
        var tImg = document.createElement('img');
        tImg.src = img.image_url;
        tImg.style.cssText = 'max-width:85%;max-height:85%;object-fit:contain;';
        thumb.appendChild(tImg);
        thumb.onclick = function() {
            mainImg.src = tImg.src;
            Array.from(galleryContainer.children).forEach(function(c) { c.style.borderColor = 'transparent'; });
            thumb.style.borderColor = '#fff';
        };
        galleryContainer.appendChild(thumb);
    });

    // WhatsApp button
    var waBtn = document.getElementById('modalWhatsAppBtn');
    var productName = product.product_name || 'this product';
    var waMsg = encodeURIComponent(WHATSAPP_MSG + productName + ' - ₦' + Number(product.selling_price).toLocaleString());
    waBtn.href = 'https://wa.me/<?php echo $whatsapp_number; ?>?text=' + waMsg;

    // Call button
    document.getElementById('modalCallBtn').href = 'tel:<?php echo $whatsapp_number_raw; ?>';

    // Quantity controls
    var qtyMinus = document.getElementById('modalQtyMinus');
    var qtyPlus = document.getElementById('modalQtyPlus');
    var qtyVal = document.getElementById('modalQtyVal');

    qtyMinus.onclick = function() {
        if (modalQty > 1) {
            modalQty--;
            qtyVal.textContent = modalQty;
            updateModalTotal();
        }
    };
    qtyPlus.onclick = function() {
        var max = Number(product.stock_quantity || product.available_stock) || 99;
        if (modalQty < max) {
            modalQty++;
            qtyVal.textContent = modalQty;
            updateModalTotal();
        }
    };

    // Checkout button — adds product `modalQty` times and opens checkout
    var addBtn = document.getElementById('modalAddCart');
    addBtn.onclick = function() {
        for (var i = 0; i < modalQty; i++) {
            cart.push(product);
        }
        updateCartUI();
        closeModal();
        openCheckout();
        showToast(modalQty + 'x ' + product.product_name + ' added', '&#128722;');
    };

    document.getElementById('productModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('productModal')) closeModal();
    if (event.target == document.getElementById('checkoutModal')) closeCheckout();
};

window.addEventListener('scroll', function() {
    document.getElementById('scrollTop').classList.toggle('visible', window.scrollY > 200);
});

// Delegated click handler for product cards
document.getElementById('productGrid').addEventListener('click', function(e) {
    var card = e.target.closest('.product-card');
    if (!card) return;
    var uuid = card.getAttribute('data-product-uuid');
    if (!uuid) return;
    if (e.target.closest('.btn-details') || e.target.closest('.product-image')) {
        e.preventDefault();
        var product = {
            product_id: card.getAttribute('data-product-id'),
            product_name: card.querySelector('.product-name').textContent,
            category: card.querySelector('.product-category').textContent,
            selling_price: card.getAttribute('data-product-price'),
            image_url: card.getAttribute('data-product-image'),
            description: card.getAttribute('data-product-desc'),
            stock_quantity: card.getAttribute('data-product-stock'),
            uuid: uuid
        };
        openProductModal(product);
    } else if (e.target.closest('.btn-add-cart')) {
        e.preventDefault();
        addToCartByUuid(uuid);
    }
});
</script>
<script>
let currentSlideIndex = 0;
let slideEls = [];
let dotEls = [];
let slideInterval = null;

function setSlide(n) {
    slideEls.forEach(el => el.classList.remove('active'));
    dotEls.forEach(el => el.classList.remove('active'));
    currentSlideIndex = n;
    slideEls[currentSlideIndex].classList.add('active');
    dotEls[currentSlideIndex].classList.add('active');
}

function nextSlide() {
    if (slideEls.length > 0) setSlide((currentSlideIndex + 1) % slideEls.length);
}

function buildSlides(slides) {
    const container = document.getElementById('slidesContainer');
    const dotsContainer = document.getElementById('sliderDots');
    if (!slides || slides.length === 0) {
        container.innerHTML = '<div class="slide active"><div class="slide-overlay"></div><div class="slide-content"><h2>Welcome to Sheriff Shevvy Enterprises</h2><a href="#products" class="btn-explore"><span>Shop Now</span></a></div></div>';
        dotsContainer.innerHTML = '<div class="dot active"></div>';
        slideEls = container.querySelectorAll('.slide');
        dotEls = dotsContainer.querySelectorAll('.dot');
        return;
    }
    container.innerHTML = slides.map((s, i) => {
        const bgStyle = s.image_url ? `background: url(${STORE_URL}${s.image_url}); background-size: cover; background-repeat: no-repeat; background-position: center;` : 'background: linear-gradient(135deg, #020617 0%, #0f172a 100%);';
        const title = s.title || 'Welcome';
        const subtitle = s.subtitle ? `<p style="color:#94a3b8;font-size:1.3rem;margin:8px 0 0;">${s.subtitle}</p>` : '';
        const cta = s.cta_text ? `<a href="${s.cta_link || '#products'}" class="btn-explore"><span>${s.cta_text}</span></a>` : '';
        return `<div class="slide${i === 0 ? ' active' : ''}" style="${bgStyle}"><div class="slide-overlay"></div><div class="slide-content"><h2>${title}</h2>${subtitle}${cta}</div></div>`;
    }).join('');
    dotsContainer.innerHTML = slides.map((s, i) => `<div class="dot${i === 0 ? ' active' : ''}" onclick="setSlide(${i})"></div>`).join('');
    slideEls = container.querySelectorAll('.slide');
    dotEls = dotsContainer.querySelectorAll('.dot');
}

fetch(STORE_URL + 'api/hero_slides.php')
    .then(r => r.json())
    .then(res => {
        buildSlides(res.data);
        if (slideEls.length > 0) slideInterval = setInterval(nextSlide, 5000);
    })
    .catch(() => {
        buildSlides([]);
        slideInterval = setInterval(nextSlide, 5000);
    });
</script>

<script>
// ── Category Sidebar ──
let selectedCategory = '';

function buildCategories() {
    const cats = new Set();
    document.querySelectorAll('.product-card').forEach(function(card) {
        const c = card.querySelector('.product-category').textContent.trim();
        if (c) cats.add(c);
    });
    const list = document.getElementById('categoryList');
    list.innerHTML = '<li class="cat-item active" data-category="">All Products</li>';
    [...cats].sort().forEach(function(c) {
        const li = document.createElement('li');
        li.className = 'cat-item';
        li.textContent = c;
        li.dataset.category = c;
        list.appendChild(li);
    });
    list.addEventListener('click', function(e) {
        const li = e.target.closest('.cat-item');
        if (!li) return;
        list.querySelectorAll('.cat-item').forEach(function(el) { el.classList.remove('active'); });
        li.classList.add('active');
        selectedCategory = li.dataset.category;
        filterProducts();
        document.getElementById('productGrid').scrollIntoView({behavior:'smooth'});
    });
}

function filterProducts() {
    var q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(function(card) {
        var name = card.querySelector('.product-name').textContent.toLowerCase();
        var cat = card.querySelector('.product-category').textContent.toLowerCase();
        var matchSearch = name.includes(q) || cat.includes(q);
        var matchCat = !selectedCategory || cat === selectedCategory.toLowerCase();
        card.style.display = (matchSearch && matchCat) ? '' : 'none';
    });
}

// Build categories after products render
buildCategories();
</script>
</body>
</html>
