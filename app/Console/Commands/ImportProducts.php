<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bulk-load products from a spreadsheet (.xlsx or .csv) with these headings:
 *   title, sku, barcode, category, brand, unit, price, cost_price, stock, is_active, description
 *
 * Categories, brands and units are matched by NAME (any case) and created when missing.
 * Opening stock is booked as a normal "in" stock entry at the default stock location, so the
 * stock trigger and inventory reports stay correct.
 *
 * Products that already exist (same barcode, then SKU, then exact title) are skipped,
 * so the command is safe to run again. Use --update to refresh their details instead.
 *
 *   php artisan gozak:import-products storage/app/imports/gozakmart-products.xlsx --dry-run
 *   php artisan gozak:import-products storage/app/imports/gozakmart-products.xlsx
 */
class ImportProducts extends Command
{
    protected $signature = 'gozak:import-products
        {file : Path to the .xlsx or .csv file}
        {--dry-run : Check the file and report what would happen, without saving}
        {--update : Update products that already exist (name, prices, category, brand, unit) – stock is never changed for them}
        {--no-stock : Do not book opening stock}
        {--location= : Stock location ID for opening stock (default: the default location)}';

    protected $description = 'Import products with categories, brands, units and opening stock';

    protected array $maps = ['categories' => [], 'brands' => [], 'units' => []];
    protected array $created = ['categories' => 0, 'brands' => 0, 'units' => 0];

    public function handle(): int
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $file = $this->argument('file');
        if (!is_file($file)) {
            $file = base_path($file);
        }
        if (!is_file($file)) {
            $this->error('File not found: ' . $this->argument('file'));
            return self::FAILURE;
        }

        $rows = $this->readRows($file);
        $this->info(count($rows) . ' rows read from ' . basename($file));
        $first = $rows ? reset($rows) : [];
        $this->line('Columns found: ' . implode(', ', array_keys($first)));
        foreach (['title', 'price'] as $need) {
            if (!array_key_exists($need, $first)) {
                $this->error("The file has no \"{$need}\" column. The first row must be: title, sku, barcode, category, brand, unit, price, cost_price, stock, is_active, description");
                return self::FAILURE;
            }
        }

        $dry = (bool) $this->option('dry-run');
        $location = $this->option('no-stock') ? null : $this->location($dry);
        $this->loadMaps();

        $existing = $this->existingIndex();
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'stock_rows' => 0];
        $report = fopen(storage_path('app/imports/import-report-' . now()->format('Ymd-His') . '.csv'), 'w');
        fputcsv($report, ['row', 'title', 'result', 'message']);

        $bar = $this->output->createProgressBar(count($rows));
        foreach (array_chunk($rows, 250, true) as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $rowNum => $r) {
                    $bar->advance();
                    [$result, $msg] = $this->importRow($r, $existing, $location, $dry, $counts);
                    $counts[$result]++;
                    if ($result === 'failed' && $counts['failed'] <= 5) {
                        $this->newLine();
                        $this->warn("Row {$rowNum} failed: {$msg}");
                    }
                    if ($result !== 'created' || $msg) {
                        fputcsv($report, [$rowNum, $r['title'] ?? '', $result, $msg]);
                    }
                }
                $dry ? DB::rollBack() : DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->newLine();
                $this->error('Stopped at a batch error: ' . $e->getMessage() . ' – nothing from this batch was saved. Fix and run again (finished rows are skipped).');
                fclose($report);
                return self::FAILURE;
            }
        }
        $bar->finish();
        fclose($report);
        $this->newLine(2);

        $this->table(['', 'Count'], [
            ['Products created', $counts['created']],
            ['Products updated', $counts['updated']],
            ['Skipped (already exist)', $counts['skipped']],
            ['Failed rows', $counts['failed']],
            ['Opening stock entries', $counts['stock_rows']],
            ['New categories', $this->created['categories']],
            ['New brands', $this->created['brands']],
            ['New units', $this->created['units']],
        ]);
        $this->line('Row-by-row report: storage/app/imports/');
        if ($dry) {
            $this->warn('Dry run – nothing was saved.');
        }
        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ rows

    protected function importRow(array $r, array &$existing, ?StockLocation $location, bool $dry, array &$counts): array
    {
        $title = trim((string) ($r['title'] ?? ''));
        $price = $this->money($r['price'] ?? '');
        if ($title === '') {
            return ['failed', 'No title'];
        }
        if ($price === null) {
            return ['failed', 'No valid price'];
        }

        $barcode = preg_replace('/\s+/', '', (string) ($r['barcode'] ?? ''));
        $sku = strtoupper(trim((string) ($r['sku'] ?? '')));

        $id = ($barcode !== '' ? ($existing['barcode'][$barcode] ?? null) : null)
            ?? ($sku !== '' ? ($existing['sku'][$sku] ?? null) : null)
            ?? $existing['title'][mb_strtolower($title)] ?? null;

        $data = [
            'title'       => Str::limit($title, 250, ''),
            'price'       => $price,
            'cost_price'  => $this->money($r['cost_price'] ?? ''),
            'category_id' => $this->resolve('categories', $r['category'] ?? '', $dry),
            'brand_id'    => $this->resolve('brands', $r['brand'] ?? '', $dry),
            'is_active'   => $this->bool($r['is_active'] ?? 'yes'),
        ];
        if (!empty($r['description'])) {
            $data['description'] = $r['description'];
        }
        $unitId = $this->resolve('units', $r['unit'] ?? '', $dry);

        if ($id) {
            if (!$this->option('update')) {
                return ['skipped', 'Already exists (product #' . $id . ')'];
            }
            if (!$dry) {
                Product::whereKey($id)->update(array_filter($data, fn ($v) => $v !== null));
                if ($unitId) {
                    $this->attachUnit($id, $unitId);
                }
            }
            return ['updated', ''];
        }

        if ($sku !== '' && isset($existing['sku'][$sku])) {
            $sku = '';
        }
        $product = new Product($data + [
            'sku'          => $sku !== '' ? $sku : null,
            'barcode'      => $barcode !== '' ? Str::limit($barcode, 64, '') : null,
            'product_type' => 'simple',
            'stock'        => 0,
            'is_featured'  => false,
        ]);
        if ($dry) {
            $existing['title'][mb_strtolower($title)] = -1;
            if ($barcode !== '') { $existing['barcode'][$barcode] = -1; }
            return ['created', ''];
        }
        $product->save();
        if (empty($product->sku)) {
            $product->sku = 'GZK-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
            $product->saveQuietly();
        }
        $existing['title'][mb_strtolower($title)] = $product->id;
        $existing['barcode'][$product->barcode] = $product->id;
        $existing['sku'][strtoupper($product->sku)] = $product->id;

        if ($unitId) {
            $this->attachUnit($product->id, $unitId);
        }

        $qty = (int) round((float) str_replace(',', '', (string) ($r['stock'] ?? 0)));
        if ($location && $qty > 0) {
            $ref = 'IMP-' . now()->format('ymd') . '-' . $product->id;
            $stock = Stock::create([
                'product_id' => $product->id, 'stock_location_id' => $location->id, 'user_id' => null,
                'type' => 'in', 'quantity' => $qty, 'previous_quantity' => 0, 'new_quantity' => $qty,
                'unit_cost' => $data['cost_price'] ?? $price, 'total_cost' => ($data['cost_price'] ?? $price) * $qty,
                'reference_number' => $ref, 'reference_type' => 'purchase',
                'notes' => 'Opening stock from product import', 'transaction_date' => now(),
            ]);
            StockMovement::create([
                'stock_id' => $stock->id, 'product_id' => $product->id, 'stock_location_id' => $location->id,
                'movement_type' => 'in', 'quantity' => $qty, 'balance' => $qty, 'reference' => $ref,
                'description' => 'Opening stock from product import', 'user_id' => null,
            ]);
            $counts['stock_rows']++;
        }
        return ['created', ''];
    }

    protected function attachUnit(int $productId, int $unitId): void
    {
        if (!DB::table('product_unit')->where('product_id', $productId)->exists()) {
            DB::table('product_unit')->insert(['product_id' => $productId, 'unit_id' => $unitId, 'quantity_per_unit' => 1,
                'created_at' => now(), 'updated_at' => now()]);
        }
    }

    // ------------------------------------------------------------------ lookups

    protected function loadMaps(): void
    {
        foreach (DB::table('categories')->get(['id', 'name']) as $c) {
            $this->maps['categories'][mb_strtolower(trim($c->name))] ??= (int) $c->id;
        }
        foreach (DB::table('brands')->get(['id', 'name']) as $b) {
            $this->maps['brands'][mb_strtolower(trim($b->name))] ??= (int) $b->id;
        }
        $cols = array_intersect(['id', 'name', 'abbreviation', 'short_name'], Schema::getColumnListing('units'));
        foreach (DB::table('units')->get($cols) as $u) {
            $this->maps['units'][mb_strtolower(trim($u->name))] ??= (int) $u->id;
            foreach (['abbreviation', 'short_name'] as $k) {
                if (!empty($u->$k)) {
                    $this->maps['units'][mb_strtolower(trim($u->$k))] ??= (int) $u->id;
                }
            }
        }
    }

    /** Name → id, creating the category / brand / unit if it doesn't exist yet. */
    protected function resolve(string $table, $name, bool $dry): ?int
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));
        if ($name === '' || $name === '-') {
            return null;
        }
        $key = mb_strtolower($name);
        if (isset($this->maps[$table][$key])) {
            return $this->maps[$table][$key] ?: null;
        }
        $this->created[$table]++;
        if ($dry) {
            return $this->maps[$table][$key] = 0;
        }
        $id = match ($table) {
            'categories' => Category::create(['name' => $name])->id,
            'brands'     => Brand::create(['name' => $name])->id,
            'units'      => $this->createUnit($name),
        };
        return $this->maps[$table][$key] = (int) $id;
    }

    protected function createUnit(string $name): int
    {
        $cols = Schema::getColumnListing('units');
        $abbr = strtolower(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)) ?: 'u';
        $base = $abbr;
        $n = 1;
        while (in_array('abbreviation', $cols) && DB::table('units')->where('abbreviation', $abbr)->exists()) {
            $abbr = $base . ++$n;
        }
        $row = ['name' => $name, 'abbreviation' => $abbr, 'short_name' => $abbr, 'is_active' => 1, 'is_default' => 0,
            'created_at' => now(), 'updated_at' => now()];
        return (int) DB::table('units')->insertGetId(array_intersect_key($row, array_flip($cols)));
    }

    protected function existingIndex(): array
    {
        $idx = ['barcode' => [], 'sku' => [], 'title' => []];
        foreach (DB::table('products')->select('id', 'barcode', 'sku', 'title')->cursor() as $p) {
            if ($p->barcode) { $idx['barcode'][$p->barcode] = $p->id; }
            if ($p->sku) { $idx['sku'][strtoupper($p->sku)] = $p->id; }
            $idx['title'][mb_strtolower(trim($p->title))] ??= $p->id;
        }
        return $idx;
    }

    protected function location(bool $dry): ?StockLocation
    {
        if ($id = $this->option('location')) {
            return StockLocation::findOrFail($id);
        }
        $loc = StockLocation::where('is_default', true)->first() ?? StockLocation::orderBy('id')->first();
        if (!$loc && !$dry) {
            $loc = StockLocation::create(['name' => 'Main Store', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
        }
        if ($loc) {
            $this->info("Opening stock goes to location: {$loc->name}");
        }
        return $loc;
    }

    // ------------------------------------------------------------------ file + values

    /** Returns [rowNumber => [heading => value]] using the first row as headings. */
    protected function readRows(string $file): array
    {
        if (Str::endsWith(strtolower($file), '.csv')) {
            $h = fopen($file, 'r');
            $all = [];
            while (($line = fgetcsv($h)) !== false) {
                $all[] = $line;
            }
            fclose($h);
        } else {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $all = $reader->load($file)->getSheet(0)->toArray(null, false, false, false);
        }
        $head = array_map(function ($x) {
            $x = is_object($x) && method_exists($x, 'getPlainText') ? $x->getPlainText() : (string) $x;
            $x = preg_replace('/^\xEF\xBB\xBF/', '', $x);            // UTF-8 BOM from Excel CSVs
            return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($x))), '_');
        }, array_shift($all) ?? []);
        $rows = [];
        foreach ($all as $i => $line) {
            if (!array_filter($line, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $row = [];
            foreach ($head as $j => $k) {
                if ($k !== '') {
                    $v = $line[$j] ?? null;
                    if (is_object($v) && method_exists($v, 'getPlainText')) {
                        $v = $v->getPlainText();
                    }
                    if (is_float($v) && floor($v) == $v && abs($v) < 1e15) {
                        $v = (string) (int) $v;
                    }
                    $row[$k] = $v === null ? null : trim((string) $v);
                }
            }
            $rows[$i + 2] = $row;
        }
        return $rows;
    }

    protected function money($v): ?float
    {
        $s = preg_replace('/[^\d.\-]/', '', (string) $v);
        return is_numeric($s) && (float) $s >= 0 ? round((float) $s, 2) : null;
    }

    protected function bool($v): bool
    {
        return !in_array(strtolower(trim((string) $v)), ['no', 'n', '0', 'false', 'inactive', 'off'], true);
    }
}
