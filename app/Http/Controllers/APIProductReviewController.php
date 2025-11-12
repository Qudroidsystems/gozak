<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class APIProductReviewController extends Controller
{
    /**
     * Display a listing of the product reviews.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ProductReview::with([
                'product' => function ($q) {
                    $q->select('id', 'title', 'thumbnail');
                },
                'user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'profile_image');
                }
            ])
            ->select('id', 'product_id', 'user_id', 'rating', 'comment', 'user_name', 'user_image_url', 'company_comment', 'company_timestamp', 'created_at');

            // Filter by product_id if provided
            if ($request->has('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }

            // Add pagination
            $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $formattedReviews = $reviews->map(function ($review) {
                // Compute user_name from existing fields (fallback to stored or username if needed)
                $userName = $review->user_name ?? 
                    ($review->user && $review->user->first_name && $review->user->last_name 
                        ? $review->user->first_name . ' ' . $review->user->last_name 
                        : ($review->user->username ?? 'Anonymous')) 
                    ?? null;

                // Map profile_image to user_image_url
                $userImageUrl = $review->user_image_url ?? ($review->user->profile_image ?? null);

                return [
                    'id' => $review->id,
                    'user_id' => $review->user_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user_name' => $userName,
                    'timestamp' => $review->created_at->toIso8601String(),
                    'user_image_url' => $userImageUrl,
                    'company_comment' => $review->company_comment,
                    'company_timestamp' => $review->company_timestamp?->toIso8601String(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedReviews,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'total' => $reviews->total(),
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created product review in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string|max:1000',
            'user_name' => 'nullable|string|max:255',
            'user_image_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $review = ProductReview::create([
                'product_id' => $request->product_id,
                'user_id' => $request->user_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'user_name' => $request->user_name,
                'user_image_url' => $request->user_image_url,
            ]);

            DB::commit();

            // Load relationships for response
            $review->load([
                'product' => function ($q) {
                    $q->select('id', 'title', 'thumbnail');
                },
                'user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'profile_image');
                }
            ]);

            // Compute user_name from existing fields (fallback to stored or username if needed)
            $userName = $review->user_name ?? 
                ($review->user && $review->user->first_name && $review->user->last_name 
                    ? $review->user->first_name . ' ' . $review->user->last_name 
                    : ($review->user->username ?? 'Anonymous')) 
                ?? null;

            // Map profile_image to user_image_url
            $userImageUrl = $review->user_image_url ?? ($review->user->profile_image ?? null);

            $reviewData = [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $userName,
                'timestamp' => $review->created_at->toIso8601String(),
                'user_image_url' => $userImageUrl,
                'company_comment' => $review->company_comment,
                'company_timestamp' => $review->company_timestamp?->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $reviewData,
                'message' => 'Review created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add or update a company comment on a review.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCompanyComment(Request $request, $id)
    {
        $review = ProductReview::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $review->update([
                'company_comment' => $request->company_comment,
                'company_timestamp' => now(),
            ]);

            DB::commit();

            // Load relationships for response
            $review->load([
                'product' => function ($q) {
                    $q->select('id', 'title', 'thumbnail');
                },
                'user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'profile_image');
                }
            ]);

            // Compute user_name from existing fields (fallback to stored or username if needed)
            $userName = $review->user_name ?? 
                ($review->user && $review->user->first_name && $review->user->last_name 
                    ? $review->user->first_name . ' ' . $review->user->last_name 
                    : ($review->user->username ?? 'Anonymous')) 
                ?? null;

            // Map profile_image to user_image_url
            $userImageUrl = $review->user_image_url ?? ($review->user->profile_image ?? null);

            $reviewData = [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $userName,
                'timestamp' => $review->created_at->toIso8601String(),
                'user_image_url' => $userImageUrl,
                'company_comment' => $review->company_comment,
                'company_timestamp' => $review->company_timestamp?->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $reviewData,
                'message' => 'Company comment added successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add company comment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified product review.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $review = ProductReview::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'numeric|min:0|max:5',
            'comment' => 'nullable|string|max:1000',
            'user_name' => 'nullable|string|max:255',
            'user_image_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = [
                'rating' => $request->rating ?? $review->rating,
                'comment' => $request->comment ?? $review->comment,
                'user_name' => $request->user_name ?? $review->user_name,
                'user_image_url' => $request->user_image_url ?? $review->user_image_url,
            ];

            $review->update($data);

            DB::commit();

            // Load relationships for response
            $review->load([
                'product' => function ($q) {
                    $q->select('id', 'title', 'thumbnail');
                },
                'user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'profile_image');
                }
            ]);

            // Compute user_name from existing fields (fallback to stored or username if needed)
            $userName = $review->user_name ?? 
                ($review->user && $review->user->first_name && $review->user->last_name 
                    ? $review->user->first_name . ' ' . $review->user->last_name 
                    : ($review->user->username ?? 'Anonymous')) 
                ?? null;

            // Map profile_image to user_image_url
            $userImageUrl = $review->user_image_url ?? ($review->user->profile_image ?? null);

            $reviewData = [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $userName,
                'timestamp' => $review->created_at->toIso8601String(),
                'user_image_url' => $userImageUrl,
                'company_comment' => $review->company_comment,
                'company_timestamp' => $review->company_timestamp?->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $reviewData,
                'message' => 'Review updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product review.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $review = ProductReview::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $review->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review: ' . $e->getMessage(),
            ], 500);
        }
    }
}