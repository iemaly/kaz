<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarberRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\UpdateBarberRequest;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\ServiceSlot;
use Carbon\Carbon;

class BarberController extends Controller
{
    use ImageUploadTrait;

    function index()
    {
        $barbers = Barber::with('services.timeslots')->where('status',1)->orderBy('id', 'desc')->get();
        return response()->json(['status' => true, 'data' => $barbers]);
    }

    function store(StoreBarberRequest $request)
    {
        $request = $request->validated();

        try {
            !empty($request['password']) ? $request['password'] = bcrypt($request['password']) : '';
            if (!empty($request['image'])) {
                $imageName = 'uploads/barber/images/' . $request['image']->getClientOriginalName() . '.' . $request['image']->extension();
                $request['image']->move(public_path('uploads/barber/images'), $imageName);
                $request['image'] = $imageName;
            }
            $barber = Barber::create($request);

            // SEND WELCOME MAIL
            // Mail::to($barber->email)->send(new JudgeWelcomeMail);

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $barber]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => false, 'error' => $th]);
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function update(UpdateBarberRequest $request, Barber $barber)
    {
        $request = $request->validated();

        try {
            if (!empty($request['password'])) $request['password'] = bcrypt($request['password']);
            else unset($request['password']);
            $barber->update($request);
            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $barber]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

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

    function show($barber)
    {
        // Find the barber with their associated services, time slots, and bookings
        $barber = Barber::with('services.timeslots.bookings.slot')->where(['id' => $barber, 'status' => 1])->firstOrFail();

        // Get the date from the request, or use the current date if not provided
        $date = request('date', now()->toDateString());
        // dd(now(), $date);
        if(today()->gt($date)) return response()->json(['status'=>false, 'response'=>'Select today or greater date']);

        // Loop through the barber's services
        $data = $barber->toArray();
        $data['services'] = [];
        foreach ($barber->services as $i => $service) {
            // Loop through the time slots for each service
            $dataService = $service->toArray();
            $dataService['timeslots'] = [];
            foreach ($service->timeslots as $key => $timeslot) {

                $currentTime = now();

                // dd($currentTime, $currentTime->gt($this->convertTo24HourFormat($timeslot->start_time)));
                // REMOVE SLOT IF TIME IS GREATER
                if (today()->eq($date) && $currentTime->gt($this->convertTo24HourFormat($timeslot->start_time))) {
                    // unset($service->timeslots[$key]);
                    // $service->timeslots->forget($key);
                    continue;
                }
                // $barber->services[$i]->timeslots = $service->timeslots->values();
                // Subtract 1 minute from start_time and end_time
                $startMinus1Minute = now()->parse($timeslot->start_time)->format('H:i:s');
                $endMinus1Minute = now()->parse($timeslot->end_time)->format('H:i:s');

                // Check if there are any bookings for this timeslot on the specified date
                $bookings = Booking::with('slot.service.barber')
                    ->where('date', $date)
                    ->whereHas('slot.service.barber', function ($q) use ($barber) {
                        $q->where('id', $barber->id);
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
            $data['services'][] = $dataService;
        }

        // Return the response with the updated data
        return response()->json(['status' => true, 'data' => $data]);
    }

    function destroy($barber)
    {
        return Barber::destroy($barber);
    }

    function activate($barber)
    {
        $barber = Barber::findOrFail($barber);
        if ($barber->status == 0) {
            $barber->update(['status' => 1]);

            Mail::raw("Updated", function ($message) use ($barber) {
                $message->to($barber->email)->subject('Account Approved');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            return response()->json(['status' => true, 'response' => "Account approved and mail sent to barber"]);
        }
        $barber->update(['status' => 0]);
        return response()->json(['status' => true, 'response' => "Account deactivated"]);
    }

    function updateImage(Barber $barber)
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
            if (!empty($barber->image)) {
                $this->deleteImage($barber->image);
                $barber->update(['image' => (NULL)]);
            }

            // UPLOADING NEW IMAGE
            $filePath = $this->uploadImage(request()->image, 'uploads/barber/images');
            $barber->update(['image' => $filePath]);
            return response()->json(['status' => true, 'response' => 'Image Updated']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function imageDelete(Barber $barber)
    {
        if (!empty($barber->image)) {
            $this->deleteImage($barber->image);
            $barber->update(['image' => '']);
        }
        return response()->json(['status' => true, 'response' => 'Image Deleted']);
    }

    function setAndSendPassword($barber)
    {
        $allowedCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $randomPassword = Str::random(8, $allowedCharacters);
        $encryptedPassword = bcrypt($randomPassword);
        $barber = Barber::find($barber);
        $barber->update(['password' => $encryptedPassword]);

        // SORTED DATA
        $data = ['email' => $barber->email, 'password' => $randomPassword];

        // DISPATCHING JOB
        Mail::to($barber->email)->send(new JudgeCredentialMail($data));
        return response()->json(['status' => true, 'response' => 'Credentials will be sent shortly.']);
    }
}
