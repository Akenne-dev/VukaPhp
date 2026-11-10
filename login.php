<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['loggedinEmail'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }
    if (!in_array($role, ['user', 'admin'], true)) {
        $errors['role'] = 'Please choose an account type.';
    }

    if (empty($errors)) {
        $statement = mysqli_prepare($conn, 'SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 's', $email);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $errors['email'] = 'No account was found with this email. Please sign up first.';
        } elseif (!password_verify($password, $user['password'])) {
            $errors['password'] = 'Incorrect password. Please try again.';
        } elseif ($user['role'] !== $role) {
            $errors['role'] = 'This account is registered as a ' . ($user['role'] === 'admin' ? 'admin / employer.' : 'job seeker.');
        } else {
            session_regenerate_id(true);
            $_SESSION['loggedinEmail'] = $user['email'];
            $_SESSION['userName'] = $user['full_name'];
            $_SESSION['userId'] = $user['id'];
            $_SESSION['userRole'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | VUKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --vuka-orange: #ff6b00; --vuka-ink: #171615; }
        body { min-height: 100vh; font-family: Inter, sans-serif; background: #fff5f0; color: var(--vuka-ink); }
        .brand { color: var(--vuka-ink); font-size: 1.75rem; font-weight: 900; letter-spacing: -.08em; text-decoration: none; }
        .auth-card { max-width: 480px; border: 0; border-radius: 1.5rem; box-shadow: 0 1.25rem 3.5rem rgba(33, 22, 16, .12); }
        .form-control { padding: .8rem 1rem; border-radius: .75rem; border-color: #d8d1cd; }
        .form-control:focus { border-color: var(--vuka-orange); box-shadow: 0 0 0 .25rem rgba(255, 107, 0, .15); }
        .password-wrap { position: relative; } .password-wrap .form-control { padding-right: 4.4rem; }
        .password-toggle { position: absolute; right: .55rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #6c625d; font-weight: 700; font-size: .82rem; }
        .btn-vuka { background: var(--vuka-orange); border: 1px solid var(--vuka-orange); color: #171615; font-weight: 800; border-radius: .8rem; padding: .82rem 1rem; }
        .btn-vuka:hover { background: #e85f00; border-color: #e85f00; color: #171615; }
        .divider { display: flex; align-items: center; gap: .8rem; color: #837a74; font-size: .85rem; } .divider::before, .divider::after { content: ''; height: 1px; background: #e4dfdc; flex: 1; }
        .footer-links a { color: #6c625d; text-decoration: none; } .footer-links a:hover { color: var(--vuka-orange); }
    </style>
</head>
<body class="d-flex flex-column">
    <header class="container py-4"><a class="brand" href="index.php">VUKA</a></header>
    <main class="container flex-grow-1 d-flex align-items-center justify-content-center py-4">
        <section class="card auth-card w-100 p-4 p-sm-5" aria-labelledby="login-title">
            <h1 id="login-title" class="h2 fw-bold mb-2">Welcome back</h1>
            <p class="text-secondary mb-4">Log in with your Vuka account.</p>
            <form method="post" action="login.php">
                <div class="mb-3"><label class="form-label fw-semibold" for="email">Email</label><input class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email" required><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?></div>
                <div class="mb-3"><label class="form-label fw-semibold" for="role">Log in as</label><select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" id="role" name="role"><option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Job seeker</option><option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin / employer</option></select><?php if (isset($errors['role'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['role']); ?></div><?php endif; ?></div>
                <div class="mb-4"><label class="form-label fw-semibold" for="password">Password</label><div class="password-wrap"><input class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" type="password" name="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button></div><?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?></div>
                <button class="btn btn-vuka w-100" type="submit">Log in</button>
            </form>
            <p class="text-center mt-3 mb-4"><a class="text-dark fw-semibold" href="#">Forgot your password?</a></p>
            <div class="divider mb-4">or</div>
            <button class="btn btn-outline-dark w-100 rounded-3 py-2 fw-semibold" type="button" onclick="alert('Google sign-in will be available soon.')">Continue with Google</button>
            <p class="text-center text-secondary mt-4 mb-0">Don’t have an account? <a class="text-dark fw-bold" href="signup.php">Create free account</a></p>
        </section>
    </main>
    <footer class="container text-center py-4 small text-secondary footer-links"><p class="mb-2">&copy; <?php echo date('Y'); ?> Vuka Ltd. All rights reserved.</p><a href="#">Terms &amp; conditions</a><span class="mx-2">·</span><a href="#">Privacy policy</a><span class="mx-2">·</span><a href="#">Cookies</a><span class="mx-2">·</span><a href="#">Legal &amp; compliance</a></footer>
    <script>document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => { const input = document.getElementById(button.dataset.passwordToggle); const show = input.type === 'password'; input.type = show ? 'text' : 'password'; button.textContent = show ? 'Hide' : 'Show'; button.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); }));</script>
</body>
</html>
