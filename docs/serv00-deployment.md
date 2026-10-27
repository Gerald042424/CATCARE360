# Serv00 deployment

This project deploys automatically when changes are pushed to the `main` branch.

## One-time Serv00 setup

1. Create a PHP website in the Serv00 panel for the chosen free `*.serv00.net` address or a custom domain.
2. Create a MySQL database and user, then import the project database through phpMyAdmin.
3. Create a folder named `app` alongside the website's `public_html` folder. The deployment creates the required `writable/` folder in that same parent directory.
4. Create `.env` in that parent directory, alongside `app/`, `public_html/`, and `writable/`. It must contain `CI_ENVIRONMENT = production`, the site's HTTPS `app.baseURL`, and the Serv00 MySQL connection values. Never commit this file.
5. Add the GitHub Actions deploy public key to `~/.ssh/authorized_keys` on Serv00.
6. In GitHub, add these repository Actions secrets:
   - `SERV00_HOST` — for example `s4.serv00.com`
   - `SERV00_USERNAME` — the Serv00 account login
   - `SERV00_DOMAIN` — the website hostname created in Serv00
   - `SERV00_SSH_PRIVATE_KEY` — the matching private deploy key

The workflow copies application code to `app/` and only public web assets to `public_html/`. It deliberately preserves the production `.env`, runtime logs, sessions, and uploaded documents.

## Updating the live site

Push the completed change to `main`. GitHub Actions installs the production Composer dependencies and deploys it over SSH.

## Database changes

Code, CSS, JavaScript, and view updates deploy automatically. Any schema migration must be run against the Serv00 MySQL database separately before deploying code that relies on it.
