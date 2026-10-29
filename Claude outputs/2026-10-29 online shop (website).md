# GozakMart online shop: shop.gozakmart.ng (29 Oct 2026)

Customers can now browse, buy, pay and track orders on the website, just like in the app. It uses the same products, prices, deals, stock, delivery couriers and Paystack/OPay as the app. Website and app accounts are the same, so orders and addresses show in both.

The admin panel is unchanged: staff still sign in at **/login** and work from **/dashboard**. Customers sign in at **/account/login**, which is a separate login, so a customer signing in never gets into the admin panel.

## Pages
| Page | Address |
|---|---|
| Home (banners, category menu, Flash Sales with countdown, Top selling / New / Trending / On sale, brands) | `/` |
| Category, with filters (price, in stock, discount) and sorting | `/c/{id}/{slug}` |
| Brand | `/brand/{id}/{slug}` |
| Search | `/search?q=` |
| Flash Sales (lightning deals) | `/deals` |
| Product: gallery + video + zoom, options (size/colour with stock), deal price, specifications, size guide, reviews, related products | `/p/{id}/{slug}` |
| Cart | `/cart` |
| Checkout: address (map pin), delivery options (GozakMart Delivery and couriers on Logistics), Paystack / OPay | `/checkout` |
| Sign in / Create account (email verification, same as the app) | `/account/login`, `/account/register` |
| My orders + tracking, Pay now for unpaid orders, "I've received my order" | `/account/orders` |
| Saved items | `/account/saved` |
| Addresses | `/account/addresses` |
| Help | `/help` |

## Deploy steps
1. Upload the changed files (listed below).
2. Run `php artisan migrate`. This creates the `wishlist_items` table for saved items.
3. Run `php artisan optimize:clear`.
4. Point **shop.gozakmart.ng** at the same Laravel app (same document root, `public/`). If the admin panel also lives on that domain, nothing else is needed. The shop is at `/`, the admin at `/login` and `/dashboard`.
5. Paystack: no dashboard change is needed. Website payments come back through the existing `/payment-callback`, and the site then sends the shopper to their order page.

## Test checklist (after deploy)
- [ ] Home page loads, with banners and Flash Sales.
- [ ] Open a product that has sizes. Picking an out-of-stock size disables Add to cart.
- [ ] Add to cart from a product page and from a category page. The cart badge updates.
- [ ] Create an account. The verify email arrives. Sign in.
- [ ] Add an address with a map pin. At checkout, the delivery options load and show the price.
- [ ] Pay with Paystack (test mode). You land on the order page, which shows "Paid".
- [ ] Pay with OPay. Same result.
- [ ] The same order shows in the mobile app under the same account.
- [ ] Admin: /login and /dashboard still work. A customer account cannot open /dashboard.

## Files
New:
- `app/Http/Controllers/Storefront/`: ShopController, CartController, CheckoutController, AccountController, AuthController.
- `app/Services/Storefront/`: StoreCatalog, CartService.
- `app/Http/Middleware/`: UseCustomerGuard, StoreAuthenticate.
- `resources/views/store/`: 16 pages.
- `public/store/`: store.css, store.js.
- `database/migrations/2026_10_29_090000_create_wishlist_items_table.php`.

Changed:
- `routes/web.php`: the shop routes replace the old welcome page on `/`.
- `config/auth.php`: adds the `customer` login guard.
- `bootstrap/app.php`: adds the `store` and `store.auth` middleware.
- `app/Http/Controllers/PaymentController.php`: OPay return sends web shoppers to their order.

Backups of the four changed files: `Claude outputs/*.bak-2026-10-29-shop`.
