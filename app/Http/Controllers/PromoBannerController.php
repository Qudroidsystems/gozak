<?php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromoBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promo_banner|Create promo_banner|Update promo_banner|Delete promo_banner', ['only' => ['index']]);
        $this->middleware('permission:Create promo_banner', ['only' => ['store']]);
        $this->middleware('permission:Update promo_banner', ['only' => ['update']]);
        $this->middleware('permission:Delete promo_banner', ['only' => ['destroy']]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $pagetitle = 'Promo Banners';
        $banners   = PromoBanner::orderBy('sort_order')->orderBy('created_at', 'desc')->paginate(12);

        return view('promo_banners.index', compact('banners', 'pagetitle'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('promo_banners', 'public');
        }

        PromoBanner::create($data);

        return response()->json(['success' => true, 'message' => 'Promo banner created successfully'], 201);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $banner = PromoBanner::findOrFail($id);
        $data   = $this->validated($request, $id);

        if ($request->hasFile('image')) {
            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $data['image_url'] = $request->file('image')->store('promo_banners', 'public');
        }

        $banner->update($data);

        return response()->json(['success' => true, 'message' => 'Promo banner updated successfully']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $banner = PromoBanner::findOrFail($id);

        if ($banner->image_url) {
            Storage::disk('public')->delete($banner->image_url);
        }

        $banner->delete();

        return response()->json(['success' => true, 'message' => 'Promo banner deleted successfully']);
    }

    // ── Reorder (drag-and-drop from admin) ────────────────────────────────────

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        foreach ($request->ids as $order => $id) {
            PromoBanner::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['success' => true]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function validated(Request $request, $ignoreId = null): array
    {
        $request->validate([
            'badge_text'      => 'required|string|max:80',
            'title'           => 'required|string|max:120',
            'subtitle'        => 'required|string|max:300',
            'cta_text'        => 'required|string|max:60',
            'cta_route'       => 'nullable|string|max:120',
            'image'           => ($ignoreId ? 'nullable' : 'nullable') . '|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'gradient_start'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'gradient_end'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_screen'   => 'required|in:home,category,product,offers,all',
            'active'          => 'required|boolean',
            'starts_at'       => 'nullable|date',
            'ends_at'         => 'nullable|date|after_or_equal:starts_at',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        return $request->only([
            'badge_text', 'title', 'subtitle', 'cta_text', 'cta_route',
            'gradient_start', 'gradient_end', 'accent_color',
            'target_screen', 'active', 'starts_at', 'ends_at', 'sort_order',
        ]);
    }
}
