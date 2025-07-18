<?php

namespace App\Http\Controllers;

use App\Models\RestockCounter;
use App\Models\RestockCounterItem;
use App\Models\CounterProductStock;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceProduct;
use App\Models\ReturnPurchaseInvoice;
use App\Models\Transaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestockCounterController extends Controller
{
    //create purchaseInvoice controller method
    public function createRestockCounter(Request $request): JsonResponse
    {
        
        try {
            DB::beginTransaction();
            $userId = $request->get('data')['sub'] ?? null;
            
            if (!$userId) {
                return response()->json(['error' => 'Unauthorized: No user ID found in token'], 401);
            }

            $counterId = $request->input('counterId');
            $note = $request->input('note');
            $restockItems = $request->input('restockCounterProduct');

            $totalAmount = 0;
            $totalQuantity = 0;

            foreach ($restockItems as $item) {
                $total = (float)$item['productPurchasePrice'] * (int)$item['productQuantity'];
                $totalAmount += $total;
                $totalQuantity += (int)$item['productQuantity'];
            }

            // 1. Create the restock counter record
            $restock = RestockCounter::create([
                'user_id' => $userId,
                'counter_id' => $counterId,
                'total_amount' => $totalAmount,
                'total_quantity' => $totalQuantity,
                'note' => $note,
            ]);

            // 2. Store each item in restock_counter_items
            foreach ($restockItems as $item) {
                $productId = (int)$item['productId'];
                $quantity = (int)$item['productQuantity'];
                $purchasePrice = (float)$item['productPurchasePrice'];
                $salePrice = (float)$item['productSalePrice'];
                $subTotal = $quantity * $purchasePrice;

                RestockCounterItem::create([
                    'restock_counter_id' => $restock->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'sub_total' => $subTotal,
                ]);

                // 3. Update or insert counter_product_stock
                $stock = CounterProductStock::firstOrNew([
                    'counter_id' => $counterId,
                    'product_id' => $productId,
                ]);

                $stock->current_quantity = ($stock->current_quantity ?? 0) + $quantity;
                $stock->save();
            }

            // update product table
            Product::where('id', $productId)->decrement('productQuantity', $quantity);

            DB::commit();

            return response()->json(['message' => 'Restock created successfully', 'restock' => $restock], 201);
        } catch (Exception $err) {
            DB::rollBack();
            Log::error('Restock creation failed', ['error' => $err->getMessage()]);
            return response()->json(['error' => 'An error occurred during restocking. Please try again later.'], 500);
        }
    }


    // get all the purchaseInvoice controller method
    public function getAllRestockCounter(Request $request): JsonResponse
    {
        Log::info("request getAllRestockCounter", $request->all());
        if ($request->query('query') === 'info') {
            try {
                $aggregation = PurchaseInvoice::selectRaw('COUNT(id) as id, SUM(totalAmount) as totalAmount, SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, SUM(discount) as discount')
                    ->first();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'purchase')
                    ->where(function ($query) {
                        $query->orWhere('creditId', 1)
                            ->orWhere('creditId', 2);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the discountEarned amount
                $totalDiscountAmount = Transaction::where('type', 'purchase')
                    ->where('creditId', 13)
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transactions returnPurchaseInvoice amount
                $paidAmountReturn = Transaction::where('type', 'purchase_return')
                    ->where(function ($query) {
                        $query->orWhere('debitId', 1)
                            ->orWhere('debitId', 2);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // get return purchaseInvoice information with products of this purchase invoice
                $totalReturnAmount = ReturnPurchaseInvoice::selectRaw('COUNT(id) as id, SUM(totalAmount) as totalAmount')
                    ->first();

                $dueAmount = $aggregation->totalAmount -
                    $aggregation->discount -
                    $totalPaidAmount->amount -
                    $totalDiscountAmount->amount -
                    $totalReturnAmount->totalAmount +
                    $paidAmountReturn->amount;

                $result = [
                    '_count' => [
                        'id' => $aggregation->id
                    ],
                    '_sum' => [
                        'totalAmount' => $aggregation->totalAmount,
                        'dueAmount' => $dueAmount,
                        'paidAmount' => $aggregation->paidAmount,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting PurchaseInvoice. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $allPurchase = PurchaseInvoice::where('id', $request->query('purchase'))
                    ->with('purchaseInvoiceProduct')
                    ->orderBy('id', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allPurchase->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting PurchaseInvoice. Please try again later.'], 500);
            }
        } else {
           try {
        $pagination = getPagination($request->query());
        $counterId = $request->query('counterId');

        $startDate = Carbon::parse($request->query('startdate'));
        $endDate = Carbon::parse($request->query('enddate'));

        // ====== Aggregations ======
        $aggregations = RestockCounter::where('counter_id', $counterId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(id) as id, SUM(total_amount) as totalAmount, SUM(total_quantity) as totalQuantity')
            ->first();

        // ====== All Restock Entries ======
        $restockCounters = RestockCounter::with('user:id,username', 'items.product:id,name')
            ->where('counter_id', $counterId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('id')
            ->get();

        $totalCounterProductStock = CounterProductStock::where('counter_id', $counterId)->sum('current_quantity');

        $counted = $restockCounters->count('id');
        $totalAmount = $restockCounters->sum('total_amount');
        $totalQuantity = $restockCounters->sum('total_quantity');

        $modifiedData = collect($restockCounters)->skip($pagination['skip'])->take($pagination['limit']);

        $converted = arrayKeysToCamelCase($modifiedData->toArray());

        // ====== All Product Stocks at This Counter ======
        $productStocks = CounterProductStock::with('product:id,name,productQuantity')
            ->where('counter_id', $counterId)
            ->get();

        $products = $productStocks->map(function ($item) {
            return [
                'productId' => $item->product_id,
                'productName' => $item->product->name ?? '',
                'currentQuantity' => $item->current_quantity,
            ];
        });

        // ====== Final Aggregation ======
        $finalAggregations = [
            '_count' => [
                'id' => $counted,
            ],
            '_sum' => [
                'totalAmount' => $totalAmount,
                'totalQuantity' => $totalQuantity,
                'totalAvlStock' => $totalCounterProductStock,
            ],
        ];

        return response()->json([
            'aggregations' => $finalAggregations,
            'allRestockCounter' => $converted,
            'allCounterProducts' => $products,
        ], 200);
    } catch (Exception $err) {
        Log::error("Restock fetch error", [
        'message' => $err->getMessage(),
        'trace' => $err->getTraceAsString(), // optional but helpful
    ]);
        return response()->json([
            'error' => 'An error occurred while retrieving counter stock and restock data.',
            'message' => $err->getMessage(),
        ], 500);
    }
        }
    }

    // get a single purchaseInvoice controller method
    public function getSingleRestockCounter(Request $request, $id): JsonResponse
    {
        try {
            // get single purchase invoice information with products
            $singlePurchaseInvoice = PurchaseInvoice::where('id', $id)
                ->with('purchaseInvoiceProduct.product', 'supplier')
                ->first();

            // get all transactions related to this purchase invoice
            $transactions = Transaction::where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('type', 'purchase')
                        ->orWhere('type', 'purchase_return');
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the paidAmount
            $transactions2 = Transaction::where('relatedId', $id)
                ->where('type', 'purchase')
                ->where(function ($query) {
                    $query->orWhere('creditId', 1)
                        ->orWhere('creditId', 2);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the discountEarned amount
            $transactions3 = Transaction::where('relatedId', $id)
                ->where('type', 'purchase')
                ->where('creditId', 13)
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transactions returnPurchaseInvoice amount
            $transactions4 = Transaction::where('relatedId', $id)
                ->where('type', 'purchase_return')
                ->where(function ($query) {
                    $query->orWhere('debitId', 1)
                        ->orWhere('debitId', 2);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // get return purchaseInvoice information with products of this purchase invoice
            $returnPurchaseInvoice = ReturnPurchaseInvoice::where('purchaseInvoiceId', $id)
                ->with('returnPurchaseInvoiceProduct', 'returnPurchaseInvoiceProduct.product')
                ->get();

            // sum of total paid amount
            $totalPaidAmount = $transactions2->sum('amount');

            // sum of total discount earned amount
            $totalDiscountAmount = $transactions3->sum('amount');

            // sum of total return purchase invoice amount
            $paidAmountReturn = $transactions4->sum('amount');

            // sum total amount of all return purchase invoice related to this purchase invoice
            $totalReturnAmount = $returnPurchaseInvoice->sum('totalAmount');


            $dueAmount = $singlePurchaseInvoice->totalAmount -
                $singlePurchaseInvoice->discount -
                $totalPaidAmount -
                $totalDiscountAmount -
                $totalReturnAmount +
                $paidAmountReturn;


            $status = "UNPAID";
            if ($dueAmount <= (float)0) {
                $status = "PAID";
            }

            $convertedSingleInvoice = arrayKeysToCamelCase($singlePurchaseInvoice->toArray());
            $convertedReturnInvoice = arrayKeysToCamelCase($returnPurchaseInvoice->toArray());
            $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());
            $finalResult = [
                'status' => $status,
                'totalPaidAmount' => $totalPaidAmount,
                'totalReturnAmount' => $totalReturnAmount,
                'dueAmount' => $dueAmount,
                'singlePurchaseInvoice' => $convertedSingleInvoice,
                'returnPurchaseInvoice' => $convertedReturnInvoice,
                'transactions' => $convertedTransactions,
            ];

            return response()->json($finalResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting PurchaseInvoice. Please try again later.'], 500);
        }
    }
}
