<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\LightningDeal;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StoreSetting;
use App\Services\CategoryTreeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Read-side helpers for the shopping website: product queries for listings,
 * card data (price, discount, live deal price, rating), categories, settings.
 * Prices shown here are for display; every order is re-priced on the server
 * by OrderPricingService, exactly like app orders.
 */
class StoreCatalog
{
    /** Products customers may see. */
    public function query(): Builder
    {
        return Product::query()
            ->where(fn ($q) => $q->where('products.is_active', true)->orWhereNull('products.is_active'))
            ->select([
                'products.id', 'products.title', 'products.thumbnail', 'products.price', 'products.sale_price',
                'products.stock', 'products.product_type', 'products.category_id', 'products.brand_id',
                'products.sold_quantity', 'products.is_featured', 'products.is_new', 'products.is_trending',
                'products.created_at',
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');
    }

    /** Products in a category or any of its sub-categories (direct column or pivot). */
    public function inCategory(Builder $q, int $categoryId): Builder
    {
        $ids = array_merge([$categoryId], app(CategoryTreeService::class)->descendantIds($categoryId));
        return $q->where(function ($w) use ($ids) {
            $w->whereIn('products.category_id', $ids)
              ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        });
    }

    public function search(Builder $q, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        return $q->where(function ($w) use ($like) {
            $w->where('products.title', 'like', $like)
              ->orWhere('products.sku', 'like', $like)
              ->orWhere('products.description', 'like', $like)
              ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
              ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
        });
    }

    /** Apply ?sort= from the listing pages. */
    public function sort(Builder $q, ?string $sort): Builder
    {
        $price = 'COALESCE(NULLIF(products.sale_price, 0), products.price)';
        return match ($sort) {
            'price_asc'  => $q->orderByRaw("$price asc"),
            'price_desc' => $q->orderByRaw("$price desc"),
            'newest'     => $q->orderByDesc('products.created_at'),
            'rating'     => $q->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'discount'   => $q->orderByRaw('CASE WHEN products.sale_price > 0 AND products.sale_price < products.price THEN (products.price - products.sale_price) / products.price ELSE 0 END DESC'),
            default      => $q->orderByDesc('products.sold_quantity')->orderByDesc('products.id'),   // popularity
        };
    }

    /**
     * Turns products into the array the product-card partial expects.
     * One query for all live deals on the page.
     */
    public function cards(iterable $products): Collection
    {
        $products = collect($products);
        $deals = $this->liveDeals($products->pluck('id'));

        return $products->map(function (Product $p) use ($deals) {
            $price = (float) $p->price;
            $sale  = ((float) $p->sale_price > 0 && (float) $p->sale_price < $price) ? (float) $p->sale_price : null;
            $deal  = $deals[$p->id] ?? null;
            if ($deal) {
                $dealPrice = round($price * (1 - ((int) $deal->discount_percentage) / 100), 2);
                if ($dealPrice > 0 && ($sale === null || $dealPrice < $sale)) {
                    $sale = $dealPrice;
                }
            }
            $now = $sale ?? $price;

            return [
                'id'        => $p->id,
                'title'     => $p->title,
                'url'       => $this->productUrl($p),
                'image'     => ProductImage::publicUrl($p->thumbnail),
                'price'     => $now,
                'old_price' => $sale !== null ? $price : null,
                'discount'  => $sale !== null && $price > 0 ? (int) round(($price - $sale) / $price * 100) : null,
                'rating'    => round((float) ($p->reviews_avg_rating ?? 0), 1),
                'reviews'   => (int) ($p->reviews_count ?? 0),
                'in_stock'  => (int) $p->stock > 0,
                'variable'  => $p->product_type === 'variable',
                'deal'      => (bool) $deal,
                'deal_ends' => $deal?->endsAtLagos()?->toIso8601String(),
            ];
        });
    }

    /** product_id => LightningDeal for deals running now. */
    public function liveDeals($productIds): array
    {
        $ids = collect($productIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }
        try {
            return LightningDeal::live()->whereIn('product_id', $ids)->get()->keyBy('product_id')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function productUrl(Product $p): string
    {
        return route('store.product', ['id' => $p->id, 'slug' => Str::slug($p->title) ?: 'item']);
    }

    public function categoryUrl(Category $c): string
    {
        return route('store.category', ['id' => $c->id, 'slug' => Str::slug($c->name) ?: 'all']);
    }

    /** Top-level categories with their children, for the header / left menu (cached 10 min). */
    public function menuCategories(): Collection
    {
        return Cache::remember('store_menu_categories_v1', now()->addMinutes(10), function () {
            try {
                return Category::query()
                    ->whereNull('parent_id')
                    ->where(fn ($q) => $q->where('is_nsfw', false)->orWhereNull('is_nsfw'))
                    ->with(['children' => fn ($q) => $q->orderBy('name')])
                    ->orderByDesc('is_featured')->orderBy('name')
                    ->get(['id', 'name', 'image', 'parent_id', 'is_featured'])
                    ->map(fn ($c) => [
                        'id'       => $c->id,
                        'name'     => $c->name,
                        'url'      => $this->categoryUrl($c),
                        'image'    => ProductImage::publicUrl($c->image),
                        'children' => $c->children->map(fn ($ch) => [
                            'id' => $ch->id, 'name' => $ch->name, 'url' => $this->categoryUrl($ch),
                        ])->values()->all(),
                    ]);
            } catch (\Throwable $e) {
                return collect();
            }
        });
    }

    public function settings(): array
    {
        return Cache::remember('store_web_settings_v1', now()->addMinutes(10), function () {
            $s = null;
            try {
                $s = StoreSetting::getSettings();
            } catch (\Throwable $e) {
            }
            return [
                'name'    => $s->store_name ?? config('app.name', 'GozakMart'),
                'logo'    => $s && $s->logo ? ProductImage::publicUrl($s->logo) : null,
                'phone'   => $s->phone ?? null,
                'email'   => $s->email ?? null,
                'address' => $s->address ?? null,
                'motto'   => $s->motto ?? null,
                'app_url' => 'https://play.google.com/store/apps/details?id=ng.gozakmart.shop',
            ];
        });
    }
}
