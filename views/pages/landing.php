<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="TwinTalk" />
    <title>TwinTalk - Real-time Chat Application</title>
    
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- AOS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        :root {
            --primary-color: #4a3dff;
            --secondary-color: #6c63ff;
            --light-bg: #f5f5ff;
            --dark-text: #333333;
            --light-text: #ffffff;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--light-text);
            padding: 120px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://cdn.pixabay.com/photo/2018/03/22/02/37/background-3249063_1280.png');
            background-size: cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 1rem;
        }
        
        .main-heading {
            font-weight: 700;
            font-size: 3.4rem;
            margin-bottom: 1.5rem;
        }
        
        .subheading {
            font-size: 1.4rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .cta-button {
            background-color: var(--light-text);
            color: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
        }
        
        .app-demo {
            position: relative;
            z-index: 1;
        }
        
        .app-screenshot {
            border-radius: 30px;
            max-width: 100%;
            transform: perspective(1000px) rotateY(-5deg);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            color: var(--primary-color);
            font-weight: 700;
        }
        
        footer {
            background: var(--primary-color);
            color: white;
            padding: 50px 0 20px;
        }
        
        .floating-app {
            /* animation: float 50s ease-in-out infinite; */
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="main-heading">Real-Time, <br>One-on-One Chats Made Simple</h1>
                    <p class="subheading">TwinTalk connects two individuals instantly in a private chat. <br>No groups. No noise. Just pure conversation.</p>
                    
                    <?php if($isLoggedIn): ?>
                        <a href="/chat" class="btn cta-button">Go to Chat <i class="fas fa-arrow-right ms-2"></i></a>
                    <?php else: ?>
                        <a href="/login" class="btn cta-button">Log In <i class="fas fa-sign-in-alt ms-2"></i></a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <div class="app-demo text-center">
                        <img src="/assets/chat-app-ui.png" alt="TwinTalk App UI" class="app-screenshot floating-app img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container py-5">
        <h2 class="section-title" data-aos="fade-up" data-aos-duration="800">Why Choose TwinTalk?</h2>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                <div class="feature-card">
                    <i class="fas fa-comments feature-icon"></i>
                    <h3 class="feature-title">Real-time Messaging</h3>
                    <p class="feature-description">Instant one-on-one messaging with real-time delivery. Keep conversations flowing without delays.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                <div class="feature-card">
                    <i class="fas fa-keyboard feature-icon"></i>
                    <h3 class="feature-title">Typing Indicators</h3>
                    <p class="feature-description">See when your chat partner is typing, making conversations feel more natural and responsive.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                <div class="feature-card">
                    <i class="fas fa-user-check feature-icon"></i>
                    <h3 class="feature-title">Online Status</h3>
                    <p class="feature-description">Know when your contacts are online or offline with real-time status indicators.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
                <div class="feature-card">
                    <i class="fas fa-lock feature-icon"></i>
                    <h3 class="feature-title">Private Conversations</h3>
                    <p class="feature-description">TwinTalk is designed exclusively for one-on-one conversations, ensuring focused communication.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="250">
                <div class="feature-card">
                    <i class="fas fa-user-plus feature-icon"></i>
                    <h3 class="feature-title">Easy Contact Discovery</h3>
                    <p class="feature-description">Find and connect with other users by searching for their email or phone number.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="350">
                <div class="feature-card">
                    <i class="fas fa-envelope feature-icon"></i>
                    <h3 class="feature-title">Email Registration</h3>
                    <p class="feature-description">Quick and simple account creation with email verification for secure access.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- App Showcase Section -->
    <section class="container-fluid py-5 bg-light">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up" data-aos-duration="800">See TwinTalk in Action</h2>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right" data-aos-duration="1000">
                    <h3>Designed for Focused Communication</h3>
                    <p class="mb-4">TwinTalk provides an intuitive user interface that helps you stay focused on what matters most - genuine one-on-one conversations.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> Direct messaging with individual contacts</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> Real-time typing indicators</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> Online/offline status updates</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> Simple user discovery by email or phone</li>
                    </ul>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <img src="/assets/images/landing-app-demo.png" alt="TwinTalk Interface" class="img-fluid rounded-3 shadow-lg" style="height: 350px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-duration="600">
                    <h4>TwinTalk</h4>
                    <p>Real-time one-on-one messaging platform. Stay connected with the people who matter most through private conversations.</p>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">
                    <h5>Product</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Pricing</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Security</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-4" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    <h5>Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Blog</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-4" data-aos="fade-up" data-aos-duration="600" data-aos-delay="300">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Privacy</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Terms</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Cookies</a></li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 bg-white">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p>&copy; <?php echo date('Y'); ?> TwinTalk. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            once: true,
            offset: 100,
            easing: 'ease-out-cubic'
        });

        // Refresh AOS when window is resized
        window.addEventListener('resize', function() {
            AOS.refresh();
        });
    </script>
</body>
</html>