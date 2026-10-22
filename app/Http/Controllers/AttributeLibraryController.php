<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin › Products › Attribute Library
 *
 * Set up Size, Colour, Shoe Size … once (with colour swatches), then pick
 * them on any product instead of retyping values.
 */
class AttributeLibraryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View product|Create product|Update product', ['only' => ['index', 'json']]);
        $this->middleware('permission:Create product|Update product', ['only' => ['store', 'update', 'destroy']]);
    }

    public function index()
    {
        $pagetitle  = 'Attribute Library';
        $attributes = Variant::with('values')->withCount('productAttributes')->ordered()->get();

        return view('catalog.attributes', compact('pagetitle', 'attributes'));
    }

    /** Used by the product form to offer library attributes. */
    public function json()
    {
        $attributes = Variant::active()->with('values')->ordered()->get()->map(fn (Variant $v) => [
            'id'           => $v->id,
            'name'         => $v->name,
            'display_type' => $v->display_type,
            'hint'         => $v->hint,
            'values'       => $v->values->map(fn ($val) => [
                'value'     => $val->value,
                'color_hex' => $val->color_hex,
            ])->values(),
        ]);

        return response()->json(['success' => true, 'data' => $attributes]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new Variant());
    }

    public function update(Request $request, Variant $attribute)
    {
        return $this->save($request, $attribute);
    }

    public function destroy(Variant $attribute)
    {
        // Products keep their own copy of names/values, so deleting a library
        // entry never breaks a product — it just un-links it.
        $attribute->delete();

        return response()->json(['success' => true, 'message' => 'Attribute removed from the library']);
    }

    /**
     * Body: name, display_type, hint?, is_active?, values: [{value, color_hex?}, …]
     * (values may also be a comma-separated string)
     */
    protected function save(Request $request, Variant $variant)
    {
        $values = $request->input('values');
        if (is_string($values)) {
            $values = collect(explode(',', $values))
                ->map(fn ($v) => ['value' => trim($v)])
                ->filter(fn ($v) => $v['value'] !== '')
                ->values()
                ->all();
            $request->merge(['values' => $values]);
        }

        $validator = Validator::make($request->all(), [
            'name'               => ['required', 'string', 'max:60',
                function ($attr, $val, $fail) use ($variant) {
                    $exists = Variant::where('slug', Str::slug($val))
                        ->when($variant->exists, fn ($q) => $q->where('id', '!=', $variant->id))
                        ->exists();
                    if ($exists) $fail('An attribute with this name already exists.');
                }],
            'display_type'       => ['required', Rule::in(Variant::DISPLAY_TYPES)],
            'hint'               => 'nullable|string|max:120',
            'is_active'          => 'nullable|boolean',
            'values'             => 'required|array|min:1',
            'values.*.value'     => 'required|string|max:60|distinct:ignore_case',
            'values.*.color_hex' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}([0-9A-Fa-f]{2})?$/'],
        ], [
            'values.required'         => 'Add at least one value.',
            'values.*.value.distinct' => 'Each value can only appear once.',
            'values.*.color_hex.regex'=> 'Colours must be hex codes like #1A237E.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $variant) {
            $variant->fill([
                'name'         => trim($request->name),
                'slug'         => Str::slug($request->name),
                'display_type' => $request->display_type,
                'hint'         => $request->hint,
                'is_active'    => $request->boolean('is_active', true),
            ]);
            if (!$variant->exists) {
                $variant->sort_order = (int) Variant::max('sort_order') + 1;
            }
            $variant->save();

            // Replace values, keeping ids of values that still exist.
            $keep = [];
            foreach (array_values($request->values) as $i => $row) {
                $value = VariantValue::firstOrNew([
                    'variant_id' => $variant->id,
                    'value'      => trim($row['value']),
                ]);
                $value->color_hex  = $row['color_hex'] ?? null;
                $value->sort_order = $i;
                $value->save();
                $keep[] = $value->id;
            }
            $variant->values()->whereNotIn('id', $keep)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Attribute saved',
            'data'    => $variant->fresh('values'),
        ]);
    }
}
