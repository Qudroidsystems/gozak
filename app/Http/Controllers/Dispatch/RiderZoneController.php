<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DeliveryRider;
use App\Models\Dispatch\DeliveryZone;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RiderZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage riders|Manage dispatch|View dispatch', ['only' => ['riders', 'zones']]);
        $this->middleware('permission:Manage riders', ['except' => ['riders', 'zones']]);
    }

    // ── Riders ───────────────────────────────────────────────────────────────

    public function riders()
    {
        $riders = DeliveryRider::with(['branch', 'user'])
            ->withCount(['batches as runs_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('is_active')->orderBy('name')->get();
        $branches  = StockLocation::active()->orderBy('name')->get(['id', 'name']);
        $pagetitle = 'Delivery riders';
        return view('dispatch.riders', compact('riders', 'branches', 'pagetitle'));
    }

    public function storeRider(Request $request)
    {
        $data = $this->riderData($request);
        DB::transaction(function () use ($request, $data) {
            $rider = DeliveryRider::create($data + ['is_active' => true]);
            $this->linkLogin($request, $rider);
        });
        return back()->with('success', 'Rider added.' . ($request->filled('login_email') ? ' They can sign in and open /rider on their phone.' : ''));
    }

    public function updateRider(Request $request, DeliveryRider $rider)
    {
        $data = $this->riderData($request);
        DB::transaction(function () use ($request, $rider, $data) {
            $rider->update($data + ['is_active' => $request->boolean('is_active')]);
            $this->linkLogin($request, $rider);
        });
        return back()->with('success', 'Rider updated.');
    }

    /**
     * Rider app login: link (or create) a staff user account with the "Rider" role.
     * Customer (app) accounts are never converted.
     */
    private function linkLogin(Request $request, DeliveryRider $rider): void
    {
        $email    = strtolower(trim((string) $request->input('login_email')));
        $password = (string) $request->input('login_password');
        if ($email === '') {
            if ($password !== '' && $rider->user) {
                $this->checkPassword($password);
                $rider->user->forceFill(['password' => Hash::make($password)])->save();
            }
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['login_email' => 'Enter a valid email for the rider login.']);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            if ($user->role !== 'staff') {
                throw ValidationException::withMessages(['login_email' => 'That email belongs to a customer account. Use a different email for the rider login.']);
            }
            $taken = DeliveryRider::where('user_id', $user->id)->where('id', '!=', $rider->id)->first();
            if ($taken) {
                throw ValidationException::withMessages(['login_email' => "That login is already used by rider {$taken->name}."]);
            }
            if ($password !== '') {
                $this->checkPassword($password);
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        } else {
            $this->checkPassword($password, true);
            $parts = preg_split('/\s+/', trim($rider->name), 2);
            $base  = Str::slug(Str::before($email, '@'), '_') ?: 'rider';
            $username = $base;
            for ($i = 2; User::where('username', $username)->exists(); $i++) {
                $username = $base . $i;
            }
            $user = User::forceCreate([
                'first_name'   => $parts[0] ?? $rider->name,
                'last_name'    => $parts[1] ?? 'Rider',
                'username'     => $username,
                'email'        => $email,
                'phone_number' => $rider->phone,
                'password'     => Hash::make($password),
                'role'         => 'staff',
            ]);
        }

        if (!$user->hasRole('Rider')) {
            $user->assignRole('Rider');
        }
        $rider->update(['user_id' => $user->id]);
    }

    private function checkPassword(string $password, bool $required = false): void
    {
        if ($password === '' && !$required) {
            return;
        }
        if (mb_strlen($password) < 8) {
            throw ValidationException::withMessages(['login_password' => 'The rider login password must be at least 8 characters.']);
        }
    }

    private function riderData(Request $request): array
    {
        return $request->validate([
            'name'              => 'required|string|max:120',
            'phone'             => 'nullable|string|max:32',
            'vehicle_type'      => ['required', Rule::in(array_keys(DeliveryRider::VEHICLES))],
            'vehicle_plate'     => 'nullable|string|max:32',
            'capacity'          => 'nullable|integer|min:1|max:999',
            'stock_location_id' => 'nullable|integer|exists:stock_locations,id',
            'notes'             => 'nullable|string|max:500',
        ]);
    }

    // ── Zones ────────────────────────────────────────────────────────────────

    public function zones()
    {
        $zones     = DeliveryZone::with('branch')->orderByDesc('is_active')->orderBy('sort_order')->orderBy('name')->get();
        $branches  = StockLocation::active()->orderBy('name')->get(['id', 'name']);
        $pagetitle = 'Delivery zones';
        return view('dispatch.zones', compact('zones', 'branches', 'pagetitle'));
    }

    public function storeZone(Request $request)
    {
        DeliveryZone::create($this->zoneData($request) + ['is_active' => true]);
        return back()->with('success', 'Zone added.');
    }

    public function updateZone(Request $request, DeliveryZone $zone)
    {
        $zone->update($this->zoneData($request, $zone) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Zone updated.');
    }

    private function zoneData(Request $request, ?DeliveryZone $zone = null): array
    {
        $request->merge(['code' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->code))]);
        $data = $request->validate([
            'code'              => ['required', 'string', 'min:2', 'max:8',
                Rule::unique('delivery_zones', 'code')->where('stock_location_id', $request->stock_location_id)->ignore($zone?->id)],
            'name'              => 'required|string|max:120',
            'areas'             => 'nullable|string|max:5000',
            'color'             => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order'        => 'nullable|integer|min:0|max:999',
            'stock_location_id' => 'nullable|integer|exists:stock_locations,id',
        ]);
        // One area per line (or comma separated), in the order a rider would pass them.
        $data['areas'] = collect(preg_split('/[\r\n,]+/', (string) ($data['areas'] ?? '')))
            ->map(fn ($a) => trim($a))->filter()->unique(fn ($a) => mb_strtolower($a))->values()->all();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }
}
