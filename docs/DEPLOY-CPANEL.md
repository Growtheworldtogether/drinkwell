# Putting DRinkWeLL on cPanel (no Terminal needed)

You build one zip on your PC, upload it, and a setup page in the browser does the rest: settings file, database tables, machines, your admin account and the first reference code. About 20 minutes.

## What you need
- cPanel hosting with PHP 8.3 or newer and MySQL 8.0.16+ (or MariaDB 10.3+).
- A domain (or subdomain) for the site.
- On your PC: the project folder, PHP, Composer and Node (the same ones you use to run it locally).

## 1. Make the release zip (on your PC)
In the project folder, double-click `scripts\make-release.bat` (or run it in a command window).
It builds the styles, packs only what the live site needs, and makes `drinkwell-release.zip` in the project folder. Your own `.env` and local database are never included.

## 2. Pick the PHP version
cPanel > **MultiPHP Manager**: tick your domain, choose **PHP 8.4** (or 8.3), Apply.
cPanel > **Select PHP Version** > Extensions (only if your host shows this page): make sure these are ticked: pdo_mysql, mbstring, openssl, fileinfo, tokenizer, dom, ctype, curl, gd, zip.

## 3. Create the database
cPanel > **MySQL Database Wizard**:
1. Database name, e.g. `drinkwell` (cPanel adds a prefix, so it becomes `youruser_drinkwell`).
2. A user with a strong password (use the generator, and copy the password somewhere safe).
3. Tick **ALL PRIVILEGES**, then finish.

Keep the full database name, user name and password for step 7.

## 4. Upload and extract
There are two zips. Pick the one that fits your domain:

**A. Your domain must use `public_html` (most main domains)** — use `drinkwell-release-public_html.zip`.
1. cPanel > **File Manager**, open `public_html` and delete what the host put there (`index.html`, `default.html` and the like; leave `cgi-bin` and `.well-known` alone).
2. Go up to your home folder (`/home/youruser`, the one that holds `public_html`).
3. **Upload** `drinkwell-release-public_html.zip` there, right-click it > **Extract** into `/home/youruser`. Then delete the zip.

You now have `public_html` (the site's public files) and a `drinkwell` folder next to it with the rest. `drinkwell` is outside `public_html`, so it has no web address: the site opens at `https://yourdomain.com` and nothing is reachable at `yourdomain.com/drinkwell`. Skip step 5.

**B. You can set the document root (subdomains and addon domains)** — use `drinkwell-release.zip`.
1. In your home folder create a folder `drinkwell`, upload the zip into it and **Extract** there. Then delete the zip.
2. Do step 5.

## 5. Point the domain at the public folder (zip B only)
cPanel > **Domains** > Manage > set the **Document Root** to `drinkwell/public`.

## 6. Turn on HTTPS
cPanel > **SSL/TLS Status** > select the domain > **Run AutoSSL**. Wait until it shows a green lock. Sign-in only works over https.

## 7. Run the setup wizard
1. Open `https://yourdomain.com/install.php`.
2. The first page checks your server. Fix anything red (usually the PHP version or an extension from step 2), then reload.
3. It asks for a setup key. In File Manager open `drinkwell/storage/app/install-key.txt` (in your home folder, not in public_html) and copy the code into the page. This makes sure only you can run the setup.
4. Fill in the pages:
   - **Site**: name, `https://yourdomain.com`, support email, time zone.
   - **Database**: host `localhost`, and the name, user and password from step 3. It tests the connection before moving on.
   - **Email**: the mailbox that sends sign-in codes. Create it first in cPanel > Email Accounts (for example `no-reply@yourdomain.com`), then enter the server, port **465** and its password. Choosing "Set up email later" skips this, but then nobody can sign up until email works.
   - **Admin**: your name, email and password (12+ characters with upper and lower case and a number). Leave "Add the demo member" ticked if you want Arthur Pendelton's sample dashboard to show people. Leave "Testing only: show one-time codes on the screen" off for real members.
5. Press **Install now**. When it says **Your site is ready**, copy the cron command and the first reference code from that page.

The installer then locks itself and tries to delete itself. If `install.php` is still there (in `public_html`, or in `drinkwell/public`), delete it in File Manager.

## 8. Add the cron job (the clock)
cPanel > **Cron Jobs** > Common settings: **Once Per Minute** (`* * * * *`). Command: paste the line from the success page, which looks like:

```
/usr/local/bin/ea-php84 /home/youruser/drinkwell/artisan schedule:run >> /dev/null 2>&1
```

If you picked PHP 8.3, use `ea-php83`. This runs the daily sales and payouts.

## 9. Check it
Sign in as admin, open **Settings > System**. Every line should say **Good**. Anything marked **Check** or **Fix now** tells you what to do. The cron line turns Good a few minutes after step 8.

Then give members the sign-up link with the reference code. Make more codes in **Settings > Reference codes**.

## Updating the site later
No File Manager needed:
1. On GitHub, open **Actions** > **Build release zip** > **Run workflow**. After about 3 minutes the new version is on the **Releases** page.
2. On your site, sign in as admin > **Settings > System** > **Check for updates**.
3. When it says a new version is ready, enter the developer code and press **Install update**. It takes a minute or two; members see a "Back in a minute" page meanwhile.

The site downloads the right zip for your setup, checks its fingerprint, backs up every file it replaces, keeps your settings (`.env`), uploads and database, applies database changes and clears caches. If something goes wrong it puts the old files back. **Restore the previous version** on the same page undoes the last update.

If your host blocks the site from reaching GitHub, upload and extract the release zip by hand as in step 4, then press **Install updates** under "Database and caches".

## Connecting or changing email later
Sign in as admin > **Settings > Email** and enter the developer code `DW-EMAIL-1453` (it stays open for 30 minutes). Tick "Send email through this mailbox", enter the mail server, port (465, or 587), the email address and its password, and save. Then use **Send a test email** on the same page. Sign-in codes, password resets, pairing details and withdrawal notices all go out through this mailbox. The password is stored encrypted, and these details take the place of the MAIL_ lines in `.env`.

Where to find the details: cPanel > **Email Accounts** > **Connect Devices** next to the address.

## Email that reaches the inbox
cPanel > **Email Deliverability**: if SPF or DKIM shows a problem, press **Repair**. Without these, sign-in codes often land in spam.

## Changing a setting later
Settings live in `drinkwell/.env` (File Manager > show hidden files). After you edit it, open **Settings > System** and press **Install updates** so the site reads the new values.

## Backups
cPanel > **Backup** > download a MySQL database backup, or phpMyAdmin > Export. Also download `drinkwell/storage/app/private` (members' uploads) and your `.env`.

## Troubleshooting
- **Error 500 or a blank page**: open `drinkwell/storage/logs/laravel.log` in File Manager and look at the last lines. Check the `storage` and `bootstrap/cache` folders have permission 755.
- **Only the home page works**: the document root is not `drinkwell/public`, or the hidden `.htaccess` file in `public` is missing.
- **Page has no styles**: the zip was made without `npm run build` working. Run `make-release.bat` again and look for errors.
- **Cannot sign in, page just reloads**: the site is not on https yet (step 6), or `APP_URL` in `.env` does not start with `https://`.
- **Sign-in codes never arrive**: open Settings > Email and press Send a test email; the message shows what the mail server said. Try port 587 instead of 465, re-enter the password, then check Email Deliverability.
- **Machines never earn**: the cron job is missing or uses the wrong PHP path; Settings > System shows when it last ran.
- **"Already installed" on install.php**: setup finished before. To start over, delete `storage/app/installed.lock` and `.env`, and empty the database in phpMyAdmin.
