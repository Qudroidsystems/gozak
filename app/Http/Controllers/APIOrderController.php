<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Order;
use App\Models\Orders;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Import Log Facade

class APIOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('sales.product')->get();
        return response()->json($orders);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }



    public function store(Request $request)
    {

        $request->validate([
            'total' => 'required|numeric|min:1',
        ]);

        try {
            $order = Order::create([
                'user_id' => Auth::id(),
                'total' => $request->total,
                'status' => 'pending'
            ]);

            return redirect()->route('dashboard')->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error placing order: ' . $e->getMessage());
        }
    

    }
     /**
      * Calculate the total amount for the order items.
      *
      * @param array $items
      * @return float
      * @throws \Exception
      */
     private function calculateTotalAmount(array $items): float
     {
         $total = 0;

         foreach ($items as $item) {
             $product = Product::find($item['productId']);
             if (!$product) {
                 throw new \Exception("Product with ID {$item['productId']} not found.");
             }

             if (is_null($product->base_price)) {
                //  Log::error("Product price is null", [
                //      'product_id' => $item['productId'],
                //      'product_name' => $product->name ?? 'Unknown',
                //  ]);
                //  throw new \Exception("Product with ID {$item['productId']} has no price.");
             }

             $itemTotal = $product->base_price * $item['quantity'];
             $total += $itemTotal;

             //Log the details for debugging
            //  Log::info("Calculating total for item", [
            //      'product_id' => $item['productId'],
            //      'price' => $product->base_price,
            //      'quantity' => $item['quantity'],
            //      'item_total' => $itemTotal,
            //  ]);
         }

         //Log::info("Total amount calculated: {$total}");

         return $total;
     }
    /**
     * Show an order.
     */
    public function show($id)
    {
        $order = Order::with(['sales.product', 'payment'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order, 200);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
