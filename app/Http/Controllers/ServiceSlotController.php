<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceSlotRequest;
use App\Http\Requests\UpdateServiceSlotRequest;
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
            ['service_id' => $request->route('service')],
            ['service_id' => [
                Rule::exists('barbers', 'id'),
            ]]
        );
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
        }
        
        $request = $request->validated();

        try {
            $request['service_id'] = $request->route('service');
            $slot = ServiceSlot::create($request);

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $slot]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function update(UpdateBarberServiceRequest $request, BarberService $barber_service)
    {
        $validator = Validator::make(
            ['barber_id' => $request->route('barber')],
            ['barber_id' => [
                Rule::exists('barbers', 'id'),
            ]]
        );
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
        }

        $request = $request->validated();

        try {
            $request['barber_id'] = request()->barber;
            $barber_service->update($request);
            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $barber_service]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function show(BarberService $barber_service)
    {
        return response()->json(['status' => true, 'data' => $barber_service]);
    }

    function destroy(BarberService $barber_service)
    {
        return $barber_service->delete();
    }

    function updateImage(BarberService $barber_service)
    {
        $validator = Validator::make(
            request()->all(),
            [
                'image' => 'required|mimes:jpeg,jpg,png,gif|max:30000',
            ]
        );

        if ($validator->fails()) return response()->json(['status' => false, 'error' => $validator->errors()]);

        try {
            // DELETING OLD IMAGE IF EXISTS
            if (!empty($barber_service->image)) {
                $this->deleteImage($barber_service->image);
                $barber_service->update(['image' => (NULL)]);
            }

            // UPLOADING NEW IMAGE
            $filePath = $this->uploadImage(request()->image, 'uploads/barber/services');
            $barber_service->update(['image' => $filePath]);
            return response()->json(['status' => true, 'response' => 'Image Updated']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function imageDelete(BarberService $barber_service)
    {
        if (!empty($barber_service->image)) {
            $this->deleteImage($barber_service->image);
            $barber_service->update(['image' => '']);
        }
        return response()->json(['status' => true, 'response' => 'Image Deleted']);
    }
}
