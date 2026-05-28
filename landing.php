<?php
/**
 * SHERIFF SHEVVY ENTERPRISES - Ecommerce Landing Page
 */

// Initialize Product model
require_once __DIR__ . '/app/config/Config.php';
require_once __DIR__ . '/app/config/DatabaseConnection.php';
require_once __DIR__ . '/app/models/Product.php';

Config::load();

// Compute correct app base URL for JS
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$appRoot = str_replace('\\', '/', realpath(__DIR__));
$basePath = str_replace($docRoot, '', $appRoot);
$storeBaseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/';

$productModel = new Product();
$products = $productModel->getProductsWithStock();

// WhatsApp settings
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
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>var STORE_URL = '<?php echo $storeBaseUrl; ?>';</script>
    <style>
        :root {
            --primary: #f5c04a;
            --primary-dark: #b45309;
            --secondary: #d4af37;
            --bg-light: #020617;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --white: #0f172a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --gold-glow: rgba(245, 192, 74, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 192, 74, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(15, 23, 42, 0.1) 0px, transparent 50%);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* ── HEADER ── */
        header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--glass-border);
            position: sticky; top: 0; z-index: 100;
        }

        .logo { display: flex; align-items: center; gap: 12px; }
        .logo .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, rgba(245,192,74,0.15), rgba(245,192,74,0.05));
            border: 1px solid rgba(245,192,74,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .logo .logo-icon svg { width: 18px; height: 18px; }
        .logo h1 {
            font-size: 1.2rem;
            font-weight: 900;
            background: linear-gradient(to right, #f5c04a, #f59e0b);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        nav { display: flex; align-items: center; gap: 6px; }
        nav a {
            text-decoration: none;
            color: #94a3b8;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        nav a:hover { color: #fff; background: rgba(255,255,255,0.05); }

        .login-btn {
            background: linear-gradient(135deg, #f5c04a, #d97706) !important;
            color: #020617 !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            font-weight: 700;
            font-size: 0.75rem !important;
            text-decoration: none;
            white-space: nowrap;
        }
        .login-btn:hover {
            box-shadow: 0 8px 24px rgba(245,192,74,0.3);
            transform: translateY(-1px);
        }

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
            nav { display: none; flex-direction: column; position: absolute; top: 100%; left: 0; right: 0; background: rgba(15,23,42,0.95); backdrop-filter: blur(24px); border-bottom: 1px solid var(--glass-border); padding: 16px; gap: 4px; }
            nav.open { display: flex; }
            nav a { padding: 12px 16px; }
            .hamburger { display: flex; }
        }

        .hero {
            padding: 100px 5% 80px;
            text-align: center;
            background: radial-gradient(circle at center, var(--gold-glow), transparent 70%);
            margin-bottom: 60px;
            position: relative;
        }

        .hero h2 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            color: #ffffff;
            letter-spacing: -1px;
            background: linear-gradient(to bottom, #fff 50%, #f5c04a);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 30px;
            font-weight: 500;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #f5c04a, #d97706);
            color: #020617;
            text-decoration: none;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            cursor: pointer;
        }

        .hero-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -10px rgba(245, 192, 74, 0.4);
            filter: brightness(1.08);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 5% 60px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .product-card {
            background: rgba(15, 23, 42, 0.4);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
            border-color: rgba(245, 192, 74, 0.3);
            background: rgba(15, 23, 42, 0.6);
        }

        .product-image {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 1));
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--glass-border);
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
            transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));
        }

        .product-card:hover .product-image img {
            transform: scale(1.1) rotate(2deg);
        }

        .product-info {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #ffffff;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 3em;
            line-height: 1.5;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 900;
            color: #ffffff;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stock-badge {
            font-size: 0.65rem;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-in { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .stock-out { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1.2fr 0.5fr;
            gap: 8px;
        }

        .btn-action {
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-details {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-details:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-add-cart {
            background: rgba(245, 192, 74, 0.1);
            color: var(--primary);
            border: 1px solid rgba(245, 192, 74, 0.2);
        }

        .btn-add-cart:hover {
            background: var(--primary);
            color: #020617;
            box-shadow: 0 10px 20px -5px rgba(245, 192, 74, 0.3);
        }

        .btn-share {
            background: rgba(255, 255, 255, 0.03);
            color: #64748b;
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 16px;
            padding: 0;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .btn-share:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
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
            color: #94a3b8;
        }
        .share-popup a:hover {
            background: rgba(251,191,36,0.1);
            color: #f5c04a;
        }
        .share-popup a svg { width: 18px; height: 18px; }

        /* Cart Drawer */
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
        .cart-item-price { color: var(--primary); font-weight: 800; font-size: 0.9rem; }
        .cart-item-remove { cursor: pointer; color: #ef4444; font-size: 0.8rem; position: absolute; right: 0; bottom: 20px; }
        .cart-footer { margin-top: 30px; padding-top: 30px; border-top: 2px solid var(--glass-border); }
        .cart-total { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; color: white; margin-bottom: 25px; }
        .btn-checkout { width: 100%; padding: 18px; background: var(--primary); color: #020617; border: none; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }

        /* Floating Cart Icon */
        .cart-trigger {
            position: fixed; top: 120px; right: 30px; width: 60px; height: 60px;
            background: #0f172a; border: 1px solid var(--glass-border);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 500; cursor: pointer;
            transition: transform 0.3s;
        }
        .cart-trigger:hover { transform: scale(1.1); background: var(--primary); }
        .cart-trigger:hover svg { fill: #020617; }
        .cart-count { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 12px; font-weight: 800; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #0f172a; }
        .cart-trigger svg { width: 24px; height: 24px; fill: white; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 6, 23, 0.9);
            backdrop-filter: blur(10px);
            display: none; justify-content: center; align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #0f172a;
            width: 900px; max-width: 95vw;
            max-height: 90vh;
            border-radius: 32px;
            border: 1px solid var(--glass-border);
            display: flex; overflow: hidden;
            position: relative;
        }

        .modal-close {
            position: absolute; top: 20px; right: 20px;
            background: rgba(255,255,255,0.05); border: none;
            color: white; width: 40px; height: 40px; border-radius: 50%;
            cursor: pointer; font-size: 24px; z-index: 10;
        }

        .modal-left { flex: 1; background: #020617; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .modal-left img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .modal-right { flex: 1.2; padding: 60px; overflow-y: auto; }
        .modal-title { font-size: 2rem; font-weight: 800; margin-bottom: 10px; color: white; }
        .modal-price { font-size: 1.8rem; font-weight: 900; color: var(--primary); margin-bottom: 30px; }
        .modal-desc-title { font-size: 0.9rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; }
        .modal-desc { color: #94a3b8; font-size: 1.1rem; line-height: 1.8; margin-bottom: 40px; }

        /* Checkout Modal Form */
        .checkout-form { display: grid; gap: 20px; margin-top: 20px; }
        .checkout-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .input-field { background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 10px; padding: 12px; color: white; font-family: inherit; }
        .input-field:focus { outline: none; border-color: var(--primary); background: rgba(255,255,255,0.06); }

        /* ── FOOTER ── */
        html, body { height: 100%; }
        .page-wrap { min-height: 100vh; display: flex; flex-direction: column; }
        .page-main { flex: 1; }
        .site-footer {
            background: rgba(15, 23, 42, 0.6);
            border-top: 1px solid var(--glass-border);
            padding: 60px 5% 0;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        .footer-grid {
            max-width: 1400px; margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 48px;
            padding-bottom: 48px;
        }
        .footer-col h3 {
            font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.5px;
            color: #f5c04a; margin-bottom: 20px;
        }
        .footer-col .brand { font-size: 1.1rem; font-weight: 900; background: linear-gradient(to right, #f5c04a, #f59e0b); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px; }
        .footer-col p { color: #64748b; font-size: 0.82rem; line-height: 1.8; }
        .footer-col a {
            display: block; color: #64748b; text-decoration: none;
            font-size: 0.82rem; padding: 5px 0;
            transition: color 0.3s;
        }
        .footer-col a:hover { color: #f5c04a; }
        .footer-social { display: flex; gap: 10px; margin-top: 16px; }
        .footer-social a {
            width: 38px; height: 38px;
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .footer-social a:hover { border-color: #f5c04a; background: rgba(251,191,36,0.1); }
        .footer-social a svg { width: 16px; height: 16px; }
        .footer-bottom {
            border-top: 1px solid var(--glass-border);
            padding: 24px 5%;
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
        }
        .footer-bottom strong { color: #f5c04a; }
        @media (max-width: 900px) {
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
        }
        @media (max-width: 600px) {
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
            .site-footer { padding: 40px 5% 0; }
        }

        /* Floating WhatsApp */
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

        /* Responsive adjustments */
        @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
        .search-bar {
            max-width: 500px; margin: 0 auto 40px;
            display: flex; gap: 0;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            overflow: hidden;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .search-bar:focus-within {
            border-color: rgba(245,192,74,0.3);
            box-shadow: 0 0 0 4px rgba(251,191,36,0.06);
        }
        .search-bar input {
            flex: 1; padding: 14px 18px;
            background: transparent; border: none;
            color: #f8fafc; font-size: 0.9rem;
            outline: none; font-family: inherit;
        }
        .search-bar input::placeholder { color: #475569; }
        .search-bar button {
            padding: 0 18px;
            background: rgba(255,255,255,0.04); border: none;
            color: #cbd5e1; cursor: pointer;
            display: flex; align-items: center;
            transition: all 0.2s;
        }
        .search-bar button:hover { background: rgba(251,191,36,0.1); color: #f5c04a; }
        .search-bar button svg { width: 20px; height: 20px; stroke-width: 2.5; }

        .scroll-top {
            position: fixed; bottom: 30px; left: 30px;
            width: 46px; height: 46px;
            border-radius: 50%;
            background: rgba(251,191,36,0.12);
            border: 1px solid rgba(251,191,36,0.2);
            color: #f5c04a;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 99;
            opacity: 0; visibility: hidden;
            transform: translateY(20px);
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
            backdrop-filter: blur(12px);
        }
        .scroll-top.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .scroll-top:hover { background: rgba(251,191,36,0.2); transform: translateY(-3px); }
        .scroll-top svg { width: 20px; height: 20px; }

        @media (max-width: 768px) {
            .hero h2 { font-size: 2.2rem; }
            .product-grid { grid-template-columns: 1fr; }
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
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="28" height="28" rx="6" stroke="#f5c04a" stroke-width="2"/>
                <path d="M10 16L14 20L22 12" stroke="#f5c04a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>SHERIFF SHEVVY</h1>
    </div>
    <button class="hamburger" id="hamburger" onclick="toggleNav()">
        <span></span><span></span><span></span>
    </button>
    <nav id="navLinks">
        <a href="index.php">Home</a>
        <a href="#products">Products</a>
        <a href="#contact">Contact</a>
    </nav>
    <a href="login.php" class="login-btn">Admin Login</a>
</header>

<div class="cart-trigger" id="cartTrigger" onclick="toggleCart()">
    <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
    <span class="cart-count" id="cartCount">0</span>
</div>

<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <h2 class="cart-title">Your Order</h2>
        <button class="modal-close" style="position: static;" onclick="toggleCart()">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total Amount</span>
            <span id="cartTotal">₦0.00</span>
        </div>
        <button class="btn-checkout" onclick="openCheckout()">Checkout & Pay on Delivery</button>
    </div>
</div>

<div class="modal-overlay" id="checkoutModal">
    <div class="modal-content" style="width: 600px; flex-direction: column; padding: 40px; overflow-y: auto;">
        <button class="modal-close" onclick="closeCheckout()">&times;</button>
        <h2 class="modal-title">Checkout Details</h2>
        <p style="color: #64748b; margin-bottom: 30px;">Complete your order for Pay on Delivery.</p>
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
                    <label>Payment Method</label>
                    <input type="text" class="input-field" value="Pay on Delivery" disabled>
                </div>
            </div>
            <div class="input-group">
                <label>Delivery Address</label>
                <textarea class="input-field" name="customer_address" rows="3" required placeholder="Street name, City, State"></textarea>
            </div>
            <button type="submit" class="btn-checkout" style="margin-top: 20px;">Place Order Now</button>
        </form>
    </div>
</div>

<section class="hero">
    <h2>Premium Foreign Used Laptops</h2>
    <p>Discover high-quality, tested, and reliable hardware. Managed by SHERIFF SHEVVY with absolute precision.</p>
</section>

<div class="container">
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search products..." oninput="filterProducts()">
        <button><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></button>
    </div>
    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
<div class="product-image">
                        <img src="<?php echo $product['image_url'] ?: 'uploads/products/dell/sheriff_login_3d.png'; ?>" alt="Product">
                    </div>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <div class="product-price">
                        <span>₦<?php echo number_format($product['selling_price'], 2); ?></span>
                        <span class="stock-badge <?php echo $product['stock_quantity'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                        </span>
                    </div>
                    <div class="card-actions">
                        <button class="btn-action btn-details" onclick='openProductModal(<?php echo json_encode($product); ?>)'>Details</button>
                        <button class="btn-action btn-add-cart" onclick='addToCart(<?php echo json_encode($product); ?>)'>Add to Cart</button>
                        <button class="btn-action btn-share" onclick='event.stopPropagation();toggleShare(this, <?php echo json_encode($product); ?>)' title="Share">&#x2191;</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <div class="modal-left">
            <img id="modalImg" src="" alt="">
        </div>
        <div class="modal-right">
            <div id="modalCat" class="product-category"></div>
            <h2 id="modalTitle" class="modal-title"></h2>
            <div id="modalPrice" class="modal-price"></div>
            <div class="modal-desc-title">Product Description</div>
            <p id="modalDesc" class="modal-desc"></p>
            <button id="modalAddCart" class="btn-action btn-add-cart" style="display: block; width: 100%; padding: 18px; font-size: 14px;">Add to Cart</button>
        </div>
    </div>
</div>

<a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" class="whatsapp-float">
    <svg viewBox="0 0 448 512"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.7 19.3 70.1 29.5 106.2 29.5 122.4 0 222-99.6 222-222 0-59.3-23.1-115.1-65-157.1zM223.9 446.3c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.7-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.5-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18c-5.1-1.9-8.8-2.8-12.5 3.1-3.7 5.9-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.2-16.4-14.5-27.4-32.5-30.6-38.1-3.2-5.6-.3-8.6 2.5-11.4 2.5-2.5 5.5-6.5 8.3-9.7 2.8-3.2 3.7-5.5 5.6-9.2 1.9-3.7 1-7-0.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-0.2-6.9-0.2-10.6-0.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.9-19.1 19.1-19.1 46.5 0 27.4 20 53.9 22.8 57.6 2.8 3.7 39.4 63.9 98.9 87.4 60 23.8 80.5 19.8 94.6 18.5 14.1-1.3 45.4-18.5 51.9-36.5 6.4-18 6.5-33.3 4.6-36.5-1.9-3.2-6.9-5.2-12.4-8z"/></svg>
</a>

<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg>
</button>

<footer class="site-footer" id="contact">
    <div class="footer-grid">
        <div class="footer-col">
            <div class="brand">SHERIFF SHEVVY</div>
            <p style="margin-bottom:8px;">Premium foreign-used laptops &amp; enterprise-grade computing solutions. Quality tested, reliable hardware, handcrafted excellence.</p>
            <p style="margin-bottom:16px;font-size:0.75rem;color:#64748b;border-left:2px solid rgba(245,192,74,0.3);padding-left:12px;"><em>Excellence in every detail — from our workshop to your doorstep.</em></p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="#64748b"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="#64748b"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5"/></svg>
                </a>
                <a href="#" aria-label="Twitter">
                    <svg viewBox="0 0 24 24" fill="#64748b"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                </a>
                <a href="#" aria-label="LinkedIn">
                    <svg viewBox="0 0 24 24" fill="#64748b"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2zM4 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                </a>
            </div>
        </div>
        <div class="footer-col">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="#products">Products</a>
            <a href="login.php">Admin Portal</a>
        </div>
        <div class="footer-col">
            <h3>Products</h3>
            <a href="#">Laptops</a>
            <a href="#">Desktops</a>
            <a href="#">Accessories</a>
            <a href="#">Enterprise</a>
        </div>
        <div class="footer-col">
            <h3>Contact</h3>
            <p style="margin-bottom:6px;">Lalubu Street, Oke-Ilewo, Ibara, Abeokuta, Ogun State, Nigeria</p>
            <p style="margin-bottom:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <a href="tel:<?php echo $whatsapp_number_raw; ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:rgba(251,191,36,0.1);color:#f5c04a;transition:all 0.2s;" title="Call us">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </a>
                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:rgba(37,211,102,0.1);color:#25D366;transition:all 0.2s;" title="WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <span style="color:#94a3b8;font-size:0.88rem;"><?php echo $whatsapp_number_raw; ?></span>
            </p>
            <p><a href="mailto:akintundesheriff09@gmail.com" style="display:inline;">akintundesheriff09@gmail.com</a></p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> <strong>SHERIFF SHEVVY ENTERPRISES</strong>. All rights reserved. Handcrafted for Excellence.
    </div>
</footer>

<script>
// Mobile nav toggle
function toggleNav() {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('hamburger').classList.toggle('active');
}
document.querySelectorAll('.nav-links a, #navLinks a').forEach(a => a.addEventListener('click', () => {
    document.getElementById('navLinks').classList.remove('open');
    document.getElementById('hamburger').classList.remove('active');
}));

// Share Logic
function toggleShare(el, product) {
    const popup = el.querySelector('.share-popup') || createSharePopup(el, product);
    const isOpen = popup.classList.contains('open');
    document.querySelectorAll('.share-popup.open').forEach(p => p.classList.remove('open'));
    if (!isOpen) popup.classList.add('open');
}
function createSharePopup(el, product) {
    const div = document.createElement('div');
    div.className = 'share-popup';
    const url = encodeURIComponent(window.location.href.split('#')[0] + '?product=' + encodeURIComponent(product.product_name));
    const text = encodeURIComponent('Check out ' + product.product_name + ' at SHERIFF SHEVVY - \u20A6' + Number(product.selling_price).toLocaleString());
    div.innerHTML = `
        <a href="https://wa.me/?text=${text}%20${url}" target="_blank" title="WhatsApp" onclick="event.stopPropagation()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=${url}" target="_blank" title="Facebook" onclick="event.stopPropagation()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a href="https://twitter.com/intent/tweet?text=${text}&url=${url}" target="_blank" title="Twitter" onclick="event.stopPropagation()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
        </a>
        <a href="#" onclick="event.stopPropagation();navigator.clipboard.writeText(decodeURIComponent('${url}'));this.title='Copied!';setTimeout(()=>this.title='Copy Link',1500);return false;" title="Copy Link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </a>
    `;
    el.appendChild(div);
    return div;
}
// Close share popups on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-share')) {
        document.querySelectorAll('.share-popup.open').forEach(p => p.classList.remove('open'));
    }
});

// Cart Logic
let cart = JSON.parse(localStorage.getItem('sheriff_cart') || '[]');

function updateCartUI() {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    cartCount.textContent = cart.length;
    if (cart.length === 0) {
        cartItems.innerHTML = '<div style="text-align: center; color: #64748b; margin-top: 50px;">Your cart is empty.</div>';
        cartTotal.textContent = '₦0.00';
    } else {
        let total = 0;
        cartItems.innerHTML = cart.map((item, index) => {
            total += Number(item.selling_price);
            return `
                <div class="cart-item">
                    <div class="cart-item-img"><img src="${item.image_url ? STORE_URL + item.image_url : STORE_URL + 'uploads/products/dell/sheriff_login_3d.png'}"></div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.product_name}</div>
                        <div class="cart-item-price">₦${Number(item.selling_price).toLocaleString()}</div>
                    </div>
                    <div class="cart-item-remove" onclick="removeFromCart(${index})">Remove</div>
                </div>
            `;
        }).join('');
        cartTotal.textContent = '₦' + total.toLocaleString();
    }
    localStorage.setItem('sheriff_cart', JSON.stringify(cart));
}

function addToCart(product) {
    cart.push(product);
    updateCartUI();
    toggleCart(true);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function toggleCart(forceOpen = null) {
    const drawer = document.getElementById('cartDrawer');
    if (forceOpen === true) drawer.classList.add('open');
    else if (forceOpen === false) drawer.classList.remove('open');
    else drawer.classList.toggle('open');
}

function openCheckout() {
    if (cart.length === 0) {
        alert('Please add items to your cart first.');
        return;
    }
    document.getElementById('checkoutModal').style.display = 'flex';
}

function closeCheckout() {
    document.getElementById('checkoutModal').style.display = 'none';
}

document.getElementById('checkoutForm').onsubmit = async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const orderData = {
        name: formData.get('customer_name'),
        phone: formData.get('customer_phone'),
        address: formData.get('customer_address'),
        items: cart,
        total: cart.reduce((sum, item) => sum + Number(item.selling_price), 0)
    };
    let summary = "*NEW ORDER - Pay on Delivery*\n\n";
    summary += `*Customer:* ${orderData.name}\n`;
    summary += `*Phone:* ${orderData.phone}\n`;
    summary += `*Address:* ${orderData.address}\n\n`;
    summary += `*Items:*\n`;
    orderData.items.forEach((item, i) => { summary += `${i+1}. ${item.product_name} (₦${Number(item.selling_price).toLocaleString()})\n`; });
    summary += `\n*TOTAL: ₦${orderData.total.toLocaleString()}*`;
    const waUrl = `https://wa.me/<?php echo $whatsapp_number; ?>?text=${encodeURIComponent(summary)}`;
    
    // Save to DB first
    try {
        await fetch('api/web_order.php', {
            method: 'POST', body: JSON.stringify(orderData)
        });
    } catch (err) { console.error('DB preservation failed', err); }

    cart = [];
    updateCartUI();
    closeCheckout();
    toggleCart(false);
    window.open(waUrl, '_blank');
    alert('Order placed successfully! Redirecting to WhatsApp for confirmation.');
};

updateCartUI();

function openProductModal(product) {
    document.getElementById('modalImg').src = product.image_url ? STORE_URL + product.image_url : STORE_URL + 'uploads/products/dell/sheriff_login_3d.png';
    document.getElementById('modalCat').textContent = product.category;
    document.getElementById('modalTitle').textContent = product.product_name;
    document.getElementById('modalPrice').textContent = '₦' + Number(product.selling_price).toLocaleString();
    const cleanDesc = (product.description || "No description available.").replace(/\n/g, '<br>');
    document.getElementById('modalDesc').innerHTML = cleanDesc;
    document.getElementById('modalAddCart').onclick = function() { addToCart(product); closeModal(); };
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
}

// Search
function filterProducts() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.querySelector('.product-name').textContent.toLowerCase();
        const cat = card.querySelector('.product-category').textContent.toLowerCase();
        card.style.display = name.includes(q) || cat.includes(q) ? '' : 'none';
    });
}

// Scroll to top
window.addEventListener('scroll', () => {
    document.getElementById('scrollTop').classList.toggle('visible', window.scrollY > 200);
});
</script>
</div>
</div>
</body>
</html>
