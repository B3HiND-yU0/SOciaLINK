<?php
/** Create, update and delete comments. */
class CommentController extends Controller
{
    private const MAX_LEN = 500;

    public function store(): void
    {
        $this->requireLogin();
        $this->requirePost();
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));

        if (!(new PostModel())->find($postId)) {
            flash('danger', 'That post no longer exists.');
            redirect_back(url('feed/index'));
        }
        if ($content === '' || mb_strlen($content) > self::MAX_LEN) {
            flash('warning', 'Comments must be 1–' . self::MAX_LEN . ' characters.');
            redirect_back(url('feed/index'), 'post-' . $postId);
        }
        (new CommentModel())->create($postId, (int)auth_id(), $content);
        flash('success', 'Comment added.');
        redirect_back(url('feed/index'), 'post-' . $postId);
    }

    public function update(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $comments = new CommentModel();
        $comment = $comments->find((int)$id);
        $this->authorize($comment);

        $content = trim((string)($_POST['content'] ?? ''));
        if ($content === '' || mb_strlen($content) > self::MAX_LEN) {
            flash('warning', 'Comments must be 1–' . self::MAX_LEN . ' characters.');
        } else {
            $comments->update((int)$comment['id'], $content);
            flash('success', 'Comment updated.');
        }
        redirect_back(url('feed/index'), 'post-' . $comment['post_id']);
    }

    public function delete(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $comments = new CommentModel();
        $comment = $comments->find((int)$id);
        $this->authorize($comment);
        $comments->delete((int)$comment['id']);
        flash('success', 'Comment deleted.');
        redirect_back(url('feed/index'), 'post-' . $comment['post_id']);
    }

    private function authorize(?array $comment): void
    {
        if (!$comment) {
            flash('danger', 'That comment no longer exists.');
            redirect_back(url('feed/index'));
        }
        if ((int)$comment['user_id'] !== (int)auth_id()) {
            flash('danger', 'You can only change your own comments.');
            redirect_back(url('feed/index'));
        }
    }
}
