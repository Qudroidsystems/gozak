<?php

namespace App\Http\Controllers;

use App\Models\Credit\CreditAccount;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditCollectionAttempt;
use App\Models\Credit\CreditLedgerEntry;
use App\Models\Credit\CreditMandate;
use App\Models\Credit\CreditStatement;
use App\Models\Order;
use App\Services\Credit\CreditGateway;
use App\Services\Credit\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** Gozak Credit for the mobile app. All routes: auth:sanctum. */
class APICreditController extends Controller
{
    public function __construct(protected CreditService $credit, protected CreditGateway $gateway)
    {
    }

    protected function ok($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json(array_filter(['success' => true, 'message' => $message, 'data' => $data], fn ($v) => $v !== null), $code);
    }

    protected function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }

    protected function account(Request $request): ?CreditAccount
    {
        return $this->credit->accountFor($request->user());
    }

    /** GET /credit — card, limits, what's due, application status, terms. */
    public function summary(Request $request): JsonResponse
    {
        return $this->ok($this->credit->summary($request->user()));
    }

    /** POST /credit/apply */
    public function apply(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$this->credit->isOfferedTo($user)) {
            return $this->fail('Gozak Credit is not available yet.', 403);
        }
        $s = $this->credit->settings();
        $request->merge([
            'bvn'            => preg_replace('/\D/', '', (string) $request->input('bvn')),
            'account_number' => preg_replace('/\D/', '', (string) $request->input('account_number')),
        ]);
        $data = $request->validate([
            'full_name'         => 'required|string|max:150',
            'phone'             => ['required', 'string', 'regex:/^(\+?234|0)[789][01]\d{8}$/'],
            'date_of_birth'     => 'required|date|before:-18 years',
            'bvn'               => 'required|digits:11',
            'employment_status' => ['required', Rule::in(array_keys(CreditApplication::EMPLOYMENT))],
            'employer'          => 'nullable|string|max:150',
            'monthly_income'    => ['required', Rule::in(array_keys(CreditApplication::INCOME_BANDS))],
            'address'           => 'required|string|max:255',
            'state'             => 'required|string|max:60',
            'requested_limit'   => 'required|numeric|min:' . $s->min_limit . '|max:' . $s->max_limit,
            'bank_code'         => 'required|string|max:20',
            'bank_name'         => 'nullable|string|max:120',
            'account_number'    => 'required|digits:10',
            'accept_terms'      => 'accepted',
            'accept_autodebit'  => 'accepted',
        ], [
            'date_of_birth.before'      => 'You must be 18 or older to apply.',
            'accept_terms.accepted'     => 'Please accept the Gozak Credit terms.',
            'accept_autodebit.accepted' => 'Please agree to automatic repayment from your bank account.',
            'phone.regex'               => 'Enter a valid Nigerian phone number.',
            'bvn.digits'                => 'Your BVN has 11 digits.',
            'account_number.digits'     => 'Account numbers have 10 digits.',
            'bank_code.required'        => 'Choose your bank.',
        ]);

        try {
            $app = $this->credit->apply($user, $data, $request->ip());
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->ok(['id' => $app->id, 'status' => $app->status], 'Application received. We\'ll notify you once it\'s reviewed (usually within 24 hours).', 201);
    }

    /** POST /credit/quote {amount} — fee and total for checkout. */
    public function quote(Request $request): JsonResponse
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        $a = $this->account($request);
        if (!$a || !$this->credit->isOfferedTo($request->user())) {
            return $this->fail('Gozak Credit is not available.', 404);
        }
        $q = $this->credit->quote($a, (float) $request->amount);
        $enough = $q['total'] <= $a->available() + 0.001;
        return $this->ok($q + [
            'available' => $a->available(),
            'can_pay'   => $a->canSpend() && $enough,
            'status'    => $a->status,
            'reason'    => !$a->canSpend() ? $a->statusLabel() : (!$enough ? 'Not enough available credit' : null),
        ]);
    }

    /** POST /credit/pay-order {order_id} */
    public function payOrder(Request $request): JsonResponse
    {
        $request->validate(['order_id' => 'required|string|max:64']);
        $order = Order::where('id', $request->order_id)->where('user_id', $request->user()->id)->first();
        if (!$order) {
            return $this->fail('Order not found.', 404);
        }
        try {
            $r = $this->credit->payOrder($request->user(), $order);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->ok([
            'order_id'  => $order->id,
            'fee'       => $r['fee'],
            'total'     => $r['total'] ?? null,
            'available' => $r['account']->available(),
        ], 'Paid with Gozak Credit');
    }

    public function statements(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a) {
            return $this->ok([]);
        }
        return $this->ok(CreditStatement::where('credit_account_id', $a->id)->latest('period_end')->limit(36)->get()->map->toApi()->values());
    }

    public function statement(Request $request, int $id): JsonResponse
    {
        $a = $this->account($request);
        $st = $a ? CreditStatement::where('credit_account_id', $a->id)->find($id) : null;
        if (!$st) {
            return $this->fail('Statement not found.', 404);
        }
        return $this->ok($st->toApi() + [
            'entries'  => $st->entries->map->toApi()->values(),
            'attempts' => $st->collectionAttempts()->limit(20)->get()->map(fn ($at) => [
                'channel'    => $at->channel,
                'amount'     => $at->amount,
                'status'     => $at->status,
                'created_at' => $at->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a) {
            return $this->ok([]);
        }
        $page = CreditLedgerEntry::where('credit_account_id', $a->id)->latest('id')->paginate(30);
        return response()->json([
            'success' => true,
            'data'    => collect($page->items())->map->toApi()->values(),
            'meta'    => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }

    // ── Repayment methods ────────────────────────────────────────────────────

    public function linkBank(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a || $a->status === CreditAccount::CLOSED) {
            return $this->fail('You need an approved Gozak Credit account first.', 404);
        }
        $request->validate(['account_number' => 'nullable|digits:10', 'bank_code' => 'nullable|string|max:20']);
        try {
            return $this->ok($this->gateway->startDirectDebit($a, $request->account_number, $request->bank_code));
        } catch (\Throwable $e) {
            Log::warning('Credit linkBank failed: ' . $e->getMessage());
            return $this->fail('We couldn\'t start the bank link right now. Please try again shortly.');
        }
    }

    public function linkCard(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a || $a->status === CreditAccount::CLOSED) {
            return $this->fail('You need an approved Gozak Credit account first.', 404);
        }
        try {
            return $this->ok($this->gateway->startCardLink($a));
        } catch (\Throwable $e) {
            Log::warning('Credit linkCard failed: ' . $e->getMessage());
            return $this->fail('We couldn\'t start the card link right now. Please try again shortly.');
        }
    }

    /** POST /credit/mandates/check {reference?} — after the WebView closes. */
    public function checkMandates(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a) {
            return $this->fail('No credit account.', 404);
        }
        $q = $a->mandates()->where('status', 'pending');
        if ($request->filled('reference')) {
            $q->where('reference', $request->reference);
        }
        foreach ($q->get() as $m) {
            $m->type === 'card' ? $this->gateway->confirmCard($m->reference) : $this->gateway->refreshMandate($m);
        }
        return $this->ok($this->credit->summary($request->user())['account']);
    }

    public function makePrimary(Request $request, int $id): JsonResponse
    {
        $a = $this->account($request);
        $m = $a ? CreditMandate::where('credit_account_id', $a->id)->find($id) : null;
        if (!$m) {
            return $this->fail('Not found.', 404);
        }
        try {
            $this->gateway->makePrimary($m);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->ok($this->credit->summary($request->user())['account']);
    }

    public function removeMandate(Request $request, int $id): JsonResponse
    {
        $a = $this->account($request);
        $m = $a ? CreditMandate::where('credit_account_id', $a->id)->find($id) : null;
        if (!$m) {
            return $this->fail('Not found.', 404);
        }
        if ($m->status === 'active' && $a->balance > 0.009 && $a->activeMandates()->count() <= 1) {
            return $this->fail('You owe ₦' . number_format($a->balance, 2) . '. Add another bank account or card before removing this one.');
        }
        $this->gateway->revokeMandate($m, $request->user());
        return $this->ok($this->credit->summary($request->user())['account'], 'Removed');
    }

    // ── Pay early / pay overdue ──────────────────────────────────────────────

    public function repay(Request $request): JsonResponse
    {
        $a = $this->account($request);
        if (!$a) {
            return $this->fail('No credit account.', 404);
        }
        $request->validate(['amount' => 'required|numeric|min:100']);
        try {
            return $this->ok($this->gateway->startRepayment($a, (float) $request->amount));
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            Log::warning('Credit repay init failed: ' . $e->getMessage());
            return $this->fail('We couldn\'t start the payment. Please try again.');
        }
    }

    public function verifyRepay(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string|max:100']);
        $a = $this->account($request);
        $at = $a ? CreditCollectionAttempt::where('credit_account_id', $a->id)->where('reference', $request->reference)->first() : null;
        if (!$at) {
            return $this->fail('Payment not found.', 404);
        }
        $at = $this->gateway->verifyRepayment($at->reference);
        return $this->ok(
            ['status' => $at?->status, 'account' => $this->credit->summary($request->user())['account']],
            $at?->status === 'success' ? 'Payment received. Thank you!' : null
        );
    }
}
