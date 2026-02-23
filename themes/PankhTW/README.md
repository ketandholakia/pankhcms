# PankhTW (Tailwind Theme)

This theme uses TailwindCSS via the Tailwind CLI (proper build).

## Build CSS (once)

From the theme directory:

- `npm install`
- `npm run build`

## Watch CSS (recommended while developing)

- `npm run watch`

Tailwind will rebuild `assets/css/theme.css` whenever you change theme Blade files.

### Devilbox note

If Node is not installed on your host, run the commands inside your Devilbox Node container.
Typical examples (adjust to your setup):

- `docker-compose exec node sh -lc "cd /shared/httpd/pankhCMS/themes/PankhTW && npm install && npm run watch"`

If your project path inside containers differs, replace `/shared/httpd/pankhCMS` accordingly.
