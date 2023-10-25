<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\admins\CarTypeRequest;
use App\Models\CarType;
use App\Transformers\CarTypeTransformer;
use Illuminate\Http\Request;

class CarTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $car_types = CarType::get();

        $car_types = fractal()
            ->collection($car_types)
            ->transformWith(new CarTypeTransformer('dashboard'))
            ->toArray();

        return $this->dataResponse($car_types, 'all car types', 200);
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
    public function store(CarTypeRequest $request)
    {
        //
        $data = $request->validated();

        $car_type = CarType::create([

            'en'=>['name'=>$data['name_en']],
            'ar'=>['name'=>$data['name_ar']]
        ]);

        try{

            $car_type->addMedia($data['image'])
    
            ->toMediaCollection('car-images');
            }
            catch(\Exception $e){
    
                return $this->dataResponse(null,__('failed to store'),500);
            }
    
        if($car_type){

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
    public function update(CarTypeRequest $request, string $id)
    {
        //
        $data = $request->validated();

        $car_type = CarType::findOrFail($id);

        $updated = $car_type->update([
           'en'=>['name'=>$data['name_en']?? $car_type->translate('en')->name],
           'ar'=>['name'=>$data['name_ar']??$car_type->translate('ar')->name],
        ]);
        try{
            $car_type->clearMediaCollection('car-images');
            
            $car_type->addMedia($data['image'])
    
            ->toMediaCollection('car-images');
            }
            catch(\Exception $e){
    
                return $this->dataResponse(null,__('failed to store'),500);
            }
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
        $car_type = CarType::findOrFail($id);

        if($car_type->delete()){

            return $this->dataResponse(null,__('deleted successfully'),200);
        }
        return $this->dataResponse(null,__('failed to delete'),500);

    }
}
