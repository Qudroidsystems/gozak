# Product variants, photos/video & specs — 22 Oct 2026

## Deploy (Laravel — shop.gozakmart.ng)

```bash
php artisan migrate
php artisan db:seed --class=AttributeLibrarySeeder   # starter Colour/Size/Shoe Size/Storage… (safe to re-run)
php artisan optimize:clear
php artisan storage:link                              # only if /storage isn't linked yet
```

All migrations only **add** tables/columns; existing products keep working.

**Video uploads:** videos are capped at 60 s / 30 MB. Make sure PHP allows it
(php.ini, or cPanel › Select PHP Version › Options):

```
upload_max_filesize = 32M
post_max_size       = 40M
```

The Photos/video page shows the server's current limits and warns if they're too low.

**Order stock check (new):** checkout now refuses quantities above stock and
variable products with no option chosen. If your stock counts aren't up to
date yet, switch it off in `.env` until they are:

```
SHOP_ENFORCE_STOCK=false
```

## Deploy (Flutter app)

```bash
flutter pub get        # adds video_player
flutter analyze
flutter run            # test a variable product + a product with a video
```

## What's new

### Admin
- **Products › Attribute Library** (sidebar): set up Size, Colour (with swatch
  colours), Shoe Size… once.
- **Product form › Variations**:
  - "From library" fills an attribute's name + values.
  - "Fill all rows" bar: cost / price / sale price / add-stock for every variation.
  - Each variation shows its **current stock** and has an **Add stock** box
    (recorded in the stock ledger at the default location).
  - "Generate Variations" on an existing product now **keeps** existing rows
    (id, prices, image, stock) for combinations that still exist.
- **Product menu › "Photos, video & specs"** (also a button in the edit modal):
  - upload photos (auto-resized to WebP, with a small thumbnail), drag to reorder,
    tie photos to a colour/option
  - upload short videos (browser makes the poster frame; no ffmpeg needed)
  - Specifications list and Size guide (with templates)

### App
- Option picker: colour swatches / photo chips, size boxes, sold-out options
  greyed out, "Only N left", size-guide sheet.
- Picking a colour jumps the gallery to that colour's photos.
- Gallery: swipeable photos + videos, counter, thumbnails; tap for full-screen
  viewer with pinch/double-tap zoom and swipe-down to close.
- Videos only download when tapped (saves customers' data).
- Specifications table on the product page.
- Price row follows the chosen size/colour.

## Bugs fixed along the way
1. **Out-of-stock sizes could be ordered**: the app fell back to the whole
   product's stock when the chosen variation was sold out. The server didn't
   check stock either. Both are now fixed.
2. **Attribute values were split on spaces**: "Navy Blue, Wine Red" saved as
   four values. Values are now split on commas only.
3. **Attribute rows from the form were never saved**: the code read
   `$request->attributes` (Laravel's internal request bag) instead of the form
   input. The app only worked because the API rebuilt options from variations.
4. **Regenerating variations wiped their stock history**: rows lost their ids,
   so saving deleted and re-created every variation (cascading its stock rows).
5. **Bulk discount used cost price** instead of price for variations.
6. **Product page ignored the fetched product**: it kept showing the list copy,
   so detail-only data (and fresh stock) never showed.
7. Product detail and related-products API ran one stock query per variation;
   these now use the existing one-query stock preload.

## API additions (GET /api/products/{id})
Old keys are unchanged. New:
- `media[]`: `{id, type: image|video, url, thumb_url, poster_url, duration_seconds, attribute_name, attribute_value, alt, is_primary}`
- `has_video`
- `product_attributes[].display_type` (`color|size|image|button`), `.hint`,
  `.options[]` `{value, color_hex, image, in_stock}`
- `specifications[]` `{label, value, group}` and `size_guide` `{unit, columns, rows, note}` (detail endpoint only)
- `images[]` now lists photos only, in admin order (videos never appear there)

---

# Part 2: speed + security (same day)

## Backend speed
- **Product model**: removed the `retrieved` hook that re-added the stock ledger
  for every product loaded (1 extra query per product on every page and API
  call). `products.stock` is already kept in sync by `StockObserver` and the
  DB triggers.
- **Product model**: `$appends` is now empty. `current_stock`, `total_sold`,
  `revenue` and `margin` were added to every product's JSON, which cost about 3
  queries per product. Nothing read them from JSON. The accessors still work
  when used directly, and use pre-loaded values when present.
- **Admin product table**: was about 5 queries per row. Stock now comes from one
  subquery (`Product::withLedgerStock()`), and sales from the existing `withSum`.
- **API product detail / related**: one stock query instead of one per variation.
- New photos are saved as WebP (max 1600px, plus a 400px thumbnail); product
  thumbnails are 800px. Existing photos are unchanged; re-upload the heaviest ones.

## Server: turn on compression + image caching (Apache)
Add this at the **top** of `public/.htaccess` (keep the existing Laravel rules
below it). On cPanel hosting both modules are usually enabled.

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json text/html text/css application/javascript text/javascript image/svg+xml
</IfModule>
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/webp "access plus 30 days"
    ExpiresByType image/jpeg "access plus 30 days"
    ExpiresByType image/png  "access plus 30 days"
    ExpiresByType video/mp4  "access plus 30 days"
    ExpiresByType text/css   "access plus 7 days"
    ExpiresByType application/javascript "access plus 7 days"
</IfModule>
```

Also on the live server: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
after each deploy, and set `APP_DEBUG=false`.

## Security fix
`/api/fcm/send-test`, `/api/fcm/test-lightning-single`, `/api/fcm/test-lightning-multiple`
and `/api/fcm/test-connection` needed **no login**, so anyone could send push
notifications to your customers. They are now only registered when `APP_ENV=local`.
The logged-in `/api/fcm/*` routes the app uses are unchanged.

## Google Play: found so far
- `pubspec.yaml` has **geolocator** again (delivery map pin), which was removed in
  the earlier compliance pass. The app now asks for location, so the Play
  **Data safety** form must declare approximate/precise location ("App
  functionality", optional), and the app should only ask when the customer taps
  "use my location".
- The backend's legal pages are at **/privacy**, **/terms** and **/delete-account**.
  `PLAY_STORE_CHECKLIST.md` says the app links to `/privacy-policy` and
  `/account/delete`. If `lib/utils/constants/legal_links.dart` still uses those,
  they are 404s. Point them at the three URLs above, and use the same URLs in
  Play Console.

---

# Part 4: 23 Oct 2026

## Run after deploying
```bash
php artisan migrate        # tax_enabled, scan_shop_enabled, orders.channel
php artisan optimize:clear
```
App: `flutter pub get` (adds mobile_scanner), then `flutter analyze`.

## 1. Payment "initialisation failed"
- The server now returns the real reason in `message` (the app shows it), with a code:
  PAY-KEY (Paystack key missing/invalid), PAY-EMAIL, PAY-CALLBACK, PAY-NET, PAY-DB, PAY-GW (Paystack's own message), PAY-500.
- Uses the signed-in customer's email when the app sends none; refuses ₦0 orders clearly.
- Raw error details are only returned when APP_DEBUG=true.
- **To find the original cause**, on the live server: `grep -A3 "initializePayment: EXCEPTION" storage/logs/laravel.log | tail -20`

## 2. Stock now goes down when people buy (new: OrderStockService)
Before today **no order ever reduced stock**.
- Card/OPay orders: stock out when the payment succeeds.
- Pay on delivery / credit: stock out when the order is placed.
- Cancelled: stock goes back. Entries are referenced SALE-<order>-<item> / RTN-<order>-<item>, so nothing is double-counted.
- Orders placed before this deploy are not back-filled. Do a stock count after deploying.

## 3. Inventory: stock per size/colour
Inventory › Adjust Stock / Transfer Stock show an **Option** picker for products with options (required),
with each option's stock at the chosen location.

## 4. Product page: edit variations
Product › View: the variations table now shows **real** stock (it read a column removed in January, so it always showed 0)
and lets you edit SKU, cost, price, sale price and **Stock change** (+10 / -2) per row, at a chosen location.
Fixed: variation cost price was never saved (`cost_price` missing from ProductVariation::$fillable).

## 5. Tax on/off
Settings › Payment Gateways › Checkout rates: **Charge tax** switch next to the rate. Off = no tax on orders, the app hides the tax line
(older app versions get 0%), the saved rate is kept.

## 6. Delivery wording
Checkout shows "Couriers by GozakMart Delivery · provided on <marketplace>" (adds "and partners" when other couriers are listed).

## 7. Scan & Shop
- **Admin:** Inventory › Locations → edit a store → switch on **Scan & Shop store**. The ⋮ menu has **Print Scan & Shop QR** (poster for the entrance).
- **Barcodes:** uses the product barcode and each variation's barcode (or SKU), so give each size/colour its own barcode to skip the option picker.
- **App:** scan icon in the home app bar → pick the store or scan the poster → scan items → cart → checkout (banner says it's from that store) → pay → leave.
- **Orders:** `channel = scan_shop` and `stock_location_id = store`, shown in Orders as a green "Scan & Shop · <store>" badge (filter: `channel=scan_shop`).
  Staff pick from that store and deliver as usual. Stock is taken from that store.
- Scanned items skip the stock-count check (the customer is holding the item).
- Camera use was added to the privacy policy. Play Console: no extra declaration needed for camera.
- API: `GET /api/scan-shop/stores`, `GET /api/scan-shop/lookup?code=&store_id=`; `POST /api/orders` accepts `channel` + `store_id`.

## 8. App polish
- Product cards: ▶ badge for products with a video, "N colours" hint.
- Cart: uses the colour's gallery photo when a variation has no photo of its own.
