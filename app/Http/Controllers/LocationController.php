<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Street;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate(['region_id' => ['required', 'exists:regions,id']]);

        return response()->json(District::query()->where('region_id', $validated['region_id'])->orderBy('name')->get(['id', 'name']));
    }

    public function wards(Request $request): JsonResponse
    {
        $validated = $request->validate(['district_id' => ['required', 'exists:districts,id']]);

        return response()->json(Ward::query()->where('district_id', $validated['district_id'])->orderBy('name')->get(['id', 'name']));
    }

    public function streets(Request $request): JsonResponse
    {
        $validated = $request->validate(['ward_id' => ['required', 'exists:wards,id']]);

        return response()->json(Street::query()->where('ward_id', $validated['ward_id'])->orderBy('name')->get(['id', 'name']));
    }
}
