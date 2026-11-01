<?php
/** Data access for the `users` table. */
class UserModel
{
    public function findById(int $id): ?array
    {
        $row = Database::query('SELECT * FROM users WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $row = Database::query('SELECT * FROM users WHERE username = ?', [$username])->fetch();
        return $row ?: null;
    }

    public function usernameExists(string $username): bool
    {
        return (bool)Database::query('SELECT 1 FROM users WHERE username = ?', [$username])->fetchColumn();
    }

    public function create(string $username, string $passwordHash, string $fullName, ?string $profileImage): int
    {
        Database::query(
            'INSERT INTO users (username, password, full_name, profile_image) VALUES (?, ?, ?, ?)',
            [$username, $passwordHash, $fullName, $profileImage]
        );
        return Database::lastId();
    }

    public function updateProfile(int $id, string $fullName, string $bio, ?string $avatar, ?string $cover): void
    {
        Database::query(
            'UPDATE users SET full_name = ?, bio = ?, profile_image = COALESCE(?, profile_image), cover_image = COALESCE(?, cover_image) WHERE id = ?',
            [$fullName, $bio, $avatar, $cover, $id]
        );
    }

    public function clearAvatar(int $id): void
    {
        Database::query('UPDATE users SET profile_image = NULL WHERE id = ?', [$id]);
    }

    public function clearCover(int $id): void
    {
        Database::query('UPDATE users SET cover_image = NULL WHERE id = ?', [$id]);
    }

    public function search(string $term, int $limit = 30): array
    {
        $like = '%' . addcslashes(ltrim($term, '@'), '%_\\') . '%';
        return Database::query(
            'SELECT id, username, full_name, bio, profile_image FROM users
             WHERE username LIKE ? OR full_name LIKE ?
             ORDER BY full_name ASC LIMIT ?',
            [$like, $like, $limit]
        )->fetchAll();
    }

    /** People the viewer does not follow yet, most-followed first. */
    public function suggestions(int $viewerId, int $limit = 5): array
    {
        return Database::query(
            'SELECT u.id, u.username, u.full_name, u.profile_image,
                    (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS followers
             FROM users u
             WHERE u.id <> ?
               AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
             ORDER BY followers DESC, u.id DESC
             LIMIT ?',
            [$viewerId, $viewerId, $limit]
        )->fetchAll();
    }

    public function stats(int $userId): array
    {
        $row = Database::query(
            'SELECT
                (SELECT COUNT(*) FROM posts   WHERE user_id = ?)      AS posts,
                (SELECT COUNT(*) FROM follows WHERE following_id = ?) AS followers,
                (SELECT COUNT(*) FROM follows WHERE follower_id = ?)  AS following',
            [$userId, $userId, $userId]
        )->fetch();
        return array_map('intval', $row);
    }
}
