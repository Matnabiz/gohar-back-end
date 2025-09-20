<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{
    public function getStates()
    {
        $res = Http::get('https://iran-locations-api.ir/api/v1/fa/states');
        return $res->json(); // Laravel automatically sends JSON to frontend
    }

    public function getCities($stateId)
    {
        $res = Http::get("https://iran-locations-api.ir/api/v1/fa/cities?state_id={$stateId}");
        return $res->json();
    }
}
