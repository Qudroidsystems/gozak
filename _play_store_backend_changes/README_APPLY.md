# Gozak Mart backend — Play Store compliance changes (NOT applied to your project)

Nothing in your existing backend was modified. Everything here is new; copy it in when you are ready.

## 1. Pages Google and the app need
1. Copy `resources/views/legal/*` into `resources/views/legal/` of the backend.
2. Paste `routes_web_snippet.php` into `routes/web.php` (public, outside auth groups).
3. Set a REAL support email (SUPPORT_EMAIL) — the pages default to support@gozakmart.ng; make sure that mailbox exists.
4. Deploy, then open /privacy, /terms, /delete-account in a browser on https://gozakmart.ng (or shop.gozakmart.ng, whichever you use in the app).
5. Have the wording checked against how Gozak Credit really works (fees, late fee, BVN use) — it is a draft.
6. Play Console: Privacy policy URL = /privacy; Data safety > Data deletion URL = /delete-account.
7. Remove/replace the old template pages: /api/privacy-policy and /api/user-data-safety (the latter says "Houzi Real Estate").

## 2. Deep links
`public/.well-known/assetlinks.json` — fill in the SHA-256 of the Play App Signing key (and upload key if you want). It must be reachable at
https://gozakmart.ng/.well-known/assetlinks.json AND https://shop.gozakmart.ng/.well-known/assetlinks.json (same file, `application/json`, no redirect). Laravel's web server must serve dotfile folders (nginx: do not `deny all` for /.well-known).

## 3. Account deletion safeguard
See `APIUserController_destroy_suggested.php` — blocks deletion while Gozak Credit is owed; stops leaking exception messages.
Decide your retention rule for orders/payments (currently everything cascade-deletes) and make /privacy §5 and /delete-account match it.

## 4. Do these on the server / repo (I did not touch them)
- Rotate: PAYSTACK_SECRET_KEY, PUSHER_APP_SECRET, GOOGLE_CLIENT_SECRET, and the Firebase service-account keys; `.env` is TRACKED in git:
  `git rm --cached .env` then purge history (git filter-repo / BFG) and force-push; keep .env in .gitignore.
- Production `.env`: APP_ENV=production, APP_DEBUG=false, LOG_LEVEL=warning, APP_URL=https://shop.gozakmart.ng, real MAIL_MAILER.
- Delete from the web root: `public/storage.zip` (82 MB), `public/storageaaaa/`.
- Delete `resources/views/privacy/eliab cv.*` and `~$iab cv.html`.
- FIREBASE_PROJECT_ID / service-account.json must be the SAME Firebase project as the app's google-services.json
  (app = gozakcommerce, backend = eshop-556c3, your notes say shopgozak-63159). Three storage/app/firebase/service-account*.json files exist — keep only the right one.
- App text inconsistency: the Refund-bank screen says "we never ask for your … BVN" but Gozak Credit asks for BVN. Reword that screen.
- Credit: complete Play Console's Financial features declaration; confirm whether CBN/FCCPC digital-lending rules apply to Gozak Credit.
