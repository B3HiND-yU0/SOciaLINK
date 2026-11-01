<?php
/** The newsfeed / timeline. */
class FeedController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $me = $this->currentUser();
        $posts = (new PostModel())->feed((int)$me['id'], POSTS_PER_PAGE + 1, 0);
        $hasMore = count($posts) > POSTS_PER_PAGE;
        $posts = $this->withComments(array_slice($posts, 0, POSTS_PER_PAGE));

        $users = new UserModel();
        $this->render('feed/index', [
            'posts'       => $posts,
            'hasMore'     => $hasMore,
            'suggestions' => $users->suggestions((int)$me['id'], 5),
            'stats'       => $users->stats((int)$me['id']),
        ], 'Home');
    }

    /** AJAX "Load more": returns rendered post cards. */
    public function more(): void
    {
        $this->requireLogin();
        $page = max(1, (int)($_GET['page'] ?? 2));
        $posts = (new PostModel())->feed((int)auth_id(), POSTS_PER_PAGE + 1, ($page - 1) * POSTS_PER_PAGE);
        $hasMore = count($posts) > POSTS_PER_PAGE;
        $posts = $this->withComments(array_slice($posts, 0, POSTS_PER_PAGE));
        $html = '';
        foreach ($posts as $post) {
            $html .= render_partial('post', ['post' => $post]);
        }
        $this->json(['ok' => true, 'html' => $html, 'hasMore' => $hasMore]);
    }
}
