<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceSlotRequest;
use App\Http\Requests\UpdateServiceSlotRequest;
use App\Models\BarberService;
use App\Models\ServiceSlot;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceSlotController extends Controller
{
    function index()
    {
    }

    function store(StoreServiceSlotRequest $request)
    {
        $validator = Validator::make(
            ['service_id' => request()->barber_service],
            ['service_id' => [
                Rule::exists('barber_services', 'id'),
            ]]
        );
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
        }
        
        $request = $request->validated();

        try {
            foreach($request['slot'] as $slot)
            {
                $slot['service_id'] = request()->barber_service;
                ServiceSlot::create($slot);
            }

            return response()->json(['status' => true, 'response' => 'Record Created']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function update(UpdateServiceSlotRequest $request, BarberService $barber_service,ServiceSlot $slot)
    {
        $request = $request->validated();

        try {
            $slot->update($request);
            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $slot]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function show(ServiceSlot $slot)
    {
        return response()->json(['status' => true, 'data' => $slot]);
    }

    function destroy(BarberService $barber_service, ServiceSlot $slot)
    {
        return $slot->delete();
    }
}
