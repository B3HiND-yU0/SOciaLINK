<?php
/** Data access for the `likes` table (one like per user per post). */
class LikeModel
{
    /** @return array{liked: bool, count: int} */
    public function toggle(int $postId, int $userId): array
    {
        $exists = Database::query('SELECT id FROM likes WHERE post_id = ? AND user_id = ?', [$postId, $userId])->fetchColumn();
        if ($exists) {
            Database::query('DELETE FROM likes WHERE id = ?', [(int)$exists]);
            $liked = false;
        } else {
            Database::query('INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)', [$postId, $userId]);
            $liked = true;
        }
        return ['liked' => $liked, 'count' => $this->count($postId)];
    }

    public function count(int $postId): int
    {
        return (int)Database::query('SELECT COUNT(*) FROM likes WHERE post_id = ?', [$postId])->fetchColumn();
    }
}
