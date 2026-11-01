<?php
/** Data access for the `posts` table. */
class PostModel
{
    private const SELECT = 'SELECT p.*, u.username, u.full_name, u.profile_image,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id)    AS like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
        EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id = p.id AND l2.user_id = ?) AS liked_by_me
        FROM posts p JOIN users u ON u.id = p.user_id';

    public function feed(int $viewerId, int $limit, int $offset): array
    {
        return Database::query(
            self::SELECT . ' ORDER BY p.created_at DESC, p.id DESC LIMIT ? OFFSET ?',
            [$viewerId, $limit, $offset]
        )->fetchAll();
    }

    public function byUser(int $userId, int $viewerId): array
    {
        return Database::query(
            self::SELECT . ' WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC',
            [$viewerId, $userId]
        )->fetchAll();
    }

    public function search(string $term, int $viewerId, int $limit = 40): array
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';
        return Database::query(
            self::SELECT . ' WHERE p.content LIKE ? ORDER BY p.created_at DESC, p.id DESC LIMIT ?',
            [$viewerId, $like, $limit]
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM posts WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public function create(int $userId, string $content, ?string $image): int
    {
        Database::query('INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)', [$userId, $content, $image]);
        return Database::lastId();
    }

    public function update(int $id, string $content, ?string $image, bool $replaceImage): void
    {
        if ($replaceImage) {
            Database::query('UPDATE posts SET content = ?, image = ? WHERE id = ?', [$content, $image, $id]);
        } else {
            Database::query('UPDATE posts SET content = ? WHERE id = ?', [$content, $id]);
        }
    }

    public function delete(int $id): void
    {
        Database::query('DELETE FROM posts WHERE id = ?', [$id]);
    }
}
