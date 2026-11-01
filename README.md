# SOciaLINK
Mini social networking web app - PHP (MVC) + MySQL + Bootstrap 5.
Final project for Web Systems and Technologies.

## Run it (XAMPP / WAMP / Laragon)
1. Copy this folder to `htdocs/socialink` (XAMPP) and start **Apache** and **MySQL**.
2. Open **phpMyAdmin** -> **Import** -> choose `sql/social_app.sql` -> **Go**.
   (This creates the `socialink` database, the tables and sample data.)
3. If your MySQL has a password, edit `config/database.php`.
4. Open **http://localhost/socialink/** in the browser.

Requirements: PHP 8.0+ with `pdo_mysql`, `mbstring` and `fileinfo` (all on by default in XAMPP).
Internet is needed for the Bootstrap, icon and Google Fonts CDN files.

### Demo accounts (password for all: `password`)
`maria` - `juan` - `lea` - `carlo` - `ana`

## Features
- **Auth:** register with client + server validation, login/logout, `password_hash()`, profile picture upload, session regeneration, login throttle
- **Profile:** avatar, cover photo, name, username, bio, post list, edit profile
- **Posts (CRUD):** create (text + optional image), newsfeed (latest first), edit and delete own posts
- **Comments (CRUD):** add, edit, delete own comments, shown under each post
- **Likes:** AJAX like button + counter, one like per user per post
- **Search:** people by name/username, posts by keyword or #hashtag
- **Bonus:** AJAX likes, follow/unfollow, dark mode, "Load more" feed, image validation, cover photo, hashtags

## Architecture
```
app/controllers  Auth, Feed, Post, Comment, Like, Follow, Profile, Search
app/models       UserModel, PostModel, CommentModel, LikeModel, FollowModel (PDO + prepared statements only)
app/views        layouts, auth, feed, profile, search, partials (no SQL in views)
app/core         Database, Controller base, helpers
config/          database.php, config.php
public/          index.php (front controller), assets/, uploads/
sql/             social_app.sql
```
Routes look like `index.php?r=controller/action/param` (no mod_rewrite needed).

## Security
Prepared statements everywhere - output escaped with `htmlspecialchars` - CSRF token on every POST -
owner checks on edit/delete - upload validation (real MIME type, size, random file names) -
PHP execution blocked in `uploads/` - app/config/sql folders blocked by `.htaccess`.

## Troubleshooting
- **"Database not reachable"** -> start MySQL, import `sql/social_app.sql`, check `config/database.php`.
- **Images do not upload** -> make sure `public/uploads/*` folders are writable.
- **Page looks unstyled** -> you are offline; the CDN files could not load.
"# SOciaLink" 
