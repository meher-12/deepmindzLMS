<?php
include 'db.php';
include 'User.php';
include 'Auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Management System</title>
    <style>
        * 
            margin: 0;
    /* ── Reset ── */
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* ── Base ── */
body {
    font-family: 'Space Grotesk', 'Segoe UI', Tahoma, Verdana, sans-serif;
    background: #050812;
    color: #e2eaff;
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── Animated grid background ── */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 210, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 210, 255, 0.04) 1px, transparent 1px);
    background-size: 48px 48px;
    animation: gridScroll 20s linear infinite;
    pointer-events: none;
    z-index: 0;
}

/* ── Ambient glow orbs ── */
body::after {
    content: '';
    position: fixed;
    top: -120px;
    left: -80px;
    width: 480px;
    height: 480px;
    background: radial-gradient(circle, rgba(0, 210, 255, 0.1) 0%, transparent 70%);
    animation: orbDrift 8s ease-in-out infinite alternate;
    pointer-events: none;
    z-index: 0;
}

@keyframes gridScroll {
    from { background-position: 0 0; }
    to   { background-position: 48px 48px; }
}

@keyframes orbDrift {
    from { transform: translate(0, 0) scale(1); opacity: 0.6; }
    to   { transform: translate(60px, 40px) scale(1.2); opacity: 1; }
}

/* ── Header ── */
header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(5, 8, 18, 0.75);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 210, 255, 0.15);
    padding: 16px 0;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* ── H1 logo ── */
h1 {
    font-size: 1.5em;
    font-weight: 700;
    color: #00d2ff;
    letter-spacing: 1px;
    text-transform: uppercase;
    animation: logoPulse 3s ease-in-out infinite alternate;
    cursor: default;
    transition: color 0.3s;
}

h1:hover {
    color: #c084fc;
}

@keyframes logoPulse {
    from { text-shadow: 0 0 12px rgba(0, 210, 255, 0.4); }
    to   { text-shadow: 0 0 28px rgba(0, 210, 255, 0.9), 0 0 60px rgba(0, 210, 255, 0.25); }
}

/* ── Nav buttons ── */
.nav-btn {
    position: relative;
    overflow: hidden;
    background: transparent;
    border: 1px solid rgba(0, 210, 255, 0.3);
    color: #7fb8d8;
    padding: 9px 18px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 500;
    font-family: inherit;
    letter-spacing: 0.3px;
    transition: color 0.3s, border-color 0.3s;
}

.nav-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0, 210, 255, 0.12), rgba(140, 0, 255, 0.08));
    opacity: 0;
    transition: opacity 0.3s;
}

.nav-btn:hover::before { opacity: 1; }
.nav-btn:hover {
    color: #00d2ff;
    border-color: rgba(0, 210, 255, 0.65);
    transform: translateY(-2px);
}

/* ── Sign-in dropdown ── */
.signin-dropdown {
    position: relative;
}

.signin-btn {
    position: relative;
    overflow: hidden;
    background: rgba(0, 210, 255, 0.1);
    border: 1px solid rgba(0, 210, 255, 0.45);
    color: #00d2ff;
    padding: 9px 18px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 600;
    font-family: inherit;
    letter-spacing: 0.3px;
    transition: background 0.3s, border-color 0.3s, transform 0.3s;
}

.signin-btn:hover {
    background: rgba(0, 210, 255, 0.18);
    border-color: rgba(0, 210, 255, 0.8);
    transform: translateY(-2px);
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: rgba(8, 14, 32, 0.96);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 210, 255, 0.2);
    border-radius: 10px;
    min-width: 160px;
    z-index: 200;
    overflow: hidden;
    animation: dropIn 0.18s ease;
}

.signin-dropdown:hover .dropdown-content {
    display: block;
}

@keyframes dropIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.dropdown-content a {
    display: block;
    padding: 12px 16px;
    color: #7fb8d8;
    text-decoration: none;
    font-size: 13.5px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    transition: background 0.2s, color 0.2s;
}

.dropdown-content a:last-child { border-bottom: none; }
.dropdown-content a:hover {
    background: rgba(0, 210, 255, 0.1);
    color: #00d2ff;
}

/* ── Main ── */
main {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 56px 24px;
}

/* ── Hero ── */
.hero {
    text-align: center;
    margin-bottom: 72px;
}

.hero h2 {
    font-size: 2.8em;
    font-weight: 700;
    color: #00d2ff;
    margin-bottom: 16px;
    line-height: 1.2;
    animation: heroGlow 2.5s ease-in-out infinite alternate, fadeInUp 0.8s ease-out both;
}

@keyframes heroGlow {
    from { text-shadow: 0 0 20px rgba(0, 210, 255, 0.4); }
    to   { text-shadow: 0 0 50px rgba(0, 210, 255, 0.85), 0 0 100px rgba(0, 210, 255, 0.2); }
}

.hero p {
    font-size: 1.15em;
    color: #4e6590;
    margin-bottom: 32px;
    animation: fadeInUp 0.8s ease-out 0.15s both;
}

/* ── Section headings ── */
.stats h3,
.gallery h3,
.features h3 {
    font-size: 1.5em;
    font-weight: 700;
    color: #00d2ff;
    text-align: center;
    margin-bottom: 32px;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-shadow: 0 0 20px rgba(0, 210, 255, 0.4);
}

/* ── Stats section ── */
.stats {
    text-align: center;
    margin-bottom: 72px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    position: relative;
    overflow: hidden;
    background: rgba(8, 16, 36, 0.8);
    border: 1px solid rgba(0, 210, 255, 0.15);
    padding: 28px 24px;
    border-radius: 14px;
    transition: border-color 0.3s, transform 0.3s;
    animation: fadeInUp 0.6s ease-out both;
}

.stat-item::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 210, 255, 0.7), transparent);
    animation: scanLine 3s ease-in-out infinite;
}

.stat-item::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: radial-gradient(ellipse at top, rgba(0, 210, 255, 0.05), transparent 70%);
    pointer-events: none;
}

.stat-item:hover {
    border-color: rgba(0, 210, 255, 0.5);
    transform: translateY(-5px);
}

.stat-item h4 {
    font-size: 2.2em;
    font-weight: 700;
    color: #00d2ff;
    margin-bottom: 8px;
    text-shadow: 0 0 20px rgba(0, 210, 255, 0.5);
}

.stat-item p {
    color: #4e6590;
    font-size: 13.5px;
}

@keyframes scanLine {
    0%, 100% { opacity: 0; }
    50%       { opacity: 1; }
}

/* ── Gallery ── */
.gallery {
    text-align: center;
    margin-bottom: 72px;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.gallery-grid img {
    width: 100%;
    border-radius: 12px;
    border: 1px solid rgba(0, 210, 255, 0.12);
    transition: transform 0.35s ease, border-color 0.3s, box-shadow 0.35s;
    display: block;
}

.gallery-grid img:hover {
    transform: scale(1.04);
    border-color: rgba(0, 210, 255, 0.4);
    box-shadow: 0 0 30px rgba(0, 210, 255, 0.15);
}

/* ── CTA / full-width button ── */
button {
    position: relative;
    overflow: hidden;
    width: 100%;
    padding: 14px;
    background: transparent;
    border: 1px solid rgba(0, 210, 255, 0.4);
    color: #00d2ff;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: color 0.3s, border-color 0.3s, transform 0.3s;
}

button::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0, 210, 255, 0.15), rgba(140, 0, 255, 0.1));
    opacity: 0;
    transition: opacity 0.3s;
}

button:hover::before { opacity: 1; }
button:hover {
    border-color: rgba(0, 210, 255, 0.8);
    transform: translateY(-2px);
}

/* ── Features ── */
.features {
    margin-top: 72px;
    text-align: center;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

.feature-item {
    background: rgba(8, 16, 36, 0.75);
    border: 1px solid rgba(140, 0, 255, 0.15);
    padding: 26px 22px;
    border-radius: 14px;
    text-align: left;
    transition: border-color 0.3s, transform 0.3s;
    animation: fadeInUp 0.6s ease-out both;
}

.feature-item:hover {
    border-color: rgba(140, 0, 255, 0.5);
    transform: translateY(-6px);
}

.feature-item h4 {
    color: #c084fc;
    margin-bottom: 10px;
    font-size: 1.1em;
    font-weight: 600;
    text-shadow: 0 0 12px rgba(192, 132, 252, 0.3);
}

.feature-item p {
    color: #4e6590;
    font-size: 13.5px;
    line-height: 1.65;
}

/* ── About & Contact cards ── */
.about, .contact {
    position: relative;
    max-width: 800px;
    margin: 0 auto 64px;
    background: rgba(8, 16, 36, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.07);
    padding: 36px 40px;
    border-radius: 16px;
}

/* Corner bracket deco */
.about::before, .about::after,
.contact::before, .contact::after {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    border-color: rgba(0, 210, 255, 0.4);
    border-style: solid;
}
.about::before, .contact::before {
    top: 12px; left: 12px;
    border-width: 1px 0 0 1px;
}
.about::after, .contact::after {
    bottom: 12px; right: 12px;
    border-width: 0 1px 1px 0;
}

.about h3, .contact h3 {
    color: #00d2ff;
    text-align: center;
    font-size: 1.2em;
    font-weight: 600;
    margin-bottom: 14px;
    text-shadow: 0 0 16px rgba(0, 210, 255, 0.35);
}

.about p, .contact p {
    color: #4e6590;
    text-align: center;
    font-size: 14px;
    line-height: 1.75;
}

/* ── Footer ── */
footer {
    position: relative;
    z-index: 1;
    background: rgba(5, 8, 18, 0.85);
    backdrop-filter: blur(10px);
    color: #2d4060;
    text-align: center;
    padding: 22px;
    margin-top: 64px;
    border-top: 1px solid rgba(0, 210, 255, 0.1);
    font-size: 13px;
    letter-spacing: 0.3px;
}

/* ── Shared animations ── */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        gap: 14px;
    }
    .hero h2 {
        font-size: 2em;
    }
    .feature-grid {
        grid-template-columns: 1fr;
    }
    .about, .contact {
        padding: 24px 20px;
    }
}
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTL0HOgnEtlmQ0w__zaBbAkCdp01CD60g3lFA&s">    
            <h1>SkillForge</h1>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="nav-buttons">
                    <button class="nav-btn" onclick="document.getElementById('contact').scrollIntoView({behavior: 'smooth'})">Contact Us</button>
                    <button class="nav-btn" onclick="document.getElementById('about').scrollIntoView({behavior: 'smooth'})">About Us</button>
                </div>
                <div class="signin-dropdown">
                    <button class="signin-btn">Sign In</button>
                    <div class="dropdown-content">
                        <a href="login.php?mode=register">Register</a>
                        <a href="login.php?mode=login">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main>
        <section class="hero">
            <h2>Welcome to Our LMS</h2>
            <p>Join thousands of learners and start your educational journey today.</p>
        </section>
        <section class="stats">
            <h3>Our Impact</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <h4>10,000+</h4>
                    <p>Active Learners</p>
                </div>
                <div class="stat-item">
                    <h4>500+</h4>
                    <p>Courses Available</p>
                </div>
                <div class="stat-item">
                    <h4>98%</h4>
                    <p>Completion Rate</p>
                </div>
            </div>
        </section>
        <section class="gallery">
            <h3>Explore Our Platform</h3>
            <div class="gallery-grid">
                <img src="data:image/png;base64,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" alt="Course Dashboard">
                <img src="data:image/jpeg;base64,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" alt="Interactive Lessons">
                <img src="data:image/jpeg;base64,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" alt="Progress Tracking">
            </div>
        </section>
        <section class="features">
            <h3>Why Choose Our LMS?</h3>
            <div class="feature-grid">
                <div class="feature-item">
                    <h4>📚 Comprehensive Courses</h4>
                    <p>Access a wide range of courses designed by experts.</p>
                </div>
                <div class="feature-item">
                    <h4>📝 Interactive Assignments</h4>
                    <p>Engage with hands-on assignments and projects.</p>
                </div>
                <div class="feature-item">
                    <h4>📊 Progress Tracking</h4>
                    <p>Monitor your learning progress with detailed analytics.</p>
                </div>
            </div>
        </section>
        <section id="about" class="about">
            <h3>About Us</h3>
            <p>We are dedicated to providing high-quality online education to learners worldwide. Our platform offers interactive courses, personalized learning paths, and comprehensive support to help you achieve your goals.</p>
        </section>
        <section id="contact" class="contact">
            <h3>Contact Us</h3>
            <p>Have questions? Reach out to us at contact@lms.com or call (123) 456-7890.</p>
        </section>
    </main>
    <footer>
        <p>2023 SkillForge. All rights reserved.</p>
    </footer>
</body>
</html>