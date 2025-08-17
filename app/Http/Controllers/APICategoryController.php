<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class APICategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->select('id', 'name', 'image', 'parent_id', 'is_featured');

        // Apply filters
        if ($request->has('is_featured') && $request->is_featured === 'true') {
            $query->where('is_featured', true);
        }

        // Add pagination
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $categories = $query->paginate($perPage);

        $formattedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name ?? '',
                'image' => $category->image ? url(Storage::url($category->image)) : '',
                'parent_id' => $category->parent_id,
                'is_featured' => $category->is_featured ?? false,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedCategories,
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
            ],
        ]);
    }
}