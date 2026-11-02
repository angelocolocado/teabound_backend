<?php

namespace App\Http\Controllers;

use App\Http\Requests\Coupon\CouponPatchRequest;
use App\Http\Requests\Coupon\CouponPostRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Coupon::class);

        return response()->json(Coupon::query()->orderBy('code')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CouponPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);

        return response()->json(Coupon::create($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        Gate::authorize('view', $coupon);

        return response()->json($coupon);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CouponPatchRequest $request, Coupon $coupon): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        $coupon->update($data);

        return response()->json($coupon->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon): Response
    {
        Gate::authorize('delete', $coupon);
        $coupon->delete();

        return response()->noContent();
    }
}
