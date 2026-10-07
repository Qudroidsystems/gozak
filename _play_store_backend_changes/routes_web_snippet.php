<?php
/*
 * ADD to routes/web.php — OUTSIDE any auth / role middleware group.
 * Public URLs used by the app and Play Console:
 *   https://gozakmart.ng/privacy            (or shop.gozakmart.ng)
 *   https://gozakmart.ng/terms
 *   https://gozakmart.ng/delete-account     <- paste this one into Play Console > Data safety > Data deletion
 */
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/delete-account', 'legal.delete-account')->name('legal.delete-account');

// Optional: set once in config/app.php  =>  'support_email' => env('SUPPORT_EMAIL', 'support@gozakmart.ng'),
// and SUPPORT_EMAIL=your-real-address in .env (otherwise the pages show support@gozakmart.ng).
