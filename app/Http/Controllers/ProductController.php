<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $q = Product::query();

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($w) use ($s) {
                    $w->where('name','like',"%{$s}%")
                      ->orWhere('description','like',"%{$s}%");
                });
            }

            $products = $q->with('branches')->orderBy('name')->paginate(20)->withQueryString();

            return view('products.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Product.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString(), 'query'=>$request->all()]);
            return redirect()->back()->with('error','Gagal memuat daftar produk.');
        }
    }

    
    public function create()
    {
        try {
            return view('products.create');
        } catch (\Throwable $e) {
            Log::error('Product.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form produk.');
        }
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',
            'hour' => 'nullable|numeric|min:0', 
            
            'is_exclusive' => 'boolean',
            'snack' => 'boolean',
            'water' => 'boolean',
            'magazine' => 'boolean',
            'custom_exclusive_benefits' => 'nullable|array',
            'custom_exclusive_benefits.*' => 'string|max:255',
            
            'branches' => 'nullable|array',
            'branches.*.name' => 'required_with:branches|string|max:255',
            'branches.*.origin_region' => 'nullable|string|max:255',
            'branches.*.destination_region' => 'nullable|string|max:255',
            'branches.*.duration_minutes' => 'required_with:branches|integer|min:1',
            'branches.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            
            $data['is_exclusive'] = $request->boolean('is_exclusive');
            $data['snack'] = $request->boolean('snack');
            $data['water'] = $request->boolean('water');
            $data['magazine'] = $request->boolean('magazine');

            $product = Product::create(\Illuminate\Support\Arr::except($data, ['branches']));

            
            if ($request->has('branches') && is_array($request->branches)) {
                foreach ($request->branches as $branchData) {
                    $product->branches()->create($branchData);
                }
            }

            DB::commit();
            return redirect()->route('products.index')->with('success','Product dan cabang berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.store error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal membuat product: ' . $e->getMessage());
        }
    }

    
    public function show(Product $product)
    {
        try {
            return view('products.show', compact('product')); 
        } catch (\Throwable $e) {
            Log::error('Product.show error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka produk.');
        }
    }

    
    public function edit(Product $product)
    {
        try {
            return view('products.edit', compact('product'));
        } catch (\Throwable $e) {
            Log::error('Product.edit error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form edit produk.');
        }
    }

    
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:2000',
            'hour' => 'nullable|numeric|min:0',
             
             'is_exclusive' => 'boolean',
             'snack' => 'boolean',
             'water' => 'boolean',
             'magazine' => 'boolean',
             'custom_exclusive_benefits' => 'nullable|array',
             'custom_exclusive_benefits.*' => 'string|max:255',
             
             'branches' => 'nullable|array',
             'branches.*.id' => 'nullable|integer|exists:product_branches,id',
             'branches.*.name' => 'required_with:branches|string|max:255',
             'branches.*.origin_region' => 'nullable|string|max:255',
             'branches.*.destination_region' => 'nullable|string|max:255',
             'branches.*.duration_minutes' => 'required_with:branches|integer|min:1',
             'branches.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
             
             $data['is_exclusive'] = $request->boolean('is_exclusive');
             $data['snack'] = $request->boolean('snack');
             $data['water'] = $request->boolean('water');
             $data['magazine'] = $request->boolean('magazine');

            $product->update(\Illuminate\Support\Arr::except($data, ['branches']));

            
            
            
            
            
            
            
            if ($request->has('branches') && is_array($request->branches)) {
                $incomingIds = [];
                
                foreach ($request->branches as $branchData) {
                    
                    if (isset($branchData['_destruct']) && $branchData['_destruct'] == 1) {
                        if (isset($branchData['id'])) {
                            
                            $product->branches()->where('id', $branchData['id'])->delete();
                        }
                        continue;
                    }

                    if (isset($branchData['id']) && $branchData['id']) {
                        
                        $b = $product->branches()->find($branchData['id']);
                        if ($b) {
                            $b->update($branchData);
                            $incomingIds[] = $b->id;
                        }
                    } else {
                        
                        $newBranch = $product->branches()->create($branchData);
                        $incomingIds[] = $newBranch->id;
                    }
                }
            }

            DB::commit();
            return redirect()->route('products.index')->with('success','Product diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.update error: '.$e->getMessage(), ['product_id'=>$product->id, 'payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal memperbarui product: ' . $e->getMessage());
        }
    }

    
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            if ($product->orders()->exists()) {
                return redirect()->back()->with('error','Tidak dapat menghapus product yang masih punya order.');
            }

            $product->delete();
            DB::commit();
            return redirect()->route('products.index')->with('success','Product dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.destroy error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal menghapus product.');
        }
    }
}
