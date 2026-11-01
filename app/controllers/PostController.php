<?php
/** Create, update and delete posts. */
class PostController extends Controller
{
    private const MAX_LEN = 1000;

    public function store(): void
    {
        $this->requireLogin();
        $this->requirePost();
        $content = trim((string)($_POST['content'] ?? ''));

        if (mb_strlen($content) > self::MAX_LEN) {
            flash('danger', 'Posts can be up to ' . self::MAX_LEN . ' characters.');
            redirect_back(url('feed/index'));
        }
        [$image, $err] = save_image('image', 'posts');
        if ($err) {
            flash('danger', $err);
            redirect_back(url('feed/index'));
        }
        if ($content === '' && !$image) {
            flash('warning', 'Write something or add a photo before posting.');
            redirect_back(url('feed/index'));
        }
        $id = (new PostModel())->create((int)auth_id(), $content, $image);
        flash('success', 'Your post is live.');
        redirect(url('feed/index') . '#post-' . $id);
    }

    public function update(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $posts = new PostModel();
        $post = $posts->find((int)$id);
        $this->authorize($post);

        $content = trim((string)($_POST['content'] ?? ''));
        if (mb_strlen($content) > self::MAX_LEN) {
            flash('danger', 'Posts can be up to ' . self::MAX_LEN . ' characters.');
            redirect_back(url('feed/index'), 'post-' . $post['id']);
        }
        [$newImage, $err] = save_image('image', 'posts');
        if ($err) {
            flash('danger', $err);
            redirect_back(url('feed/index'), 'post-' . $post['id']);
        }
        $remove = !empty($_POST['remove_image']);
        $finalImage = $newImage ?: ($remove ? null : $post['image']);
        if ($content === '' && !$finalImage) {
            if ($newImage) {
                delete_image($newImage, 'posts');
            }
            flash('warning', 'A post needs some text or a photo.');
            redirect_back(url('feed/index'), 'post-' . $post['id']);
        }
        $replace = ($newImage !== null) || $remove;
        $posts->update((int)$post['id'], $content, $finalImage, $replace);
        if ($replace) {
            delete_image($post['image'], 'posts');
        }
        flash('success', 'Post updated.');
        redirect_back(url('feed/index'), 'post-' . $post['id']);
    }

    public function delete(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $posts = new PostModel();
        $post = $posts->find((int)$id);
        $this->authorize($post);
        $posts->delete((int)$post['id']);
        delete_image($post['image'], 'posts');
        flash('success', 'Post deleted.');
        redirect_back(url('feed/index'));
    }

    /** Only the author may change a post. */
    private function authorize(?array $post): void
    {
        if (!$post) {
            flash('danger', 'That post no longer exists.');
            redirect_back(url('feed/index'));
        }
        if ((int)$post['user_id'] !== (int)auth_id()) {
            flash('danger', 'You can only change your own posts.');
            redirect_back(url('feed/index'));
        }
    }
}
