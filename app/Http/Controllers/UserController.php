<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\StoreUserRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\BarberBookingMail;
use App\Mail\UserBookingMail;
use App\Models\BarberService;
use App\Models\Booking;
use App\Models\ServiceSlot;
use App\Models\User;
use Exception;

class UserController extends Controller
{
    use ImageUploadTrait;

    function index()
    {
        $users = User::where('status',1)->orderBy('id', 'desc')->get();
        return response()->json(['status' => true, 'data' => $users]);
    }

    function store(StoreUserRequest $request)
    {
        $request = $request->validated();

        try {
            !empty($request['password']) ? $request['password'] = bcrypt($request['password']) : '';
            if (!empty($request['image'])) 
            {
                $imageName = 'uploads/user/images/'.$request['image']->getClientOriginalName().'.'.$request['image']->extension();
                $request['image']->move(public_path('uploads/user/images'), $imageName);
                $request['image']=$imageName;
            }
            $user = User::create($request);

            // SEND WELCOME MAIL
            // Mail::to($user->email)->send(new JudgeWelcomeMail);

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $user]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => false, 'error' => $th]);
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function update(UpdateUserRequest $request, User $user)
    {
        $request = $request->validated();

        try {
            if (!empty($request['password'])) $request['password'] = bcrypt($request['password']);
            else unset($request['password']);
            $user->update($request);
            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $user]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function show(User $user)
    {
        if(auth('user_api')->check()) $user = auth()->user();
        return response()->json(['status' => true, 'data' => $user]);
    }

    function destroy($user)
    {
        return User::destroy($user);
    }

    function activate($user)
    {
        $user = User::findOrFail($user);
        if ($user->status == 0) {
            $user->update(['status' => 1]);

            Mail::raw("Updated", function ($message) use ($user) {
                $message->to($user->email)->subject('Account Approved');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            return response()->json(['status' => true, 'response' => "Account approved and mail sent to user"]);
        }
        $user->update(['status' => 0]);
        return response()->json(['status' => true, 'response' => "Account deactivated"]);
    }

    function updateImage()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'image' => 'required|mimes:jpeg,jpg,png,gif|max:30000',
            ]
        );

        if ($validator->fails()) return response()->json(['status' => false, 'error' => $validator->errors()]);

        try {
            $user = auth()->user();
            // DELETING OLD IMAGE IF EXISTS
            if (!empty($user->image)) {
                $this->deleteImage($user->image);
                $user->update(['image' => (NULL)]);
            }

            // UPLOADING NEW IMAGE
            $filePath = $this->uploadImage(request()->image, 'uploads/user/images');
            $user->update(['image' => $filePath]);
            return response()->json(['status' => true, 'response' => 'Image Updated']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function imageDelete()
    {
        $user = auth()->user();

        if (!empty($user->image)) {
            $this->deleteImage($user->image);
            $user->update(['image' => '']);
        }
        return response()->json(['status' => true, 'response' => 'Image Deleted']);
    }

    function setAndSendPassword($user)
    {
        $allowedCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $randomPassword = Str::random(8, $allowedCharacters);
        $encryptedPassword = bcrypt($randomPassword);
        $user = User::find($user);
        $user->update(['password' => $encryptedPassword]);

        // SORTED DATA
        $data = ['email' => $user->email, 'password' => $randomPassword];

        // DISPATCHING JOB
        Mail::to($user->email)->send(new JudgeCredentialMail($data));
        return response()->json(['status' => true, 'response' => 'Credentials will be sent shortly.']);
    }

    protected function forget()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'email' => 'required|min:6|max:50',
            ]
        );

        if ($validator->fails()) return response(['status' => false, 'errors' => $validator->errors()]);

        $user = User::where('email', request()->email)->first();
        if ($user == null) {
            return response(['status' => false, 'message' => 'It looks like we do not have this account!']);
        } else {
            $token = rand(1000, 9999);

            Mail::raw("$token", function ($message) {
                $message->to(request()->email)->subject('Forget Password');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            $user->reset_token = $token;
            if ($user->update()) return response(['status' => true, 'message' => 'Reset Token Has Been Sent! Check Your Email For The Link']);
        }
    }

    protected function resetPwd()
    {
        $controls = request()->all();
        $rules = array(
            'password' => 'required|confirmed|min:6|max:60',
            'token' => 'required|digits:4',
            'password_confirmation' => 'required|min:6|max:60',
        );
        $messages = [
            'password.required' => 'Password is Required field',
            'password_confirmation.required' => 'Password Confirmation is Required field',
        ];
        $validator = Validator::make($controls, $rules, $messages);
        if ($validator->fails()) {
            return response(['status' => false, 'errors' => $validator->errors()]);
        }

        $user = User::where('reset_token', request()->token)->first();
        if ($user != null) {
            $user->password = bcrypt(request()->password);
            $user->reset_token = null;
            $user->update();
            return response(['status' => true, 'errors' => 'Password Updated']);
        }
        return response(['status' => false, 'errors' => 'Token Incorrect Or Token Expired']);
    }

    // BOOKING
    function book(StoreBookingRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $validatedData['user_id'] = auth()->id();
            // IF ADMIN CREATING ON BEHALF
            if(auth()->user()->getTable()=='admins') $validatedData['user_id'] = request()->user;
            $booking = Booking::create($validatedData);

            // SMS
            $user = auth()->user();
            if(auth()->user()->getTable()=='admins') $user = User::find(request()->user);
            if($user->phone)
            {
                $sms= Admin::sendSms($user->phone, $booking);
                if(!$sms['status']) dd($sms['error']);
            }

            // SEND MAIL
            if($user->email) Mail::to(auth()->user()->getTable()=='admins'?User::find($validatedData['user_id'])->email:auth()->user()->email)->send(new UserBookingMail($booking));
            Mail::to(ServiceSlot::with('service.barber')->find($booking->slot_id)->service->barber->email)->send(new BarberBookingMail($booking));
            Mail::to(env('ADMIN_EMAIL'))->send(new BarberBookingMail($booking));

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $booking]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => false, 'error' => $th]);
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    public function pay(StoreBookingRequest $request, BarberService $barber_service)
    {
        $validatedRequest = $request->validated();
        $amount = $barber_service->price;
        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $checkout_session = $stripe->checkout->sessions->create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $barber_service->title,
                        ],
                        'unit_amount' =>  $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'slot' => $request['slot_id'],
                    'user' => auth()->id(),
                    'date' => $request['date'],
                ],
                'customer_email' => auth()->user()->email,
                'mode' => 'payment',
                'success_url' => route('users.services.booking') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('PAYMENT_CANCEL_URL').'?cancel',
            ]);
            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $checkout_session->url]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    protected function payStore()
    {
        $checkout_session_id = $_GET['session_id'];

        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $session = $stripe->checkout->sessions->retrieve($checkout_session_id);
            if(Booking::where('payment_id',$session->payment_intent)->exists()) redirect(env('PAYMENT_CANCEL_URL').'?failed=invalid request payment already exists on this session id');
            Booking::create(['user_id'=>$session->metadata->user, 'slot_id'=>$session->metadata->slot, 'payment_id'=>$session->payment_intent, 'date'=>$session->metadata->date]);
            Mail::to(User::find($session->metadata->user)->email)->send(new UserBookingMail($session->metadata));
            Mail::to(ServiceSlot::with('service.barber')->find($session->metadata->slot)->service->barber->email)->send(new BarberBookingMail($session->metadata));
            Mail::to(env('ADMIN_EMAIL'))->send(new BarberBookingMail($session->metadata));
            
            return redirect(env('PAYMENT_SUCCESS_URL') . '?success=true');
        } catch (Exception $e) {
            return redirect(env('PAYMENT_CANCEL_URL').'?failed='.$e->getMessage());
        }
    }
}
