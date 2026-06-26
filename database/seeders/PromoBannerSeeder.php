<?php
// database/seeders/PromoBannerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromoBanner;
use Illuminate\Support\Facades\Log;

class PromoBannerSeeder extends Seeder
{
    public function run()
    {
        Log::info('📢 [PromoBannerSeeder] Running seeder...');

        $now = now();

        $banners = [
            [
                'badge_text' => '⚡ TODAY ONLY',
                'title' => 'Flash Sale — Up to 70% Off',
                'subtitle' => 'Grab the best deals before they\'re gone. Limited stock available.',
                'cta_text' => 'Shop Now',
                'cta_route' => 'all_products',
                'gradient_start' => '#FF4E50',
                'gradient_end' => '#F9A720',
                'accent_color' => '#FFD700',
                'target_screen' => 'home',
                'active' => true,
                'starts_at' => $now,
                'ends_at' => $now->copy()->addDays(7),
                'sort_order' => 0,
                'show_once_daily' => true,
            ],
            [
                'badge_text' => '🆕 NEW IN',
                'title' => 'Fresh Arrivals This Week',
                'subtitle' => 'Discover the latest products added to the store just for you.',
                'cta_text' => 'Explore',
                'cta_route' => 'all_products',
                'gradient_start' => '#4776E6',
                'gradient_end' => '#8E54E9',
                'accent_color' => '#B39DDB',
                'target_screen' => 'home',
                'active' => true,
                'starts_at' => $now,
                'ends_at' => $now->copy()->addDays(14),
                'sort_order' => 1,
                'show_once_daily' => true,
            ],
            [
                'badge_text' => '🎁 EARN REWARDS',
                'title' => 'Refer a Friend, Get ₦500',
                'subtitle' => 'Share GozakMart with friends and earn rewards on every signup.',
                'cta_text' => 'Refer Now',
                'cta_route' => 'referral',
                'gradient_start' => '#11998E',
                'gradient_end' => '#38EF7D',
                'accent_color' => '#00E676',
                'target_screen' => 'home',
                'active' => true,
                'starts_at' => $now,
                'ends_at' => $now->copy()->addDays(30),
                'sort_order' => 2,
                'show_once_daily' => true,
            ],
        ];

        foreach ($banners as $banner) {
            $created = PromoBanner::create($banner);
            Log::info('📢 [PromoBannerSeeder] Created banner: ' . $created->title);
        }

        Log::info('📢 [PromoBannerSeeder] Seeder completed');
    }
}
