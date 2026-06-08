# Breezy CMS

A lightweight, hand-rolled PHP CMS with no database. Content is stored as JSON
files on disk. The admin panel provides a drag-and-drop page builder, a media
library, site-wide theme options, and per-page password protection.

## Features

- **Drag-and-drop page builder** — hero sections, 1–4 column containers (including
  asymmetric split layouts), and widgets (rich text, image, video, file download,
  button).
- **Home page** — built with the same builder; always published at `/`.
- **Pages** — create, publish/draft, reorder for navigation, optional per-page
  password protection.
- **Theme** — site-wide colors (hex), self-hosted Google Fonts, font sizes, and
  corner presets; applied to the public site only.
- **Media library** — upload images, videos, and files; reuse via a picker in the
  builder. Limits follow PHP's `upload_max_filesize` / `post_max_size`.
- **Rich text** — [Quill](https://quilljs.com/) editor (bundled locally) with
  theme fonts, colors, and alignment.
- **Security** — bcrypt passwords, CSRF on all POSTs, 30-minute idle session
  timeout, Cloudflare Turnstile (configurable), validated uploads served through
  the app (not directly from disk).

## Requirements

- PHP 8.1+ (developed on 8.4). No database, no Composer.

## Quick start (local)

From the project root:

```bash
php -S localhost:8000 -t public
```

Then visit:

- Site: <http://localhost:8000/>
- Admin: <http://localhost:8000/admin/login>

### First-time setup

1. Log in at `/admin/login` with `admin@example.com` and password `admin123`,
   then change it immediately under **Admin → Settings**.
2. For local dev without Turnstile network calls, set `TURNSTILE_ENABLED=0` in
   your environment or `'enabled' => false` in `config/config.php` under
   `turnstile`.

## Project structure

```
public/            Web root: index.php (front controller), assets/
app/
  Core/            Router, App, Auth, Theme, View, Csrf, Html, JsonStore, …
  Repositories/    Settings, Page, Media, Users
  Controllers/     Public + Admin
  Views/           layouts, templates, admin screens, partials
config/            config.php, fonts.php (curated font registry)
storage/
  content/         settings.json, home.json, pages/, media/index.json, users.json
  uploads/         images/, videos/, files/  (served via the app)
```

## How pages are stored

Each page (and the home page) stores a `layout` array: an ordered list of
**containers** (column rows) and **hero** sections. Each container column holds
an ordered list of **widgets**. The builder posts this tree as `layout_json`; the
server sanitizes and persists it as JSON.

Protected pages store a bcrypt `password_hash` on the page itself. Unlock state
is tracked per slug in the PHP session (30-minute idle timeout).

## Storage & backup

All content is plain files. To back up or move the site, copy `storage/`.
The repo ships generic starter files (`users.json`, `settings.json`, `home.json`)
so the site runs on first download; your own content and uploads stay out of the
repo via `.gitignore`. The starter `users.json` holds the default `admin123`
hash — change the password after first login, and don't commit real password
hashes back to a public repo.

Writes use atomic temp-file + `flock` + `rename`.

## Deployment

- Point the web server's document root at `public/`.
- Ensure `storage/` is writable by PHP and not web-accessible.
- Apache: enable `mod_rewrite` (see `public/.htaccess`). Nginx: route non-file
  requests to `public/index.php`.
- Set real Cloudflare Turnstile keys (or disable Turnstile) before going live.
- If the app lives in a subdirectory, set `base_path` in `config/config.php`.

## Swapping storage later

Controllers talk only to Repository classes, so the flat-file backend can be
replaced with a database implementation without touching controllers or views.
