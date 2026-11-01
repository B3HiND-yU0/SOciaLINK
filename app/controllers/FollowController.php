<?php
/** Follow / unfollow (AJAX or plain form). */
class FollowController extends Controller
{
    public function toggle(?string $id = null): void
    {
        $this->requireLogin();
        $this->requirePost();
        $targetId = (int)$id;
        $me = (int)auth_id();

        if ($targetId === $me || !(new UserModel())->findById($targetId)) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => 'You cannot follow this account.'], 422);
            }
            flash('warning', 'You cannot follow this account.');
            redirect_back(url('feed/index'));
        }
        $result = (new FollowModel())->toggle($me, $targetId);
        if ($this->wantsJson()) {
            $this->json(['ok' => true] + $result);
        }
        redirect_back(url('feed/index'));
    }
}
