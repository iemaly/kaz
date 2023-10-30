<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarberServiceRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateBarberServiceRequest;
use App\Models\BarberService;
use Illuminate\Validation\Rule;

class BarberServiceController extends Controller
{
    use ImageUploadTrait;

    function index()
    {
        $services = BarberService::where('barber_id', request()->barber)->orderBy('id', 'desc')->get();
        return response()->json(['status' => true, 'data' => $services]);
    }

    function store(StoreBarberServiceRequest $request)
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
            if (!empty($request['image'])) 
            {
                $filePath = $this->uploadImage(request()->image, 'uploads/barber/services');
                $request['image']=$filePath;
            }
            $request['barber_id'] = request()->barber;
            $service = BarberService::create($request);

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $service]);
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
