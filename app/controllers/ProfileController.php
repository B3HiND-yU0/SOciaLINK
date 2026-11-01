<?php
/** Profile pages and profile editing. */
class ProfileController extends Controller
{
    public function show(?string $username = null): void
    {
        $this->requireLogin();
        $me = $this->currentUser();
        $users = new UserModel();
        $profile = $users->findByUsername(trim((string)$username));
        if (!$profile) {
            $this->notFound('That profile does not exist.');
        }
        $posts = $this->withComments((new PostModel())->byUser((int)$profile['id'], (int)$me['id']));
        $this->render('profile/show', [
            'profile'     => $profile,
            'posts'       => $posts,
            'stats'       => $users->stats((int)$profile['id']),
            'isMe'        => (int)$profile['id'] === (int)$me['id'],
            'isFollowing' => (new FollowModel())->isFollowing((int)$me['id'], (int)$profile['id']),
        ], $profile['full_name']);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $this->render('profile/edit', ['errors' => pull_errors()], 'Edit profile');
        clear_old();
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requirePost();
        $me = $this->currentUser();

        $fullName = trim(preg_replace('/\s+/', ' ', (string)($_POST['full_name'] ?? '')));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $_SESSION['old'] = ['full_name' => $fullName, 'bio' => $bio];
        $errors = [];

        $len = mb_strlen($fullName);
        if ($len < 2 || $len > 60) {
            $errors['full_name'] = 'Enter your full name (2–60 characters).';
        }
        if (mb_strlen($bio) > 200) {
            $errors['bio'] = 'Bio can be up to 200 characters.';
        }

        $avatar = $cover = null;
        if (!$errors) {
            [$avatar, $e1] = save_image('avatar', 'avatars');
            [$cover, $e2]  = save_image('cover', 'covers');
            if ($e1) { $errors['avatar'] = $e1; }
            if ($e2) { $errors['cover'] = $e2; }
            if ($errors) {   // don't leave orphan files behind
                delete_image($avatar, 'avatars');
                delete_image($cover, 'covers');
            }
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            redirect(url('profile/edit'));
        }

        $users = new UserModel();
        if (!empty($_POST['remove_avatar']) && !$avatar) {
            delete_image($me['profile_image'], 'avatars');
            $users->clearAvatar((int)$me['id']);
        }
        if (!empty($_POST['remove_cover']) && !$cover) {
            delete_image($me['cover_image'], 'covers');
            $users->clearCover((int)$me['id']);
        }
        $users->updateProfile((int)$me['id'], $fullName, $bio, $avatar, $cover);
        if ($avatar) { delete_image($me['profile_image'], 'avatars'); }
        if ($cover)  { delete_image($me['cover_image'], 'covers'); }

        unset($_SESSION['old']);
        flash('success', 'Profile saved.');
        redirect(url('profile/show/' . rawurlencode($me['username'])));
    }
}
