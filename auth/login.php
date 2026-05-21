<?php require_once __DIR__ . '/../includes/lang.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bdtsc-color: #28687F;
            --bdtsc-dark: #1e4f61;
            --bg-gradient: linear-gradient(135deg, #0f54f5 0%, #12ace4 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            /* ለተለያዩ ስክሪኖች ተስማሚ እንዲሆን (Flexibility) */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px; /* በስልክ ሲከፈት ከዳር እንዳይጣበቅ */
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 420px;
            padding: 30px; /* ከ 40px ወደ 30px ቀንሼዋለሁ (ለስልክ እንዲመች) */
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        /* በኮምፒውተር ላይ ሲሆን Padding እንዲጨምር */
        @media (min-width: 768px) {
            .login-card {
                padding: 45px;
            }
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: var(--bdtsc-color);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 15px; /* ለንባብ ምቹ እንዲሆን */
        }

        .form-control:focus {
            border-color: var(--bdtsc-color);
            box-shadow: 0 0 0 4px rgba(40, 104, 127, 0.1);
        }

        .btn-bdtsc {
            background-color: var(--bdtsc-color);
            color: white;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-bdtsc:hover {
            background-color: var(--bdtsc-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 104, 127, 0.2);
            color: white;
        }

        .back-home {
            color: var(--bdtsc-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* በስልክ ላይ ፅሁፎች ትንሽ እንዲያንሱ */
        @media (max-width: 480px) {
            h3 { font-size: 1.25rem; }
            .small { font-size: 0.75rem; }
            .login-card { border-radius: 20px; }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-end mb-2 no-print">
        <a href="<?php echo lang_url('en'); ?>" class="badge text-decoration-none border text-dark fw-normal <?php echo $current_lang == 'en' ? 'active' : ''; ?>"><?php echo __('english'); ?></a>
        <a href="<?php echo lang_url('am'); ?>" class="badge text-decoration-none border text-dark fw-normal <?php echo $current_lang == 'am' ? 'active' : ''; ?>"><?php echo __('amharic'); ?></a>
    </div>

    <div class="text-center mb-4">
        <img src="../assets/images/bdtsc_logo.png" alt="BDTSC Logo" style="width: 65px; margin-bottom: 10px;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2942/2942504.png';">
        <h3 class="fw-bold mb-1" style="color: var(--bdtsc-color);">BDTSC - IETMS</h3>
        <p class="text-muted small"><?php echo __('industrial_task_management_system'); ?></p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success py-2 mb-3 small border-0 shadow-sm rounded-3">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger py-2 mb-3 small border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    
    <form action="login_process.php" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-secondary"><?php echo __('email'); ?></label>
            <input type="email" name="email" class="form-control" placeholder="name@bdtsc.com" required>
        </div>
        
        <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label small fw-bold text-secondary mb-0"><?php echo __('password'); ?></label>
        <a href="forgot_password.php" class="small text-decoration-none fw-bold" style="color: var(--bdtsc-color); font-size: 11px;"><?php echo __('forgot'); ?></a>
    </div>
    <div class="input-group">
        <input type="password" name="password" id="password" class="form-control" style="border-right: none;" placeholder="••••••••" required>
        <span class="input-group-text bg-white" style="border-left: none; border-radius: 0 12px 12px 0; cursor: pointer;" id="togglePassword">
            <i class="bi bi-eye-slash text-secondary"></i>
        </span>
    </div>
</div>

       <div class="d-grid mb-3">
    <button type="submit" name="login_btn" id="loginBtn" class="btn btn-bdtsc" disabled style="opacity: 0.6; cursor: not-allowed;">
        <?php echo __('login_button'); ?> <i class="bi bi-arrow-right-short ms-1"></i>
    </button>
</div>
    </form>

    <div class="text-center mt-2">
        <a href="../index.php" class="back-home">
            <i class="bi bi-house-door"></i> <?php echo __('back_to_home'); ?>
        </a>
    </div>

    <div class="text-center mt-4 pt-3 border-top">
        <p class="text-muted" style="font-size: 10px; margin-bottom: 0;">
            &copy; 2026 Bahir Dar Textile Share Company (BDTSC)
        </p>
    </div>
</div>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        // የአይነቱን መቀያየር (Toggle the type attribute)
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // የአይን ምልክቱን መቀየር (Toggle the eye / eye-slash icon)
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    // የፓስወርድ ኢንፑቱን እና በተኑን ማግኘት
    const passwordInput = document.querySelector('input[name="password"]');
    const loginButton = document.querySelector('#loginBtn');

    passwordInput.addEventListener('input', function() {
        // የፓስወርዱ ርዝመት ከ 6 በታች ከሆነ በተኑ አይሰራም
        if (this.value.length >= 6) {
            loginButton.disabled = false;
            loginButton.style.opacity = "1";
            loginButton.style.cursor = "pointer";
        } else {
            loginButton.disabled = true;
            loginButton.style.opacity = "0.6";
            loginButton.style.cursor = "not-allowed";
        }
    });
</script>

</body>
</html>