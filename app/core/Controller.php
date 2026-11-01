<?php
/** Base controller: rendering, auth guards, JSON helpers. */
abstract class Controller
{
    protected function render(string $view, array $data = [], string $title = ''): void
    {
        $data['pageTitle'] = $title !== '' ? $title . ' · ' . APP_NAME : APP_NAME;
        $data['currentUser'] = $this->currentUser();
        extract($data, EXTR_SKIP);
        require APP_PATH . '/views/layouts/header.php';
        require APP_PATH . '/views/' . $view . '.php';
        require APP_PATH . '/views/layouts/footer.php';
    }

    protected function currentUser(): ?array
    {
        static $user = false;
        if ($user === false) {
            $user = is_logged_in() ? (new UserModel())->findById(auth_id()) : null;
            if (is_logged_in() && !$user) {   // account no longer exists
                $_SESSION = [];
            }
        }
        return $user;
    }

    protected function requireLogin(): void
    {
        if (!$this->currentUser()) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => 'Please log in first.'], 401);
            }
            flash('warning', 'Please log in to continue.');
            redirect(url('auth/login'));
        }
    }

    protected function requireGuest(): void
    {
        if ($this->currentUser()) {
            redirect(url('feed/index'));
        }
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        if (!csrf_valid()) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => 'Session expired. Reload the page.'], 419);
            }
            flash('danger', 'Your session expired. Please try again.');
            redirect_back(url('feed/index'));
        }
    }

    protected function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    protected function notFound(string $msg = 'We could not find that page.'): void
    {
        http_response_code(404);
        $this->render('errors/404', ['message' => $msg], 'Not found');
        exit;
    }

    /** Attach comments to a list of posts with a single query (no N+1). */
    protected function withComments(array $posts): array
    {
        $ids = array_map(fn($p) => (int)$p['id'], $posts);
        $byPost = (new CommentModel())->forPosts($ids);
        foreach ($posts as &$p) {
            $p['comments'] = $byPost[$p['id']] ?? [];
        }
        unset($p);
        return $posts;
    }
}
