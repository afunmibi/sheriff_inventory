<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SHERIFF SHEVVY ENTERPRISES</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #020617;
            height: 100vh; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(251,191,36,0.08) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 75%, rgba(99,102,241,0.05) 0%, transparent 55%),
                radial-gradient(ellipse at 50% 50%, rgba(251,191,36,0.03) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        .orb {
            position: fixed; border-radius: 50%; filter: blur(80px);
            pointer-events: none; z-index: 0;
            animation: orbFloat 12s ease-in-out infinite alternate;
        }
        .orb-1 { width: 300px; height: 300px; background: rgba(251,191,36,0.04); top: -80px; right: -80px; }
        .orb-2 { width: 250px; height: 250px; background: rgba(99,102,241,0.03); bottom: -60px; left: -60px; animation-delay: -4s; }
        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, -30px) scale(1.1); }
        }

        .login-wrapper {
            position: relative; z-index: 1;
            display: flex;
            width: 1060px; max-width: 100%;
            min-height: 560px; max-height: 95vh;
            background: linear-gradient(160deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 28px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            overflow: hidden;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .login-visual {
            flex: 1.3;
            padding: 0 48px;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            background: radial-gradient(circle at 50% 40%, rgba(251,191,36,0.06), transparent 60%);
            border-right: 1px solid rgba(255,255,255,0.06);
            position: relative;
        }

        .login-visual .brand-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, rgba(251,191,36,0.12), rgba(251,191,36,0.03));
            border: 1px solid rgba(251,191,36,0.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 28px;
        }
        .login-visual .brand-icon svg { width: 34px; height: 34px; }

        .login-visual h2 {
            font-size: 1.6rem; font-weight: 900;
            letter-spacing: 2px; color: #fff; margin-bottom: 8px;
        }
        .login-visual .divider {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, transparent, #f5c04a, transparent);
            margin: 12px auto;
        }
        .login-visual p {
            color: #64748b; font-size: 0.85rem;
            font-weight: 400; letter-spacing: 0.3px;
        }
        .login-visual .feature-list {
            margin-top: 32px; text-align: left; width: 100%; max-width: 280px;
        }
        .login-visual .feature-list .feat {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 0; color: #94a3b8; font-size: 0.8rem; font-weight: 400;
        }
        .login-visual .feature-list .feat svg { width: 16px; height: 16px; flex-shrink: 0; }

        .login-form-side {
            width: 460px; min-width: 380px;
            padding: 48px 48px 40px;
            display: flex; flex-direction: column;
            justify-content: center;
            background: rgba(2, 6, 23, 0.6);
        }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px;
        }
        .top-bar a {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px; padding: 8px 16px;
            color: #94a3b8; text-decoration: none;
            font-size: 0.78rem; font-weight: 600;
            transition: all 0.3s;
        }
        .top-bar a:hover {
            background: rgba(251,191,36,0.1);
            border-color: rgba(251,191,36,0.3);
            color: #f5c04a;
        }
        .top-bar a svg { width: 14px; height: 14px; }

        .login-logo h1 {
            font-size: 1.6rem; font-weight: 900;
            background: linear-gradient(135deg, #fff 30%, #f5c04a);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px; letter-spacing: -0.3px;
        }
        .login-logo p {
            color: #64748b; font-size: 0.85rem;
            margin-bottom: 28px; font-weight: 400;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; margin-bottom: 6px;
            font-size: 0.7rem; font-weight: 700;
            color: #94a3b8; text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px 18px;
            color: #fff;
            font-size: 0.9rem; font-family: inherit;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #f5c04a;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 0 3px rgba(245, 192, 74, 0.08);
        }
        .form-control::placeholder { color: #475569; }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #f5c04a, #d97706);
            color: #020617;
            padding: 15px;
            border: none; border-radius: 12px;
            font-size: 0.9rem; font-weight: 800; font-family: inherit;
            cursor: pointer;
            transition: all 0.4s;
            text-transform: uppercase; letter-spacing: 1px;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px -8px rgba(251, 191, 36, 0.35);
            filter: brightness(1.08);
        }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .alert {
            padding: 12px 18px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            font-size: 0.82rem;
            margin-bottom: 20px;
            display: none;
        }
        .alert.show { display: block; }

        .cred-box {
            background: rgba(251, 191, 36, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.12);
            border-radius: 12px;
            padding: 14px 18px;
            margin-top: 20px;
            text-align: center;
        }
        .cred-box .cred-label {
            color: #f5c04a;
            font-size: 0.65rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 6px;
        }
        .cred-box .cred-row {
            display: flex; justify-content: center; gap: 16px;
            font-size: 0.78rem; color: #94a3b8; flex-wrap: wrap;
        }
        .cred-box .cred-row strong { color: #e2e8f0; }

        .login-footer {
            margin-top: auto; padding-top: 20px;
            text-align: center;
            color: #475569;
            font-size: 0.7rem;
            line-height: 1.6;
        }

        .spinner {
            display: inline-block; width: 16px; height: 16px;
            border: 2px solid rgba(2,6,23,0.2);
            border-top-color: #020617;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle; margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .login-visual { display: none; }
            .login-form-side { width: 100%; min-width: 0; padding: 36px 28px; }
            .login-wrapper { min-height: 0; max-height: none; }
            body { height: auto; min-height: 100dvh; overflow: auto; padding: 12px; }
            .orb-1, .orb-2 { display: none; }
        }
        @media (max-width: 480px) {
            .login-form-side { padding: 28px 18px; }
            .top-bar { flex-direction: column; gap: 10px; align-items: stretch; }
            .top-bar a { justify-content: center; }
            .login-logo h1 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-wrapper">
        <div class="login-visual">
            <div class="brand-icon">
                <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="28" height="28" rx="6" stroke="#f5c04a" stroke-width="2"/>
                    <path d="M10 16L14 20L22 12" stroke="#f5c04a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2>SHERIFF SHEVVY</h2>
            <div class="divider"></div>
            <p>Enterprise Management Suite</p>
            <div class="feature-list">
                <div class="feat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5c04a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Inventory Control
                </div>
                <div class="feat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5c04a" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Sales &amp; POS
                </div>
                <div class="feat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5c04a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Reports &amp; Analytics
                </div>
            </div>
        </div>

        <div class="login-form-side">
            <div class="top-bar">
                <a href="./">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Visit Storefront
                </a>
                <a href="./gateway" style="background:rgba(245,192,74,0.08);border-color:rgba(245,192,74,0.15);color:#f5c04a;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Gateway
                </a>
            </div>

            <div class="login-logo">
                <h1>Sign In</h1>
                <p>Authorised personnel only</p>
            </div>

            <div class="alert" id="errorAlert"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email"
                           placeholder="admin@sheriffenterprises.com" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password"
                           placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
                    Sign In
                </button>
            </form>

            <div class="cred-box">
                <div class="cred-label">Admin Credentials</div>
                <div class="cred-row">
                    <span>Email: <strong>admin@sheriffenterprises.com</strong></span>
                    <span>Pass: <strong>Admin@123</strong></span>
                </div>
            </div>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> SHERIFF SHEVVY ENTERPRISES.<br>
                Handcrafted for Excellence. | <a href="tel:+2348062328638" style="color:#f5c04a;text-decoration:none;">Shevvy Technologies</a>
            </div>
        </div>
    </div>

    <script src="assets/js/core/utils.js"></script>
    <script src="assets/js/services/apiClient.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const user = Utils.storage.get('user');
            if (user) { window.location.href = 'gateway.html'; return; }

            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const errorAlert = document.getElementById('errorAlert');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const email = form.email.value.trim();
                const password = form.password.value;

                if (!email || !password) {
                    showError('Please enter email and password');
                    return;
                }

                loginBtn.disabled = true;
                loginBtn.innerHTML = '<span class="spinner"></span> Signing in...';
                errorAlert.classList.remove('show');

                try {
                    const response = await fetch('api/login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });
                    const data = await response.json();

                    if (data.success) {
                        Utils.storage.set('user', data.data.user);
                        Utils.storage.set('token', 'logged_in');
                        window.location.href = 'gateway.html';
                    } else {
                        showError(data.message || 'Invalid credentials');
                    }
                } catch (error) {
                    showError(error.message || 'Login failed');
                } finally {
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Sign In';
                }
            });

            function showError(message) {
                errorAlert.textContent = message;
                errorAlert.classList.add('show');
            }
        });
    </script>
</body>
</html>
