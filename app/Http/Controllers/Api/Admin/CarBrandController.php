<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\admins\CarBrandRequest;
use App\Http\Requests\admins\CarTypeRequest;
use App\Models\CarBrand;
use App\Models\CarType;
use App\Transformers\CarBrandTransformer;
use Illuminate\Http\Request;

class CarBrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $car_brands = CarBrand::get();

        $car_brands = fractal()
            ->collection($car_brands)
            ->transformWith(new CarBrandTransformer('dashboard'))
            ->toArray();

        return $this->dataResponse($car_brands, 'all car brands', 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarBrandRequest $request)
    {
        //
        $data = $request->validated();

        $car_brand = CarBrand::create([

            'en'=>['name'=>$data['name_en']],
            'ar'=>['name'=>$data['name_ar']]
        ]);
        if($car_brand){

        return $this->dataResponse(null,__('stored successfully'),200);
        }
        return $this->dataResponse(null,__('failed to store'),500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarBrandRequest $request, string $id)
    {
        //
        $data = $request->validated();

        $car_brand = CarBrand::findOrFail($id);

        $updated = $car_brand->update([
           'en'=>['name'=>$data['name_en']?? $car_brand->translate('en')->name],
           'ar'=>['name'=>$data['name_ar']?? $car_brand->translate('ar')->name],
        ]);
        if($updated)
        {
            return $this->dataResponse(null,__('updated successfully'),200);
        }
            return $this->dataResponse(null,__('failed to update'),500);



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $car_brand = CarBrand::findOrFail($id);

        if($car_brand->delete()){

            return $this->dataResponse(null,__('deleted successfully'),200);
        }
        return $this->dataResponse(null,__('failed to delete'),500);

    }
}
