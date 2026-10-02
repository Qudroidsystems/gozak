<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\StoreSetting;
use App\Services\OrderPricingService;
use App\Services\Payment\OpayGateway;
use App\Support\PaymentGatewayCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Settings › Payment Gateways: admins enter each gateway's test and live keys
 * here (no .env editing). Secrets are encrypted at rest and never sent back
 * to the browser. Ported from the CSS Kabba portal.
 */
class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage payment gateways');
    }

    public function index()
    {
        foreach (PaymentGatewayCatalog::PROVIDERS as $key => $def) {
            PaymentGateway::firstOrCreate(
                ['provider_key' => $key],
                ['name' => $def['name'], 'mode' => 'sandbox', 'is_active' => false, 'config' => []]
            );
        }

        $order    = array_keys(PaymentGatewayCatalog::PROVIDERS);
        $gateways = PaymentGateway::whereIn('provider_key', $order)->get()
            ->sortBy(fn ($g) => array_search($g->provider_key, $order))->values();

        return view('admin.payment-gateways.index', [
            'pagetitle' => 'Payment Gateways',
            'gateways'  => $gateways,
            'checkout'  => app(OrderPricingService::class)->settings(),
            'urls'      => [
                'paystack_webhook'  => route('payment.webhook'),
                'paystack_callback' => route('payment.callback'),
                'opay_webhook'      => route('payment.opay.webhook'),
                'opay_return'       => route('payment.opay.return'),
            ],
        ]);
    }

    /** Save the checkout rates the server prices orders with (tax %, shipping). */
    public function updateCheckout(Request $request)
    {
        $data = $request->validate([
            'tax_rate'                => 'required|numeric|min:0|max:100',
            'shipping_fee'            => 'required|numeric|min:0|max:10000000',
            'free_shipping_threshold' => 'required|numeric|min:0|max:1000000000',
        ]);

        $setting = StoreSetting::first() ?? new StoreSetting(['store_name' => config('app.name', 'GozakMart')]);
        $setting->fill($data);
        $setting->save();
        cache()->forget('store_settings');

        return $this->reply($request, true, 'Checkout settings saved. New orders will use these rates.');
    }

    /** Save mode, on/off and credentials. Blank credential inputs keep the saved value. */
    public function updateConfig(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $fields  = PaymentGatewayCatalog::fields($gateway->provider_key);

        $data = $request->validate([
            'mode'            => 'required|in:sandbox,live',
            'is_active'       => 'nullable|boolean',
            'credentials'     => 'nullable|array',
            'credentials.*'   => 'nullable|array',
            'credentials.*.*' => 'nullable|string|max:1000',
            'clear'           => 'nullable|array',
            'clear.*'         => 'nullable|array',
        ]);

        $errors = [];
        foreach (['test', 'live'] as $set) {
            foreach ($fields as $field => $def) {
                $value = trim((string) ($data['credentials'][$set][$field] ?? ''));

                if (!empty($data['clear'][$set][$field])) {
                    $gateway->putCredential($field, $set, null);
                    continue;
                }
                if ($value === '') {
                    continue; // keep what is saved
                }
                $prefix = $def['prefix'][$set] ?? null;
                if ($prefix && !str_starts_with($value, $prefix)) {
                    $errors[] = ($set === 'live' ? 'Live' : 'Test') . " {$def['label']} should start with \"{$prefix}\".";
                    continue;
                }
                if (str_contains(strtolower($value), 'xxxx')) {
                    $errors[] = ($set === 'live' ? 'Live' : 'Test') . " {$def['label']} looks like a placeholder.";
                    continue;
                }
                $gateway->putCredential($field, $set, $value);
            }
        }

        $gateway->mode      = $data['mode'];
        $gateway->is_active = (bool) ($data['is_active'] ?? false);

        if ($gateway->is_active && !$gateway->isConfigured()) {
            $errors[] = 'Add the ' . ($gateway->mode === 'live' ? 'live' : 'test') . ' keys before switching '
                . $gateway->name . ' on in ' . ($gateway->mode === 'live' ? 'Live' : 'Sandbox') . ' mode.';
        }

        if ($errors) {
            return $this->reply($request, false, implode(' ', $errors), 422);
        }

        $gateway->secret_key = null; // secrets live encrypted in config only
        $gateway->public_key = $gateway->credential('public_key', PaymentGatewayCatalog::set($gateway->mode));
        $gateway->save();

        return $this->reply($request, true, $gateway->name . ' settings saved.', 200, $this->summary($gateway));
    }

    public function toggleGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        if (!$gateway->is_active && !$gateway->isConfigured()) {
            return $this->reply($request, false, 'Add the ' . ($gateway->mode === 'live' ? 'live' : 'test') . ' keys first.', 422);
        }

        $gateway->update(['is_active' => !$gateway->is_active]);

        return $this->reply($request, true, $gateway->name . ' is now ' . ($gateway->is_active ? 'on' : 'off') . '.', 200, $this->summary($gateway));
    }

    /** Check the saved keys against the provider. ?set=test|live (default: current mode). */
    public function testGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $set     = in_array($request->input('set'), ['test', 'live'], true) ? $request->input('set') : PaymentGatewayCatalog::set($gateway->mode);
        $label   = $set === 'live' ? 'live' : 'test';

        if (!$gateway->isConfigured($set)) {
            return response()->json(['success' => false, 'message' => "No complete {$label} keys saved for {$gateway->name}."]);
        }

        try {
            $result = match ($gateway->provider_key) {
                'paystack' => $this->testBearer('https://api.paystack.co/bank?perPage=1', $gateway->credential('secret_key', $set)),
                'opay'     => OpayGateway::testKeys($set, $gateway->credential('merchant_id', $set), $gateway->credential('secret_key', $set)),
                default    => ['success' => true, 'message' => "{$label} details are saved."],
            };
        } catch (\Throwable $e) {
            Log::warning('Gateway test failed', ['gateway' => $gateway->provider_key, 'error' => $e->getMessage()]);
            $result = ['success' => false, 'message' => 'Could not reach ' . $gateway->name . '. Check the server\'s internet connection.'];
        }

        if ($result['success'] && empty($result['plain'])) {
            $result['message'] = "{$gateway->name} accepted the {$label} keys.";
        }
        return response()->json($result);
    }

    protected function testBearer(string $url, string $secret): array
    {
        $res = Http::withToken($secret)->acceptJson()->timeout(20)->get($url);
        if ($res->successful()) {
            return ['success' => true];
        }
        $msg = $res->json('message') ?: ('HTTP ' . $res->status());
        return ['success' => false, 'message' => 'Rejected: ' . $msg, 'plain' => true];
    }

    protected function summary(PaymentGateway $g): array
    {
        $masked = [];
        foreach (['test', 'live'] as $set) {
            foreach (PaymentGatewayCatalog::fields($g->provider_key) as $field => $def) {
                $masked[$set][$field] = $g->maskedCredential($field, $set);
            }
        }
        return [
            'id'              => $g->id,
            'is_active'       => (bool) $g->is_active,
            'mode'            => $g->mode,
            'test_configured' => $g->isConfigured('test'),
            'live_configured' => $g->isConfigured('live'),
            'masked'          => $masked,
        ];
    }

    protected function reply(Request $request, bool $ok, string $message, int $status = 200, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message, 'gateway' => $extra ?: null], $status);
        }
        return back()->with($ok ? 'success' : 'error', $message);
    }
}
