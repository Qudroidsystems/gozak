<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class APIBrandController extends Controller
{
    /**
     * Display a listing of the brands.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Brand::with('categories')->latest();

        // Optional filtering by category_id
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $brands = $query->paginate(20);
        return BrandResource::collection($brands);
    }

    /**
     * Display the specified brand.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $brand = Brand::with('categories')->find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        return new BrandResource($brand);
    }

    /**
     * Store a newly created brand in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'required|string|max:255',
            'categories' => 'array|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $brand = Brand::create([
                'name' => $request->name,
                'logo' => $request->logo,
            ]);

            // Attach categories if provided
            if ($request->has('categories')) {
                $brand->categories()->sync($request->categories);
            }

            DB::commit();

            // Load categories for response
            $brand->load('categories');
            return new BrandResource($brand);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create brand', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified brand in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:brands,name,' . $id,
            'logo' => 'string|max:255',
            'categories' => 'array|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $brand->update([
                'name' => $request->input('name', $brand->name),
                'logo' => $request->input('logo', $brand->logo),
            ]);

            // Sync categories if provided
            if ($request->has('categories')) {
                $brand->categories()->sync($request->categories);
            }

            DB::commit();

            // Load categories for response
            $brand->load('categories');
            return new BrandResource($brand);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update brand', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified brand from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        try {
            DB::beginTransaction();

            // Detach categories from the pivot table
            $brand->categories()->detach();
            $brand->delete();

            DB::commit();
            return response()->json(['message' => 'Brand deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete brand', 'error' => $e->getMessage()], 500);
        }
    }
}