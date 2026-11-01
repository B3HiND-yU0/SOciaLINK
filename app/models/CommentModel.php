<?php
/** Data access for the `comments` table. */
class CommentModel
{
    /** @return array<int, array> comments grouped by post id, oldest first */
    public function forPosts(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($postIds), '?'));
        $rows = Database::query(
            "SELECT c.*, u.username, u.full_name, u.profile_image
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.post_id IN ($in)
             ORDER BY c.created_at ASC, c.id ASC",
            array_map('intval', $postIds)
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int)$r['post_id']][] = $r;
        }
        return $grouped;
    }

    public function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM comments WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public function create(int $postId, int $userId, string $content): int
    {
        Database::query('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)', [$postId, $userId, $content]);
        return Database::lastId();
    }

    public function update(int $id, string $content): void
    {
        Database::query('UPDATE comments SET content = ? WHERE id = ?', [$content, $id]);
    }

    public function delete(int $id): void
    {
        Database::query('DELETE FROM comments WHERE id = ?', [$id]);
    }
}
