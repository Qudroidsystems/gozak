<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Finds a picture for every product that has none and sets it as the product thumbnail.
 *
 * Free sources only, no API key needed. Products with a real retail barcode (EAN/UPC) are looked up in:
 *  1. Open Food Facts, Open Beauty Facts and Open Products Facts (free, unlimited, CC BY-SA pictures)
 *  2. UPCitemdb free lookup (about 100 look-ups a day – the run stops when the day's limit is reached)
 * Products with in-store codes (no real barcode) can't be matched automatically and are skipped.
 * (Optional, paid: set BRAVE_SEARCH_API_KEY in .env and use --source=all to also web-search the rest.)
 *
 * Every pick is logged to storage/app/product-images/log.csv and shown on
 * storage/app/product-images/review.html so wrong pictures can be removed with --undo.
 *
 *   php artisan products:fetch-images --limit=50 --dry-run     # see what it would pick
 *   php artisan products:fetch-images --limit=500              # do 500 products
 *   php artisan products:fetch-images --category="Wines & Spirits"
 *   php artisan products:fetch-images --undo=12,57,301         # remove wrong pictures
 */
class FetchProductImages extends Command
{
    protected $signature = 'products:fetch-images
        {--limit=200 : How many products to process this run}
        {--ids= : Only these product IDs (comma separated)}
        {--category= : Only products in this category name}
        {--source=barcode : barcode (free) | all (barcode + paid Brave web search, needs BRAVE_SEARCH_API_KEY)}
        {--force : Also replace pictures products already have}
        {--retry : Try again products that found nothing last time}
        {--dry-run : Show the picks without downloading or saving anything}
        {--min-size=300 : Smallest acceptable width/height in pixels}
        {--size=800 : Pictures are resized to fit inside this many pixels}
        {--undo= : Remove the auto picture from these product IDs and never auto-pick them again}';

    protected $description = 'Find and attach product pictures from barcode databases and web image search';

    protected string $dir = 'product-images';           // on the local disk (storage/app)
    protected array $state = ['missed' => [], 'blocked' => []];
    protected array $byTitle = [];                       // normalised title => saved path (re-use for duplicates)
    protected int $searches = 0;
    protected bool $upcLimitHit = false;
    protected bool $offOnly = false;
    protected int $upcCalls = 0;

    /** Sites whose pictures are watermarked stock photos, social posts, or not product shots. */
    protected array $badHosts = ['pinterest.', 'pinimg.', 'facebook.', 'fbcdn.', 'instagram.', 'tiktok.', 'x.com', 'twitter.',
        'shutterstock.', 'istockphoto.', 'gettyimages.', 'alamy.', 'dreamstime.', '123rf.', 'depositphotos.', 'vecteezy.',
        'freepik.', 'adobe.', 'youtube.', 'ytimg.', 'wikipedia.', 'reddit.', 'redd.it', 'lookaside.'];

    /** Shops that usually have clean white-background product shots – preferred. */
    protected array $goodHosts = ['jumia.', 'konga.', 'amazon.', 'media-amazon.', 'walmart.', 'tesco.', 'sainsburys.', 'asda.',
        'carrefour.', 'shoprite.', 'spar.', 'ebayimg.', 'aliexpress.', 'alicdn.', 'noon.', 'takealot.', 'supermart.ng',
        'ubuy.', 'boots.', 'superdrug.', 'samsung.', 'apple.', 'oraimo.', 'binatone.', 'nivea.', 'unilever.', 'nestle.'];

    public function handle(): int
    {
        Storage::disk('local')->makeDirectory($this->dir);
        $this->loadState();

        if ($undo = $this->option('undo')) {
            return $this->undo(array_filter(array_map('intval', explode(',', $undo))));
        }

        $key = config('services.brave.key', env('BRAVE_SEARCH_API_KEY'));
        $source = $this->option('source');
        if ($source !== 'barcode' && !$key) {
            $source = 'barcode';
        }

        $q = Product::query()->with('brand:id,name')->orderBy('id');
        if (!$this->option('force')) {
            $q->where(fn ($w) => $w->whereNull('thumbnail')->orWhere('thumbnail', ''));
        }
        if ($ids = $this->option('ids')) {
            $q->whereIn('id', array_map('intval', explode(',', $ids)));
        }
        if ($cat = $this->option('category')) {
            $q->whereHas('category', fn ($c) => $c->where('name', $cat));
        }
        $skip = array_keys($this->state['blocked']);
        if (!$this->option('retry')) {
            $skip = array_merge($skip, array_keys($this->state['missed']));
        }
        if ($skip) {
            $q->whereNotIn('id', $skip);
        }

        if ($source === 'barcode') {
            $ids = [];
            foreach ((clone $q)->toBase()->select('products.id', 'products.barcode')->cursor() as $row) {
                if ($this->isRealBarcode((string) $row->barcode)) {
                    $ids[] = $row->id;
                    if (count($ids) >= (int) $this->option('limit')) {
                        break;
                    }
                }
            }
            $products = $ids ? Product::with('brand:id,name')->whereIn('id', $ids)->orderBy('id')->get() : collect();
        } else {
            $products = $q->limit((int) $this->option('limit'))->get();
        }
        if ($products->isEmpty()) {
            $this->info('No products need a picture.');
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $log = $dry ? null : $this->openLog();
        $done = $missed = 0;
        $bar = $this->output->createProgressBar($products->count());
        $rows = [];

        foreach ($products as $p) {
            if ($this->upcLimitHit && !$this->offOnly) {
                $this->newLine();
                $this->warn('UPCitemdb free daily limit reached – continuing with Open Facts only. Run again tomorrow with --retry for the rest.');
                $this->offOnly = true;
            }
            $bar->advance();
            try {
                [$path, $pick] = $this->findAndSave($p, $source, $key, $dry);
            } catch (\Throwable $e) {
                [$path, $pick] = [null, ['source' => 'error', 'image' => '', 'page' => '', 'note' => $e->getMessage()]];
            }

            if ($path || ($dry && !empty($pick['image']))) {
                $done++;
                if (!$dry) {
                    $this->attach($p, $path);
                    unset($this->state['missed'][$p->id]);
                }
            } else {
                $missed++;
                if (!$dry && !$this->upcLimitHit) {
                    $this->state['missed'][$p->id] = now()->toDateString();
                }
            }
            $row = [$p->id, $p->title, $pick['source'] ?? 'none', $pick['image'] ?? '', $pick['page'] ?? '', $path ?? '', $pick['note'] ?? ''];
            $rows[] = $row;
            if ($log) {
                fputcsv($log, $row);
            }
        }
        $bar->finish();
        $this->newLine(2);

        if ($dry) {
            $this->table(['ID', 'Product', 'Source', 'Image URL'], array_map(fn ($r) => [$r[0], Str::limit($r[1], 40), $r[2], Str::limit($r[3], 70)], $rows));
        } else {
            fclose($log);
            $this->saveState();
            $this->writeReview();
            $this->info('Review the pictures: ' . Storage::disk('local')->path("{$this->dir}/review.html"));
        }
        $this->info("Found {$done}, not found {$missed}. UPCitemdb look-ups used: {$this->upcCalls}" . ($this->searches ? ", web searches: {$this->searches}" : '') . '.');

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ finding

    protected function findAndSave(Product $p, string $source, ?string $key, bool $dry): array
    {
        $norm = $this->norm($p->title);
        if (!$dry && isset($this->byTitle[$norm]) && Storage::disk('public')->exists($this->byTitle[$norm])) {
            return [$this->byTitle[$norm], ['source' => 'same name', 'image' => $this->byTitle[$norm], 'page' => '']];
        }

        $candidates = [];
        if ($source !== 'search' && $this->isRealBarcode((string) $p->barcode)) {
            $candidates = array_merge($candidates, $this->barcodeLookup($p->barcode));
        }
        if ($source !== 'barcode' && $key) {
            $candidates = array_merge($candidates, $this->search($p, $key));
        }
        if (!$candidates) {
            return [null, ['source' => 'none', 'note' => 'no match']];
        }
        if ($dry) {
            return [null, $candidates[0]];
        }

        foreach (array_slice($candidates, 0, 5) as $c) {
            foreach (array_filter([$c['image'], $c['fallback'] ?? null]) as $url) {
                if ($path = $this->download($url, $p)) {
                    $this->byTitle[$norm] = $path;
                    return [$path, $c];
                }
            }
        }
        return [null, ['source' => 'none', 'note' => 'matches found but none downloaded']];
    }

    /** EAN-8 / UPC-A / EAN-13 / GTIN-14 with a valid check digit, and not an in-store 20–29 code. */
    protected function isRealBarcode(string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (!in_array(strlen($code), [8, 12, 13, 14], true) || preg_match('/^0+$/', $code)) {
            return false;
        }
        if (strlen($code) === 13 && $code[0] === '2') {
            return false; // restricted-circulation (made in-store)
        }
        $digits = str_split(strrev(substr($code, 0, -1)));
        $sum = 0;
        foreach ($digits as $i => $d) {
            $sum += (int) $d * ($i % 2 === 0 ? 3 : 1);
        }
        return (10 - $sum % 10) % 10 === (int) substr($code, -1);
    }

    protected function barcodeLookup(string $code): array
    {
        $code = preg_replace('/\D/', '', $code);
        $out = [];
        foreach (['world.openfoodfacts.org', 'world.openbeautyfacts.org', 'world.openproductsfacts.org'] as $host) {
            $res = $this->http()->get("https://{$host}/api/v2/product/{$code}.json", ['fields' => 'product_name,image_front_url,image_url']);
            if ($res->ok() && $res->json('status') == 1 && ($img = $res->json('product.image_front_url') ?: $res->json('product.image_url'))) {
                $out[] = ['source' => explode('.', $host)[1], 'image' => $img, 'page' => "https://{$host}/product/{$code}",
                    'note' => 'CC BY-SA (' . $res->json('product.product_name') . ')'];
                break;
            }
        }
        if (!$out && !$this->upcLimitHit) {
            $out = $this->upcLookup($code);
        }
        return $out;
    }

    /** UPCitemdb free trial endpoint – no key, about 100 requests a day and a few per minute. */
    protected function upcLookup(string $code): array
    {
        usleep(11000000); // ~6 per minute keeps under the burst limit
        $res = $this->http()->get('https://api.upcitemdb.com/prod/trial/lookup', ['upc' => $code]);
        $this->upcCalls++;
        if ($res->status() === 429 || $res->json('code') === 'TOO_FAST' || $res->json('code') === 'EXCEED_LIMIT') {
            $this->upcLimitHit = true;
            return [];
        }
        $item = $res->ok() ? ($res->json('items.0') ?? null) : null;
        if (!$item || empty($item['images'])) {
            return [];
        }
        $out = [];
        foreach (array_slice($item['images'], 0, 4) as $img) {
            if (!Str::contains(strtolower($img), $this->badHosts)) {
                $out[] = ['source' => 'upcitemdb', 'image' => $img, 'page' => 'https://www.upcitemdb.com/upc/' . $code, 'note' => $item['title'] ?? ''];
            }
        }
        return $out;
    }

    protected function search(Product $p, string $key): array
    {
        $query = $this->query($p);
        $cacheFile = "{$this->dir}/cache/" . md5($query) . '.json';
        if (Storage::disk('local')->exists($cacheFile)) {
            $results = json_decode(Storage::disk('local')->get($cacheFile), true) ?: [];
        } else {
            usleep(1100000); // free plan allows 1 request per second
            $res = $this->http()->withHeaders(['X-Subscription-Token' => $key, 'Accept' => 'application/json'])
                ->get('https://api.search.brave.com/res/v1/images/search', ['q' => $query, 'count' => 20, 'safesearch' => 'strict']);
            $this->searches++;
            if ($res->status() === 429) {
                sleep(3);
                return [];
            }
            if (!$res->ok()) {
                throw new \RuntimeException('Image search failed: HTTP ' . $res->status());
            }
            $results = $res->json('results') ?? [];
            Storage::disk('local')->put($cacheFile, json_encode($results));
        }

        $min = (int) $this->option('min-size');
        $want = $this->words($p->title . ' ' . ($p->brand->name ?? ''));
        $scored = [];
        foreach ($results as $r) {
            $img = $r['properties']['url'] ?? null;
            if (!$img) {
                continue;
            }
            $host = strtolower(parse_url($img, PHP_URL_HOST) . ' ' . parse_url($r['url'] ?? '', PHP_URL_HOST));
            if (Str::contains($host, $this->badHosts)) {
                continue;
            }
            $w = (int) ($r['properties']['width'] ?? 0);
            $h = (int) ($r['properties']['height'] ?? 0);
            if (($w && $w < $min) || ($h && $h < $min) || ($w && $h && ($w / $h > 2.2 || $h / $w > 2.2))) {
                continue;
            }
            // How many words of the product name appear in the result title / page?
            $got = $this->words(($r['title'] ?? '') . ' ' . ($r['url'] ?? ''));
            $overlap = $want ? count(array_intersect($want, $got)) / count($want) : 0;
            if ($overlap < 0.34) {
                continue;
            }
            $score = $overlap * 10 + (Str::contains($host, $this->goodHosts) ? 3 : 0) + ($w >= 600 ? 1 : 0);
            $scored[] = ['score' => $score, 'source' => 'web search', 'image' => $img, 'fallback' => $r['thumbnail']['src'] ?? null,
                'page' => $r['url'] ?? '', 'note' => sprintf('match %d%%', $overlap * 100)];
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        return $scored;
    }

    protected function query(Product $p): string
    {
        $t = $p->title;
        $t = preg_replace('/\b(X\s?\d+|\d+\s?X|CTN|CARTON|PCS?|PET)\b/i', ' ', $t);   // pack counts confuse search
        $t = preg_replace('/\s+/', ' ', trim($t));
        $brand = $p->brand->name ?? '';
        if ($brand && stripos($t, $brand) === false) {
            $t = "{$brand} {$t}";
        }
        return Str::limit($t, 120, '');
    }

    // ------------------------------------------------------------------ saving

    protected function download(string $url, Product $p): ?string
    {
        try {
            $res = $this->http()->timeout(25)->get($url);
        } catch (\Throwable) {
            return null;
        }
        if (!$res->ok() || !Str::startsWith((string) $res->header('Content-Type'), 'image/') || strlen($res->body()) > 10 * 1024 * 1024) {
            return null;
        }
        try {
            $size = (int) $this->option('size');
            $img = Image::read($res->body());
            $min = (int) $this->option('min-size');
            if ($img->width() < $min * 0.8 && $img->height() < $min * 0.8) {
                return null;
            }
            $jpg = (string) $img->scaleDown($size, $size)->toJpeg(85);
        } catch (\Throwable) {
            return null;
        }
        $path = 'product/auto/' . $p->id . '-' . Str::slug(Str::limit($p->title, 60, '')) . '.jpg';
        Storage::disk('public')->put($path, $jpg);
        return $path;
    }

    protected function attach(Product $p, string $path): void
    {
        \DB::table('products')->where('id', $p->id)->update(['thumbnail' => $path]); // not save(): the model's retrieved hook would also rewrite stock
        if (!ProductImage::where('product_id', $p->id)->where('image_path', $path)->exists()) {
            ProductImage::where('product_id', $p->id)->update(['is_primary' => false]);
            ProductImage::create(['product_id' => $p->id, 'image_path' => $path, 'is_primary' => true, 'sort_order' => 0]);
        }
    }

    protected function undo(array $ids): int
    {
        foreach (Product::whereIn('id', $ids)->get() as $p) {
            if ($p->thumbnail && Str::startsWith($p->thumbnail, 'product/auto/')) {
                ProductImage::where('product_id', $p->id)->where('image_path', $p->thumbnail)->delete();
                // the file may be shared by products with the same name – only delete if nothing else uses it
                if (!Product::where('thumbnail', $p->thumbnail)->where('id', '!=', $p->id)->exists()) {
                    Storage::disk('public')->delete($p->thumbnail);
                }
                \DB::table('products')->where('id', $p->id)->update(['thumbnail' => null]);
            }
            $this->state['blocked'][$p->id] = now()->toDateString();
            $this->line("Removed auto picture from #{$p->id} {$p->title}");
        }
        $this->saveState();
        $this->writeReview();
        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ helpers

    protected function http()
    {
        return Http::withHeaders(['User-Agent' => 'GozakMart/1.0 (product image lookup; ' . config('mail.from.address') . ')'])
            ->timeout(20)->retry(2, 500, throw: false);
    }

    protected function norm(string $t): string
    {
        return preg_replace('/[^a-z0-9]+/', ' ', strtolower($t));
    }

    protected function words(string $t): array
    {
        $stop = ['the', 'and', 'with', 'for', 'new', 'big', 'small', 'of', 'in', 'com', 'www', 'https', 'http', 'html', 'product', 'products'];
        $w = preg_split('/[^a-z0-9]+/', strtolower($t), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($w, fn ($x) => strlen($x) > 1 && !in_array($x, $stop, true))));
    }

    protected function openLog()
    {
        $file = Storage::disk('local')->path("{$this->dir}/log.csv");
        $new = !file_exists($file);
        $h = fopen($file, 'a');
        if ($new) {
            fputcsv($h, ['product_id', 'title', 'source', 'image_url', 'page_url', 'saved_as', 'note']);
        }
        return $h;
    }

    protected function loadState(): void
    {
        $f = "{$this->dir}/state.json";
        if (Storage::disk('local')->exists($f)) {
            $this->state = array_merge($this->state, json_decode(Storage::disk('local')->get($f), true) ?: []);
        }
    }

    protected function saveState(): void
    {
        Storage::disk('local')->put("{$this->dir}/state.json", json_encode($this->state));
    }

    /** One page with every auto picture, newest first, to scan for wrong ones. */
    protected function writeReview(): void
    {
        $file = Storage::disk('local')->path("{$this->dir}/log.csv");
        if (!file_exists($file)) {
            return;
        }
        $rows = array_map('str_getcsv', file($file));
        array_shift($rows);
        $latest = [];
        foreach ($rows as $r) {
            if (!empty($r[5])) {
                $latest[$r[0]] = $r;
            }
        }
        $live = Product::whereIn('id', array_keys($latest))->pluck('thumbnail', 'id');
        $cards = '';
        foreach (array_reverse($latest, true) as $id => $r) {
            if (($live[$id] ?? null) !== $r[5]) {
                continue; // removed or replaced since
            }
            $src = e(asset('storage/' . $r[5]));
            $cards .= '<figure><img loading="lazy" src="' . $src . '"><figcaption><b>#' . e($id) . '</b> ' . e($r[1])
                . '<br><small>' . e($r[2]) . ($r[4] ? ' · <a target="_blank" href="' . e($r[4]) . '">source</a>' : '') . '</small></figcaption></figure>';
        }
        $html = '<!doctype html><meta charset="utf-8"><title>Product pictures to review</title><style>body{font:14px system-ui;margin:16px;background:#f6f7f8}'
            . 'main{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px}figure{margin:0;background:#fff;border-radius:8px;padding:8px}'
            . 'img{width:100%;aspect-ratio:1;object-fit:contain;background:#fff}figcaption{font-size:12px;margin-top:6px}</style>'
            . '<h2>Auto pictures (' . count($latest) . ')</h2><p>Wrong picture? Note the #IDs and run <code>php artisan products:fetch-images --undo=ID,ID</code></p><main>'
            . $cards . '</main>';
        file_put_contents(Storage::disk('local')->path("{$this->dir}/review.html"), $html);
    }
}
