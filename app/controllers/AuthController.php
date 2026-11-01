<?php
/** Registration, login and logout. */
class AuthController extends Controller
{
    public function login(): void
    {
        $this->requireGuest();
        $this->render('auth/login', ['errors' => pull_errors()], 'Log in');
        clear_old();
    }

    public function attempt(): void
    {
        $this->requireGuest();
        $this->requirePost();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $_SESSION['old'] = ['username' => $username];

        // Simple brute-force throttle: 5 failures per minute per session
        $fails = array_filter($_SESSION['login_fails'] ?? [], fn($t) => $t > time() - 60);
        if (count($fails) >= 5) {
            $_SESSION['errors'] = ['form' => 'Too many attempts. Please wait a minute and try again.'];
            redirect(url('auth/login'));
        }

        $user = $username !== '' ? (new UserModel())->findByUsername($username) : null;
        $hash = $user['password'] ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
        $ok = password_verify($password, $hash) && $user;

        if (!$ok) {
            $fails[] = time();
            $_SESSION['login_fails'] = $fails;
            $_SESSION['errors'] = ['form' => 'Wrong username or password.'];
            redirect(url('auth/login'));
        }

        session_regenerate_id(true);
        unset($_SESSION['login_fails'], $_SESSION['old']);
        $_SESSION['user_id'] = (int)$user['id'];
        flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect(url('feed/index'));
    }

    public function register(): void
    {
        $this->requireGuest();
        $this->render('auth/register', ['errors' => pull_errors()], 'Create account');
        clear_old();
    }

    public function store(): void
    {
        $this->requireGuest();
        $this->requirePost();

        $fullName = trim(preg_replace('/\s+/', ' ', (string)($_POST['full_name'] ?? '')));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');
        $_SESSION['old'] = ['full_name' => $fullName, 'username' => $username];

        $users = new UserModel();
        $errors = [];

        $len = mb_strlen($fullName);
        if ($len < 2 || $len > 60) {
            $errors['full_name'] = 'Enter your full name (2–60 characters).';
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
            $errors['username'] = 'Username must be 3–20 characters: letters, numbers or underscore.';
        } elseif ($users->usernameExists($username)) {
            $errors['username'] = 'That username is taken. Try another one.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password needs at least 8 characters with a letter and a number.';
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        $avatar = null;
        if (!$errors) {
            [$avatar, $imgError] = save_image('avatar', 'avatars');
            if ($imgError) {
                $errors['avatar'] = $imgError;
            }
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            redirect(url('auth/register'));
        }

        $id = $users->create($username, password_hash($password, PASSWORD_DEFAULT), $fullName, $avatar);
        session_regenerate_id(true);
        unset($_SESSION['old']);
        $_SESSION['user_id'] = $id;
        flash('success', 'Account created. Welcome to ' . APP_NAME . '!');
        redirect(url('feed/index'));
    }

    public function logout(): void
    {
        $this->requirePost();
        $_SESSION = [];
        session_regenerate_id(true);
        flash('info', 'You have been logged out.');
        redirect(url('auth/login'));
    }
}
