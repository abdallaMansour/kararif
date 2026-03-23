<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;

class CountryController extends Controller
{
    /**
     * Return list of countries for registration, filters, etc.
     */
    public function index()
    {
        $countries = config('countries', []);
        return ApiResponse::success([
            'countries' => $countries,
        ]);
    }
}
