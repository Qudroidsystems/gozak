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
        $categories = Category::query()
            ->select('id', 'name', 'image', 'parent_id', 'is_featured')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name ?? '',
                    'image' => $category->image ? url(Storage::url($category->image)) : '',
                    'parent_id' => $category->parent_id,
                    'is_featured' => $category->is_featured ?? false,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
