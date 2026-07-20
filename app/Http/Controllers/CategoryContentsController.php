<?php

namespace App\Http\Controllers;

use App\Models\CategoryContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryContentsController extends Controller
{
    // GET /api/categories
    public function index()
    {
        return response()->json(
            CategoryContent::latest()->get()
        );
    }

    // POST /api/categories
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name'
    ], [
        'name.required' => 'Nama kategori wajib diisi.',
        'name.unique' => 'Kategori sudah ada.'
    ]);

    $category = CategoryContent::create([
        'name' => $request->name,
        'slug' => Str::slug($request->name)
    ]);

    return response()->json([
        'message' => 'Kategori berhasil ditambahkan',
        'category' => $category
    ], 201);
}

    // GET /api/categories/{id}
    public function show(CategoryContent $category)
    {
        return response()->json($category);
    }

    // PUT /api/categories/{id}
    public function update(Request $request, CategoryContent $category)
    {
        $request->validate([
            'name'=>'required|string|max:255|unique:categories,name,'.$category->id
        ]);

        $category->update([
            'name'=>$request->name,
            'slug'=>Str::slug($request->name)
        ]);

        return response()->json([
            'message'=>'Kategori berhasil diubah',
            'category'=>$category
        ]);
    }

    // DELETE /api/categories/{id}
    public function destroy(CategoryContent $category)
    {
        $category->delete();

        return response()->json([
            'message'=>'Kategori berhasil dihapus'
        ]);
    }
}