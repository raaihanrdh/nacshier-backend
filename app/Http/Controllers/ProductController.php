<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ApiResponse;
    // Menampilkan semua produk yang belum dihapus (soft delete)
    public function index()
    {
        try {
            $products = Product::whereNull('deleted_at')->get();
            
            // Tambahkan URL gambar jika ada
            $products->each(function($product) {
                if ($product->image_path) {
                    $product->image_url = asset('storage/' . $product->image_path);
                }
            });
            
            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Product index error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve products', 500);
        }
    }

    // Menyimpan produk baru
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'           => 'required|string|max:255',
                'description'    => 'nullable|string',
                'selling_price'  => 'required|numeric|min:0',
                'capital_price'  => 'required|numeric|min:0',
                'category_id'    => 'required|exists:categories,category_id',
                'stock'          => 'required|integer|min:0',
                'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Mulai transaction
            DB::beginTransaction();

            // Hapus image dari validated data, karena kita akan mengelolanya secara terpisah
            $productData = collect($validated)->except(['image'])->toArray();
            
            // Upload gambar jika ada
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $productData['image_path'] = $imagePath;
            }

            // Product akan menggunakan auto-generated ID dari model
            $product = Product::create($productData);
            
            // Tambahkan URL gambar jika ada
            if ($product->image_path) {
                $product->image_url = asset('storage/' . $product->image_path);
            }

            DB::commit();
            return $this->successResponse($product, 'Product created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file gambar jika sudah diupload tapi gagal save ke database
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            
            Log::error('Error creating product: ' . $e->getMessage());
            return $this->errorResponse('Gagal menyimpan produk: ' . $e->getMessage(), 500);
        }
    }

    // Menampilkan detail produk
    public function show($id)
    {
        try {
            // Gunakan product_id sebagai parameter pencarian
            $product = Product::whereNull('deleted_at')->where('product_id', $id)->firstOrFail();
            
            // Tambahkan URL gambar jika ada
            if ($product->image_path) {
                $product->image_url = asset('storage/' . $product->image_path);
            }
            
            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            Log::error('Product show error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve product', 500);
        }
    }

    // Mengupdate produk
    public function update(Request $request, $id)
    {
        try {
            // Gunakan product_id sebagai parameter pencarian
            $product = Product::whereNull('deleted_at')->where('product_id', $id)->firstOrFail();
        
            // Logging request data
            Log::info('Update Request Data:', $request->all());
            
            // Logging file information
            if ($request->hasFile('image')) {
                Log::info('Image file detected', [
                    'name' => $request->file('image')->getClientOriginalName(),
                    'size' => $request->file('image')->getSize(),
                    'mime' => $request->file('image')->getMimeType()
                ]);
            }
        
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'selling_price' => 'sometimes|required|numeric|min:0',
                'capital_price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,category_id',
                'stock' => 'sometimes|required|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();
        
            // Hanya ambil data yang perlu diupdate, jangan include product_id
            $productData = collect($request->only([
                'name', 'description', 'selling_price', 'capital_price', 
                'category_id', 'stock'
            ]))->toArray();
            
            $oldImagePath = null;
            
            if ($request->hasFile('image')) {
                // Simpan path gambar lama untuk dihapus nanti
                $oldImagePath = $product->image_path;
                
                // Store new image
                $imagePath = $request->file('image')->store('products', 'public');
                $productData['image_path'] = $imagePath;
                
                Log::info('New image stored at:', ['path' => $imagePath]);
            }
        
            // Update produk tanpa mengubah product_id
            $product->update($productData);
            
            // Hapus gambar lama setelah update berhasil
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
            
            // Refresh model untuk mendapatkan data terbaru
            $product->fresh();
            
            // Tambahkan URL gambar
            if ($product->image_path) {
                $product->image_url = asset('storage/' . $product->image_path);
            }

            DB::commit();
            return $this->successResponse($product, 'Product updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Product not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file gambar baru jika sudah diupload tapi gagal update
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            
            Log::error('Error updating product: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengupdate produk: ' . $e->getMessage(), 500);
        }
    }

    // Soft delete produk
    public function destroy($id)
    {
        try {
            // Gunakan product_id sebagai parameter pencarian
            $product = Product::whereNull('deleted_at')->where('product_id', $id)->firstOrFail();
            $product->delete();

            return $this->successResponse(null, 'Product successfully soft deleted');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            Log::error('Product destroy error: ' . $e->getMessage());
            return $this->errorResponse('Gagal menghapus produk: ' . $e->getMessage(), 500);
        }
    }
    
    // Menghapus gambar produk
    public function removeImage($id)
    {
        try {
            // Gunakan product_id sebagai parameter pencarian
            $product = Product::whereNull('deleted_at')->where('product_id', $id)->firstOrFail();
            
            // Pastikan produk memiliki gambar
            if (!$product->image_path) {
                return response()->json([
                    'message' => 'Product has no image to remove.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            $oldImagePath = $product->image_path;
            
            // Update product record
            $product->image_path = null;
            $product->save();
            
            // Hapus file dari storage setelah update berhasil
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
            
            DB::commit();
            
            return $this->successResponse($product, 'Product image successfully removed');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Remove image error: ' . $e->getMessage());
            return $this->errorResponse('Gagal menghapus gambar produk: ' . $e->getMessage(), 500);
        }
    }
}