<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Smarter search & recommendations for the app (Jumia / Temu style).
 *
 *  GET /api/search/suggest?q=iph          live suggestions while typing
 *  GET /api/search?q=…&page=&sort=…       ranked, paginated results + facets
 *  GET /api/search/trending               what people search for (last 14 days)
 *  GET /api/products/recommendations      "You might like" (store screen)
 *
 * Re-uses APIProductController's product formatting so cards look the same.
 */
class APISearchController extends APIProductController
{
    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /** Lower-case words of at least 2 chars, max 6. */
    protected function terms(string $q): array
    {
        $q = mb_strtolower(trim(preg_replace('/\s+/', ' ', $q)));
        $words = array_values(array_filter(explode(' ', $q), fn ($w) => mb_strlen($w) >= 2));
        return array_slice(array_unique($words), 0, 6);
    }

    /** Escape LIKE wildcards. */
    protected function like(string $s): string
    {
        return '%' . addcslashes($s, '%_\\') . '%';
    }

    /** Only sellable products (active, and hide NSFW in safe mode). */
    protected function visible($query, Request $request)
    {
        static $hasActive = null, $hasNsfw = null;
        $hasActive ??= Schema::hasColumn('products', 'is_active');
        $hasNsfw   ??= Schema::hasColumn('products', 'is_nsfw');

        if ($hasActive) {
            $query->where(fn ($q) => $q->where('products.is_active', 1)->orWhereNull('products.is_active'));
        }
        if ($hasNsfw && $request->input('safe_mode') === 'true') {
            $query->where(fn ($q) => $q->where('products.is_nsfw', 0)->orWhereNull('products.is_nsfw'));
        }
        return $query->where(fn ($q) => $q->whereNull('products.product_type')->orWhere('products.product_type', '!=', 'variation'));
    }

    /** Products matching every word in title, description, brand or category. */
    protected function matchAll($query, array $terms, bool $any = false)
    {
        $method = $any ? 'orWhere' : 'where';
        return $query->where(function ($outer) use ($terms, $method) {
            foreach ($terms as $t) {
                $like = $this->like($t);
                $outer->{$method}(function ($q) use ($like) {
                    $q->where('products.title', 'like', $like)
                      ->orWhere('products.sku', 'like', $like)
                      ->orWhere('products.description', 'like', $like)
                      ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                      ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
                });
            }
        });
    }

    /** SQL relevance score: exact > starts with > title words > brand/category > description. */
    protected function relevanceSql(string $q, array $terms): array
    {
        $parts    = ['(CASE WHEN LOWER(products.title) = ? THEN 100 ELSE 0 END)', '(CASE WHEN LOWER(products.title) LIKE ? THEN 40 ELSE 0 END)'];
        $bindings = [mb_strtolower($q), addcslashes(mb_strtolower($q), '%_\\') . '%'];
        foreach ($terms as $t) {
            $parts[] = '(CASE WHEN LOWER(products.title) LIKE ? THEN 15 ELSE 0 END)';
            $bindings[] = $this->like($t);
            $parts[] = '(CASE WHEN LOWER(products.title) LIKE ? THEN 8 ELSE 0 END)';
            $bindings[] = addcslashes($t, '%_\\') . '%';
        }
        // Popularity nudges ties.
        $parts[] = 'LEAST(COALESCE(products.sold_quantity,0), 200) / 20';
        return ['(' . implode(' + ', $parts) . ')', $bindings];
    }

    protected function thumb(?string $path): ?string
    {
        return $path ? url(Storage::url(preg_replace('/^storage\//', '', $path))) : null;
    }

    protected function logSearch(Request $request, string $q, int $count): void
    {
        try {
            if (mb_strlen($q) < 2 || !Schema::hasTable('search_logs')) return;
            DB::table('search_logs')->insert([
                'query'         => mb_substr($q, 0, 120),
                'normalized'    => mb_substr(implode(' ', $this->terms($q)), 0, 120),
                'user_id'       => optional($request->user('sanctum'))->id,
                'results_count' => $count,
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // never break search for logging
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/search/suggest?q=
    // ─────────────────────────────────────────────────────────────────────
    public function suggest(Request $request)
    {
        $q     = trim((string) $request->input('q', ''));
        $terms = $this->terms($q);
        if (!$terms) {
            return response()->json(['success' => true, 'data' => ['keywords' => [], 'products' => [], 'categories' => [], 'brands' => []]]);
        }

        [$rel, $bind] = $this->relevanceSql($q, $terms);
        $productQuery = $this->visible(Product::query(), $request);
        $this->matchAll($productQuery, $terms);
        $products = $productQuery
            ->select('products.id', 'products.title', 'products.price', 'products.sale_price', 'products.thumbnail')
            ->selectRaw("$rel as relevance", $bind)
            ->orderByDesc('relevance')
            ->take(6)
            ->get();

        // Keyword completions from matching product titles (Temu-style).
        $keywords = $this->visible(Product::query(), $request)
            ->where('products.title', 'like', $this->like($terms[0]))
            ->orderByDesc('sold_quantity')
            ->take(40)
            ->pluck('title')
            ->map(function ($title) use ($q) {
                $t = mb_strtolower($title);
                $pos = mb_strpos($t, mb_strtolower($q));
                if ($pos === false) {
                    return null;
                }
                // Keep the matched phrase + up to two following words.
                $rest  = mb_substr($t, $pos);
                $words = preg_split('/\s+/', $rest);
                return trim(implode(' ', array_slice($words, 0, max(2, count(explode(' ', $q)) + 2))), " ,.-/");
            })
            ->filter()->unique()->take(6)->values();

        $categories = Category::where('name', 'like', $this->like($q))->take(4)->get(['id', 'name', 'image']);
        $brands     = Brand::where('name', 'like', $this->like($q))->take(4)->get(['id', 'name', 'logo']);

        return response()->json(['success' => true, 'data' => [
            'keywords'   => $keywords,
            'products'   => $products->map(fn ($p) => [
                'id'         => $p->id,
                'title'      => $p->title,
                'price'      => (float) $p->price,
                'sale_price' => $p->sale_price > 0 ? (float) $p->sale_price : null,
                'thumbnail'  => $this->thumb($p->thumbnail),
            ])->values(),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'brands'     => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'logo' => $this->thumb($b->logo)])->values(),
        ]]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/search?q=&page=&per_page=&sort=&category_id=&brand_id=
    //                &min_price=&max_price=&on_sale=1&in_stock=1
    // sort: relevance (default) | price_asc | price_desc | newest | popular | rating
    // ─────────────────────────────────────────────────────────────────────
    public function search(Request $request)
    {
        try {
            $q       = trim((string) $request->input('q', ''));
            $terms   = $this->terms($q);
            $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
            $sort    = (string) $request->input('sort', 'relevance');

            $build = function (bool $any) use ($request, $terms) {
                $query = $this->visible($this->baseQuery(), $request)->select('products.*');
                if ($terms) {
                    $this->matchAll($query, $terms, $any);
                }
                if ($request->filled('category_id')) {
                    $this->applyCategoryFilter($query, $request->category_id);
                }
                if ($request->filled('brand_id'))  $query->where('products.brand_id', $request->brand_id);
                if ($request->filled('min_price')) $query->whereRaw('COALESCE(NULLIF(products.sale_price,0), products.price) >= ?', [(float) $request->min_price]);
                if ($request->filled('max_price')) $query->whereRaw('COALESCE(NULLIF(products.sale_price,0), products.price) <= ?', [(float) $request->max_price]);
                if ($request->boolean('on_sale'))  $query->where('products.sale_price', '>', 0)->whereColumn('products.sale_price', '<', 'products.price');
                return $query;
            };

            $query   = $build(false);
            $relaxed = false;
            // No exact match for every word → fall back to "any word" (typo / extra-word tolerant).
            if ($terms && count($terms) > 1 && !(clone $query)->exists()) {
                $query   = $build(true);
                $relaxed = true;
            }

            // Facets (from the whole result set, before paging)
            $ids = (clone $query)->reorder()->limit(1000)->pluck('products.id');
            $facets = [
                'categories' => Category::whereIn('id', Product::whereIn('id', $ids)->pluck('category_id')->filter()->unique())
                    ->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'brands'     => Brand::whereIn('id', Product::whereIn('id', $ids)->pluck('brand_id')->filter()->unique())
                    ->get(['id', 'name'])->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                'price'      => [
                    'min' => (float) (Product::whereIn('id', $ids)->selectRaw('MIN(COALESCE(NULLIF(sale_price,0), price)) as v')->value('v') ?? 0),
                    'max' => (float) (Product::whereIn('id', $ids)->selectRaw('MAX(COALESCE(NULLIF(sale_price,0), price)) as v')->value('v') ?? 0),
                ],
            ];

            switch ($sort) {
                case 'price_asc':  $query->orderByRaw('COALESCE(NULLIF(products.sale_price,0), products.price) asc'); break;
                case 'price_desc': $query->orderByRaw('COALESCE(NULLIF(products.sale_price,0), products.price) desc'); break;
                case 'newest':     $query->orderByDesc('products.created_at'); break;
                case 'popular':    $query->orderByDesc('products.sold_quantity'); break;
                case 'rating':     $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'); break;
                default:
                    if ($terms) {
                        [$rel, $bind] = $this->relevanceSql($q, $terms);
                        $query->orderByRaw("$rel desc", $bind);
                    } else {
                        $query->orderByDesc('products.sold_quantity');
                    }
            }
            $query->orderByDesc('products.id');

            $page = $query->paginate($perPage);
            $items = collect($page->items());
            $this->preloadStock($items->pluck('id'));
            $data = $items->map(fn ($p) => $this->formatProductData($p));

            if ($request->boolean('in_stock')) {
                $data = $data->filter(fn ($p) => ($p['stock'] ?? 0) > 0);
            }

            if ((int) $page->currentPage() === 1 && $q !== '') {
                $this->logSearch($request, $q, $page->total());
            }

            return response()->json([
                'success'    => true,
                'query'      => $q,
                'relaxed'    => $relaxed,
                'data'       => $data->values(),
                'facets'     => $facets,
                'pagination' => [
                    'current_page' => $page->currentPage(),
                    'last_page'    => $page->lastPage(),
                    'total'        => $page->total(),
                    'per_page'     => $page->perPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Search failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Search failed. Please try again.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/search/trending
    // ─────────────────────────────────────────────────────────────────────
    public function trending()
    {
        $terms = Cache::remember('search_trending_v1', 600, function () {
            $out = collect();
            if (Schema::hasTable('search_logs')) {
                $out = DB::table('search_logs')
                    ->where('created_at', '>=', now()->subDays(14))
                    ->where('results_count', '>', 0)
                    ->select('normalized', DB::raw('COUNT(*) as hits'), DB::raw('MAX(query) as sample'))
                    ->groupBy('normalized')
                    ->orderByDesc('hits')
                    ->take(10)
                    ->get()
                    ->map(fn ($r) => $r->normalized ?: $r->sample);
            }
            // Not enough searches yet → use best-selling product names / categories.
            if ($out->count() < 6) {
                $extra = Product::where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', '!=', 'variation'))
                    ->orderByDesc('sold_quantity')->take(10)->pluck('title')
                    ->map(fn ($t) => mb_strtolower(implode(' ', array_slice(preg_split('/\s+/', $t), 0, 3))));
                $out = $out->concat($extra)->unique()->take(10);
            }
            return $out->values();
        });

        return response()->json(['success' => true, 'data' => $terms]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/products/recommendations?category_id=&limit=20&viewed=1,2,3&seed=
    // "You might like": ranks products (in the category and its sub-categories
    // when given) by the shopper's own signals — brands & categories they
    // bought or viewed — plus best-sellers, ratings, deals and freshness, with
    // a little seeded randomness so the list feels alive.
    // Each product gets a short `rec_reason` the app shows as a tag.
    // ─────────────────────────────────────────────────────────────────────
    public function recommendations(Request $request)
    {
        try {
            $limit = min(max((int) $request->input('limit', 20), 1), 50);
            $seed  = (int) $request->input('seed', crc32(now()->toDateString()));
            $user  = $request->user('sanctum');

            // Shopper signals
            $viewed = collect(explode(',', (string) $request->input('viewed', '')))
                ->map(fn ($v) => (int) $v)->filter()->take(30);
            $boughtIds = collect();
            if ($user) {
                $boughtIds = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.user_id', $user->id)
                    ->pluck('order_items.product_id')->unique()->take(100);
            }
            $signalProducts = Product::whereIn('id', $viewed->merge($boughtIds)->unique())->get(['id', 'brand_id', 'category_id']);
            $likedBrands = $signalProducts->pluck('brand_id')->filter()->countBy();
            $likedCats   = $signalProducts->pluck('category_id')->filter()->countBy();

            // Candidates
            $query = $this->visible($this->baseQuery(), $request)->select('products.*');
            if ($request->filled('category_id')) {
                $catIds = Category::where('parent_id', $request->category_id)->pluck('id')->push((int) $request->category_id);
                $query->where(function ($q) use ($catIds) {
                    $q->whereIn('products.category_id', $catIds)
                      ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $catIds));
                });
            }
            if ($viewed->isNotEmpty()) {
                $query->whereNotIn('products.id', $viewed->take(5)); // don't echo what they just looked at
            }
            $candidates = $query->orderByDesc('products.sold_quantity')->take(200)->get();
            $this->preloadStock($candidates->pluck('id'));

            $now = now();
            $scored = $candidates->map(function ($p) use ($likedBrands, $likedCats, $boughtIds, $seed, $now) {
                $sold    = (int) ($p->sold_quantity ?? 0);
                $rating  = (float) ($p->reviews_avg_rating ?? 0);
                $reviews = (int) ($p->reviews_count ?? 0);
                $onSale  = $p->sale_price > 0 && $p->sale_price < $p->price;
                $stock   = $this->calculateProductStock($p->id);

                $score = log(1 + $sold) * 3
                    + $rating * min($reviews, 20) / 20 * 2
                    + ($onSale ? 2 : 0)
                    + ($p->is_trending ? 1.5 : 0)
                    + ($p->is_new ? 1 : 0)
                    + ($p->created_at && $p->created_at->gt($now->copy()->subDays(30)) ? 1 : 0)
                    + ($p->brand_id && isset($likedBrands[$p->brand_id]) ? 2.5 + min($likedBrands[$p->brand_id], 3) * 0.3 : 0)
                    + ($p->category_id && isset($likedCats[$p->category_id]) ? 1.5 : 0)
                    - ($boughtIds->contains($p->id) ? 1.5 : 0)
                    - ($stock <= 0 ? 6 : 0)
                    + (crc32($seed . ':' . $p->id) % 1000) / 1000 * 1.5;

                // Short, honest reason tag
                $reason = null;
                if ($p->brand_id && isset($likedBrands[$p->brand_id]) && $p->brand) {
                    $reason = 'More from ' . $p->brand->name;
                } elseif ($onSale) {
                    $pct = (int) round((1 - $p->sale_price / max(0.01, $p->price)) * 100);
                    $reason = $pct >= 5 ? "{$pct}% off" : 'On sale';
                } elseif ($sold >= 20) {
                    $reason = 'Best seller';
                } elseif ($rating >= 4.3 && $reviews >= 3) {
                    $reason = 'Top rated';
                } elseif ($p->is_trending) {
                    $reason = 'Trending';
                } elseif ($p->created_at && $p->created_at->gt($now->copy()->subDays(30))) {
                    $reason = 'New arrival';
                }

                return ['p' => $p, 'score' => $score, 'reason' => $reason];
            })->sortByDesc('score')->take($limit)->values();

            $data = $scored->map(function ($row) {
                $out = $this->formatProductData($row['p']);
                $out['rec_reason'] = $row['reason'];
                return $out;
            });

            return response()->json(['success' => true, 'data' => $data, 'personalized' => $likedBrands->isNotEmpty() || $likedCats->isNotEmpty()]);
        } catch (\Throwable $e) {
            Log::error('Recommendations failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'data' => [], 'message' => 'Could not load recommendations'], 500);
        }
    }
}
