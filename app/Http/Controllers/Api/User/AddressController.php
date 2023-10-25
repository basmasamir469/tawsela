<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\AddressRequest;
use App\Models\Address;
use App\Transformers\AddressTransformer;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses;
        $addresses = fractal()
        ->collection($addresses)
        ->transformWith(new AddressTransformer())
        ->toArray();
        return $this->dataResponse($addresses,'addresses',200);     

    }

    public function store(AddressRequest $request)
    {
        $data = $request->validated();
        if($data['type'] == Address::HOME || $data['type'] == Address::WORK)
        {
            if($request->user()->addresses()->where('type',$data['type'])->first())
            {
                  return $this->dataResponse(null,__('failed to save addresss work && home address already existed'),422);
            }
        }

        $request->user()->addresses()->create([
            'type'       => $data['type'],
            'name'       => $data['name'],
            'latitude'   => $data['latitude'],
            'longitude'   => $data['longitude'],
        ]);
        return $this->dataResponse(null,__('address stored successfully'),200);     

    }
}
