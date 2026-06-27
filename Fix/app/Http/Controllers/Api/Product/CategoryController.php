<?php

namespace App\Http\Controllers\Api\Product;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function createCategory(Request $request)
    {
        $validated = $request->validate([
            'category_name' => ['required']
        ]);

        $category = Category::create([
            'category_name' => $validated['category_name']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category berhasil dibuat',
            'data' => $category
        ]);
    }

    public function getCategories()
    {
        $categories = Category::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    public function getCategoryDetail($id)
    {
        $category = Category::find($id);

        if (!$category) {

            return response()->json([
                'status' => false,
                'message' => 'Category tidak ditemukan'
            ], 404);

        }

        return response()->json([
            'status' => true,
            'data' => $category
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {

            return response()->json([
                'status' => false,
                'message' => 'Category tidak ditemukan'
            ], 404);

        }

        $validated = $request->validate([
            'category_name' => ['required']
        ]);

        $category->update([
            'category_name' => $validated['category_name']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category berhasil diupdate',
            'data' => $category
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::find($id);

        if (!$category) {

            return response()->json([
                'status' => false,
                'message' => 'Category tidak ditemukan'
            ], 404);

        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category berhasil dihapus'
        ]);
    }
}