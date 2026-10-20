# Putting DRinkWeLL on cPanel shared hosting

Plain steps, in order. Replace `USER` with your cPanel username and `yourdomain.com` with your domain.

## 0. What you need
- A cPanel host with PHP 8.3 or newer, MySQL 8.0.16+ or MariaDB 10.3+, cron jobs, and free SSL (AutoSSL).
- SSH access is easiest. Without it, there is a no-SSH route in step 8.
- On your PC: PHP, Composer, Node (to build the site files).

## 1. Choose PHP 8.3 and turn on extensions
cPanel > MultiPHP Manager > pick your domain > PHP 8.3 (or 8.4) > Apply.
cPanel > Select PHP Version > Extensions: tick pdo_mysql, mysqlnd, mbstring, openssl, curl, fileinfo, gd, tokenizer, xml, ctype, zip.

## 2. Create the database
cPanel > MySQL Databases.
1. Create a database named `drinkwell`. It becomes `USER_drinkwell`.
2. Create a user `afuser` with a strong password. It becomes `USER_afuser`.
3. Add the user to the database with ALL PRIVILEGES.
Write all three down.

## 3. Build the site on your PC
In the project folder:
```
git fetch origin
git merge origin/claude/public-pages
composer install --no-dev --optimize-autoloader
npm install
npm run build
```
Do not upload `.env`, `node_modules`, `.git`, or `database/database.sqlite`.

## 4. Upload
Make a zip of the project (without the folders above). On Windows 10/11 in the project folder:
```
tar -a -cf release.zip --exclude=node_modules --exclude=.git --exclude=.env --exclude=*.sqlite .
```
(Avoid PowerShell Compress-Archive: it breaks folder paths on Linux.)
cPanel > File Manager > go to `/home/USER/` (above public_html) > create folder `drinkwell` > Upload `release.zip` there > Extract.
Result: `/home/USER/drinkwell/artisan`, `/home/USER/drinkwell/public`, and so on.

## 5. Point the domain at the `public` folder
Best way: cPanel > Domains > Manage (your domain) > Document Root = `drinkwell/public`. For a subdomain, create it with that document root.

If your host does not let you change it (alternative):
1. Copy everything inside `drinkwell/public` into `public_html`.
2. Edit `public_html/index.php`: change `__DIR__.'/../vendor/autoload.php'` to `__DIR__.'/../drinkwell/vendor/autoload.php'`, and the same for `bootstrap/app.php` and the maintenance file path.

## 6. Make the .env file
In File Manager open `drinkwell`, copy `.env.production.example` to `.env`, and edit:
- APP_URL = `https://yourdomain.com`
- APP_KEY: on your PC run `php artisan key:generate --show` and paste the result (looks like `base64:...`). Keep it forever, it signs codes and sessions.
- DB_DATABASE = `USER_drinkwell`, DB_USERNAME = `USER_afuser`, DB_PASSWORD = yours, DB_HOST = `localhost`. Keep `DB_CONNECTION=mysql`.
- ADMIN_EMAIL and ADMIN_PASSWORD (your admin login; used once).
- Mail: see step 9.
Keep APP_ENV=production and APP_DEBUG=false.

## 7. Create the tables (SSH)
cPanel > Terminal (or ssh in). Then:
```
cd ~/drinkwell
/usr/local/bin/ea-php83 artisan migrate --force
/usr/local/bin/ea-php83 artisan db:seed --force
/usr/local/bin/ea-php83 artisan storage:link
```
If `ea-php83` is not found, run `php -v` first; if it is lower than 8.3, use the path your host lists in MultiPHP.
Now open `.env` and delete the ADMIN_PASSWORD line.
To add another admin later: `php artisan drinkwell:make-admin "Name" email@example.com`.

If `storage:link` fails (public is `public_html`): `ln -s /home/USER/drinkwell/storage/app/public /home/USER/public_html/storage`.

## 8. No SSH? Build the database from your PC
1. cPanel > Remote MySQL > add your PC's public IP (search "what is my ip").
2. On your PC, temporarily set `.env` DB_HOST to your host's server name (shown in Remote MySQL / MySQL Databases) and the DB_* values from step 2.
3. Run `php artisan migrate --force` then `php artisan db:seed --force` (with ADMIN_EMAIL and ADMIN_PASSWORD set).
4. Restore your `.env` and remove the IP from Remote MySQL. Create the storage link by uploading nothing: use File Manager > Symlink option if present, or ask the host.
Some hosts block remote MySQL; then ask support to run the three commands, or import a phpMyAdmin dump.

## 9. Email (needed for sign-up codes)
Easiest: cPanel > Email Accounts > create `no-reply@yourdomain.com`. Then in `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=no-reply@yourdomain.com
MAIL_PASSWORD=that account's password
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
```
Then cPanel > Email Deliverability > fix SPF and DKIM for the domain, or mail lands in spam.
Alternative, Resend over SMTP: MAIL_HOST=smtp.resend.com, MAIL_PORT=465, MAIL_SCHEME=smtps, MAIL_USERNAME=resend, MAIL_PASSWORD=your Resend API key, and use a verified domain as the From address.

## 10. The clock (cron)
The simulation moves only when the scheduler runs. cPanel > Cron Jobs > every minute (`* * * * *`):
```
/usr/local/bin/ea-php83 /home/USER/drinkwell/artisan schedule:run >> /dev/null 2>&1
```
Some hosts limit cron to every 5 minutes; the clock still works, just in bigger steps.

## 11. HTTPS
cPanel > SSL/TLS Status > Run AutoSSL for the domain. Make sure APP_URL starts with https. If the site sits behind a proxy or CDN, set TRUSTED_PROXIES=* in `.env`.

## 12. Check everything
```
cd ~/drinkwell
/usr/local/bin/ea-php83 artisan config:cache
/usr/local/bin/ea-php83 artisan route:cache
/usr/local/bin/ea-php83 artisan view:cache
/usr/local/bin/ea-php83 artisan drinkwell:doctor
```
Fix anything that says FAIL or WARN. Optional: `artisan drinkwell:warm-tiles` fills the map tiles (needs outbound internet).
After any `.env` change run `artisan config:clear` then `config:cache` again.

## 13. Log in
Visit your domain, sign in with ADMIN_EMAIL and the password, open Admin and check Clock and Content. Keep CSP_ENFORCE=false until the browser console shows no blocked items.

## Updating later
Build on your PC (`npm run build`), upload the changed files (keep `.env` and `storage`), then run `artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`.

## Backups
cPanel > Backup > download a full backup or a MySQL backup, or phpMyAdmin > Export. `artisan drinkwell:backup` needs mysqldump and may be blocked on shared hosts. ID uploads live in `storage/app/private`, back that up too.

## Troubleshooting
- White page or error 500: look at `storage/logs/laravel.log`; check `storage` and `bootstrap/cache` are writable (permissions 755, or 775).
- "No application encryption key": APP_KEY is empty.
- Styles missing: `public/build` was not uploaded.
- Page not found on every link except home: the document root is not `public`, or `.htaccess` was not copied (it is a hidden file).
- Codes never arrive: wrong MAIL_PORT/MAIL_SCHEME pair, or no SPF/DKIM.
- Clock does not move: cron job missing; `drinkwell:doctor` shows the last scheduler run.
- Database errors about CHECK: MySQL older than 8.0.16 or MariaDB older than 10.3.
