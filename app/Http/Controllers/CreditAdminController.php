<?php

namespace App\Http\Controllers;

use App\Models\Credit\CreditAccount;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditAuditLog;
use App\Models\Credit\CreditCollectionAttempt;
use App\Models\Credit\CreditLedgerEntry;
use App\Models\Credit\CreditMandate;
use App\Models\Credit\CreditSetting;
use App\Models\Credit\CreditStatement;
use App\Models\Order;
use App\Services\Credit\CreditBillingService;
use App\Services\Credit\CreditGateway;
use App\Services\Credit\CreditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin › Gozak Credit (/admin/credit).
 *   View credit                 dashboards, lists, account pages
 *   Review credit applications  approve / reject
 *   Manage credit               limits, status, payments, adjustments, debits, waivers
 *   Manage credit settings      master switch, pricing, calendar, collections policy
 */
class CreditAdminController extends Controller
{
    public function __construct(protected CreditService $credit, protected CreditGateway $gateway)
    {
        $this->middleware('permission:View credit|Manage credit|Review credit applications|Manage credit settings');
        $this->middleware('permission:Review credit applications', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:Manage credit', ['only' => [
            'setLimit', 'setStatus', 'setMarkup', 'adjust', 'recordPayment', 'debit', 'waiveFee', 'recalculate', 'revokeMandate', 'primaryMandate', 'refreshMandate', 'runBilling',
        ]]);
        $this->middleware('permission:Manage credit settings', ['only' => ['settings', 'saveSettings']]);
    }

    protected function back(string $msg, string $type = 'success')
    {
        return back()->with($type, $msg);
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $s = CreditSetting::current();
        $tz = CreditSetting::TZ;
        $monthStart = now($tz)->startOfMonth()->setTimezone(config('app.timezone'));

        $accounts = CreditAccount::selectRaw('status, COUNT(*) n, SUM(credit_limit) lim, SUM(balance) bal')->groupBy('status')->get()->keyBy('status');
        $live = $accounts->except([CreditAccount::CLOSED]);
        $totalLimit = (float) $live->sum('lim');
        $outstanding = (float) $live->sum('bal');

        $open = CreditStatement::whereIn('status', CreditStatement::OPEN);
        $dueOpen = (float) (clone $open)->sum(DB::raw('amount_due - amount_paid'));
        $overdue = CreditStatement::where('status', 'overdue');
        $overdueAmt = (float) (clone $overdue)->sum(DB::raw('amount_due - amount_paid'));
        $overdueCount = (clone $overdue)->distinct()->count('credit_account_id');

        $collectedMonth = (float) CreditLedgerEntry::where('type', 'payment')->where('created_at', '>=', $monthStart)->sum('amount');
        $spentMonth = (float) CreditLedgerEntry::where('type', 'purchase')->where('created_at', '>=', $monthStart)->sum('amount');
        $feesMonth = (float) CreditLedgerEntry::whereIn('type', ['fee', 'late_fee'])->where('created_at', '>=', $monthStart)->sum('amount');

        // Collection rate: statements due in the last 60 days.
        $recent = CreditStatement::whereBetween('due_date', [now($tz)->subDays(60)->toDateString(), now($tz)->toDateString()])->where('amount_due', '>', 0);
        $dueSum = (float) (clone $recent)->sum('amount_due');
        $paidSum = (float) (clone $recent)->sum(DB::raw('LEAST(amount_paid, amount_due)'));
        $collectionRate = $dueSum > 0 ? round($paidSum / $dueSum * 100, 1) : null;
        $onTime = (clone $recent)->where('status', 'paid')->whereRaw('DATE(paid_at) <= due_date')->count();
        $recentCount = (clone $recent)->count();

        // 30-day chart: spending vs collections per day.
        $from = now($tz)->subDays(29)->startOfDay();
        $daily = CreditLedgerEntry::whereIn('type', ['purchase', 'payment'])->where('created_at', '>=', $from->copy()->setTimezone(config('app.timezone')))
            ->get(['type', 'amount', 'created_at'])
            ->groupBy(fn ($e) => $e->created_at->copy()->setTimezone($tz)->toDateString());
        $chart = ['labels' => [], 'spent' => [], 'collected' => []];
        for ($d = $from->copy(); $d->lte(now($tz)); $d->addDay()) {
            $rows = $daily->get($d->toDateString(), collect());
            $chart['labels'][] = $d->format('j M');
            $chart['spent'][] = round($rows->where('type', 'purchase')->sum('amount'), 2);
            $chart['collected'][] = round($rows->where('type', 'payment')->sum('amount'), 2);
        }

        return view('credit.dashboard', [
            'settings'       => $s,
            'accounts'       => $accounts,
            'totalLimit'     => $totalLimit,
            'outstanding'    => $outstanding,
            'utilisation'    => $totalLimit > 0 ? round($outstanding / $totalLimit * 100, 1) : 0,
            'dueOpen'        => $dueOpen,
            'overdueAmt'     => $overdueAmt,
            'overdueCount'   => $overdueCount,
            'collectedMonth' => $collectedMonth,
            'spentMonth'     => $spentMonth,
            'feesMonth'      => $feesMonth,
            'collectionRate' => $collectionRate,
            'onTimeRate'     => $recentCount ? round($onTime / $recentCount * 100, 1) : null,
            'pendingApps'    => CreditApplication::where('status', 'pending')->count(),
            'chart'          => $chart,
            'upcoming'       => CreditStatement::with('account.user')->whereIn('status', ['issued', 'partially_paid'])
                ->whereBetween('due_date', [now($tz)->toDateString(), now($tz)->addDays(7)->toDateString()])->orderBy('due_date')->limit(10)->get(),
            'failed'         => CreditCollectionAttempt::with('account.user', 'mandate')->where('status', 'failed')->latest('id')->limit(8)->get(),
            'overdueList'    => CreditStatement::with('account.user')->where('status', 'overdue')->orderBy('due_date')->limit(8)->get(),
            'applications'   => CreditApplication::with('user')->where('status', 'pending')->latest()->limit(6)->get(),
            'nextStatement'  => $s->statementDateFor(now()),
            'nextDue'        => $s->dueDateFor($s->statementDateFor(now())),
        ]);
    }

    // ── Applications ─────────────────────────────────────────────────────────

    public function applications(Request $request)
    {
        $status = $request->input('status', 'pending');
        $q = CreditApplication::with('user', 'reviewer')->latest();
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('full_name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%")));
        }
        return view('credit.applications', [
            'rows'   => $q->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => CreditApplication::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function application(CreditApplication $application)
    {
        $user = $application->user;
        $orders = Order::where('user_id', $user->id);
        return view('credit.application', [
            'app'     => $application->load('reviewer'),
            'user'    => $user,
            'stats'   => [
                'orders'    => (clone $orders)->count(),
                'delivered' => (clone $orders)->where('status', 'delivered')->count(),
                'cancelled' => (clone $orders)->where('status', 'cancelled')->count(),
                'spent'     => (float) (clone $orders)->where('status', 'delivered')->sum('total_amount'),
                'since'     => $user->created_at,
            ],
            'recentOrders' => (clone $orders)->latest()->limit(8)->get(),
            'history'      => CreditApplication::where('user_id', $user->id)->where('id', '!=', $application->id)->latest()->get(),
            'account'      => CreditAccount::where('user_id', $user->id)->first(),
            'settings'     => CreditSetting::current(),
        ]);
    }

    public function approve(Request $request, CreditApplication $application)
    {
        $data = $request->validate(['limit' => 'required|numeric|min:1', 'note' => 'nullable|string|max:500']);
        try {
            $account = $this->credit->approve($application, (float) $data['limit'], $request->user(), $data['note'] ?? null);
        } catch (\Throwable $e) {
            return $this->back($e->getMessage(), 'error');
        }
        return redirect()->route('admin.credit.account', $account)->with('success', 'Approved. The customer has been notified to link their bank account.');
    }

    public function reject(Request $request, CreditApplication $application)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        try {
            $this->credit->reject($application, $request->user(), $data['reason']);
        } catch (\Throwable $e) {
            return $this->back($e->getMessage(), 'error');
        }
        return redirect()->route('admin.credit.applications')->with('success', 'Application rejected.');
    }

    // ── Accounts ─────────────────────────────────────────────────────────────

    public function accounts(Request $request)
    {
        $q = $this->accountQuery($request);
        return view('credit.accounts', [
            'rows'   => $q->paginate(25)->withQueryString(),
            'status' => $request->input('status', 'all'),
            'counts' => CreditAccount::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    protected function accountQuery(Request $request)
    {
        $q = CreditAccount::with('user')->withCount(['statements as overdue_count' => fn ($s) => $s->where('status', 'overdue')]);
        $status = $request->input('status', 'all');
        if ($status === 'overdue') {
            $q->whereHas('statements', fn ($s) => $s->where('status', 'overdue'));
        } elseif ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('card_name', 'like', "%{$term}%")->orWhere('card_number', 'like', "%{$term}")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%")->orWhere('phone_number', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")));
        }
        $sort = $request->input('sort', 'balance');
        return match ($sort) {
            'limit'  => $q->orderByDesc('credit_limit'),
            'newest' => $q->latest(),
            default  => $q->orderByDesc('balance'),
        };
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->accountQuery($request)->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Account', 'Customer', 'Email', 'Phone', 'Status', 'Limit', 'Balance', 'Available', 'Unbilled', 'On-time', 'Late', 'Opened']);
            foreach ($rows as $a) {
                fputcsv($out, [$a->id, $a->user?->full_name, $a->user?->email, $a->user?->phone_number, $a->status, $a->credit_limit, $a->balance, $a->available(), $a->unbilled, $a->on_time_payments, $a->late_payments, $a->created_at?->toDateString()]);
            }
            fclose($out);
        }, 'gozak-credit-accounts-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function account(CreditAccount $account)
    {
        $account->load('user', 'application');
        return view('credit.account', [
            'a'          => $account,
            'settings'   => CreditSetting::current(),
            'mandates'   => $account->mandates()->latest('id')->get(),
            'statements' => $account->statements()->limit(24)->get(),
            'ledger'     => $account->ledger()->with('creator')->limit(100)->get(),
            'attempts'   => $account->attempts()->with('mandate', 'initiator')->limit(50)->get(),
            'audit'      => $account->audit()->with('actor')->limit(100)->get(),
            'openStatements' => CreditStatement::where('credit_account_id', $account->id)->whereIn('status', CreditStatement::OPEN)->orderBy('due_date')->get(),
        ]);
    }

    public function setLimit(Request $request, CreditAccount $account)
    {
        $s = CreditSetting::current();
        $data = $request->validate(['limit' => 'required|numeric|min:0|max:' . max($s->max_limit, 1), 'reason' => 'nullable|string|max:255']);
        $this->credit->setLimit($account, (float) $data['limit'], $request->user(), $data['reason'] ?? null);
        return $this->back('Credit limit updated.');
    }

    public function setStatus(Request $request, CreditAccount $account)
    {
        $data = $request->validate(['status' => 'required|in:active,frozen,closed', 'reason' => 'nullable|string|max:255']);
        try {
            $this->credit->setStatus($account, $data['status'], $request->user(), $data['reason'] ?? null);
        } catch (\Throwable $e) {
            return $this->back($e->getMessage(), 'error');
        }
        return $this->back('Account status updated.');
    }

    public function setMarkup(Request $request, CreditAccount $account)
    {
        $data = $request->validate(['markup_percent' => 'nullable|numeric|min:0|max:50']);
        $this->credit->setMarkup($account, $request->filled('markup_percent') ? (float) $data['markup_percent'] : null, $request->user());
        return $this->back('Credit fee updated for this customer.');
    }

    public function adjust(Request $request, CreditAccount $account)
    {
        $data = $request->validate(['direction' => 'required|in:debit,credit', 'amount' => 'required|numeric|min:0.01', 'reason' => 'required|string|max:200']);
        $amount = (float) $data['amount'] * ($data['direction'] === 'credit' ? -1 : 1);
        $this->credit->adjust($account, $amount, $data['reason'], $request->user());
        return $this->back('Adjustment posted.');
    }

    public function recordPayment(Request $request, CreditAccount $account)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:1', 'reference' => 'required|string|max:80', 'note' => 'nullable|string|max:150']);
        if ((float) $data['amount'] > $account->balance + 0.009) {
            return $this->back('That is more than the customer owes (₦' . number_format($account->balance, 2) . ').', 'error');
        }
        $entry = $this->credit->recordPayment($account, (float) $data['amount'], 'manual', 'MAN-' . Str::upper(Str::slug($data['reference'], '')), $request->user()->id, $data['note'] ?? $data['reference']);
        return $entry ? $this->back('Payment recorded and applied to open statements.') : $this->back('A payment with that reference was already recorded.', 'error');
    }

    /** Debit now: one statement, or every open statement of the account. */
    public function debit(Request $request, CreditAccount $account)
    {
        $data = $request->validate(['statement_id' => 'nullable|integer', 'channel' => 'nullable|in:direct_debit,card']);
        $statements = CreditStatement::where('credit_account_id', $account->id)->whereIn('status', CreditStatement::OPEN)
            ->when($data['statement_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->orderBy('due_date')->get();
        if ($statements->isEmpty()) {
            return $this->back('Nothing to debit — there is no open statement.', 'error');
        }
        $msgs = [];
        foreach ($statements as $st) {
            try {
                $at = $this->gateway->collect($st, 'admin', $request->user(), $data['channel'] ?? null);
                $msgs[] = $st->number . ': ' . ($at?->status === 'failed' ? 'failed — ' . $at->failure_reason : ($at?->status ?? 'nothing to collect'));
            } catch (\Throwable $e) {
                $msgs[] = $st->number . ': ' . $e->getMessage();
            }
        }
        return $this->back(implode(' · ', $msgs), 'success');
    }

    public function waiveFee(Request $request, CreditStatement $statement)
    {
        try {
            $this->credit->waiveLateFee($statement, $request->user());
        } catch (\Throwable $e) {
            return $this->back($e->getMessage(), 'error');
        }
        return $this->back('Late fee waived.');
    }

    public function recalculate(CreditAccount $account)
    {
        $r = $this->credit->recalculate($account);
        $changed = abs($r['before']['balance'] - $r['after']['balance']) > 0.009 || abs($r['before']['unbilled'] - $r['after']['unbilled']) > 0.009;
        $this->credit->audit($account, $account->user_id, auth()->id(), 'balance.recalculated', $changed ? 'Balance corrected from ledger' : 'Balance verified (no change)', $r);
        return $this->back($changed ? 'Balance corrected from the ledger.' : 'Balance matches the ledger.');
    }

    public function revokeMandate(Request $request, CreditMandate $mandate)
    {
        $this->gateway->revokeMandate($mandate, $request->user());
        return $this->back('Payment method removed.');
    }

    public function primaryMandate(CreditMandate $mandate)
    {
        try {
            $this->gateway->makePrimary($mandate);
        } catch (\Throwable $e) {
            return $this->back($e->getMessage(), 'error');
        }
        return $this->back('Default payment method changed.');
    }

    public function refreshMandate(CreditMandate $mandate)
    {
        $m = $mandate->type === 'card' ? $this->gateway->confirmCard($mandate->reference) : $this->gateway->refreshMandate($mandate);
        return $this->back('Mandate status: ' . ($m?->status ?? 'unknown') . '.');
    }

    // ── Statements & collections ─────────────────────────────────────────────

    public function statements(Request $request)
    {
        $status = $request->input('status', 'open');
        $q = CreditStatement::with('account.user')->orderBy('due_date', $status === 'paid' ? 'desc' : 'asc');
        match ($status) {
            'open'  => $q->whereIn('status', CreditStatement::OPEN),
            'all'   => null,
            default => $q->where('status', $status),
        };
        if ($request->filled('due_from')) {
            $q->whereDate('due_date', '>=', $request->due_from);
        }
        if ($request->filled('due_to')) {
            $q->whereDate('due_date', '<=', $request->due_to);
        }
        $totals = ['due' => (float) (clone $q)->sum('amount_due'), 'paid' => (float) (clone $q)->sum('amount_paid')];
        return view('credit.statements', [
            'rows'   => $q->paginate(30)->withQueryString(),
            'status' => $status,
            'totals' => $totals,
        ]);
    }

    public function statement(CreditStatement $statement)
    {
        $statement->load('account.user', 'entries', 'collectionAttempts.mandate', 'collectionAttempts.initiator');
        return view('credit.statement', ['st' => $statement]);
    }

    public function collections(Request $request)
    {
        $status = $request->input('status', 'all');
        $q = CreditCollectionAttempt::with('account.user', 'mandate', 'statement', 'initiator')->latest('id');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($request->filled('channel')) {
            $q->where('channel', $request->channel);
        }
        $since = now()->subDays(30);
        $stats = CreditCollectionAttempt::where('created_at', '>=', $since)->whereIn('channel', ['direct_debit', 'card'])
            ->selectRaw('channel, status, COUNT(*) n, SUM(amount) amt')->groupBy('channel', 'status')->get();
        return view('credit.collections', ['rows' => $q->paginate(40)->withQueryString(), 'status' => $status, 'stats' => $stats]);
    }

    public function audit(Request $request)
    {
        $q = CreditAuditLog::with('actor', 'customer', 'account')->latest('id');
        if ($request->filled('action')) {
            $q->where('action', 'like', $request->action . '%');
        }
        return view('credit.audit', ['rows' => $q->paginate(50)->withQueryString()]);
    }

    // ── Settings & automation ────────────────────────────────────────────────

    public function settings()
    {
        $s = CreditSetting::current();
        return view('credit.settings', [
            's'             => $s,
            'nextStatement' => $s->statementDateFor(now()),
            'nextDue'       => $s->dueDateFor($s->statementDateFor(now())),
            'lastRun'       => cache('credit:last_run'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'enabled'            => 'nullable|boolean',
            'audience'           => 'required|in:pilot,everyone',
            'pilot_emails'       => 'nullable|string|max:5000',
            'markup_percent'     => 'required|numeric|min:0|max:50',
            'min_limit'          => 'required|numeric|min:0',
            'max_limit'          => 'required|numeric|gte:min_limit',
            'statement_day'      => 'required|integer|min:1|max:28',
            'due_day'            => 'required|integer|min:1|max:28',
            'grace_days'         => 'required|integer|min:0|max:30',
            'late_fee_flat'      => 'required|numeric|min:0',
            'late_fee_percent'   => 'required|numeric|min:0|max:20',
            'late_fee_cap'       => 'required|numeric|min:0',
            'max_attempts'       => 'required|integer|min:1|max:20',
            'retry_hours'        => 'required|integer|min:1|max:168',
            'card_fallback'      => 'nullable|boolean',
            'require_mandate'    => 'nullable|boolean',
            'reminder_days'      => ['required', 'string', 'max:30', 'regex:/^\s*\d{1,2}(\s*,\s*\d{1,2})*\s*$/'],
            'suspend_after_days' => 'required|integer|min:0|max:60',
            'terms_version'      => 'required|string|max:20',
            'terms_text'         => 'nullable|string|max:20000',
        ]);
        foreach (['enabled', 'card_fallback', 'require_mandate'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        $s = CreditSetting::current();
        $before = $s->only(array_keys($data));
        CreditSetting::whereKey($s->id)->first()->update($data);
        $changes = collect($data)->filter(fn ($v, $k) => (string) ($before[$k] ?? '') !== (string) $v)->keys()->all();
        if ($changes) {
            $this->credit->audit(null, null, $request->user()->id, 'settings.changed', 'Changed: ' . implode(', ', $changes));
        }
        return $this->back('Gozak Credit settings saved.');
    }

    public function runBilling(CreditBillingService $billing)
    {
        $r = $billing->run();
        cache(['credit:last_run' => ['at' => now()->toDateTimeString(), 'result' => $r, 'by' => auth()->user()?->full_name]], now()->addDays(7));
        return $this->back('Billing run finished: ' . collect($r)->map(fn ($v, $k) => Str::headline($k) . ' ' . (is_numeric($v) ? $v : '!'))->implode(' · '));
    }
}
