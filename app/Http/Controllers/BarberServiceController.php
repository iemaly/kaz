<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarberServiceRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateBarberServiceRequest;
use App\Models\BarberService;
use Illuminate\Validation\Rule;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\ServiceSlot;
use Carbon\Carbon;

class BarberServiceController extends Controller
{
    use ImageUploadTrait;

    function convertTo24HourFormat($time)
    {
        // Convert the time to a Carbon instance
        $carbonTime = Carbon::parse($time);

        // Check if the time is in the AM range (9 AM to 11:59 AM)
        if ($carbonTime->hour >= 9 && $carbonTime->hour < 12) {
            // Format as 24-hour time
            return $carbonTime->format('H:i');
        } else {
            // Add 12 hours and format as 24-hour time for PM range
            return $carbonTime->addHours(12)->format('H:i');
        }
    }

    function index()
    {
        $services = BarberService::with('timeslots', 'barber')->where('barber_id', request()->barber)->orderBy('id', 'desc')->get();

        $date = request('date', now()->toDateString());
        // dd(now(), $date);
        if(today()->gt($date)) return response()->json(['status'=>false, 'response'=>'Select today or greater date']);

        // Loop through the barber's services
        $dataServices = [];
        foreach ($services as $i => $service) {
            // Loop through the time slots for each service
            $dataService = $service->toArray();
            $dataService['timeslots'] = [];
            foreach ($service->timeslots as $key => $timeslot) {

                $currentTime = now();

                // dd($currentTime, $currentTime->gt($this->convertTo24HourFormat($timeslot->start_time)));
                // REMOVE SLOT IF TIME IS GREATER
                // dd((today()->eq($date) && $currentTime->gt($this->convertTo24HourFormat($timeslot->start_time))), $timeslot->id);
                if (today()->eq($date) && $currentTime->gt($this->convertTo24HourFormat($timeslot->start_time))) {
                    // unset($service->timeslots[$key]);
                    // $service->timeslots->forget($key);
                    continue;
                }
                // dd($timeslot->id ,);
                // Subtract 1 minute from start_time and end_time
                $startMinus1Minute = now()->parse($timeslot->start_time)->format('H:i:s');
                $endMinus1Minute = now()->parse($timeslot->end_time)->format('H:i:s');

                // Check if there are any bookings for this timeslot on the specified date
                $bookings = Booking::with('slot.service.barber')
                    ->where('date', $date)
                    ->whereHas('slot.service.barber', function ($q) use ($service) {
                        $q->where('id', $service->barber->id);
                    })
                    ->get();

                // Check if the timeslot is booked based on start and end time
                $isBooked = $bookings->some(function ($booking) use ($startMinus1Minute, $endMinus1Minute) {
                    $bookingStartTime = now()->parse($booking->slot->start_time)->format('H:i:s');
                    $bookingEndTime = now()->parse($booking->slot->end_time)->format('H:i:s');

                    return (
                        $bookingStartTime >= $startMinus1Minute && $bookingStartTime <= $endMinus1Minute ||
                        $bookingEndTime >= $startMinus1Minute && $bookingEndTime <= $endMinus1Minute
                    );
                });

                // Lock the timeslot for all services if it's booked in any service
                $timeslot->is_available = !$isBooked;
                $dataService['timeslots'][] = $timeslot;
            }
            $dataServices[] = $dataService;
        }
        return response()->json(['status' => true, 'data' => $dataServices]);
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
        return response()->json(['status' => true, 'data' => $barber_service->load('timeslots')]);
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
