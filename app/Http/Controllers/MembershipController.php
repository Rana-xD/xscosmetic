<?php

namespace App\Http\Controllers;

use App\Membership;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('adminormanager')->except(['search']);
    }

    public function show()
    {
        return view('membership.view');
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));
        $today = Carbon::today()->toDateString();

        $query = Membership::query()->select([
            'id',
            'phone',
            'name',
            'rank',
            'discount_percent',
            'membership_years',
            'expired_at',
            'updated_at',
        ]);

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';
            $query->where(function ($builder) use ($like) {
                $builder->where('phone', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('rank', 'like', $like)
                    ->orWhere('discount_percent', 'like', $like)
                    ->orWhere('membership_years', 'like', $like)
                    ->orWhere('expired_at', 'like', $like)
                    ->orWhereRaw("DATE_FORMAT(updated_at, '%d-%m-%Y') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(updated_at, '%Y-%m-%d') like ?", [$like]);
            });
        }

        $sortableColumns = [
            1 => 'updated_at',
            2 => 'phone',
            3 => 'name',
            4 => 'rank',
            5 => 'discount_percent',
            6 => 'membership_years',
            7 => 'expired_at',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'updated_at';

        $query->orderBy($orderColumn, $orderDirection)->orderBy('id', 'desc');

        $recordsTotal = Membership::count();
        $recordsFiltered = (clone $query)->count('id');

        $memberships = $query->skip($start)->take($length)->get();

        $data = $memberships->values()->map(function ($membership, $index) use ($start, $today) {
            $expiredAt = $membership->expired_at ? Carbon::parse($membership->expired_at) : null;
            $isExpired = $expiredAt ? $expiredAt->toDateString() < $today : false;

            return [
                'id' => $membership->id,
                'row_number' => $start + $index + 1,
                'updated_at_display' => optional($membership->updated_at)->format('d-m-Y'),
                'updated_at_order' => optional($membership->updated_at)->format('Y-m-d H:i:s'),
                'phone' => $membership->phone,
                'name' => $membership->name,
                'rank' => $membership->rank,
                'discount_percent' => (int) $membership->discount_percent,
                'discount_display' => ((int) $membership->discount_percent) . '%',
                'membership_years' => (int) $membership->membership_years,
                'membership_years_display' => (int) $membership->membership_years,
                'expired_at' => $expiredAt ? $expiredAt->format('Y-m-d') : '',
                'expired_at_display' => $expiredAt ? $expiredAt->format('Y-m-d') : '-',
                'status_key' => $isExpired ? 'expired' : 'active',
                'status_label' => $isExpired ? __('messages.expired') : __('messages.active'),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMembership($request);

        $membership = Membership::create($this->buildMembershipPayload($validated));

        return response()->json([
            'code' => 200,
            'data' => $membership,
            'message' => __('messages.membership_created'),
        ]);
    }

    public function update(Request $request)
    {
        $membership = Membership::findOrFail((int) $request->id);
        $validated = $this->validateMembership($request, $membership->id);

        $membership->update($this->buildMembershipPayload($validated));

        return response()->json([
            'code' => 200,
            'data' => $membership->fresh(),
            'message' => __('messages.membership_updated'),
        ]);
    }

    public function destroy(Request $request)
    {
        $membership = Membership::findOrFail((int) $request->id);
        $membership->delete();

        return response()->json([
            'code' => 200,
            'message' => __('messages.membership_deleted'),
        ]);
    }

    public function search(Request $request)
    {
        if (!Auth::check()) {
            abort(403);
        }

        $term = trim((string) $request->input('q', ''));

        if ($term === '') {
            return response()->json([
                'code' => 200,
                'data' => [],
            ]);
        }

        $like = '%' . $term . '%';
        $today = Carbon::today()->toDateString();

        $memberships = Membership::query()
            ->select([
                'id',
                'phone',
                'name',
                'rank',
                'discount_percent',
                'membership_years',
                'expired_at',
            ])
            ->where(function ($builder) use ($like) {
                $builder->where('phone', 'like', $like)
                    ->orWhere('name', 'like', $like);
            })
            ->orderByRaw('CASE WHEN phone = ? THEN 0 WHEN phone LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END', [
                $term,
                $term . '%',
                $term . '%',
            ])
            ->orderByRaw('CASE WHEN expired_at >= ? THEN 0 ELSE 1 END', [$today])
            ->orderBy('expired_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($membership) use ($today) {
                $expiredAt = $membership->expired_at ? Carbon::parse($membership->expired_at) : null;
                $isExpired = $expiredAt ? $expiredAt->toDateString() < $today : false;

                return [
                    'id' => $membership->id,
                    'phone' => $membership->phone,
                    'name' => $membership->name,
                    'rank' => $membership->rank,
                    'discount_percent' => (int) $membership->discount_percent,
                    'membership_years' => (int) $membership->membership_years,
                    'expired_at' => $expiredAt ? $expiredAt->format('Y-m-d') : null,
                    'is_expired' => $isExpired,
                    'status_label' => $isExpired ? __('messages.expired') : __('messages.active'),
                ];
            })
            ->values();

        return response()->json([
            'code' => 200,
            'data' => $memberships,
        ]);
    }

    protected function validateMembership(Request $request, $membershipId = null)
    {
        return $request->validate([
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('memberships', 'phone')->ignore($membershipId),
            ],
            'name' => 'required|string|max:255',
            'rank' => ['required', Rule::in(['VIP', 'DIAMOND'])],
            'membership_years' => 'required|integer|min:1|max:20',
            'expired_at' => 'nullable|date',
        ]);
    }

    protected function buildMembershipPayload(array $validated)
    {
        $years = (int) $validated['membership_years'];
        $expiredAt = !empty($validated['expired_at'])
            ? Carbon::parse($validated['expired_at'])->format('Y-m-d')
            : Carbon::today()->addYears($years)->format('Y-m-d');

        return [
            'phone' => trim($validated['phone']),
            'name' => trim($validated['name']),
            'rank' => $validated['rank'],
            'discount_percent' => $this->discountForRank($validated['rank']),
            'membership_years' => $years,
            'expired_at' => $expiredAt,
        ];
    }

    protected function discountForRank($rank)
    {
        return strtoupper($rank) === 'DIAMOND' ? 6 : 5;
    }
}
