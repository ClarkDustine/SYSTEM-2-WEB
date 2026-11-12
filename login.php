<?php
require 'config/function.php';

if(isset($_SESSION['logged_in'])){
    header('Location: index.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Valley | Staff Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
        :root {
            --accent: #6a4127;
            --text-light: #352116;
            --text-muted: #806b59;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-image: url('admin/assets/img/BeigeBG.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: var(--text-light);
            overflow: hidden;
        }
        .page-layer {
            position: fixed;
            inset: 0;
            background: rgba(247,240,228,0.24);
            pointer-events: none;
        }
        .page-content {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 3rem 16rem 3rem 3rem;
            gap: 2rem;
            z-index: 10;
        }
        .login-card {
            position: relative;
            width: 100%;
            max-width: 400px;
            background: rgba(255,250,241,0.93);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid #ddc5a4;
            border-radius: 28px;
            box-shadow: 0 16px 40px rgba(78,49,29,0.18);
            overflow: visible;
            padding: 0; /* body will handle inner padding */
        }
        .login-card::before {
            content: '';
            position: absolute;
            inset: -12px;
            z-index: -1;
            border-radius: 30px;
            background: radial-gradient(ellipse at center, rgba(214,173,120,0.36), rgba(214,173,120,0.10) 35%, transparent 65%);
            filter: blur(26px);
            opacity: 0.95;
        }
        .login-card-header {
            display: grid;
            place-items: center;
            gap: 0.7rem;
            text-align: center;
            padding: 1.5rem 1.5rem 1rem;
        }
        .login-card-header .icon-wrap {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #f0ddc0, #e3c49d);
            border: 1px solid #d4ad78;
            color: var(--accent);
            font-size: 2rem;
            box-shadow: 0 8px 24px rgba(78,49,29,0.12);
        }
        .login-card-header h2 {
            font-size: 1.75rem;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .login-card-header p {
            margin: 0;
            color: var(--text-muted);
        }
        .login-card-body {
            padding: 30px 25px;
        }
        .form-label {
            color: #6a4127;
            font-size: 0.95rem;
        }
        .form-control {
            background: #fffdf8;
            border: 1px solid #ddc5a4;
            color: #352116;
            border-radius: 14px;
            height: 45px;
            font-size: 16px;
            padding: 0.65rem 0.95rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(166,106,63,0.14);
            border-color: #a66a3f;
            background: #fffdf8;
            color: #352116;
        }
        .form-check-label { color: #806b59; }
        .forgot-link {
            color: #6a4127;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: #6a4127;
            border: 1px solid #6a4127;
            border-radius: 14px;
            height: 45px;
            font-size: 18px;
            font-weight: 700;
            padding: 0 1rem;
            box-shadow: 0 12px 24px rgba(78,49,29,0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary:hover { transform: translateY(-1px); background: #352116; border-color: #352116; }
        .mobile-home-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            margin: 0.25rem 0 1.25rem;
            border: 1px solid #6a4127;
            border-radius: 14px;
            color: #6a4127;
            font-weight: 700;
            text-decoration: none;
        }
        .mobile-home-button:hover { background: #f3e4cd; color: #352116; }
        .divider-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.75rem 0 1rem;
        }
        .divider-row span {
            flex: 1;
            height: 1px;
            background: #e3c49d;
        }
        .divider-row small {
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.8rem;
        }
        .google-btn {
            border-radius: 16px;
            border: 1px solid #ddc5a4;
            color: #352116;
            background: #fffdf8;
            padding: 0.95rem 1rem;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        .google-btn:hover {
            background: #f3e4cd;
        }
        .google-btn i {
            width: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.85rem;
            color: #6a4127;
        }
        .login-footer {
            text-align: center;
            margin-top: 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        @media (max-width: 992px) {
            .page-content {
                justify-content: center;
                padding: 2.5rem 1.5rem;
            }
            .login-card { max-width: 520px; }
        }
        @media (max-width: 768px) {
            .page-content {
                padding: 1.5rem 1rem;
            }
            .login-card { max-width: 100%; border-radius: 18px; }
            .login-card-body { padding: 28px 20px; }
            .login-card-header .icon-wrap { width: 64px; height: 64px; font-size: 1.2rem; }
            .login-card-header h2 { font-size: 1.45rem; }
            .form-control { height: 48px; }
            .btn-primary { height: 48px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="page-layer"></div>
    <div class="page-content">
        <div class="login-card">
            <div class="login-card-header">
                <div class="icon-wrap"><i class="fas fa-shopping-cart"></i></div>
                <div>
                    <h2>LOGIN</h2>
                    <p>Please sign in to continue</p>
                </div>
            </div>
            <div class="login-card-body">
                <?php alertMessage(); ?>
                <form action="login-code.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <a class="forgot-link" href="#">Forgot password?</a>
                    </div>
                    <button type="submit" name="loginBtn" class="btn btn-primary w-100 mb-3">Login</button>
                </form>
                <a class="mobile-home-button" href="../">Home</a>
                <div class="divider-row">
                    <span></span>
                    <small>OR</small>
                    <span></span>
                </div>
                <button type="button" class="btn google-btn w-100">
                    <i class="fab fa-google"></i>
                    Sign in with Google
                </button>
                <div class="login-footer">© 2026 The Valley Coffee Shop. All rights reserved.</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html> 
