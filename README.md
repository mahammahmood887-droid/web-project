# Terra Verde

PHP + MySQL project with sign-up / sign-in / sign-out, a members' home page (customer side) and an admin side.
The shopping bag is kept in the browser (localStorage), but when a signed-in customer clicks **Place order** the order is saved
in the database (`orders` and `order_items`).

## Structure

    terra-verde/
    ├── public/              web root (the only folder the web server serves)
    │   ├── index.php        shop page, bag page, newsletter sign-up (POST action=subscribe),
    │   │                    saving orders (POST action=place_order)
    │   ├── login.php        sign in (wrong passwords limited per email + IP)
    │   ├── register.php     create an account (bcrypt-hashed password)
    │   ├── home.php         customer page after sign-in
    │   ├── auth.php         guards (require_login, require_customer, require_admin, guest_only, login_user)
    │   │                    and, when opened directly, the admin side (admins only)
    │   ├── logout.php       ends the session
    │   ├── db-test.php      checks the database connection (delete before going online)
    │   ├── script.js        form checks for login/register, the bag, placing orders, shop page and extra features
    │   └── styles.css       all styles
    ├── config/
    │   └── config.php       database settings, session, helpers
    ├── storage/
    │   └── schema.sql       creates the database, tables, DB user and seed products
    └── README.md

## Run it (Ubuntu / WSL)

    sudo apt install php-cli php-mysql php-mbstring mysql-server     # once
    sudo service mysql start
    cd ~/terra-verde
    sudo mysql < storage/schema.sql                                  # once
    php -S localhost:8000 -t public

Open http://localhost:8000. Check the database at http://localhost:8000/db-test.php.
Errors are printed in the terminal where `php -S` is running.

With Apache or Nginx, point the document root to the `public` folder of this project.

## Settings

`config/config.php` reads `TV_DB_HOST`, `TV_DB_NAME`, `TV_DB_USER`, `TV_DB_PASS`.

## Roles: customer and admin

`users.role` is `customer` (default) or `admin`. Admins cannot register themselves. After signing up, make the first admin:

    sudo mysql terra_verde -e "UPDATE users SET role='admin' WHERE email='you@example.com';"

Sign in again and you land on `auth.php` (admin side: dashboard, products, users, newsletter). Admins can later promote other users from the Users tab.

## Look at the database

    sudo mysql terra_verde -e "SELECT id, full_name, email, skin_concern, role, created_at FROM users;"
    sudo mysql terra_verde -e "SELECT * FROM newsletter_subscribers;"
    sudo mysql terra_verde -e "SELECT * FROM login_attempts;"
    sudo mysql -e "DROP DATABASE terra_verde;" && sudo mysql < storage/schema.sql    # start again from scratch

Tables: `users`, `login_attempts`, `newsletter_subscribers`, `farms`, `products`, `orders`, `order_items`.

## Orders

Clicking **Place order** on the bag page sends the bag to `index.php` (POST `action=place_order`). The server checks that the user
is a signed-in customer and that the CSRF token is valid, then looks up the **current price of every product in the database**
(prices sent by the browser are ignored) and saves one row in `orders` plus one row per product in `order_items`, all in a
single transaction. Product name and unit price are copied into `order_items`, so old orders stay correct if a product is
renamed, repriced or deleted. Quantity is limited to 20 per product and hidden products cannot be ordered.

    sudo mysql terra_verde -e "SELECT o.id, u.full_name, u.email, o.total, o.created_at FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC;"
    sudo mysql terra_verde -e "SELECT * FROM order_items WHERE order_id=1;"


## Front-end features (saved in the browser only, except orders)

Search, sort and saved filter; wishlist hearts; quick view; "Your match" badge; free-sample progress bar on the bag page
(`SAMPLE_AT = 60` in `public/index.php`); order again; next shipment countdown; ritual tip of the day.
The "Order again" list is a browser-only copy of your last 5 orders; the full order history lives in the database.
Signing out clears the bag, saved items and that browser copy (not the orders saved in the database).