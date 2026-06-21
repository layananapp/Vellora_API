<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class RegionController extends Controller
{
    public function provinces()
    {
        return Http::withoutVerifying()
            ->get(
                'https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json'
            )
            ->json();
    }

    public function regencies($provinceId)
    {
        return Http::withoutVerifying()
            ->get(
                "https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{$provinceId}.json"
            )
            ->json();
    }

    public function districts($regencyId)
    {
        return Http::withoutVerifying()
            ->get(
                "https://www.emsifa.com/api-wilayah-indonesia/api/districts/{$regencyId}.json"
            )
            ->json();
    }
}