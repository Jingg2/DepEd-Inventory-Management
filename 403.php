<?php 
// Robust root calculation
$serverPath = str_replace('\\', '/', __DIR__);
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$scriptDir = str_ireplace($docRoot, '', $serverPath);
$root = rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Forbidden | Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-emerald: #10b981;
            --forest-deep: #022c22;
            --forest-mid: #064e3b;
            --text-main: #f8fafc;
            --gradient-primary: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .error-container {
            width: 100%;
            height: 100%;
            display: flex;
            position: relative;
        }

        /* Left Side - Content */
        .content-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10%;
            z-index: 10;
        }

        .error-code {
            font-size: 12rem;
            font-weight: 800;
            line-height: 1;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -0.05em;
        }

        .error-divider {
            width: 100px;
            height: 8px;
            background: var(--gradient-primary);
            border-radius: 4px;
            margin-bottom: 2rem;
        }

        .error-message {
            font-size: 1.5rem;
            color: #475569;
            margin-bottom: 2.5rem;
            max-width: 450px;
            line-height: 1.6;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 32px;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            width: fit-content;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4);
        }

        /* Right Side - Visual */
        .visual-side {
            flex: 1;
            background: var(--gradient-primary);
            position: relative;
            clip-path: ellipse(100% 150% at 100% 50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* CSS Bunny Illustration */
        .bunny-wrapper {
            position: absolute;
            left: -80px;
            bottom: 20%;
            transform: rotate(-15deg);
        }

        .bunny-head {
            width: 180px;
            height: 200px;
            background: white;
            border-radius: 50% 50% 45% 45%;
            position: relative;
            box-shadow: -10px 10px 30px rgba(0,0,0,0.1);
        }

        .bunny-ear {
            width: 50px;
            height: 120px;
            background: white;
            border-radius: 50% 50% 10% 10%;
            position: absolute;
            top: -90px;
        }

        .bunny-ear.left { left: 30px; transform: rotate(-10deg); }
        .bunny-ear.right { right: 30px; transform: rotate(10deg); }

        .bunny-ear::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 80px;
            background: #ffebf1;
            border-radius: 50%;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .eye {
            width: 18px;
            height: 18px;
            background: #1e293b;
            border-radius: 50%;
            position: absolute;
            top: 70px;
        }

        .eye.left { left: 45px; }
        .eye.right { right: 45px; }

        .nose {
            width: 14px;
            height: 10px;
            background: #f472b6;
            border-radius: 50%;
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
        }

        .blush {
            width: 25px;
            height: 12px;
            background: #ffdae9;
            border-radius: 50%;
            position: absolute;
            top: 105px;
            opacity: 0.6;
        }

        .blush.left { left: 25px; }
        .blush.right { right: 25px; }

        .paw {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            position: absolute;
            bottom: -20px;
            border: 2px solid #f1f5f9;
        }

        .paw.left { left: 10px; }
        .paw.right { right: 10px; }

        /* Animation Sequence - Staggered Lateral Peek from Left */
        @keyframes peek-head {
            0%, 25% { transform: translateX(-400px) rotate(-15deg); }
            45%, 75% { transform: translateX(0) rotate(-10deg); }
            90%, 100% { transform: translateX(-400px) rotate(-15deg); }
        }

        @keyframes peek-paw-left {
            0%, 10% { transform: translateX(-150px); }
            30%, 75% { transform: translateX(0); }
            90%, 100% { transform: translateX(-150px); }
        }

        @keyframes peek-paw-right {
            0%, 25% { transform: translateX(-150px); }
            45%, 75% { transform: translateX(0); }
            90%, 100% { transform: translateX(-150px); }
        }

        .bunny-wrapper {
            position: absolute;
            left: -40px; /* Base position for visibility */
            bottom: 25%;
        }

        .bunny-head {
            animation: peek-head 6s infinite ease-in-out;
            transform-origin: bottom center;
        }

        .paw.left {
            animation: peek-paw-left 6s infinite ease-in-out;
        }

        .paw.right {
            animation: peek-paw-right 6s infinite ease-in-out;
        }

        @media (max-width: 992px) {
            .error-code { font-size: 8rem; }
            .content-side { padding: 0 5%; }
            .visual-side { display: none; }
        }

    </style>
</head>
<body>
    <div class="error-container">
        <div class="content-side">
            <h1 class="error-code">403</h1>
            <div class="error-divider"></div>
            <p class="error-message">
                Hold on! This area is restricted. You don't have the permissions to view this directory.
            </p>
            <a href="<?php echo $root; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Take Me Back
            </a>
        </div>
        <div class="visual-side">
            <div class="bunny-wrapper">
                <div class="bunny-head">
                    <div class="bunny-ear left"></div>
                    <div class="bunny-ear right"></div>
                    <div class="eye left"></div>
                    <div class="eye right"></div>
                    <div class="nose"></div>
                    <div class="blush left"></div>
                    <div class="blush right"></div>
                </div>
                <div class="paw left"></div>
                <div class="paw right"></div>
            </div>
        </div>
    </div>
</body>
</html>
