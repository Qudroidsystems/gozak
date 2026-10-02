<?php
// app/Http/Controllers/PromoBannerController.php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
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
        $this->middleware('permission:View promo_banner|Create promo_banner|Update promo_banner|Delete promo_banner', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create promo_banner', ['only' => ['store']]);
        $this->middleware('permission:Update promo_banner', ['only' => ['update', 'toggleStatus', 'reorder']]);
        $this->middleware('permission:Delete promo_banner', ['only' => ['destroy', 'bulkAction']]);
    }

    /**
     * Display a listing of the promo banners.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Promo Banners';

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

        return view('promo_banners.index', compact('pagetitle', 'analytics'));
    }

    /** Yajra DataTable endpoint (filters: screen, display_style, status). */
    public function data(Request $request)
    {
        $now = now();
        $query = PromoBanner::query()
            ->when($request->filled('screen'), fn ($q) => $q->where('target_screen', $request->screen))
            ->when($request->filled('display_style'), fn ($q) => $q->where('display_style', $request->display_style))
            ->when($request->status === 'active', fn ($q) => $q->where('active', true)
                ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now)))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false))
            ->when($request->status === 'scheduled', fn ($q) => $q->where('active', true)->whereNotNull('starts_at')->where('starts_at', '>', $now))
            ->when($request->status === 'expired', fn ($q) => $q->where('active', true)->whereNotNull('ends_at')->where('ends_at', '<', $now));

        return DataTables::eloquent($query)
            ->addColumn('checkbox', fn ($b) => '<input type="checkbox" class="row-select form-check-input" value="' . $b->id . '">')
            ->addColumn('handle', fn ($b) => '<i class="bi bi-grip-vertical fs-5 pb-drag-handle" title="Drag to reorder"></i>')
            ->addColumn('preview', function ($b) {
                if ($b->image_url) {
                    return '<img src="' . e($b->full_image_url) . '" alt="" class="img-fluid rounded" style="width:120px;height:67px;object-fit:cover;">';
                }
                return '<div style="background:linear-gradient(135deg,' . e($b->gradient_start) . ',' . e($b->gradient_end) . ');width:120px;height:67px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;padding:4px;text-align:center;color:#fff;">'
                    . '<span>' . e(Str::limit($b->badge_text, 15)) . '</span></div>';
            })
            ->addColumn('content', fn ($b) => '<div class="d-flex flex-column">'
                . '<span class="badge bg-dark-subtle text-dark d-inline-block mb-1 align-self-start" style="font-size:10px;">' . e($b->badge_text) . '</span>'
                . '<span class="fw-semibold">' . e(Str::limit($b->title, 40)) . '</span>'
                . '<small class="text-muted">' . e(Str::limit($b->subtitle, 50)) . '</small>'
                . '<small class="text-primary"><i class="bi bi-arrow-right-circle me-1"></i>' . e($b->cta_text)
                . ($b->cta_route ? ' <span class="badge bg-info-subtle text-info ms-1">' . e($b->cta_route) . '</span>' : '') . '</small></div>')
            ->addColumn('style', function ($b) {
                [$name, $cls, $icon] = [
                    'coupon'   => ['Coupon', 'bg-warning-subtle text-warning', 'bi-ticket-perforated'],
                    'voucher'  => ['Voucher', 'bg-info-subtle text-info', 'bi-award'],
                    'gradient' => ['Gradient', 'bg-primary-subtle text-primary', 'bi-palette'],
                ][$b->display_style] ?? ['Auto-cycle', 'bg-secondary-subtle text-secondary', 'bi-shuffle'];
                return '<span class="badge ' . $cls . ' d-inline-flex align-items-center gap-1"><i class="bi ' . $icon . '"></i> ' . $name . '</span>';
            })
            ->addColumn('screen', fn ($b) => '<span class="badge bg-info-subtle text-info"><i class="bi bi-phone me-1"></i>' . e(ucfirst($b->target_screen)) . '</span>')
            ->addColumn('schedule', function ($b) {
                if (!$b->starts_at && !$b->ends_at) {
                    return '<span class="text-muted small"><i class="bi bi-infinity me-1"></i> Always</span>';
                }
                return '<div class="d-flex flex-column">'
                    . '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>From: ' . e($b->starts_at?->format('d M Y H:i') ?? '—') . '</small>'
                    . '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>To: ' . e($b->ends_at?->format('d M Y H:i') ?? '—') . '</small>'
                    . ($b->show_once_daily ? '<span class="badge bg-secondary-subtle text-secondary mt-1 align-self-start" style="font-size:9px;"><i class="bi bi-repeat me-1"></i> Once daily</span>' : '')
                    . '</div>';
            })
            ->addColumn('status', function ($b) use ($now) {
                [$cls, $icon, $text] = ['bg-success-subtle text-success', 'bi-check-circle', 'Active'];
                if (!$b->active) {
                    [$cls, $icon, $text] = ['bg-secondary-subtle text-secondary', 'bi-slash-circle', 'Inactive'];
                } elseif ($b->starts_at && $b->starts_at > $now) {
                    [$cls, $icon, $text] = ['bg-warning-subtle text-warning', 'bi-clock', 'Scheduled'];
                } elseif ($b->ends_at && $b->ends_at < $now) {
                    [$cls, $icon, $text] = ['bg-danger-subtle text-danger', 'bi-clock-history', 'Expired'];
                }
                return '<span class="badge ' . $cls . ' d-inline-flex align-items-center gap-1"><i class="bi ' . $icon . '"></i> ' . $text . '</span>';
            })
            ->editColumn('sort_order', fn ($b) => '<span class="badge bg-secondary-subtle text-secondary">' . (int) $b->sort_order . '</span>')
            ->addColumn('action', fn ($banner) => view('promo_banners.partials.actions', compact('banner'))->render())
            ->filterColumn('content', function ($q, $k) {
                $q->where(fn ($w) => $w->where('title', 'like', "%{$k}%")
                    ->orWhere('badge_text', 'like', "%{$k}%")
                    ->orWhere('subtitle', 'like', "%{$k}%")
                    ->orWhere('cta_text', 'like', "%{$k}%"));
            })
            ->orderColumn('content', 'title $1')
            ->rawColumns(['checkbox', 'handle', 'preview', 'content', 'style', 'screen', 'schedule', 'status', 'sort_order', 'action'])
            ->toJson();
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

            // offset = position of the first row on the current DataTable page,
            // so reordering page 2 doesn't collide with page 1
            $offset = max(0, (int) $request->input('offset', 0));
            foreach ($request->ids as $order => $id) {
                PromoBanner::where('id', $id)->update(['sort_order' => $offset + $order]);
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
