<?php
/** Search people or posts. */
class SearchController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $me = $this->currentUser();
        $q = trim((string)($_GET['q'] ?? ''));
        $type = (string)($_GET['type'] ?? '');
        if (!in_array($type, ['users', 'posts'], true)) {
            $type = (isset($q[0]) && $q[0] === '#') ? 'posts' : 'users';
        }

        $users = $posts = [];
        if (mb_strlen($q) >= 2) {
            if ($type === 'users') {
                $users = (new UserModel())->search($q);
            } else {
                $posts = $this->withComments((new PostModel())->search($q, (int)$me['id']));
            }
        }
        $this->render('search/index', [
            'q' => $q, 'type' => $type, 'users' => $users, 'posts' => $posts,
            'tooShort' => $q !== '' && mb_strlen($q) < 2,
        ], $q !== '' ? 'Search: ' . $q : 'Search');
    }
}
