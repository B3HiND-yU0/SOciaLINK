<?php
/** Data access for the `follows` table. */
class FollowModel
{
    public function isFollowing(int $followerId, int $targetId): bool
    {
        return (bool)Database::query(
            'SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?',
            [$followerId, $targetId]
        )->fetchColumn();
    }

    /** @return array{following: bool, followers: int} */
    public function toggle(int $followerId, int $targetId): array
    {
        if ($this->isFollowing($followerId, $targetId)) {
            Database::query('DELETE FROM follows WHERE follower_id = ? AND following_id = ?', [$followerId, $targetId]);
            $following = false;
        } else {
            Database::query('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)', [$followerId, $targetId]);
            $following = true;
        }
        $followers = (int)Database::query('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$targetId])->fetchColumn();
        return ['following' => $following, 'followers' => $followers];
    }
}
