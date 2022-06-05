<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
// use Image;
use Intervention\Image\ImageManagerStatic as Image;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $params = $request->all();
        $variants = Variant::with('product_variants')->get();

        $date = $request->date;
        $price_from = $request->price_from;
        $price_to = $request->price_to;
        $variant = $request->variant;
        $title = $request->title;

        $products = Product::when(request()->filled('date'), function ($query) use ($date) {
            return $query->whereDate('created_at', $date);
        })->when(request()->filled('title'), function ($query) use ($title) {
            return $query->where('title', "LIKE", "%" . $title . "%");
        })->when(request()->filled('price_from'), function ($query) use ($price_from) {
            return $query->where(function ($query1) use ($price_from) {
                $query1->whereHas('variants', function ($query2) use ($price_from) {
                    return $query2->where('price', '>=', $price_from);
                });
            });
        })->when(request()->filled('price_to'), function ($query) use ($price_to) {
            return $query->where(function ($query1) use ($price_to) {
                $query1->whereHas('variants', function ($query2) use ($price_to) {
                    return $query2->where('price', '<=', $price_to);
                });
            });
        })->when(request()->filled('variant'), function ($query) use ($variant) {
            return  $query->where(function ($query1) use ($variant) {
                $query1->whereHas('variants.variant_one', function ($query2) use ($variant) {
                    return $query2->where('id', $variant);
                })->orWhereHas('variants.variant_two', function ($query2) use ($variant) {
                    return $query2->where('id', $variant);
                })->orWhereHas('variants.variant_three', function ($query2) use ($variant) {
                    return $query2->where('id', $variant);
                });
            });
        })->with(['variants', 'variants.variant_one', 'variants.variant_two', 'variants.variant_three'])->orderBy('id', 'DESC')->paginate(3);

        return view('products.index', compact('products', 'variants', 'params'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => "required",
            'sku' => "required|unique:products,sku",
            'description' => "required",
            'product_image' => "required|array",
            'product_variant' => "required|array",
            'product_variant_prices' => "required|array",
        ]);

        // create a new product first
        $product  = new Product();
        $product->title = $request->title;
        $product->sku = $request->sku;
        $product->description = $request->description;
        $product->save();


        // store product variants in an array to use to store product variant price
        $product_variants = [];
        foreach ($request->product_variant as $key => $variant) {
            foreach ($variant['tags'] as $tag) {
                $productVariant = new ProductVariant();
                $productVariant->variant_id = $variant['option'];
                $productVariant->variant = $tag;
                $productVariant->product_id = $product->id;
                $productVariant->save();
                $product_variants[$key][$tag] = $productVariant;
            }
        }

        // store product variant price
        foreach ($request->product_variant_prices as $product_variant_price) {
            $productVariantPrice = new ProductVariantPrice();
            $productVariantPrice->stock = $product_variant_price['stock'];
            $productVariantPrice->price = $product_variant_price['price'];
            $productVariantPrice->product_id = $product->id;
            foreach (explode('/', $product_variant_price['title']) as $key => $variant) {
                if ($key === 0) {
                    $productVariantPrice->product_variant_one = $product_variants[$key][$variant]->id ?? null;
                } elseif ($key === 1) {
                    $productVariantPrice->product_variant_two = $product_variants[$key][$variant]->id ?? null;
                } elseif ($key === 2) {
                    $productVariantPrice->product_variant_three = $product_variants[$key][$variant]->id ?? null;
                }
            }
            $productVariantPrice->save();
        }
        // product images store
        foreach ($request->product_image as $file) {
            try {
                $photo = Image::make($file);
                $image_parts = explode(";base64,", $file);
                $image_type_aux = explode("image/", $image_parts[0]);
                $file_name = time() . rand('0000', '9999') . '.' . $image_type_aux[1];

                $photo->resize(40, 40)->save(storage_path('app/public/product-images/' . $file_name, 50), 777, true);
                $photo->resize(40, 40)->save(storage_path('app/public/product-thumbnails/' . $file_name, 50), 777, true);


                $productImage = new ProductImage();
                $productImage->file_path = $file_name;
                // $productImage->thumbnail = $file_name;
                $productImage->product_id = $product->id;
                $productImage->save();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        return response()->json(['message' => "Successfully created product."], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $product->load(['images', 'variants', 'variants.variant_one', 'variants.variant_two', 'variants.variant_three'])->get();
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'title' => "required",
            'sku' => "required|unique:products,sku," . $product->id,
            'description' => "required",
            'product_variant' => "required|array",
            'product_variant_prices' => "required|array",
        ]);

        // delete previous product variant first
        $product->load([
            'variants.variant_one' => function ($query) {
                $query->delete();
            },
            'variants.variant_one' => function ($query) {
                $query->delete();
            },
            'variants.variant_one' => function ($query) {
                $query->delete();
            },
            'variants' => function ($query) {
                $query->delete();
            }
        ]);

        // update product data
        $product->title = $request->title;
        $product->sku = $request->sku;
        $product->description = $request->description;
        $product->save();

        // store product variants in an array to use to store product variant price
        $product_variants = [];
        foreach ($request->product_variant as $key => $variant) {
            foreach ($variant['tags'] as $tag) {
                $productVariant = new ProductVariant();
                $productVariant->variant_id = $variant['option'];
                $productVariant->variant = $tag;
                $productVariant->product_id = $product->id;
                $productVariant->save();
                $product_variants[$key][$tag] = $productVariant;
            }
        }

        // store product variant price
        foreach ($request->product_variant_prices as $product_variant_price) {
            $productVariantPrice = new ProductVariantPrice();
            $productVariantPrice->stock = $product_variant_price['stock'];
            $productVariantPrice->price = $product_variant_price['price'];
            $productVariantPrice->product_id = $product->id;
            foreach (explode('/', $product_variant_price['title']) as $key => $variant) {
                if ($key === 0) {
                    $productVariantPrice->product_variant_one = $product_variants[$key][$variant]->id ?? null;
                } elseif ($key === 1) {
                    $productVariantPrice->product_variant_two = $product_variants[$key][$variant]->id ?? null;
                } elseif ($key === 2) {
                    $productVariantPrice->product_variant_three = $product_variants[$key][$variant]->id ?? null;
                }
            }
            $productVariantPrice->save();
        }
        // product images store
        foreach ($request->product_image as $file) {
            try {
                $photo = Image::make($file);
                $image_parts = explode(";base64,", $file);
                $image_type_aux = explode("image/", $image_parts[0]);
                $file_name = time() . rand('0000', '9999') . '.' . $image_type_aux[1];

                $photo->resize(40, 40)->save(storage_path('app/public/product-images/' . $file_name, 50), 777, true);
                $photo->resize(40, 40)->save(storage_path('app/public/product-thumbnails/' . $file_name, 50), 777, true);


                $productImage = new ProductImage();
                $productImage->file_path = $file_name;
                // $productImage->thumbnail = $file_name;
                $productImage->product_id = $product->id;
                $productImage->save();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        return response()->json(['message' => "Successfully updated product."], 200);
    }

    public function deleteImage($file_name)
    {
        if (file_exists(storage_path('app/public/product-images/' . $file_name))) {
            unlink(storage_path('app/public/product-images/' . $file_name));
            unlink(storage_path('app/public/product-thumbnails/' . $file_name));
        }
        ProductImage::where('file_path', $file_name)->delete();
        return response()->json(['message' => "Successfully deleted image."], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
