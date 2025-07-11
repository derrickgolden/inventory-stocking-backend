<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{
    //create a single counter controller method
    public function createSingleCounter(Request $request): JsonResponse
    {
            try {
                $file_paths = $request->file_paths;

                $createdCounter = Counter::create([
                    'name' => $request->input('name'),
                    'counterThumbnailImage' => $file_paths[0] ?? null,
                    'staffId' => (int)$request->input('staffId'),
                    'description' => $request->input('description'),
                ]);


                if ($createdCounter && $request->input('productSubCategoryId')) {
                    $createdCounter->load('productSubCategory');
                }

                $currentAppUrl = url('/');
                if ($createdCounter) {
                    $createdCounter->counterThumbnailImageUrl = "{$currentAppUrl}/counter-image/{$createdCounter->counterThumbnailImage}";
                }

                $converted = arrayKeysToCamelCase($createdCounter->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                 Log::error('Counter Creation Failed', [
                    'message' => $err->getMessage(),
                    'line' => $err->getLine(),
                    'file' => $err->getFile(),
                    'trace' => $err->getTraceAsString(),
                ]);

                return response()->json(['error' => 'An error occurred during create counter.Please try again later.'], 500);
            }
    }

    // get all the product controller method
    public function getAllCounter(Request $request): JsonResponse
    {
        
        if ($request->query('query') === 'all') {
            try {
                $getAllCounter = Counter::orderBy('id', 'desc')
                    ->with('users')
                    ->get();

                $currentAppUrl = url('/');
                collect($getAllCounter)->map(function ($counter) use ($currentAppUrl) {
                    if ($counter->counterThumbnailImage) {
                        $counter->counterThumbnailImageUrl = "{$currentAppUrl}/product-image/{$counter->counterThumbnailImage}";
                    }
                    return $counter;
                });

                $converted = arrayKeysToCamelCase($getAllCounter->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting counter.Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'info') {
            try {
                $aggregation = Product::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'search') {
            try {
                $getAllProduct = Product::orWhere('name', 'LIKE', '%' . $request->query('key') . '%')
                    ->orWhere('sku', 'LIKE', '%' . $request->query('key') . '%')
                    ->with('productSubCategory', 'productColor.color')
                    ->orderBy('id', 'desc')
                    ->get();

                // remove productPurchasePrice
                $getAllProduct->map(function ($item) {
                    unset($item->productPurchasePrice);
                });

                collect($getAllProduct)->map(function ($product) {
                    $totalCount = count($product->reviewRating);

                    if (count($product->reviewRating) > 0) {
                        $product->totalRating = $product->reviewRating->reduce(function ($acc, $curr) {
                            return ($acc + $curr->rating);
                        }, 0) / $totalCount;
                    } else {
                        $product->totalRating = 0;
                    }
                    return $product;
                });

                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->counterThumbnailImage) {
                        $product->counterThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->counterThumbnailImage}";
                    }
                    return $product;
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } elseif ($request->query('status')) {
            try {
                $pagination = getPagination($request->query());
                $getAllProduct = Product::orderBy('id', 'desc')
                    ->where('status', $request->query("status"))
                    ->with('productSubCategory', 'productBrand', 'productColor.color')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                collect($getAllProduct)->map(function ($product) {
                    $totalCount = count($product->reviewRating);

                    if (count($product->reviewRating) > 0) {
                        $product->totalRating = $product->reviewRating->reduce(function ($acc, $curr) {
                            return ($acc + $curr->rating);
                        }, 0) / $totalCount;
                    } else {
                        $product->totalRating = 0;
                    }
                    return $product;
                });

                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->counterThumbnailImage) {
                        $product->counterThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->counterThumbnailImage}";
                    }
                    return $product;
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());
                $finalResult = [
                    'getAllProduct' => $converted,
                    'totalProduct' => Product::where('status', $request->query('status'))->count(),
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else {
            try {
                $pagination = getPagination($request->query());
                $getAllProduct = Product::orderBy('id', 'desc')
                    ->where('status', 'true')
                    ->with('productSubCategory', 'productBrand', 'productColor.color')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                collect($getAllProduct)->map(function ($product) {
                    $totalCount = count($product->reviewRating);

                    if (count($product->reviewRating) > 0) {
                        $product->totalRating = $product->reviewRating->reduce(function ($acc, $curr) {
                            return ($acc + $curr->rating);
                        }, 0) / $totalCount;
                    } else {
                        $product->totalRating = 0;
                    }
                    return $product;
                });

                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->counterThumbnailImage) {
                        $product->counterThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->counterThumbnailImage}";
                    }
                    return $product;
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());
                $finalResult = [
                    'getAllProduct' => $converted,
                    'totalProduct' => Product::where('status', 'true')->count(),
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        }
    }

    // get a single singleProduct controller method
    public function getSingleCounter($id): JsonResponse
    {
        try {
            $singleCounter = Counter::where('id', (int)$id)
                ->with('users')
                ->first();

            $currentAppUrl = url('/');
            if ($singleCounter->counterThumbnailImage) {
                $singleCounter->counterThumbnailImageUrl = "{$currentAppUrl}/product-image/{$singleCounter->counterThumbnailImage}";
            }

            if (!$singleCounter) {
                return response()->json(['error' => 'Counter not found!'], 404);
            }
            $converted = arrayKeysToCamelCase($singleCounter->toArray());

            $finalResult = [
                'singleCounter' => $converted,
            ];

            return response()->json($finalResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting counter.Please try again later.'], 500);
        }
    }

    // update a single product controller method
    public function updateSingleProduct(Request $request, $id): JsonResponse
    {
        try {
            if ($request->hasFile('images')) {
                $file_paths = $request->file_paths;

                $product = Product::where('id', $id)->update([
                    'name' => $request->input('name'),
                    'counterThumbnailImage' => $file_paths[0],
                    'productSubCategoryId' => (int)$request->input('productSubCategoryId'),
                    'productBrandId' => (int)$request->input('productBrandId'),
                    'description' => $request->input('description'),
                    'sku' => $request->input('sku'),
                    'productQuantity' => (int)$request->input('productQuantity'),
                    'productPurchasePrice' => takeUptoThreeDecimal((float)$request->input('productPurchasePrice')),
                    'productSalePrice' => takeUptoThreeDecimal((float)$request->input('productSalePrice')),
                    'unitType' => $request->input('unitType'),
                    'unitMeasurement' => takeUptoThreeDecimal((float)$request->input('unitMeasurement')) ?? null,
                    'reorderQuantity' => (int)$request->input('reorderQuantity') ?? null,
                    'productVat' => takeUptoThreeDecimal((float)$request->input('productVat')) ?? null,
                ]);

                if (!$product) {
                    return response()->json(['error' => 'Failed To Updated Product'], 404);
                }

                return response()->json(['message' => 'Product updated Successfully'], 200);
            }

            $product = Product::where('id', $id)->first();
            $product->update($request->all());

            if (!$product) {
                return response()->json(['error' => 'Failed To Updated Product'], 404);
            }
            
            return response()->json(['message' => 'Product updated Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updated product.Please try again later.'], 500);
        }
    }


    // delete a single product controller method
    public function deleteSingleProduct(Request $request, $id): JsonResponse
    {
        try {
            $deletedProduct = Product::where('id', (int)$id)
                ->update([
                    'status' => $request->input('status'),
                ]);

            if (!$deletedProduct) {
                return response()->json(['error' => 'Failed To Delete Product'], 404);
            }
            return response()->json(['message' => 'Product deleted Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete product.Please try again later.'], 500);
        }
    }
}
