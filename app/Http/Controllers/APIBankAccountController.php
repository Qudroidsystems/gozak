<?php

namespace App\Http\Controllers;

use App\Models\UserBankAccount;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Customer bank accounts for refunds (mobile app). auth:sanctum.
 *
 *   GET    /banks                         list of Nigerian banks
 *   POST   /bank-accounts/resolve         look up the account name (no save)
 *   GET    /bank-accounts                 my accounts (numbers masked)
 *   POST   /bank-accounts                 save (name is re-resolved on the server)
 *   PATCH  /bank-accounts/{id}/default    make default
 *   DELETE /bank-accounts/{id}
 */
class APIBankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage own bank accounts|user-edit')->only(['store', 'makeDefault', 'destroy']);
    }

    public function banks(): JsonResponse
    {
        try {
            $banks = app(PaystackService::class)->listBanks();
        } catch (\Throwable $e) {
            Log::warning('Bank list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load banks right now. Please try again.'], 503);
        }
        return response()->json(['success' => true, 'data' => $banks]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $res = app(PaystackService::class)->resolveAccount($data['account_number'], $data['bank_code']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $this->friendly($e->getMessage())], 422);
        }

        return response()->json(['success' => true, 'data' => [
            'account_name' => $res['account_name'],
            'name_matches' => $this->nameMatches($request->user(), $res['account_name']),
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $rows = UserBankAccount::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->latest()->get();

        return response()->json(['success' => true, 'data' => $rows->map->toApi()->values(), 'max' => UserBankAccount::MAX_PER_USER]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->validated($request) + $request->validate([
            'bank_name'  => 'nullable|string|max:120',
            'is_default' => 'nullable|boolean',
        ]);

        if (UserBankAccount::where('user_id', $user->id)->count() >= UserBankAccount::MAX_PER_USER) {
            return response()->json(['success' => false, 'message' => 'You can save up to ' . UserBankAccount::MAX_PER_USER . ' bank accounts. Remove one first.'], 422);
        }

        $hash = UserBankAccount::hashNumber($data['bank_code'], $data['account_number']);
        if (UserBankAccount::where('user_id', $user->id)->where('account_hash', $hash)->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already saved this account.'], 422);
        }

        // Never trust the name sent by the app — ask Paystack again.
        $paystack = app(PaystackService::class);
        try {
            $res = $paystack->resolveAccount($data['account_number'], $data['bank_code']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $this->friendly($e->getMessage())], 422);
        }

        $bankName = collect(rescue(fn () => $paystack->listBanks(), [], false))->firstWhere('code', $data['bank_code'])['name']
            ?? ($data['bank_name'] ?? 'Bank');

        $account = DB::transaction(function () use ($user, $data, $res, $hash, $bankName) {
            $makeDefault = ($data['is_default'] ?? false) || !UserBankAccount::where('user_id', $user->id)->exists();
            if ($makeDefault) {
                UserBankAccount::where('user_id', $user->id)->update(['is_default' => false]);
            }
            return UserBankAccount::create([
                'user_id'        => $user->id,
                'bank_code'      => $data['bank_code'],
                'bank_name'      => $bankName,
                'account_number' => $data['account_number'],
                'account_hash'   => $hash,
                'account_last4'  => substr($data['account_number'], -4),
                'account_name'   => Str::limit($res['account_name'], 150, ''),
                'name_matches'   => $this->nameMatches($user, $res['account_name']),
                'is_default'     => $makeDefault,
                'verified_at'    => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Bank account saved.', 'data' => $account->toApi()], 201);
    }

    public function makeDefault(Request $request, int $id): JsonResponse
    {
        $account = UserBankAccount::where('user_id', $request->user()->id)->findOrFail($id);
        DB::transaction(function () use ($account) {
            UserBankAccount::where('user_id', $account->user_id)->update(['is_default' => false]);
            $account->update(['is_default' => true]);
        });
        return response()->json(['success' => true, 'data' => $account->fresh()->toApi()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = UserBankAccount::where('user_id', $request->user()->id)->findOrFail($id);
        $wasDefault = $account->is_default;
        $account->delete();

        if ($wasDefault) {
            UserBankAccount::where('user_id', $request->user()->id)->latest()->first()?->update(['is_default' => true]);
        }
        return response()->json(['success' => true, 'message' => 'Bank account removed.']);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    protected function validated(Request $request): array
    {
        $request->merge(['account_number' => preg_replace('/\D/', '', (string) $request->input('account_number'))]);
        return $request->validate([
            'bank_code'      => 'required|string|max:20',
            'account_number' => 'required|digits:10',
        ], [
            'account_number.digits' => 'Account numbers have 10 digits (NUBAN).',
        ]);
    }

    /** Does the bank's account name share a name with the customer? */
    protected function nameMatches($user, string $accountName): bool
    {
        $norm = fn ($s) => collect(preg_split('/[^a-z]+/', strtolower((string) $s)))->filter(fn ($w) => strlen($w) > 1);
        $mine = $norm($user->first_name . ' ' . $user->last_name);
        return $mine->isNotEmpty() && $norm($accountName)->intersect($mine)->isNotEmpty();
    }

    protected function friendly(string $message): string
    {
        $m = strtolower($message);
        if (str_contains($m, 'could not resolve') || str_contains($m, 'invalid account') || str_contains($m, 'unknown bank')) {
            return 'We could not find that account. Check the number and the bank.';
        }
        if (str_contains($m, 'limit') || str_contains($m, 'too many') || str_contains($m, 'exceeded')) {
            if (rescue(fn () => app(PaystackService::class)->isTestMode(), false, false)) {
                // Paystack test keys only allow a handful of account look-ups per day.
                return 'Account checks are limited while payments are in test mode. Switch to live Paystack keys, or try again tomorrow.';
            }
            return 'Too many checks right now. Please try again in a few minutes.';
        }
        return $message ?: 'We could not verify that account number.';
    }
}
