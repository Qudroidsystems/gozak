<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProductReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View review', ['only' => ['index', 'show', 'data', 'edit']]);
        $this->middleware('permission:Create review', ['only' => ['store']]);
        $this->middleware('permission:Update review', ['only' => ['update', 'addCompanyComment']]);
        $this->middleware('permission:Delete review', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Reviews Management";

        $stats = [
            'total'      => ProductReview::count(),
            'average'    => round((float) ProductReview::avg('rating'), 1),
            'unanswered' => ProductReview::whereNull('company_comment')->count(),
            'low'        => ProductReview::where('rating', '<=', 2)->count(),
        ];
        $breakdown = ProductReview::selectRaw('FLOOR(rating) as stars, COUNT(*) as total')
            ->groupBy('stars')->pluck('total', 'stars');

        return view('reviews.index', compact('pagetitle', 'stats', 'breakdown'));
    }

    /** Yajra DataTable endpoint. */
    public function data(Request $request)
    {
        $query = ProductReview::query()
            ->leftJoin('products', 'products.id', '=', 'product_reviews.product_id')
            ->select('product_reviews.*', 'products.title as product_title', 'products.thumbnail as product_thumbnail')
            ->when($request->filled('rating'), fn ($q) => $q->whereRaw('FLOOR(product_reviews.rating) = ?', [(int) $request->rating]))
            ->when($request->reply === 'answered', fn ($q) => $q->whereNotNull('product_reviews.company_comment'))
            ->when($request->reply === 'unanswered', fn ($q) => $q->whereNull('product_reviews.company_comment'))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_reviews.product_id', $request->product_id));

        $user = $request->user();

        return DataTables::eloquent($query)
            ->addColumn('product', function ($r) {
                $img = $r->product_thumbnail
                    ? '<img src="' . e(asset('storage/' . $r->product_thumbnail)) . '" class="gz-thumb" alt="">'
                    : '<span class="gz-avatar"><i class="ri-box-3-line"></i></span>';
                return '<div class="d-flex align-items-center gap-2">' . $img . '<span class="fw-semibold">' . e(Str::limit($r->product_title ?? 'Deleted product', 40)) . '</span></div>';
            })
            ->editColumn('user_name', fn ($r) => e($r->user_name ?: '—') . ($r->location ? '<small class="d-block text-muted">' . e($r->location) . '</small>' : ''))
            ->editColumn('rating', function ($r) {
                $n = (int) round($r->rating);
                return '<span class="text-warning" title="' . e($r->rating) . '">' . str_repeat('★', $n) . '<span class="text-muted">' . str_repeat('☆', max(0, 5 - $n)) . '</span></span>';
            })
            ->editColumn('comment', fn ($r) => '<span title="' . e($r->comment) . '">' . e(Str::limit($r->comment, 80)) . '</span>')
            ->addColumn('reply', fn ($r) => $r->company_comment
                ? '<span class="status-pill st-success">Replied</span>'
                : '<span class="status-pill st-warning">Awaiting reply</span>')
            ->editColumn('created_at', fn ($r) => optional($r->created_at)->format('d M Y'))
            ->addColumn('action', function ($r) use ($user) {
                $h = '<div class="gz-actions"><button class="btn btn-sm btn-soft-info view-btn" data-id="' . $r->id . '" title="View / reply"><i class="ph-chat-circle-text"></i></button>';
                if ($user->can('Delete review')) {
                    $h .= '<button class="btn btn-sm btn-soft-danger delete-btn" data-id="' . $r->id . '" title="Delete"><i class="ph-trash"></i></button>';
                }
                return $h . '</div>';
            })
            ->filterColumn('product', fn ($q, $k) => $q->where('products.title', 'like', "%{$k}%"))
            ->orderColumn('product', 'products.title $1')
            ->rawColumns(['product', 'user_name', 'rating', 'comment', 'reply', 'action'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get user details
            $user = \App\Models\User::find($request->user_id);
            
            $review = ProductReview::create([
                'product_id' => $request->product_id,
                'user_id' => $request->user_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'user_name' => $user->first_name . ' ' . $user->last_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review added successfully',
                'review' => $review->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add review: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $review = ProductReview::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $review->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'review' => $review->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addCompanyComment(Request $request, $id)
    {
        $review = ProductReview::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $review->update([
                'company_comment' => $request->company_comment,
                'company_timestamp' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company comment added successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add company comment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $review = ProductReview::findOrFail($id);
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $review = ProductReview::with(['product', 'user'])->findOrFail($id);

        return response()->json([
            'id' => $review->id,
            'product_id' => $review->product_id,
            'product_title' => $review->product->title,
            'user_id' => $review->user_id,
            'user_name' => $review->user_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'company_comment' => $review->company_comment,
            'created_at' => $review->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}