<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarberRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\UpdateBarberRequest;
use App\Models\Barber;

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

    function show($barber)
    {
        // Find the barber with their associated services, time slots, and bookings
        $barber = Barber::with('services.timeslots.bookings')->where(['id'=>$barber, 'status'=>1])->firstOrFail();

        // Get the date from the request, or use the current date if not provided
        $date = request('date', now()->toDateString());

        // Loop through the barber's services
        foreach ($barber->services as $service) {
            // Loop through the time slots for each service
            foreach ($service->timeslots as $timeslot) {
                // Check if there are any bookings for this timeslot on the specified date
                $isAvailable = $timeslot->bookings
                    ->where('date', $date)
                    ->isEmpty();

                // Add the 'is_available' attribute to the timeslot with the result
                $timeslot->is_available = $isAvailable;
            }
        }

        // Return the response with the updated data
        return response()->json(['status' => true, 'data' => $barber]);
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
