<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\APIOrderTrackingController;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\WishlistItem;
use App\Services\OrderTrackingService;
use App\Services\Storefront\StoreCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** My account on the website: orders + tracking, saved items, addresses. */
class AccountController extends Controller
{
    // ── Orders ────────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items:id,order_id,product_id,title,image,quantity,price', 'items.product:id,thumbnail'])
            ->latest()
            ->paginate(10);

        return view('store.account.orders', ['orders' => $orders]);
    }

    public function order(string $id, OrderTrackingService $tracking)
    {
        $order = Order::where('user_id', Auth::id())
            ->with(['items.product:id,title,thumbnail', 'shippingAddress'])
            ->findOrFail($id);

        $track = [];
        try {
            $track = $tracking->tracking($order);
        } catch (\Throwable $e) {
            $track = ['steps' => [], 'history' => []];
        }

        return view('store.account.order', [
            'order' => $order,
            'track' => $track,
            'paid'  => $order->isPaid(),
        ]);
    }

    /** POST /account/orders/{id}/received — same as "I got my order" in the app. */
    public function confirmReceived(Request $request, string $id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);
        $res = app(APIOrderTrackingController::class)->confirm($request, $id);
        $data = $res instanceof \Illuminate\Http\JsonResponse ? $res->getData(true) : [];

        return redirect()->route('store.account.order', $order->id)
            ->with(!empty($data['success']) ? 'store_success' : 'store_error', $data['message'] ?? (!empty($data['success']) ? 'Thanks for confirming!' : 'Could not confirm right now.'));
    }

    // ── Saved items ───────────────────────────────────────────────────────────

    public function wishlist(StoreCatalog $catalog)
    {
        $ids = [];
        try {
            $ids = WishlistItem::where('user_id', Auth::id())->latest()->pluck('product_id')->all();
        } catch (\Throwable $e) {
        }
        $products = $ids ? $catalog->query()->whereIn('products.id', $ids)->get()->sortBy(fn ($p) => array_search($p->id, $ids)) : collect();

        return view('store.account.wishlist', ['cards' => $catalog->cards($products)]);
    }

    public function toggleWishlist(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer|exists:products,id']);
        $existing = WishlistItem::where('user_id', Auth::id())->where('product_id', $data['product_id'])->first();
        if ($existing) {
            $existing->delete();
            $msg = 'Removed from saved items';
        } else {
            WishlistItem::create(['user_id' => Auth::id(), 'product_id' => $data['product_id']]);
            $msg = 'Saved. Find it under Account › Saved items';
        }
        return back()->with('store_success', $msg);
    }

    // ── Addresses ─────────────────────────────────────────────────────────────

    public function addresses()
    {
        return view('store.account.addresses', [
            'addresses' => Address::where('user_id', Auth::id())->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function createAddress(Request $request)
    {
        return view('store.account.address-form', [
            'address' => new Address(['country' => 'Nigeria', 'name' => trim(Auth::user()->first_name . ' ' . Auth::user()->last_name), 'phone_number' => Auth::user()->phone_number]),
            'back'    => $request->query('back'),
        ]);
    }

    public function editAddress(Request $request, int $id)
    {
        return view('store.account.address-form', [
            'address' => Address::where('user_id', Auth::id())->findOrFail($id),
            'back'    => $request->query('back'),
        ]);
    }

    public function saveAddress(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'phone_number'    => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'alternate_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'street'          => 'required|string|max:255',
            'landmark'        => 'nullable|string|max:255',
            'city'            => 'required|string|max:255',
            'lga'             => 'nullable|string|max:255',
            'state'           => 'required|string|max:255',
            'address_type'    => 'nullable|in:home,office,other',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'is_default'      => 'nullable|boolean',
            'back'            => 'nullable|in:checkout',
        ], [
            'phone_number.regex' => 'Enter a valid phone number, e.g. 0803 123 4567.',
        ]);

        $address = $id ? Address::where('user_id', Auth::id())->findOrFail($id) : new Address(['user_id' => Auth::id()]);
        $first = !Address::where('user_id', Auth::id())->exists();

        DB::transaction(function () use ($address, $data, $request, $first) {
            $makeDefault = $first || $request->boolean('is_default');
            if ($makeDefault) {
                Address::where('user_id', Auth::id())->update(['is_default' => false]);
            }
            $address->fill(collect($data)->except(['back', 'is_default'])->all() + ['country' => 'Nigeria']);
            $address->user_id = Auth::id();
            $address->is_default = $makeDefault || (bool) $address->is_default;
            $address->save();
        });

        if (($data['back'] ?? null) === 'checkout') {
            return redirect()->route('store.checkout', ['address' => $address->id])->with('store_success', 'Address saved');
        }
        return redirect()->route('store.account.addresses')->with('store_success', 'Address saved');
    }

    public function deleteAddress(int $id)
    {
        $address = Address::where('user_id', Auth::id())->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            Address::where('user_id', Auth::id())->latest()->first()?->update(['is_default' => true]);
        }
        return redirect()->route('store.account.addresses')->with('store_success', 'Address removed');
    }
}
