<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\APIProductController;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\LightningDeal;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\WishlistItem;
use App\Services\Storefront\StoreCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Shopping website: home, categories, search, deals, brands, product pages. */
class ShopController extends Controller
{
    public const PER_PAGE = 40;

    public function __construct(private StoreCatalog $catalog)
    {
    }

    // ── Home ──────────────────────────────────────────────────────────────────

    public function home()
    {
        $data = Cache::remember('store_home_v1', now()->addMinutes(5), function () {
            $section = fn ($q, $n = 12) => $this->catalog->cards($q->limit($n)->get());

            $deals = collect();
            try {
                $dealRows = LightningDeal::live()->with('product')->orderBy('ends_at')->limit(12)->get()
                    ->filter(fn ($d) => $d->product && ($d->product->is_active ?? true));
                $order = $dealRows->pluck('product_id')->values()->flip();
                $deals = $this->catalog->cards(
                    $this->catalog->query()->whereIn('products.id', $dealRows->pluck('product_id'))->get()
                        ->sortBy(fn ($p) => $order[$p->id] ?? 99)
                );
                $dealEnds = $dealRows->min(fn ($d) => $d->endsAtLagos());
            } catch (\Throwable $e) {
                $dealEnds = null;
            }

            $banners = collect();
            try {
                $banners = Banner::query()->where('active', true)->orderBy('order')->limit(6)->get()
                    ->map(fn ($b) => [
                        'image' => ProductImage::publicUrl($b->image_url),
                        'title' => $b->title,
                        'url'   => $b->product_id
                            ? route('store.product', ['id' => $b->product_id, 'slug' => 'item'])
                            : ($b->link && preg_match('#^https?://#', $b->link) ? $b->link : route('store.deals')),
                    ])
                    ->filter(fn ($b) => $b['image']);
            } catch (\Throwable $e) {
            }

            return [
                'banners'  => $banners->values(),
                'deals'    => $deals->values(),
                'dealEnds' => isset($dealEnds) && $dealEnds ? $dealEnds->toIso8601String() : null,
                'top'      => $section($this->catalog->query()->orderByDesc('sold_quantity'), 12),
                'new'      => $section($this->catalog->query()->where('is_new', true)->latest('products.created_at'), 12),
                'trending' => $section($this->catalog->query()->where('is_trending', true)->orderByDesc('sold_quantity'), 12),
                'featured' => $section($this->catalog->query()->where('is_featured', true)->latest('products.updated_at'), 12),
                'sale'     => $section($this->catalog->query()->whereColumn('sale_price', '<', 'price')->where('sale_price', '>', 0)->orderByDesc('sold_quantity'), 12),
                'brands'   => Brand::query()->where('is_featured', true)->orderBy('name')->limit(12)->get(['id', 'name', 'logo'])
                    ->map(fn ($b) => ['name' => $b->name, 'logo' => ProductImage::publicUrl($b->logo), 'url' => route('store.brand', ['id' => $b->id, 'slug' => Str::slug($b->name) ?: 'brand'])]),
            ];
        });

        return view('store.home', $data);
    }

    // ── Listings ──────────────────────────────────────────────────────────────

    public function category(Request $request, int $id, ?string $slug = null)
    {
        $category = Category::with('children:id,name,parent_id', 'parent:id,name')->findOrFail($id);
        $q = $this->catalog->inCategory($this->catalog->query(), $category->id);

        return $this->listing($request, $q, [
            'title'      => $category->name,
            'category'   => $category,
            'subcats'    => $category->children,
            'canonical'  => $this->catalog->categoryUrl($category),
        ]);
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term !== '' && mb_strlen($term) > 100) {
            $term = mb_substr($term, 0, 100);
        }
        $q = $this->catalog->search($this->catalog->query(), $term);

        return $this->listing($request, $q, [
            'title'  => $term !== '' ? 'Results for “' . $term . '”' : 'All products',
            'term'   => $term,
            'noindex'=> true,
        ]);
    }

    public function brand(Request $request, int $id, ?string $slug = null)
    {
        $brand = Brand::findOrFail($id);
        $q = $this->catalog->query()->where('products.brand_id', $brand->id);

        return $this->listing($request, $q, ['title' => $brand->name, 'brand' => $brand]);
    }

    public function deals(Request $request)
    {
        $ids = [];
        $ends = null;
        try {
            $live = LightningDeal::live()->get(['product_id', 'ends_at']);
            $ids = $live->pluck('product_id')->all();
            $ends = $live->min(fn ($d) => $d->endsAtLagos());
        } catch (\Throwable $e) {
        }
        // Flash Sales = live lightning deals, then anything on sale
        $q = $this->catalog->query()->where(function ($w) use ($ids) {
            $w->whereIn('products.id', $ids ?: [0])
              ->orWhere(fn ($s) => $s->where('products.sale_price', '>', 0)->whereColumn('products.sale_price', '<', 'products.price'));
        });
        if (!$request->filled('sort')) {
            $q->orderByRaw('CASE WHEN products.id IN (' . (implode(',', array_map('intval', $ids)) ?: '0') . ') THEN 0 ELSE 1 END');
        }

        return $this->listing($request, $q, [
            'title'    => 'Flash Sales',
            'dealEnds' => $ends?->toIso8601String(),
        ]);
    }

    /** Shared listing page: price filter, in-stock filter, sort, pagination. */
    private function listing(Request $request, $q, array $view)
    {
        $min = $request->filled('min') ? max(0, (float) $request->query('min')) : null;
        $max = $request->filled('max') ? max(0, (float) $request->query('max')) : null;
        $price = 'COALESCE(NULLIF(products.sale_price, 0), products.price)';
        if ($min !== null) $q->whereRaw("$price >= ?", [$min]);
        if ($max !== null) $q->whereRaw("$price <= ?", [$max]);
        if ($request->boolean('in_stock')) $q->where('products.stock', '>', 0);
        if ($request->boolean('discount')) $q->where('products.sale_price', '>', 0)->whereColumn('products.sale_price', '<', 'products.price');

        $sort = in_array($request->query('sort'), ['popular', 'newest', 'price_asc', 'price_desc', 'rating', 'discount'], true)
            ? $request->query('sort') : 'popular';
        $this->catalog->sort($q, $sort);

        $page  = $q->paginate(self::PER_PAGE)->withQueryString();
        $cards = $this->catalog->cards($page->items());

        return view('store.catalog.index', $view + [
            'page'  => $page,
            'cards' => $cards,
            'sort'  => $sort,
            'min'   => $min,
            'max'   => $max,
        ]);
    }

    // ── Product page ──────────────────────────────────────────────────────────

    public function product(int $id, ?string $slug = null)
    {
        $model = Product::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))->find($id);
        if (!$model) {
            abort(404);
        }

        // Same data the app gets: media, options with swatches + stock, specs, size guide.
        $api  = app(APIProductController::class)->show($id);
        $data = json_decode($api->getContent(), true)['data'] ?? null;
        if (!$data) {
            abort(404);
        }

        $deal = null;
        try {
            $deal = LightningDeal::liveDealFor($id);
        } catch (\Throwable $e) {
        }
        $dealPct = $deal ? (int) $deal->discount_percentage : null;

        $reviews = ProductReview::where('product_id', $id)->latest()->limit(10)
            ->get(['id', 'user_name', 'rating', 'comment', 'company_comment', 'location', 'created_at']);

        $related = $this->catalog->cards(
            $this->catalog->query()
                ->where('products.id', '!=', $id)
                ->when($model->category_id, fn ($q) => $this->catalog->inCategory($q, (int) $model->category_id))
                ->orderByDesc('products.sold_quantity')
                ->limit(12)->get()
        );

        $saved = false;
        try {
            $saved = Auth::guard('customer')->check()
                && WishlistItem::where('user_id', Auth::guard('customer')->id())->where('product_id', $id)->exists();
        } catch (\Throwable $e) {
            $saved = false;   // wishlist table not migrated yet
        }

        $category = $model->category_id ? Category::find($model->category_id, ['id', 'name', 'parent_id']) : null;

        return view('store.product', [
            'p'        => $data,
            'model'    => $model,
            'dealPct'  => $dealPct,
            'dealEnds' => $deal?->endsAtLagos()?->toIso8601String(),
            'reviews'  => $reviews,
            'related'  => $related,
            'saved'    => $saved,
            'category' => $category,
            'canonical'=> $this->catalog->productUrl($model),
        ]);
    }

    public function help()
    {
        return view('store.help');
    }
}
