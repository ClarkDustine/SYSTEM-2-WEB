<?php
// Public homepage for Sweet Valley The Coffee.
// The application is kept in its existing subdirectory so this page can be
// served directly from the Apache/ngrok web root.
$appDirectory = 'Online-Point-Sale-and-Inventory-Management-System-for-The-Valley';
$appUrl = '/' . rawurlencode($appDirectory) . '/';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$loggedIn = isset($_SESSION['logged_in']);
$img = $appUrl . 'admin/assets/img/';
$loginUrl = $appUrl . 'login.php';
$dashboardUrl = $appUrl . 'admin/index.php';
$customerUrl = $appUrl . 'order-product.php#products';

$customerSteps = [
    ['Browse the menu', 'Scroll through the menu and featured products to see what is currently available.'],
    ['Add your products', 'Choose a quantity and tap Add. Your selected products will appear in Your order.'],
    ['Review your order', 'Check the quantities and total. You can update quantities or remove an item before submitting.'],
    ['Choose pickup or delivery', 'For delivery, enter your complete address. Delivery orders use Cash on Delivery; pickup orders use Cash on Pickup. The shop will confirm any delivery fee.'],
    ['Enter your contact details', 'Provide your name and phone number so the shop can confirm your pre-order.'],
    ['Submit and keep your receipt', 'Send your pre-order, then print the order confirmation and wait for the shop to confirm it.'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sweet Valley The Coffee | Milk tea, coffee, frappe and bubble waffles</title>
    <meta name="description" content="Milk tea, coffee, frappe, bubble waffles and fries at Sweet Valley The Coffee.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <style>
        :root {
            --espresso: #2b1a10;
            --seal:     #6a4127;   /* same accent as the login page */
            --kraft:    #c9a37a;
            --sand:     #f3e8d7;   /* same beige as the admin theme */
            --paper:    #fbf6ec;
            --ink:      #352116;
            --muted:    #806b59;
        }
        html { scroll-behavior: smooth; scroll-padding-top: 4.5rem; }
        body {
            background-color: var(--paper);
            background-image: linear-gradient(rgba(251, 246, 236, .80), rgba(251, 246, 236, .88)), url('<?= htmlspecialchars($img) ?>BeigeBG.png');
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }
        h1, h2, h3 { font-family: Georgia, "Times New Roman", serif; font-weight: 700; line-height: 1.1; letter-spacing: -0.01em; }
        a:focus-visible, button:focus-visible { outline: 3px solid var(--seal); outline-offset: 3px; }
        img { max-width: 100%; height: auto; }

        /* top bar */
        .bar { background: var(--sand); border-bottom: 1px solid rgba(106,65,39,.25); position: sticky; top: 0; z-index: 50; }
        .bar .brand { display: inline-flex; align-items: center; color: var(--espresso); text-decoration: none; }
        .bar .brand-logo { display: block; width: 32px !important; height: 32px !important; max-width: 32px; max-height: 32px; object-fit: contain; flex: 0 0 32px; }
        .bar .brand-name { color: var(--espresso); font-family: Georgia, "Times New Roman", serif; font-size: 1.1rem; font-weight: 700; white-space: nowrap; }
        .bar .brand small { font-family: system-ui, sans-serif; font-weight: 400; color: var(--muted); font-size: .85rem; margin-left: .4rem; }
        .bar a.link { color: var(--ink); text-decoration: none; padding: .35rem .7rem; }
        .bar a.link:hover { text-decoration: underline; }
        .btn-seal { background: var(--seal); border: 1px solid var(--seal); color: #fffaf1; font-weight: 600; border-radius: 4px; padding: .55rem 1.1rem; }
        .btn-seal:hover { background: var(--espresso); border-color: var(--espresso); color: #fff; }
        .btn-line { border: 1px solid var(--seal); color: var(--seal); font-weight: 600; border-radius: 4px; padding: .55rem 1.1rem; background: transparent; }
        .btn-line:hover { background: var(--seal); color: #fffaf1; }

        /* hero */
        .hero { background: rgba(243, 232, 215, .88); padding: 3.5rem 0 4rem; }
        .hero h1 { max-width: 14ch; font-size: clamp(2.8rem, 7vw, 5.4rem); color: var(--espresso); margin-bottom: 1rem; }
        .hero p.lead { max-width: 34rem; color: var(--ink); }
        .hero-buttons { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.5rem; }
        .hero-media { display: flex; align-items: center; justify-content: center; }
        .hero-video { display: block; width: min(calc(100% + 24px), 560px); max-width: none; aspect-ratio: 5 / 3; height: auto; object-fit: cover; pointer-events: none; user-select: none; border: 1px solid rgba(106, 65, 39, .25); border-radius: 8px; box-shadow: 0 8px 22px rgba(53, 33, 22, .16); background: #1d110a; }
        .stamp { display: inline-block; background: var(--seal); color: #fffaf1; font-family: Georgia, serif; padding: .25rem .8rem; border-radius: 3px; transform: rotate(-2deg); margin-bottom: 1.2rem; }

        /* sections */
        .block { padding: 4.5rem 0; }
        .block h2 { font-size: clamp(2rem, 4.5vw, 3rem); margin-bottom: .8rem; color: var(--espresso); }
        .block .intro { max-width: 36rem; color: var(--muted); margin-bottom: 2rem; }
        .dark { background: var(--espresso); color: #f3e8d7; }
        .dark h2 { color: #fffaf1; }
        .dark .intro { color: #d9c4a8; }

        /* menu boards */
        .menu-card { height: 100%; background: rgba(255, 250, 241, .88); padding: .7rem; border: 1px solid rgba(106, 65, 39, .24); border-radius: 6px; box-shadow: 0 8px 24px rgba(53, 33, 22, .10); }
        .menu-card img { width: 100%; height: 100%; max-height: 700px; object-fit: cover; object-position: top; border-radius: 3px; }
        .menu-card figcaption { padding: .8rem .3rem .1rem; color: var(--muted); font-size: .95rem; font-weight: 600; }

        /* product advertisements */
        .feature-card { height: 100%; overflow: hidden; border: 0; border-radius: 5px; background: #fffaf1; box-shadow: 0 10px 24px rgba(0, 0, 0, .20); }
        .feature-card img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; object-position: center; }
        .feature-card figcaption { padding: .85rem 1rem 1rem; color: var(--espresso); font-family: Georgia, serif; font-size: 1.15rem; font-weight: 700; }

        /* customer ordering guide */
        .customer-steps { list-style: none; margin: 0; padding: 0; counter-reset: customer-step; }
        .customer-steps li { position: relative; padding: 1rem 0 1rem 3rem; border-top: 1px solid rgba(243,232,215,.25); counter-increment: customer-step; }
        .customer-steps li::before { content: counter(customer-step, decimal-leading-zero); position: absolute; left: 0; top: 1rem; color: var(--kraft); font-weight: 700; letter-spacing: .08em; }
        .customer-steps h3 { font-size: 1.2rem; margin: 0 0 .2rem; color: #fffaf1; }
        .customer-steps p { margin: 0; color: #d9c4a8; }
        .dark .btn-line { border-color: var(--kraft); color: var(--kraft); }
        .dark .btn-line:hover { background: var(--kraft); color: var(--espresso); }

        footer { background: #1d110a; color: #c9b79e; padding: 2rem 0; font-size: .95rem; }
        footer a { color: #f3e8d7; }
        @media (max-width: 991.98px) {
            .bar .container { flex-wrap: wrap; row-gap: .55rem; }
            .bar nav { width: 100%; justify-content: flex-end; flex-wrap: wrap; }
        }
        @media (max-width: 575.98px) {
            .bar .container { padding-top: .65rem !important; padding-bottom: .65rem !important; }
            .bar nav { justify-content: space-between; gap: .35rem !important; }
            .bar nav .btn { flex: 1 1 calc(50% - .25rem); margin-left: 0 !important; text-align: center; }
            .bar a.link { padding: .3rem .45rem; }
            .hero { padding: 2rem 0 2.75rem; }
            .hero h1 { max-width: 12ch; font-size: clamp(2.3rem, 10vw, 3.5rem); }
            .hero p.lead { font-size: 1.05rem !important; }
            .hero .d-flex .btn { flex: 1 1 auto; text-align: center; }
            .block { padding: 2.75rem 0; }
            .menu-card { padding: .55rem; }
            .menu-card img { height: auto; max-height: none; object-fit: contain; }
            .feature-card img { aspect-ratio: 4 / 3; }
        }
    </style>
</head>
<body>

<header class="bar">
    <div class="container d-flex align-items-center justify-content-between py-2">
        <a class="brand" href="./" aria-label="Sweet Valley The Coffee home"><img class="brand-logo" src="<?= htmlspecialchars($img . 'removedbackgroundlogo.png') ?>" alt="" width="32" height="32"><span class="brand-name">The Valley</span></a>
        <nav class="d-flex align-items-center gap-1">
            <a class="link d-none d-sm-inline" href="#menu">Menu</a>
            <a class="link d-none d-sm-inline" href="#featured">Featured</a>
            <a class="link d-none d-md-inline" href="#customers">For customers</a>
            <a class="btn btn-line ms-2" href="<?= htmlspecialchars($customerUrl) ?>">Customer</a>
            <?php if ($loggedIn): ?>
                <a class="btn btn-seal ms-2" href="<?= htmlspecialchars($dashboardUrl) ?>">Open dashboard</a>
            <?php else: ?>
                <a class="btn btn-seal ms-2" href="<?= htmlspecialchars($loginUrl) ?>">Staff login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>

<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="stamp">Open daily. Tuloy po kayo!</span>
                <h1>Milk tea, coffee and waffles by the cup.</h1>
                <p class="lead fs-5">Sweet Valley The Coffee serves iced and hot coffee, milk tea, fruit tea, frappe, bubble waffles and flavored fries. Cash and GCash accepted at the counter.</p>
                <div class="hero-buttons">
                    <a class="btn btn-seal btn-lg" href="#menu">See the menu</a>
                    <a class="btn btn-line btn-lg" href="#menu">Waffles and fries</a>
                </div>
            </div>
            <div class="col-lg-6 hero-media">
                <video class="hero-video" autoplay muted loop playsinline disablepictureinpicture preload="metadata" aria-label="Sweet Valley homepage video">
                    <source src="<?= htmlspecialchars($img . 'homepagevideobackground.mp4') ?>" type="video/mp4">
                    Your browser does not support the video.
                </video>
            </div>
        </div>
    </div>
</section>

<section class="block" id="menu">
    <div class="container">
        <h2>Menu</h2>
        <p class="intro">Choose from our drinks, bubble waffles and flavored fries. Each board below shows the available flavors and prices.</p>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-4">
                <figure class="menu-card mb-0">
                    <img src="<?= $img ?>menu.jpg" alt="Drinks menu with iced coffee, hot coffee, milk tea, fruit tea, frappe and fruit milk">
                    <figcaption>Drinks menu</figcaption>
                </figure>
            </div>
            <div class="col-lg-4">
                <figure class="menu-card mb-0">
                    <img src="<?= $img ?>menu2.jpg" alt="Bubble waffle flavors including choco almond dream, bubble overload, oreo swirl, banana treats, strawberry sensation and matcha magic">
                    <figcaption>Bubble waffles</figcaption>
                </figure>
            </div>
            <div class="col-lg-4">
                <figure class="menu-card mb-0">
                    <img src="<?= $img ?>menu3.jpg" alt="Flavored fries including sweet corn, white cheddar, sour cream, cheese and barbeque">
                    <figcaption>Flavored fries</figcaption>
                </figure>
            </div>
        </div>
    </div>
</section>

<section class="block dark" id="featured">
    <div class="container">
        <h2>Made to show off</h2>
        <p class="intro">A few favorites from the counter, made fresh and served in our signature cups.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <figure class="feature-card mb-0">
                    <img src="<?= $img ?>show1.jpg" alt="Oreo swirl bubble waffle in a kraft cup">
                    <figcaption>Oreo Swirl Bubble Waffle</figcaption>
                </figure>
            </div>
            <div class="col-md-4">
                <figure class="feature-card mb-0">
                    <img src="<?= $img ?>show3.jpg" alt="Chocolate frappe topped with whipped cream and chocolate chips">
                    <figcaption>Chocolate Frappe</figcaption>
                </figure>
            </div>
            <div class="col-md-4">
                <figure class="feature-card mb-0">
                    <img src="<?= $img ?>show4.jpg" alt="Two hot coffee cups held by a barista">
                    <figcaption>Freshly Brewed Coffee</figcaption>
                </figure>
            </div>
        </div>
    </div>
</section>

<section class="block dark" id="customers">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h2>For customers</h2>
                <p class="intro">Ordering is simple. Browse the menu, add your favorites, choose pickup or delivery, and send your pre-order to the shop.</p>
                <a class="btn btn-line btn-lg" href="<?= htmlspecialchars($customerUrl) ?>">Start ordering</a>
            </div>
            <div class="col-lg-7">
                <ol class="customer-steps">
                    <?php foreach ($customerSteps as [$title, $text]): ?>
                    <li>
                        <h3><?= htmlspecialchars($title) ?></h3>
                        <p><?= htmlspecialchars($text) ?></p>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</section>

</main>

<footer>
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <span>Sweet Valley The Coffee</span>
        <span><a href="<?= htmlspecialchars($loginUrl) ?>">Staff login</a></span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
