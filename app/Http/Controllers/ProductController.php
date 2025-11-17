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
            
            // Tambahkan image URL dari base64 data
            $products->each(function($product) {
                if ($product->image_data) {
                    $product->image_url = $product->image_data; // Base64 data URL
                }
            });
            
            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Product index error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve products', 500);
        }
    }

    // Helper function untuk validasi dan convert base64 image
    private function processBase64Image($base64String)
    {
        if (!$base64String) {
            return null;
        }

        // Validasi format base64 data URL (data:image/...;base64,...)
        if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $base64String)) {
            throw new \Exception('Invalid image format. Only JPEG, PNG, and GIF are allowed.');
        }

        // Extract base64 data
        $imageData = explode(',', $base64String, 2)[1];
        $decodedImage = base64_decode($imageData);

        if ($decodedImage === false) {
            throw new \Exception('Failed to decode base64 image.');
        }

        // Validasi ukuran maksimal 2MB (2097152 bytes)
        $imageSize = strlen($decodedImage);
        if ($imageSize > 2097152) {
            throw new \Exception('Image size exceeds maximum limit of 2MB.');
        }

        // Validasi minimum size (optional, untuk memastikan bukan file kosong)
        if ($imageSize < 100) {
            throw new \Exception('Image file is too small.');
        }

        // Return base64 data URL as is (for storing in database)
        return $base64String;
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
                'image'          => 'nullable|string', // Base64 image data URL
            ]);

            // Mulai transaction
            DB::beginTransaction();

            // Proses gambar base64 jika ada
            $productData = collect($validated)->except(['image'])->toArray();
            
            if ($request->has('image') && $request->input('image')) {
                $productData['image_data'] = $this->processBase64Image($request->input('image'));
            }

            // Product akan menggunakan auto-generated ID dari model
            $product = Product::create($productData);
            
            // Tambahkan image URL dari base64 data
            if ($product->image_data) {
                $product->image_url = $product->image_data;
            }

            DB::commit();
            return $this->successResponse($product, 'Product created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            DB::rollBack();
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
            
            // Tambahkan image URL dari base64 data
            if ($product->image_data) {
                $product->image_url = $product->image_data;
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
        
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'selling_price' => 'sometimes|required|numeric|min:0',
                'capital_price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,category_id',
                'stock' => 'sometimes|required|integer|min:0',
                'image' => 'nullable|string', // Base64 image data URL
            ]);

            DB::beginTransaction();
        
            // Hanya ambil data yang perlu diupdate, jangan include product_id
            $productData = collect($request->only([
                'name', 'description', 'selling_price', 'capital_price', 
                'category_id', 'stock'
            ]))->toArray();
            
            // Proses gambar base64 jika ada
            if ($request->has('image')) {
                if ($request->input('image')) {
                    // Update dengan gambar baru
                    $productData['image_data'] = $this->processBase64Image($request->input('image'));
                } else {
                    // Hapus gambar jika image kosong/null
                    $productData['image_data'] = null;
                }
            }
        
            // Update produk tanpa mengubah product_id
            $product->update($productData);
            
            // Refresh model untuk mendapatkan data terbaru
            $product->fresh();
            
            // Tambahkan image URL dari base64 data
            if ($product->image_data) {
                $product->image_url = $product->image_data;
            }

            DB::commit();
            return $this->successResponse($product, 'Product updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Product not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            DB::rollBack();
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
            if (!$product->image_data) {
                return response()->json([
                    'message' => 'Product has no image to remove.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Update product record - hapus image_data
            $product->image_data = null;
            $product->save();
            
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