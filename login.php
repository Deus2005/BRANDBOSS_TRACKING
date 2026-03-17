<?php
/**
 * Login Page
 */
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'includes/helpers.php';

$auth = Auth::getInstance();

if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

/* =========================
   LOGIN SECURITY SETTINGS
========================= */

$max_attempts = 5;
$lock_time = 300; 

/* =========================
   SESSION INITIALIZATION
========================= */

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (!isset($_SESSION['lock_until'])) {
    $_SESSION['lock_until'] = 0;
}

/* =========================
   AUTO RESET LOCK
========================= */

if ($_SESSION['lock_until'] > 0 && time() >= $_SESSION['lock_until']) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lock_until'] = 0;
}

/* =========================
   USERNAME MEMORY
========================= */

$rememberedUser = $_COOKIE['remember_user'] ?? '';
$usernameField = $_SESSION['login_username'] ?? $rememberedUser;
unset($_SESSION['login_username']);

/* =========================
   ERROR + LOCK STATUS
========================= */

$error = '';
$remaining = 0;

if ($_SESSION['lock_until'] > time()) {

    $remaining = $_SESSION['lock_until'] - time();

    $error = "Too many failed attempts. Wait a few minutes or contact your manager/admin.";
}

/* =========================
   HANDLE LOGIN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_SESSION['lock_until'] > time()) {
        header("Location: login.php");
        exit;
    }

    $username = trim(clean($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {

        $_SESSION['login_error'] = "Please enter both username and password.";
        $_SESSION['login_username'] = $username;

        header("Location: login.php");
        exit;
    }

    $result = $auth->login($username, $password);

    if ($result['success']) {

        $_SESSION['login_attempts'] = 0;
        $_SESSION['lock_until'] = 0;

        if ($remember) {

            setcookie(
                "remember_user",
                $username,
                [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );

        } else {

            setcookie("remember_user", "", time() - 3600, "/");

        }

        header("Location: index.php");
        exit;

    } else {

        $_SESSION['login_attempts']++;

        if ($_SESSION['login_attempts'] >= $max_attempts) {

            $_SESSION['lock_until'] = time() + $lock_time;

        } else {

            $remaining_attempts = $max_attempts - $_SESSION['login_attempts'];

            $_SESSION['login_error'] =
                "Invalid username or password. Attempts remaining: $remaining_attempts";
        }

        $_SESSION['login_username'] = $username;

        header("Location: login.php");
        exit;
    }
}

/* =========================
   LOAD ERROR MESSAGE
========================= */

if (isset($_SESSION['login_error'])) {

    $error = $_SESSION['login_error'];

    unset($_SESSION['login_error']);
}

$lockUntil = $_SESSION['lock_until'];
$remaining = ($lockUntil > time()) ? $lockUntil - time() : 0;
?>

<!DOCTYPE html>
<html lang="en">
    <head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
     <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/responsiveness.css" rel="stylesheet">

    </head>

    <body class="login-page">
        <div class="signin-wrapper">
            <div class="signin-container">
                <div class="signin-image">
                    <img src="images/Logo-bg.png" alt="<?php echo APP_NAME; ?>">
                </div>

                <div class="signin-body">

                    <h2>SIGN IN</h2>
                    <p><strong>Welcome Back!</strong> Please login to your account.</p>

                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" id="loginAlert">
                        <?php echo htmlspecialchars($error); ?>

                        <?php if ($remaining > 0): ?>
                            <br>
                            Try again in 
                            <strong>
                                <span id="countdown"><?php echo $remaining; ?></span> seconds
                            </strong>.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Username or Email</label>
                            <input 
                                type="text"
                                name="username"
                                value="<?php echo htmlspecialchars($usernameField); ?>"
                                placeholder="Enter your username or email"
                                required
                                autofocus
                                <?php if(time() < $_SESSION['lock_until']) echo 'disabled'; ?>
                            >
                        </div>
                        <div class="form-group password-group">
                            <label>Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="off"
                                <?php if(time() < $_SESSION['lock_until']) echo 'disabled'; ?>
                            >
                            <span id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                        <div class="remember-me">
                            <label>
                                <input type="checkbox" name="remember">
                                Remember Me
                            </label>
                        </div>
                        <button 
                            type="submit"
                            class="signin-button"
                            <?php if(time() < $_SESSION['lock_until']) echo 'disabled'; ?>
                        >
                            SIGN IN
                        </button>
                    </form>
                    <div class="signin-footer">
                        <p>Powered by <strong>BrandBoss</strong></p>
                    </div>
                </div>
            </div>
        </div>
            
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {

            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }

        });

        // Countdown timer
        const countdownEl = document.getElementById("countdown");
        const lockUntil = <?php echo $lockUntil; ?>;

        if (countdownEl && lockUntil > 0) {

            let timeLeft = lockUntil - Math.floor(Date.now() / 1000);

            const timer = setInterval(() => {

                if (timeLeft <= 0) {

                    clearInterval(timer);
                    location.reload();
                    return;
                }
                countdownEl.textContent = timeLeft;
                timeLeft--;
            }, 1000);

        }
        // Auto hide normal alert
        setTimeout(() => {
            if (alertBox && !countdownEl) {
                alertBox.style.display = "none";
            }
        }, 4000);
        </script>

    </body>
</html>