<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backs the ⌘K spotlight search in layouts/master (ported from CSS Kabba).
 * Returns live records — products, orders, customers — in the shape the
 * spotlight script expects: {results: [{title, url, icon, category}]}.
 * Each section is only searched when the user may see it.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q    = trim((string) $request->get('q', ''));
        $user = $request->user();
        $out  = [];

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        try {
            if ($user->can('View product')) {
                Product::query()
                    ->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('barcode', 'like', "%{$q}%"))
                    ->orderBy('title')->limit(6)->get(['id', 'title', 'sku', 'price'])
                    ->each(function ($p) use (&$out) {
                        $out[] = [
                            'title'    => $p->title . ($p->sku ? " ({$p->sku})" : ''),
                            'url'      => route('web.products.edit', $p->id),
                            'icon'     => 'mdi-package-variant',
                            'category' => 'Products',
                        ];
                    });
            }

            if ($user->can('View order') && preg_match('/^#?\d+$/', $q)) {
                $id = (int) ltrim($q, '#');
                Order::query()->with('user:id,first_name,last_name')
                    ->where('id', $id)->orWhere('id', 'like', "{$id}%")
                    ->latest('id')->limit(5)->get(['id', 'user_id', 'status', 'total_amount'])
                    ->each(function ($o) use (&$out) {
                        $out[] = [
                            'title'    => "Order #{$o->id}" . ($o->user ? ' — ' . $o->user->name : '') . ' · ' . ucfirst((string) $o->status),
                            'url'      => route('adminorders.show', $o->id),
                            'icon'     => 'mdi-cart',
                            'category' => 'Orders',
                        ];
                    });
            }

            if ($user->can('View customer')) {
                Customer::query()
                    ->where(fn ($w) => $w->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('phone_number', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"))
                    ->limit(5)->get(['id', 'first_name', 'last_name', 'phone_number'])
                    ->each(function ($c) use (&$out) {
                        $out[] = [
                            'title'    => trim("{$c->first_name} {$c->last_name}") . ($c->phone_number ? " · {$c->phone_number}" : ''),
                            'url'      => route('customers.index', ['search' => $c->phone_number ?: $c->first_name]),
                            'icon'     => 'mdi-account',
                            'category' => 'Customers',
                        ];
                    });
            }
        } catch (\Throwable $e) {
            Log::warning('Spotlight search failed: ' . $e->getMessage());
        }

        return response()->json(['results' => $out]);
    }
}
