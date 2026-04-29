<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndonesiaRegion;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Get all provinces
     */
    public function getProvinces()
    {
        $provinces = IndonesiaRegion::getProvinces();
        
        return response()->json([
            'status' => 'success',
            'data' => $provinces
        ]);
    }

    /**
     * Get cities by province code
     */
    public function getCities(Request $request)
    {
        $provinceCode = $request->get('province_code');
        
        if (!$provinceCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Province code is required'
            ], 400);
        }

        $cities = IndonesiaRegion::getCitiesByProvince($provinceCode);
        
        return response()->json([
            'status' => 'success',
            'data' => $cities
        ]);
    }

    /**
     * Get districts by city code
     */
    public function getDistricts(Request $request)
    {
        $cityCode = $request->get('city_code');
        
        if (!$cityCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'City code is required'
            ], 400);
        }

        $districts = IndonesiaRegion::getDistrictsByCity($cityCode);
        
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }

    /**
     * Get villages by district code
     */
    public function getVillages(Request $request)
    {
        $districtCode = $request->get('district_code');
        
        if (!$districtCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'District code is required'
            ], 400);
        }

        $villages = IndonesiaRegion::getVillagesByDistrict($districtCode);
        
        return response()->json([
            'status' => 'success',
            'data' => $villages
        ]);
    }
}
