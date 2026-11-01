<?php
/** AJAX like / unlike. */
class LikeController extends Controller
{
    public function toggle(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $postId = (int)$id;
        if (!(new PostModel())->find($postId)) {
            $this->json(['ok' => false, 'message' => 'Post not found.'], 404);
        }
        $result = (new LikeModel())->toggle($postId, (int)auth_id());
        $this->json(['ok' => true] + $result);
    }
}
