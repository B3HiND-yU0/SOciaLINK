-- ============================================================================
-- SOciaLINK - database schema, sample data and ADVANCED SQL
--   * constraints, composite + FULLTEXT indexes, CHECKs
--   * views (aggregates, window functions)
--   * stored procedures (CTE, transaction, error handling)
--   * triggers (validation + automatic activity log)
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < sql/social_app.sql
-- Requires MySQL 8.0+ or MariaDB 10.2+ (XAMPP default is fine).
-- All sample accounts use the password:  password
-- The PHP app works unchanged with this file; the advanced objects are extra.
-- ============================================================================
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS socialink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE socialink;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW      IF EXISTS v_post_ranking;
DROP VIEW      IF EXISTS v_user_stats;
DROP VIEW      IF EXISTS v_post_stats;
DROP PROCEDURE IF EXISTS sp_feed;
DROP PROCEDURE IF EXISTS sp_suggested_users;
DROP PROCEDURE IF EXISTS sp_toggle_like;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 1. TABLES
-- ============================================================================
CREATE TABLE users (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username      VARCHAR(20)   NOT NULL,
    password      VARCHAR(255)  NOT NULL,              -- password_hash() output
    full_name     VARCHAR(60)   NOT NULL,
    bio           VARCHAR(200)  NOT NULL DEFAULT '',
    profile_image VARCHAR(255)  NULL DEFAULT NULL,
    cover_image   VARCHAR(255)  NULL DEFAULT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    FULLTEXT KEY ft_users_name (full_name, username),
    CONSTRAINT chk_users_username CHECK (CHAR_LENGTH(username) >= 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    content     TEXT         NOT NULL,
    image       VARCHAR(255) NULL DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_posts_created (created_at, id),            -- newsfeed ordering
    KEY idx_posts_user_created (user_id, created_at),  -- profile page
    FULLTEXT KEY ft_posts_content (content),           -- keyword search
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    content     VARCHAR(500) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_post_created (post_id, created_at),
    KEY idx_comments_user (user_id),
    CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_comments_not_blank CHECK (CHAR_LENGTH(TRIM(content)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE likes (                                   -- one like per user per post
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id   INT UNSIGNED NOT NULL,
    user_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_like (post_id, user_id),
    KEY idx_likes_user (user_id),
    CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follows (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    follower_id   INT UNSIGNED NOT NULL,
    following_id  INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_follow (follower_id, following_id),
    KEY idx_follows_following (following_id),
    CONSTRAINT fk_follows_follower  FOREIGN KEY (follower_id)  REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_follows_following FOREIGN KEY (following_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_follows_not_self CHECK (follower_id <> following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Filled automatically by triggers (section 4)
CREATE TABLE activity_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    action      VARCHAR(30)  NOT NULL,                 -- post_created, comment_created, ...
    entity_id   INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_user_time (user_id, created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. SAMPLE DATA  (password for every account: "password")
-- ============================================================================
INSERT INTO users (id, username, password, full_name, bio, created_at) VALUES
(1, 'maria',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos',   'Coffee, code and sunsets over Butuan Bay.', DATE_SUB(NOW(), INTERVAL 40 DAY)),
(2, 'juan',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan dela Cruz', 'IT student. Basketball on weekends.',          DATE_SUB(NOW(), INTERVAL 35 DAY)),
(3, 'lea',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lea Mercado',    'Designer. Plant mom. Always hungry.',        DATE_SUB(NOW(), INTERVAL 28 DAY)),
(4, 'carlo',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlo Reyes',    'Building things with PHP and MySQL.',        DATE_SUB(NOW(), INTERVAL 20 DAY)),
(5, 'ana',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Villanueva', 'Photographer from Nasipit.',                  DATE_SUB(NOW(), INTERVAL 12 DAY));

INSERT INTO posts (id, user_id, content, image, created_at, updated_at) VALUES
(1, 1, 'Welcome to SOciaLINK! 🎉 Say hi, share what you are working on and follow people you like. #welcome #SOciaLINK', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 2, 'Finally finished my MVC project. Controllers handle the requests, models talk to MySQL and views only show data. Clean! #php #mvc', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 3, 'Tried a new palette today: deep indigo with one bright coral accent. Less is more. #design', NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR), DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(4, 4, 'Reminder: always use prepared statements. Never concatenate user input into SQL. #security #mysql', NULL, DATE_SUB(NOW(), INTERVAL 22 HOUR), DATE_SUB(NOW(), INTERVAL 22 HOUR)),
(5, 5, 'Golden hour at the Nasipit port was unreal today. #nasipit #photography', NULL, DATE_SUB(NOW(), INTERVAL 14 HOUR), DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(6, 1, 'Who else is presenting their final project this week? Good luck, everyone! 💪 #finals', NULL, DATE_SUB(NOW(), INTERVAL 8 HOUR), DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(7, 2, 'Pro tip: escape output with htmlspecialchars() so your pages stay safe from XSS. #php #security', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(8, 3, 'Dark mode or light mode? I switch depending on my mood. 🌙☀️', NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(9, 4, 'Late night debugging session. Coffee level: critical. ☕ #devlife', NULL, DATE_SUB(NOW(), INTERVAL 25 MINUTE), DATE_SUB(NOW(), INTERVAL 25 MINUTE));

INSERT INTO comments (post_id, user_id, content, created_at, updated_at) VALUES
(1, 2, 'Hello everyone! Happy to be here 👋',                         DATE_SUB(NOW(), INTERVAL 70 HOUR), DATE_SUB(NOW(), INTERVAL 70 HOUR)),
(1, 3, 'The design looks amazing, great job!',                      DATE_SUB(NOW(), INTERVAL 69 HOUR), DATE_SUB(NOW(), INTERVAL 69 HOUR)),
(1, 5, 'Joined! Looking forward to sharing photos here.',           DATE_SUB(NOW(), INTERVAL 60 HOUR), DATE_SUB(NOW(), INTERVAL 60 HOUR)),
(2, 4, 'Nice work! Did you add CSRF protection too?',               DATE_SUB(NOW(), INTERVAL 40 HOUR), DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(2, 2, 'Yes, every form has a token. 😄',                          DATE_SUB(NOW(), INTERVAL 38 HOUR), DATE_SUB(NOW(), INTERVAL 38 HOUR)),
(3, 1, 'Love that combo. Share the hex codes!',                     DATE_SUB(NOW(), INTERVAL 28 HOUR), DATE_SUB(NOW(), INTERVAL 28 HOUR)),
(4, 3, 'Saving this one. Thank you!',                               DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(5, 1, 'Beautiful shot, Ana!',                                      DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(6, 4, 'Good luck to us all!',                                      DATE_SUB(NOW(), INTERVAL 6 HOUR),  DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(8, 5, 'Dark mode all the way.',                                    DATE_SUB(NOW(), INTERVAL 1 HOUR),  DATE_SUB(NOW(), INTERVAL 1 HOUR));

INSERT INTO likes (post_id, user_id) VALUES
(1,2),(1,3),(1,4),(1,5),
(2,1),(2,3),(2,4),
(3,1),(3,5),
(4,1),(4,2),(4,3),
(5,1),(5,2),(5,3),(5,4),
(6,2),(6,3),
(7,1),(7,4),
(8,1),(8,2),
(9,1),(9,5);

INSERT INTO follows (follower_id, following_id) VALUES
(1,2),(1,3),(1,5),
(2,1),(2,4),
(3,1),(3,5),
(4,1),(4,2),
(5,1),(5,3);

-- ============================================================================
-- 3. VIEWS  (aggregates + window functions)
-- ============================================================================
CREATE VIEW v_post_stats AS
SELECT p.id AS post_id,
       p.user_id,
       (SELECT COUNT(*) FROM likes    l WHERE l.post_id = p.id) AS like_count,
       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
FROM posts p;

CREATE VIEW v_user_stats AS
SELECT u.id AS user_id, u.username, u.full_name,
       (SELECT COUNT(*) FROM posts   WHERE user_id = u.id)      AS post_count,
       (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS follower_count,
       (SELECT COUNT(*) FROM follows WHERE follower_id = u.id)  AS following_count,
       (SELECT COUNT(*) FROM likes l JOIN posts p ON p.id = l.post_id WHERE p.user_id = u.id) AS likes_received
FROM users u;

-- Engagement = likes + 2 x comments, ranked overall and per author
CREATE VIEW v_post_ranking AS
SELECT s.post_id, s.user_id, u.username, s.like_count, s.comment_count,
       (s.like_count + 2 * s.comment_count) AS engagement,
       DENSE_RANK() OVER (ORDER BY (s.like_count + 2 * s.comment_count) DESC) AS overall_rank,
       ROW_NUMBER() OVER (PARTITION BY s.user_id
                          ORDER BY (s.like_count + 2 * s.comment_count) DESC, s.post_id DESC) AS rank_for_author
FROM v_post_stats s
JOIN users u ON u.id = s.user_id;

-- ============================================================================
-- 4. STORED PROCEDURES + TRIGGERS
-- ============================================================================
DELIMITER $$

-- Newsfeed page for one viewer (joins the stats view, flags posts the viewer liked)
CREATE PROCEDURE sp_feed(IN p_viewer INT UNSIGNED, IN p_limit INT, IN p_offset INT)
BEGIN
    SELECT p.*, u.username, u.full_name, u.profile_image,
           s.like_count, s.comment_count,
           EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = p_viewer) AS liked_by_me
    FROM posts p
    JOIN users u        ON u.id = p.user_id
    JOIN v_post_stats s ON s.post_id = p.id
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT p_limit OFFSET p_offset;
END$$

-- "People you may know": friends-of-friends ranked by mutual follows (CTEs)
CREATE PROCEDURE sp_suggested_users(IN p_viewer INT UNSIGNED, IN p_limit INT)
BEGIN
    WITH my_follows AS (
        SELECT following_id AS uid FROM follows WHERE follower_id = p_viewer
    ),
    mutuals AS (
        SELECT f.following_id AS uid, COUNT(*) AS mutual_follows
        FROM follows f
        JOIN my_follows m ON m.uid = f.follower_id
        GROUP BY f.following_id
    )
    SELECT u.id, u.username, u.full_name, u.profile_image,
           COALESCE(mu.mutual_follows, 0) AS mutual_follows,
           (SELECT COUNT(*) FROM follows x WHERE x.following_id = u.id) AS followers
    FROM users u
    LEFT JOIN mutuals mu ON mu.uid = u.id
    WHERE u.id <> p_viewer
      AND u.id NOT IN (SELECT uid FROM my_follows)
    ORDER BY mutual_follows DESC, followers DESC, u.id DESC
    LIMIT p_limit;
END$$

-- Like / unlike inside a transaction; returns the new state and count
CREATE PROCEDURE sp_toggle_like(IN p_post INT UNSIGNED, IN p_user INT UNSIGNED)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    IF EXISTS (SELECT 1 FROM likes WHERE post_id = p_post AND user_id = p_user) THEN
        DELETE FROM likes WHERE post_id = p_post AND user_id = p_user;
    ELSE
        INSERT INTO likes (post_id, user_id) VALUES (p_post, p_user);
    END IF;
    COMMIT;

    SELECT EXISTS(SELECT 1 FROM likes WHERE post_id = p_post AND user_id = p_user) AS liked,
           (SELECT COUNT(*) FROM likes WHERE post_id = p_post)                     AS like_count;
END$$

-- Triggers: data cleaning + validation
CREATE TRIGGER trg_comments_bi BEFORE INSERT ON comments
FOR EACH ROW
BEGIN
    SET NEW.content = TRIM(NEW.content);
END$$

CREATE TRIGGER trg_follows_bi BEFORE INSERT ON follows
FOR EACH ROW
BEGIN
    IF NEW.follower_id = NEW.following_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A user cannot follow themselves';
    END IF;
END$$

-- Triggers: automatic audit trail
CREATE TRIGGER trg_posts_ai AFTER INSERT ON posts
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, entity_id) VALUES (NEW.user_id, 'post_created', NEW.id);
END$$

CREATE TRIGGER trg_posts_ad AFTER DELETE ON posts
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, entity_id) VALUES (OLD.user_id, 'post_deleted', OLD.id);
END$$

CREATE TRIGGER trg_comments_ai AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, entity_id) VALUES (NEW.user_id, 'comment_created', NEW.id);
END$$

CREATE TRIGGER trg_likes_ai AFTER INSERT ON likes
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, entity_id) VALUES (NEW.user_id, 'post_liked', NEW.post_id);
END$$

CREATE TRIGGER trg_follows_ai AFTER INSERT ON follows
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, entity_id) VALUES (NEW.follower_id, 'user_followed', NEW.following_id);
END$$

DELIMITER ;

-- ============================================================================
-- 5. ADVANCED QUERY EXAMPLES  (run one at a time in phpMyAdmin > SQL)
-- ============================================================================

-- Q1. Call the stored procedures
--   CALL sp_feed(1, 10, 0);
--   CALL sp_suggested_users(1, 5);
--   CALL sp_toggle_like(9, 2);

-- Q2. Top 3 posts by engagement (window function view)
--   SELECT * FROM v_post_ranking WHERE overall_rank <= 3 ORDER BY overall_rank, post_id;

-- Q3. Best post of every author
--   SELECT r.username, p.content, r.engagement
--   FROM v_post_ranking r JOIN posts p ON p.id = r.post_id
--   WHERE r.rank_for_author = 1 ORDER BY r.engagement DESC;

-- Q4. Mutual follows (friends): self-join on follows
--   SELECT a.username AS user_a, b.username AS user_b
--   FROM follows f1
--   JOIN follows f2 ON f2.follower_id = f1.following_id AND f2.following_id = f1.follower_id
--   JOIN users a ON a.id = f1.follower_id
--   JOIN users b ON b.id = f1.following_id
--   WHERE f1.follower_id < f1.following_id;

-- Q5. Running total of posts per day (CTE + window SUM)
--   WITH daily AS (
--       SELECT DATE(created_at) AS day, COUNT(*) AS posts FROM posts GROUP BY DATE(created_at)
--   )
--   SELECT day, posts, SUM(posts) OVER (ORDER BY day) AS running_total FROM daily;

-- Q6. Most active commenters (GROUP BY + HAVING)
--   SELECT u.username, COUNT(*) AS comments_written
--   FROM comments c JOIN users u ON u.id = c.user_id
--   GROUP BY u.id, u.username HAVING COUNT(*) >= 2 ORDER BY comments_written DESC;

-- Q7. Users who have never posted (NOT EXISTS)
--   SELECT u.username FROM users u WHERE NOT EXISTS (SELECT 1 FROM posts p WHERE p.user_id = u.id);

-- Q8. Full-text search with relevance score
--   SELECT id, content, MATCH(content) AGAINST ('security' IN NATURAL LANGUAGE MODE) AS score
--   FROM posts WHERE MATCH(content) AGAINST ('security' IN NATURAL LANGUAGE MODE) ORDER BY score DESC;

-- Q9. Leaderboard from the user stats view
--   SELECT username, post_count, follower_count, likes_received,
--          RANK() OVER (ORDER BY likes_received DESC) AS popularity_rank
--   FROM v_user_stats;

-- Q10. Audit trail written by the triggers
--   SELECT l.created_at, u.username, l.action, l.entity_id
--   FROM activity_log l JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 20;
