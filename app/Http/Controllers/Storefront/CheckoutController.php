<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\APIOrderController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Address;
use App\Models\Order;
use App\Services\OrderPricingService;
use App\Services\Storefront\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Website checkout. Orders, delivery options and payments go through the very
 * same controllers the app calls (APIOrderController, PaymentController), so
 * pricing, stock checks, couriers, notifications and payment verification are
 * identical for web and app orders. The `store` middleware makes Auth::id()
 * the signed-in shopper for these calls.
 */
class CheckoutController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function show(Request $request)
    {
        $summary = $this->cart->summary();
        if (empty($summary['lines'])) {
            return redirect()->route('store.cart');
        }
        if ($summary['problems']) {
            return redirect()->route('store.cart')->with('store_error', 'Some items in your cart need your attention before checkout.');
        }

        $addresses = Address::where('user_id', Auth::id())->orderByDesc('is_default')->latest()->get();
        $selected  = $addresses->firstWhere('id', (int) $request->query('address')) ?? $addresses->first();

        $gw = $this->json(app(PaymentController::class)->gateways());
        $gateways = $gw['data']['gateways'] ?? [];

        return view('store.checkout', [
            'cart'      => $summary,
            'addresses' => $addresses,
            'selected'  => $selected,
            'gateways'  => $gateways,
            'defaultGw' => $gw['data']['default'] ?? null,
            'user'      => Auth::user(),
        ]);
    }

    /** POST /checkout/delivery-options {address_id} → same JSON as the app's endpoint. */
    public function deliveryOptions(Request $request)
    {
        $data = $request->validate(['address_id' => 'required|integer']);
        $sub = Request::create('/api/checkout/delivery-options', 'POST', [
            'address_id' => $data['address_id'],
            'items'      => $this->cart->orderItems(),
        ]);
        $sub->setUserResolver(fn () => Auth::user());

        try {
            return app(APIOrderController::class)->deliveryOptions($sub, app(\App\Services\Logistics\LogisticsService::class));
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            Log::error('Store delivery options failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Delivery options are not available right now. Please try again.'], 503);
        }
    }

    /** POST /checkout/place → create the order, start payment, send the shopper to the gateway. */
    public function place(Request $request)
    {
        $data = $request->validate([
            'address_id'         => 'required|integer',
            'delivery_option_id' => 'nullable|string|max:40',
            'delivery_fee_payer' => 'nullable|in:now,on_delivery',
            'payment_method'     => 'required|string|max:50',
        ], [
            'address_id.required'     => 'Please add or choose a delivery address.',
            'payment_method.required' => 'Please choose how you want to pay.',
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        $address = Address::where('id', $data['address_id'])->where('user_id', Auth::id())->first();
        if (!$address) {
            return back()->with('store_error', 'Please choose a delivery address.');
        }

        $addr = array_filter([
            'name'            => $address->name ?: trim(Auth::user()->first_name . ' ' . Auth::user()->last_name),
            'street'          => $address->street,
            'city'            => $address->city,
            'state'           => $address->state,
            'lga'             => $address->lga,
            'landmark'        => $address->landmark,
            'address_type'    => $address->address_type,
            'alternate_phone' => $address->alternate_phone,
            'postal_code'     => $address->postal_code,
            'country'         => $address->country ?: 'Nigeria',
            'phone_number'    => $address->phone_number,
            'latitude'        => $address->latitude,
            'longitude'       => $address->longitude,
        ], fn ($v) => $v !== null && $v !== '');

        $payload = [
            'payment_method'                   => $data['payment_method'],
            'shipping_address'                 => $addr,
            'billing_address_same_as_shipping' => true,
            'items'                            => $this->cart->orderItems(),
            'delivery_option_id'               => $data['delivery_option_id'] ?? null,
            'delivery_fee_payer'               => $data['delivery_fee_payer'] ?? 'now',
            'channel'                          => 'app',
        ];

        // 1) Create the order (same code path as the app)
        $sub = Request::create('/api/orders', 'POST', array_filter($payload, fn ($v) => $v !== null));
        $sub->setUserResolver(fn () => Auth::user());
        try {
            $res = $this->json(app(APIOrderController::class)->store($sub, app(OrderPricingService::class)));
        } catch (ValidationException $e) {
            return back()->with('store_error', collect($e->errors())->flatten()->first() ?: 'Please check your order and try again.');
        }

        if (empty($res['success']) || empty($res['order']['id'])) {
            $msg = $res['message'] ?? 'We could not place your order. Please try again.';
            if (!empty($res['errors'])) {
                $msg = collect($res['errors'])->flatten()->first() ?: $msg;
            }
            return back()->with('store_error', $msg);
        }

        $orderId = $res['order']['id'];
        $this->cart->clear();   // the order now holds the items; unpaid orders can be paid from My orders

        if (!empty($res['price_changed'])) {
            session()->flash('store_notice', $res['message']);
        }

        return $this->startPayment($orderId, $data['payment_method']);
    }

    /** POST /account/orders/{id}/pay — retry payment for an unpaid order. */
    public function pay(Request $request, string $id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);
        if ($order->isPaid()) {
            return redirect()->route('store.account.order', $order->id)->with('store_notice', 'This order is already paid.');
        }
        if ($order->status === 'cancelled') {
            return redirect()->route('store.account.order', $order->id)->with('store_error', 'This order was cancelled.');
        }
        $gateway = $request->input('payment_method') ?: $order->payment_method;
        return $this->startPayment($order->id, $gateway);
    }

    private function startPayment(string $orderId, ?string $gateway)
    {
        $sub = Request::create('/api/payment/initialize', 'POST', array_filter([
            'order_id' => $orderId,
            'email'    => Auth::user()->email,
            'gateway'  => in_array($gateway, ['paystack', 'opay'], true) ? $gateway : null,
        ]));
        $sub->setUserResolver(fn () => Auth::user());

        $res = $this->json(app(PaymentController::class)->initializePayment($sub));
        $url = $res['data']['authorization_url'] ?? null;

        if (empty($res['success']) || !$url) {
            return redirect()->route('store.account.order', $orderId)
                ->with('store_error', ($res['message'] ?? 'We could not start the payment.') . ' Your order is saved. You can pay for it from this page.');
        }

        // Paystack/OPay send the browser back to the app's callback page; that page
        // recognises this reference and forwards the shopper to checkoutReturn().
        session([
            'store_pay_ref'   => $res['data']['reference'],
            'store_pay_order' => $orderId,
        ]);

        return redirect()->away($url);
    }

    /** GET /checkout/return?reference=… — back from the payment page. */
    public function paymentReturn(Request $request)
    {
        $reference = (string) $request->query('reference', session('store_pay_ref', ''));
        $orderId   = session('store_pay_order');

        if ($reference === '') {
            return redirect()->route('store.account.orders');
        }

        $sub = Request::create('/api/payment/verify', 'POST', ['reference' => $reference]);
        $sub->setUserResolver(fn () => Auth::user());
        $res = $this->json(app(PaymentController::class)->verifyPayment($sub));

        session()->forget(['store_pay_ref', 'store_pay_order']);

        $orderId = $orderId ?: (\App\Models\Transaction::where('reference', $reference)->value('order_id'));
        $target  = $orderId ? route('store.account.order', $orderId) : route('store.account.orders');

        if (!empty($res['success'])) {
            return redirect($target)->with('store_success', 'Payment received. Thank you! Your order is being prepared.');
        }
        return redirect($target)->with('store_error', ($res['message'] ?? 'Payment was not completed.') . ' You can try again from this page.');
    }

    /** Decode a controller's JsonResponse into an array. */
    private function json($response): array
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response->getData(true) ?: [];
        }
        return [];
    }
}
