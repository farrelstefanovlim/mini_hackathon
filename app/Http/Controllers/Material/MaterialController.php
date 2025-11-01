<?php

namespace App\Http\Controllers\Material;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class MaterialController extends Controller
{
    public function index()
    {
        return view('pages.materials.index');
    }

    public function show(int $id)
    {
        $material = Material::findOrFail($id);
        return view('pages.materials.show', compact('material'));
    }

    public function create()
    {
        return view('pages.materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_path' => 'required|file|mimes:pdf,doc,docx,txt,ppt,pptx',
            'price' => 'nullable|numeric',
            'categories' => 'array|nullable',
            'categories.*' => 'string|max:255'
        ], [
            'title.required' => 'judul harus diisi.',
            'file_path.required' => 'file harus diunggah.',
            'description.string' => 'deskripsi harus berupa teks.',
            'title.max' => 'judul maksimal 255 karakter.',
            'file_path.file' => 'file harus berupa file yang valid.',
            'file_path.mimes' => 'file harus berupa salah satu dari: pdf, doc, docx, txt, ppt, pptx.',
            'price.numeric' => 'harga harus berupa angka.',
        ]);

        // Simpan file
        $filePath = $request->file('file_path')->store('materials');

        // Buat material baru
        $material = Material::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'file_name' => $filePath,
            'price' => $validated['price'] ?? 0,
        ]);

        // Tangani kategori (buat baru jika belum ada)
        if (!empty($validated['categories'])) {
            $categoryIds = [];

            foreach ($validated['categories'] as $categoryName) {
                // Hapus spasi ekstra dan buat kategori jika belum ada
                $category = Category::firstOrCreate([
                    'name' => trim($categoryName)
                ]);

                $categoryIds[] = $category->id;
            }

            // Hubungkan material dengan kategori
            $material->categories()->sync($categoryIds);
        }

        return redirect()->route('materials.index')->with('success', 'Materi berhasil diunggah!');
    }
}
