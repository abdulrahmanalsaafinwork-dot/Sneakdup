<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contact Us - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #1a1a1a; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* HEADER */
        .navbar {
            background: white; padding: 20px 40px; display: flex; align-items: center; justify-content: center;
            border-bottom: 1px solid #e5e5e5; position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .brand { 
            font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; 
            color: #111; text-decoration: none; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; 
        }
        .brand span { color: #cc0000; }
        .brand img { height: 45px; width: auto; border-radius: 6px; }

        /* MAIN WRAPPER: 50/50 Layout */
        .page-wrapper {
            max-width: 1100px;
            margin: 60px auto;
            padding: 0 20px;
            display: flex;
            gap: 50px;
            flex-grow: 1;
            align-items: flex-start;
        }

        /* LEFT SIDE: Info & Map */
        .info-col {
            flex: 1;
            padding-top: 10px;
        }
        .info-col h1 { font-family: 'Oswald', sans-serif; font-size: 48px; text-transform: uppercase; line-height: 1.1; margin-bottom: 15px; }
        .info-col p { color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 30px; }
        
        .info-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
        .info-icon { 
            background: #111; color: white; width: 40px; height: 40px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .info-text h4 { font-size: 15px; font-weight: 700; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;}
        .info-text p { font-size: 14px; color: #555; margin: 0; line-height: 1.4; }

        .map-container {
            margin-top: 40px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 280px;
            border: 1px solid #eee;
        }
        .map-container iframe {
            width: 100%; height: 100%; border: none;
        }

        /* RIGHT SIDE: The Form */
        .form-col {
            flex: 1.2;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
        }

        .form-header { margin-bottom: 30px; }
        .form-header h2 { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; color: #111; display: flex; align-items: center; gap: 10px;}
        
        .form-group { margin-bottom: 20px; }
        label { display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #444; }
        label .lucide { width: 14px; height: 14px; color: #888; }
        
        /* Premium Input Styling */
        input, textarea {
            width: 100%; padding: 15px 18px; 
            background: #f4f6f8; border: 2px solid transparent; 
            border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; 
            transition: all 0.3s ease;
        }
        input:focus, textarea:focus { 
            background: white; border-color: #111; outline: none; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        textarea { height: 140px; resize: vertical; }
        
        /* The Glow-Up Submit Button */
        .submit-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; 
            background: #111; color: white; padding: 18px; width: 100%; 
            font-size: 15px; font-weight: 800; text-transform: uppercase; 
            border: none; cursor: pointer; border-radius: 50px; 
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); 
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #cc0000; 
            transform: translateY(-4px); 
            box-shadow: 0 10px 25px rgba(204, 0, 0, 0.4); 
        }
        .submit-btn .lucide { transition: transform 0.3s ease; }
        .submit-btn:hover .lucide { transform: translateX(5px) scale(1.1); }
        
        .back-link { 
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; 
            margin-top: 25px; color: #888; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s;
            width: 100%; text-align: center;
        }
        .back-link:hover { color: #cc0000; }

        /* Responsive Design for Mobile */
        @media (max-width: 850px) {
            .page-wrapper { flex-direction: column; }
            .info-col, .form-col { width: 100%; }
            .form-col { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="shop.php" class="brand">
            <img src="images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div>SNEAK<span>D</span>UP</div>
        </a>
    </div>

    <div class="page-wrapper">
        <!-- LEFT SIDE: Info & Map -->
        <div class="info-col">
            <h1>Get In Touch</h1>
            <p>Whether you're hunting for a specific drop, have a question about an order, or just want to talk sneakers, we're here for it.</p>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="map-pin"></i></div>
                <div class="info-text">
                    <h4>SneakDup Headquarters</h4>
                    <p>Parañaque Branch<br>Metro Manila, Philippines</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="clock"></i></div>
                <div class="info-text">
                    <h4>Store Hours</h4>
                    <p>Monday - Sunday<br>10:00 AM - 9:00 PM</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="mail"></i></div>
                <div class="info-text">
                    <h4>Hit Us Up</h4>
                    <p>support@sneakdup.com<br>Inquire via Messenger for faster replies.</p>
                </div>
            </div>

            <!-- INTERACTIVE GOOGLE MAP -->
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3863.0934433912207!2d120.9932241768809!3d14.479323443942736!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397cffa30c4b80f%3A0x340db85bbcd8b0f6!2sSneakd%20Up%20Sneakers%20%26%20Apparel%20(Para%C3%B1aque)!5e0!3m2!1sen!2sph!4v1778005789877!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <!-- RIGHT SIDE: The Form -->
        <div class="form-col">
            <div class="form-header">
                <h2><i data-lucide="message-square-text" style="color: #cc0000;"></i> Send a Message</h2>
            </div>

            <form action="process_contact.php" method="POST">
                <div class="form-group">
                    <label><i data-lucide="user"></i> Full Name</label>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" required>
                </div>
                
                <div class="form-group">
                    <label><i data-lucide="mail"></i> Email Address</label>
                    <input type="email" name="email" placeholder="juan@example.com" required>
                </div>
                
                <div class="form-group">
                    <label><i data-lucide="help-circle"></i> Subject</label>
                    <input type="text" name="subject" placeholder="Order Inquiry / Product Request" required>
                </div>
                
                <div class="form-group">
                    <label><i data-lucide="pen-line"></i> Message</label>
                    <textarea name="message" placeholder="What's on your mind?..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    Send Message <i data-lucide="send"></i>
                </button>
            </form>
            
            <a href="shop.php" class="back-link"><i data-lucide="arrow-left"></i> Back to Shop</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>