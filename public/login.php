<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/Analytics.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();
$analytics = new Analytics();

// Track page view
$analytics->trackPageView();

// Redirect if already logged in
if ($session->isLoggedIn()) {
    redirect('/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new Auth();
    $result = $auth->login($username, $password);

    if ($result['success']) {
        $session->set('session_token', $result['session_token']);

        // Track login
        $analytics->trackLogin($username);

        redirect('/');
    } else {
        $error = $result['error'] ?? 'Login failed.';
    }
}

renderHeader('Login', $session);
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <!-- Registration Notice -->
        <div class="alert alert-info border-primary mb-4" style="border-width: 2px;">
            <h5 class="alert-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8.93 6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                Login with your COR Forum Account
            </h5>
            <p class="mb-2">
                This shop uses <strong>cor-forum.de</strong> accounts for authentication.
            </p>
            <p class="mb-0">
                <strong>Don't have an account yet?</strong>
            </p>
        </div>

        <!-- Registration Button -->
        <div class="d-grid gap-2 mb-4">
            <a href="https://cor-forum.de/register" target="_blank" class="btn btn-success btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 8px;">
                    <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                    <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
                </svg>
                Create COR Forum Account (Free)
            </a>
        </div>

        <!-- Login Card -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0 text-center">Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <strong>COR Forum Username</strong>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Enter your username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <strong>COR Forum Password</strong>
                        </label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 8px;">
                            <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                            <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                        </svg>
                        Login to Shop
                    </button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <small class="text-muted d-block mb-2">
                        <strong>Secure Authentication</strong> via cor-forum.de API
                    </small>
                    <a href="https://cor-forum.de" target="_blank" class="btn btn-outline-secondary btn-sm">
                        Visit COR Forum
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
