<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View review', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create review', ['only' => ['store']]);
        $this->middleware('permission:Update review', ['only' => ['update', 'addCompanyComment']]);
        $this->middleware('permission:Delete review', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Reviews Management";
        
        $query = ProductReview::with([
            'product' => function($q) {
                $q->select('id', 'title', 'thumbnail');
            },
            'user' => function($q) {
                $q->select('id', 'first_name', 'last_name', 'email');
            }
        ])->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('rating') && !empty($request->rating)) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhereHas('product', function($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $reviews = $query->paginate(20)->appends($request->all());

        return view('reviews.index', compact('reviews', 'pagetitle'));
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