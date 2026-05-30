<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday.Gold — Your Birthday. Your Way.</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: #D4AF37; text-decoration: none; }
        a:hover { color: #e6c44d; }

        /* Hero */
        .hero {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 24px;
            background: radial-gradient(ellipse at 50% 30%, rgba(212,175,55,0.12) 0%, transparent 70%);
        }

        .hero-logo {
            font-size: 3.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }

        .hero-logo .gold { color: #D4AF37; }

        .hero-tagline {
            font-size: 1.5rem;
            font-weight: 300;
            color: #ccc;
            margin-bottom: 40px;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 14px 36px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212,175,55,0.3); }

        .btn-primary {
            background: #D4AF37;
            color: #1a1a1a;
        }

        .btn-outline {
            background: transparent;
            color: #D4AF37;
            border: 2px solid #D4AF37;
        }

        .btn-outline:hover { background: rgba(212,175,55,0.1); color: #D4AF37; }

        /* Features */
        .features {
            max-width: 1100px;
            margin: 0 auto;
            padding: 80px 24px;
        }

        .features-heading {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 48px;
        }

        .features-heading .gold { color: #D4AF37; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .card {
            background: #242424;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 40px 28px;
            text-align: center;
            transition: border-color 0.3s, transform 0.3s;
        }

        .card:hover {
            border-color: #D4AF37;
            transform: translateY(-4px);
        }

        .card-icon {
            font-size: 2.8rem;
            margin-bottom: 20px;
        }

        .card h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            color: #D4AF37;
        }

        .card p {
            color: #aaa;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #2a2a2a;
            text-align: center;
            padding: 32px 24px;
            color: #666;
            font-size: 0.85rem;
        }

        .footer a { color: #D4AF37; }

        /* Responsive */
        @media (max-width: 768px) {
            .hero { min-height: 70vh; padding: 48px 20px; }
            .hero-logo { font-size: 2.4rem; }
            .hero-tagline { font-size: 1.15rem; }
            .features-grid { grid-template-columns: 1fr; gap: 20px; }
            .features { padding: 48px 20px; }
            .features-heading { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<section class="hero">
    <div class="hero-logo">Birthday<span class="gold">.Gold</span></div>
    <p class="hero-tagline">Your birthday. Your way. Gold standard.</p>
    <div class="hero-buttons">
        <a href="https://birthday.gold" class="btn btn-primary">Explore</a>
        <a href="https://birthday.gold/auth" class="btn btn-outline">Sign Up</a>
    </div>
</section>

<section class="features">
    <h2 class="features-heading">Everything you need for a <span class="gold">golden</span> celebration</h2>
    <div class="features-grid">
        <div class="card">
            <div class="card-icon">&#9993;</div>
            <h3>Custom Invites</h3>
            <p>Design stunning invitations that match your style. Send digitally, track RSVPs, and manage your guest list effortlessly.</p>
        </div>
        <div class="card">
            <div class="card-icon">&#127873;</div>
            <h3>Gift Registries</h3>
            <p>Create wishlists your guests will love. Link items from any store, set group-gifting goals, and never get duplicate presents again.</p>
        </div>
        <div class="card">
            <div class="card-icon">&#127878;</div>
            <h3>Event Planning</h3>
            <p>Organize every detail from venues to vendors. Timelines, budgets, and checklists — all in one place so you can enjoy the party.</p>
        </div>
    </div>
</section>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> Birthday.Gold &mdash; All rights reserved.
</footer>

</body>
</html>
