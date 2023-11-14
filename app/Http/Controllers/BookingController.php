<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use App\Mail\BarberBookingMail;
use App\Mail\UserBookingMail;
use App\Models\ServiceSlot;
use Twilio\Rest\Client;
use Exception;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bookings = Booking::with('user', 'slot.service.barber')->get();
        return response()->json(['status'=>true, 'data'=>$bookings]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBookingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookingRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function show(Booking $booking)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBookingRequest  $request
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $validatedData = $request->validated();

        try {
            $booking->update($validatedData);

            $sms= Admin::sendSms("+923333525173");
            if(!$sms['status']) dd($sms['error']);

            // SEND MAIL
            Mail::to($booking->user->email)->send(new UserBookingMail($booking));
            Mail::to(ServiceSlot::with('service.barber')->find($booking->slot_id)->service->barber->email)->send(new BarberBookingMail($booking));
            Mail::to(env('ADMIN_EMAIL'))->send(new BarberBookingMail($booking));

            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $booking]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => false, 'error' => $th]);
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Booking $booking)
    {
        return $booking->delete();
    }
}
