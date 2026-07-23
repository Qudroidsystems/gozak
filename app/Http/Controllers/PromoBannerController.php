<?php
// app/Http/Controllers/PromoBannerController.php

namespace App\Http\Controllers;

use App\Models\PromoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromoBannerController extends Controller
{
    /**
     * Constructor with permission middleware
     */
    public function __construct()
    {
        $this->middleware('permission:View promo_banner|Create promo_banner|Update promo_banner|Delete promo_banner', ['only' => ['index']]);
        $this->middleware('permission:Create promo_banner', ['only' => ['store']]);
        $this->middleware('permission:Update promo_banner', ['only' => ['update', 'toggleStatus']]);
        $this->middleware('permission:Delete promo_banner', ['only' => ['destroy', 'bulkAction']]);
    }

    /**
     * Display a listing of the promo banners.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Promo Banners';

        $query = PromoBanner::query();

        // ─── Search ────────────────────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('badge_text', 'like', "%{$search}%")
                  ->orWhere('subtitle', 'like', "%{$search}%")
                  ->orWhere('cta_text', 'like', "%{$search}%");
            });
        }

        // ─── Screen Filter ────────────────────────────────────────────────────
        if ($request->filled('screen')) {
            $query->where('target_screen', $request->screen);
        }

        // ─── Style Filter ─────────────────────────────────────────────────────
        if ($request->filled('display_style')) {
            $query->where('display_style', $request->display_style);
        }

        // ─── Status Filter ────────────────────────────────────────────────────
        if ($request->filled('status')) {
            $now = now();
            switch ($request->status) {
                case 'active':
                    $query->where('active', true)
                          ->where(function ($q) use ($now) {
                              $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                          })
                          ->where(function ($q) use ($now) {
                              $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                          });
                    break;
                case 'inactive':
                    $query->where('active', false);
                    break;
                case 'scheduled':
                    $query->where('active', true)
                          ->whereNotNull('starts_at')
                          ->where('starts_at', '>', $now);
                    break;
                case 'expired':
                    $query->where('active', true)
                          ->whereNotNull('ends_at')
                          ->where('ends_at', '<', $now);
                    break;
            }
        }

        // ─── Sort Order ──────────────────────────────────────────────────────
        $sortField = $request->input('sort', 'sort_order');
        $sortDirection = $request->input('order', 'asc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['sort_order', 'created_at', 'starts_at', 'ends_at', 'title', 'badge_text'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'sort_order';
        }

        $query->orderBy($sortField, $sortDirection);

        // ─── Paginate ────────────────────────────────────────────────────────
        $banners = $query->paginate(12)->withQueryString();

        // ─── Analytics ────────────────────────────────────────────────────────
        $now = now();
        $analytics = [
            'total' => PromoBanner::count(),
            'active' => PromoBanner::where('active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })
                ->count(),
            'scheduled' => PromoBanner::where('active', true)
                ->whereNotNull('starts_at')
                ->where('starts_at', '>', $now)
                ->count(),
            'expired' => PromoBanner::where('active', true)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now)
                ->count(),
        ];

        return view('promo_banners.index', compact('banners', 'pagetitle', 'analytics'));
    }

    /**
     * Store a newly created promo banner.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validated($request);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('promo_banners', 'public');
                $data['image_url'] = $path;
            }

            $banner = PromoBanner::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Promo banner created successfully',
                'data' => $banner
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified promo banner.
     */
    public function update(Request $request, $id)
    {
        try {
            $banner = PromoBanner::findOrFail($id);
            $data = $this->validated($request, $id);

            if ($request->hasFile('image')) {
                // Delete old image
                if ($banner->image_url) {
                    Storage::disk('public')->delete($banner->image_url);
                }
                $path = $request->file('image')->store('promo_banners', 'public');
                $data['image_url'] = $path;
            }

            $banner->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Promo banner updated successfully',
                'data' => $banner
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified promo banner.
     */
    public function destroy($id)
    {
        try {
            $banner = PromoBanner::findOrFail($id);

            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }

            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promo banner deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete promo banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete promo banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder promo banners (drag-and-drop).
     */
    public function reorder(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:promo_banners,id'
            ]);

            foreach ($request->ids as $order => $id) {
                PromoBanner::where('id', $id)->update(['sort_order' => $order]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banners reordered successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reorder banners: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder banners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle banner active status.
     */
    public function toggleStatus($id)
    {
        try {
            $banner = PromoBanner::findOrFail($id);
            $banner->active = !$banner->active;
            $banner->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'active' => $banner->active
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle banner status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk action for multiple banners.
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:promo_banners,id',
                'action' => 'required|in:activate,deactivate,delete'
            ]);

            $ids = $request->ids;
            $action = $request->action;

            switch ($action) {
                case 'activate':
                    PromoBanner::whereIn('id', $ids)->update(['active' => true]);
                    $message = count($ids) . ' banner(s) activated successfully';
                    break;

                case 'deactivate':
                    PromoBanner::whereIn('id', $ids)->update(['active' => false]);
                    $message = count($ids) . ' banner(s) deactivated successfully';
                    break;

                case 'delete':
                    $banners = PromoBanner::whereIn('id', $ids)->get();
                    foreach ($banners as $banner) {
                        if ($banner->image_url) {
                            Storage::disk('public')->delete($banner->image_url);
                        }
                        $banner->delete();
                    }
                    $message = count($ids) . ' banner(s) deleted successfully';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to perform bulk action: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate the request data.
     */
    private function validated(Request $request, $ignoreId = null): array
    {
        $rules = [
            'badge_text' => 'required|string|max:80',
            'title' => 'required|string|max:120',
            'subtitle' => 'required|string|max:300',
            'cta_text' => 'required|string|max:60',
            'cta_route' => 'nullable|string|max:120',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'lottie_asset' => 'nullable|string|max:255',
            'gradient_start' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'gradient_end' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'target_screen' => 'required|in:home,category,product,offers,all',
            'active' => 'required|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'nullable|integer|min:0',
            'show_once_daily' => 'boolean',

            // ── Temu-style display fields ───────────────────────────────
            'display_style' => 'nullable|in:coupon,voucher,gradient',
            'amount_text' => 'nullable|string|max:50',
            'masked_user' => 'nullable|string|max:50',
            'from_label' => 'nullable|string|max:60',
            'type_label' => 'nullable|string|max:60',
            'date_label' => 'nullable|string|max:30',
            'conditions_text' => 'nullable|string|max:150',
            'announcement_text' => 'nullable|string|max:150',
        ];

        $data = $request->validate($rules);

        // Convert boolean values
        $data['active'] = $request->boolean('active', false);
        $data['show_once_daily'] = $request->boolean('show_once_daily', true);
        $data['sort_order'] = $request->input('sort_order', 0);

        // Empty-string display_style means "auto-cycle" → store as null
        if (($data['display_style'] ?? null) === '') {
            $data['display_style'] = null;
        }

        return $data;
    }
}
