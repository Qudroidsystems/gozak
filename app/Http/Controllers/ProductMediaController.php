<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin › Products › {product} › Photos, video & specs
 *
 *  - upload photos (optionally for one colour/option), drag to reorder
 *  - upload one or more short videos (≤ 60 s, ≤ 30 MB)
 *  - specifications list and size guide
 */
class ProductMediaController extends Controller
{
    public function __construct(protected ProductMediaService $media)
    {
        $this->middleware('permission:Update product');
    }

    public function show(Product $product)
    {
        $product->load(['media', 'specifications', 'attributes']);
        $pagetitle = 'Photos, video & specs — ' . $product->title;

        // Option values a photo can be tied to, e.g. ['Colour' => ['Red', 'Blue']]
        $optionValues = $product->attributes
            ->mapWithKeys(fn ($a) => [$a->name => array_values((array) $a->values)])
            ->filter(fn ($vals) => !empty($vals))
            ->all();

        return view('catalog.product-media', [
            'pagetitle'    => $pagetitle,
            'product'      => $product,
            'optionValues' => $optionValues,
            'limits'       => [
                'video_mb'      => (int) (ProductMediaService::MAX_VIDEO_KB / 1024),
                'video_seconds' => ProductMediaService::MAX_VIDEO_SECONDS,
                'server_upload' => ini_get('upload_max_filesize'),
                'server_post'   => ini_get('post_max_size'),
            ],
        ]);
    }

    /** POST photos[] (+ attribute_name, attribute_value) */
    public function storePhotos(Request $request, Product $product)
    {
        $v = Validator::make($request->all(), [
            'photos'          => 'required|array|min:1|max:20',
            'photos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:10240',
            'attribute_name'  => 'nullable|string|max:60',
            'attribute_value' => 'nullable|string|max:60|required_with:attribute_name',
        ], ['photos.*.max' => 'Each photo must be 10 MB or smaller.']);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $created = [];
        foreach ($request->file('photos') as $file) {
            $created[] = $this->media->storePhoto($product, $file, [
                'attribute_name'  => $request->attribute_name ?: null,
                'attribute_value' => $request->attribute_name ? $request->attribute_value : null,
            ])->toMediaArray();
        }

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => count($created) . ' photo(s) added', 'data' => $created]);
    }

    /** POST video, poster (image from the browser), duration */
    public function storeVideo(Request $request, Product $product)
    {
        $v = Validator::make($request->all(), [
            'video'    => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm|max:' . ProductMediaService::MAX_VIDEO_KB,
            'poster'   => 'nullable|image|max:5120',
            'duration' => 'nullable|numeric|min:0|max:' . (ProductMediaService::MAX_VIDEO_SECONDS + 1),
            'attribute_name'  => 'nullable|string|max:60',
            'attribute_value' => 'nullable|string|max:60|required_with:attribute_name',
        ], [
            'video.max'       => 'Videos must be ' . (ProductMediaService::MAX_VIDEO_KB / 1024) . ' MB or smaller.',
            'video.mimetypes' => 'Upload an MP4, MOV or WebM video.',
            'duration.max'    => 'Videos must be ' . ProductMediaService::MAX_VIDEO_SECONDS . ' seconds or shorter.',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $item = $this->media->storeVideo(
            $product,
            $request->file('video'),
            $request->file('poster'),
            $request->filled('duration') ? (int) round($request->duration) : null,
            [
                'attribute_name'  => $request->attribute_name ?: null,
                'attribute_value' => $request->attribute_name ? $request->attribute_value : null,
            ]
        );

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => 'Video added', 'data' => $item->toMediaArray()]);
    }

    /** PATCH one item: attribute link, alt text, primary */
    public function updateItem(Request $request, Product $product, ProductImage $media)
    {
        abort_unless((int) $media->product_id === (int) $product->id, 404);

        $data = $request->validate([
            'attribute_name'  => 'nullable|string|max:60',
            'attribute_value' => 'nullable|string|max:60',
            'alt_text'        => 'nullable|string|max:160',
            'is_primary'      => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data, $product, $media, $request) {
            if ($request->has('attribute_name')) {
                $media->attribute_name  = $data['attribute_name'] ?: null;
                $media->attribute_value = $media->attribute_name ? ($data['attribute_value'] ?? null) : null;
            }
            if ($request->has('alt_text')) {
                $media->alt_text = $data['alt_text'];
            }
            if ($request->boolean('is_primary')) {
                $product->images()->where('id', '!=', $media->id)->update(['is_primary' => false]);
                $media->is_primary = true;
            } elseif ($request->has('is_primary')) {
                $media->is_primary = false;
            }
            $media->save();
        });

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'data' => $media->toMediaArray()]);
    }

    /** POST ids[] in the new order */
    public function reorder(Request $request, Product $product)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];

        DB::transaction(function () use ($ids, $product) {
            foreach (array_values($ids) as $i => $id) {
                $product->images()->where('id', $id)->update(['sort_order' => $i]);
            }
        });

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => 'Order saved']);
    }

    public function destroyItem(Product $product, ProductImage $media)
    {
        abort_unless((int) $media->product_id === (int) $product->id, 404);
        $this->media->delete($media);
        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => 'Removed']);
    }

    /** PUT specifications: [{label, value, group?}, …] */
    public function saveSpecifications(Request $request, Product $product)
    {
        $data = $request->validate([
            'specifications'         => 'nullable|array|max:60',
            'specifications.*.label' => 'required|string|max:80',
            'specifications.*.value' => 'required|string|max:1000',
            'specifications.*.group' => 'nullable|string|max:60',
        ], [
            'specifications.*.label.required' => 'Every row needs a label.',
            'specifications.*.value.required' => 'Every row needs a value.',
        ]);

        DB::transaction(function () use ($data, $product) {
            $product->specifications()->delete();
            foreach (array_values($data['specifications'] ?? []) as $i => $row) {
                $product->specifications()->create([
                    'label'      => trim($row['label']),
                    'value'      => trim($row['value']),
                    'group'      => isset($row['group']) ? trim($row['group']) ?: null : null,
                    'sort_order' => $i,
                ]);
            }
        });

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => 'Specifications saved']);
    }

    /** PUT size_guide: {unit, columns[], rows[][], note} or null to remove */
    public function saveSizeGuide(Request $request, Product $product)
    {
        if (!$request->filled('size_guide')) {
            $product->update(['size_guide' => null]);
            $this->forgetProductCache($product);
            return response()->json(['success' => true, 'message' => 'Size guide removed']);
        }

        $data = $request->validate([
            'size_guide.unit'      => 'nullable|string|max:12',
            'size_guide.columns'   => 'required|array|min:2|max:10',
            'size_guide.columns.*' => 'required|string|max:30',
            'size_guide.rows'      => 'required|array|min:1|max:40',
            'size_guide.rows.*'    => 'array',
            'size_guide.rows.*.*'  => 'nullable|string|max:30',
            'size_guide.note'      => 'nullable|string|max:300',
        ]);

        $guide   = $data['size_guide'];
        $columns = array_values($guide['columns']);
        $rows    = collect($guide['rows'])
            ->map(fn ($r) => array_slice(array_pad(array_map(fn ($c) => trim((string) $c), array_values($r)), count($columns), ''), 0, count($columns)))
            ->filter(fn ($r) => trim(implode('', $r)) !== '')
            ->values()
            ->all();

        $product->update(['size_guide' => [
            'unit'    => $guide['unit'] ?? null,
            'columns' => $columns,
            'rows'    => $rows,
            'note'    => $guide['note'] ?? null,
        ]]);

        $this->forgetProductCache($product);

        return response()->json(['success' => true, 'message' => 'Size guide saved']);
    }

    /** Clears any cached API response for this product (see APIProductController::show). */
    protected function forgetProductCache(Product $product): void
    {
        Cache::forget("api_product_detail_{$product->id}");
    }
}
