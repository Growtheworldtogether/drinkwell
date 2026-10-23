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
cPanel > **File Manager**, open your home folder (the one that holds `public_html`, not inside it).
1. Create a folder `drinkwell`.
2. Open it, **Upload** `drinkwell-release.zip`.
3. Right-click the zip > **Extract** into `/home/youruser/drinkwell`. Then delete the zip.

You should now see `app`, `public`, `vendor`, `artisan` and so on inside `drinkwell`.

## 5. Point the domain at the public folder
- Subdomain or addon domain: cPanel > **Domains** > Manage > set the **Document Root** to `drinkwell/public`.
- Main domain: if cPanel does not let you change its document root, ask your host's support: "Please set the document root of yourdomain.com to /home/youruser/drinkwell/public". It is a routine request.

## 6. Turn on HTTPS
cPanel > **SSL/TLS Status** > select the domain > **Run AutoSSL**. Wait until it shows a green lock. Sign-in only works over https.

## 7. Run the setup wizard
1. Open `https://yourdomain.com/install.php`.
2. The first page checks your server. Fix anything red (usually the PHP version or an extension from step 2), then reload.
3. It asks for a setup key. In File Manager open `drinkwell/storage/app/install-key.txt` and copy the code into the page. This makes sure only you can run the setup.
4. Fill in the pages:
   - **Site**: name, `https://yourdomain.com`, support email, time zone.
   - **Database**: host `localhost`, and the name, user and password from step 3. It tests the connection before moving on.
   - **Email**: the mailbox that sends sign-in codes. Create it first in cPanel > Email Accounts (for example `no-reply@yourdomain.com`), then enter the server, port **465** and its password. Choosing "Set up email later" skips this, but then nobody can sign up until email works.
   - **Admin**: your name, email and password (12+ characters with upper and lower case and a number). Leave "Add the demo member" ticked if you want Arthur Pendelton's sample dashboard to show people. Leave "Testing only: show one-time codes on the screen" off for real members.
5. Press **Install now**. When it says **Your site is ready**, copy the cron command and the first reference code from that page.

The installer then locks itself and tries to delete itself. If `public/install.php` is still there, delete it in File Manager.

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
1. On your PC, run `scripts\make-release.bat` again.
2. Upload the new zip into `drinkwell` and extract it, choosing to overwrite. Your `.env`, database and uploaded files are not touched (they are not in the zip).
3. Delete `public/install.php` if it came back. It is locked anyway after the first install.
4. Sign in as admin > **Settings > System** > **Install updates**. This applies database changes and clears old caches.

## Connecting or changing email later
Sign in as admin > **Settings > Email**. Tick "Send email through this mailbox", enter the mail server, port (465, or 587), the email address and its password, and save. Then use **Send a test email** on the same page. Sign-in codes, password resets, pairing details and withdrawal notices all go out through this mailbox. The password is stored encrypted, and these details take the place of the MAIL_ lines in `.env`.

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
