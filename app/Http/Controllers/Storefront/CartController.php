<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Website cart (session-based; guests can shop before signing in). */
class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function show()
    {
        try {
            $summary = $this->cart->summary();
        } catch (ValidationException $e) {
            // e.g. a product was deleted between page loads; summary() drops those lines
            $summary = $this->cart->summary();
        }
        return view('store.cart', ['cart' => $summary]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'   => 'required|integer',
            'variation_id' => 'nullable|integer',
            'quantity'     => 'nullable|integer|min:1|max:' . CartService::MAX_QTY,
        ]);

        try {
            $this->cart->add((int) $data['product_id'], $data['variation_id'] ?? null, (int) ($data['quantity'] ?? 1));
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Could not add this item.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('store_error', $msg);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Added to cart',
                'count'    => $this->cart->count(),
                'cart_url' => route('store.cart'),
            ]);
        }
        return redirect()->route('store.cart')->with('store_success', 'Added to cart');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'key'      => 'required|string|max:40|regex:/^\d+:\d+$/',
            'quantity' => 'required|integer|min:0|max:' . CartService::MAX_QTY,
            'step'     => 'nullable|integer|in:-1,1',   // the − / + buttons
        ]);
        $this->cart->setQuantity($data['key'], max(1, (int) $data['quantity'] + (int) ($data['step'] ?? 0)));
        return redirect()->route('store.cart');
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['key' => 'required|string|max:40|regex:/^\d+:\d+$/']);
        $this->cart->remove($data['key']);
        return redirect()->route('store.cart')->with('store_success', 'Item removed from your cart');
    }
}
