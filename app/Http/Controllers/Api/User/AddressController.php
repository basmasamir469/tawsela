<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\AddressRequest;
use App\Services\Address\AddressService;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService)
    {
    }

    public function index()
    {
        $addresses = $this->addressService->listForUser(auth()->user());

        return $this->dataResponse($addresses, 'addresses', 200);
    }

    public function store(AddressRequest $request)
    {
        try {
            $this->addressService->store($request->user(), $request->validated());

            return $this->dataResponse(null, __('address stored successfully'), 200);
        } catch (\RuntimeException $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }

    public function update(AddressRequest $request, $id)
    {
        try {
            $this->addressService->update($request->user(), (int) $id, $request->validated());

            return $this->dataResponse(null, __('address updated successfully'), 200);
        } catch (\RuntimeException $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }
}