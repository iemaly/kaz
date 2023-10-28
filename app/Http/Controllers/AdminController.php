<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Traits\ImageUploadTrait;


class AdminController extends Controller
{
    use ImageUploadTrait;

    protected function login(LoginRequest $request)
    {
        $request = $request->validated();
        $login = (new Admin)->login($request['email'], $request['password'], $request['type']);
        $status = false;
        if (!$login['attempt']) return response()->json(['status' => $status, 'error' => 'Invalid Credentials']);
        if ($login['attempt']) {
            $data = auth($login['role'])->user();
            // if($login['role'] == 'subadmin' && !(new Admin)->checkSubadminApproveStatus($data->id)) return response()->json(['status' => false, 'error' => 'Account Not Approved']);
            $data->update(['access_token' => $data->createToken('Access Token For ' . $login['role'])->accessToken]);
            $data['role'] = $login['role'];
            $status = true; 
        }

        return response()->json(['status' => $status, 'data' => $data]);
    }

    function show()
    {
        $admin = Admin::find(auth()->id());
        return response()->json(['status' => true, 'data' => $admin]);
    }

    protected function forgetPwdProcess()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'email' => 'required|min:6|max:50',
            ]
        );

        if ($validator->fails()) return response(['status' => false, 'errors' => $validator->errors()]);

        $admin = Admin::where('email', request()->email)->first();
        if ($admin == null) {
            return response(['status' => false, 'message' => 'It looks like we do not have this account!']);
        } else {
            $token = rand(1000, 9999);

            Mail::raw("$token", function ($message) {
                $message->to(request()->email)->subject('Forget Password');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            $admin->reset_token = $token;
            if ($admin->update()) return response(['status' => true, 'message' => 'Reset Token Has Been Sent! Check Your Email For The Link']);
        }
    }

    protected function resetPwdProcess()
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

        $admin = Admin::where('reset_token', request()->token)->first();
        if ($admin != null) {
            $admin->password = bcrypt(request()->password);
            $admin->reset_token = null;
            $admin->update();
            return response(['status' => true, 'errors' => 'Password Updated']);
        }
        return response(['status' => false, 'errors' => 'Token Incorrect Or Token Expired']);
    }

    function update(UpdateAdminRequest $request)
    {
        $request = $request->validated();

        try {
            if (!empty($request['password'])) $request['password'] = bcrypt($request['password']);
            else unset($request['password']);
            $admin = auth()->user();
            $admin->update($request);
            return response()->json(['status' => true, 'response' => 'Record Updated', 'data' => $admin]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
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

        $media = Admin::find(auth()->id());
        try {
            // DELETING OLD IMAGE IF EXISTS
            if (!empty($media->image)) {
                $this->deleteImage($media->image);
                $media->update(['image' => (NULL)]);
            }

            // UPLOADING NEW IMAGE
            $filePath = $this->uploadImage(request()->image, 'uploads/admin/images');
            $media->update(['image' => $filePath]);
            return response()->json(['status' => true, 'response' => 'Profile Updated']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function imageDelete()
    {
        $admin = Admin::find(auth()->id());
        if (!empty($admin->image)) {
            $this->deleteImage($admin->image);
            $admin->update(['image' => '']);
        }
        return response()->json(['status' => true, 'response' => 'Image Deleted']);
    }

    protected function emailVerify($role, $id)
    {   
        switch ($role) {
            case 'user':
                $user = User::find($id);
                if(!$user->email_verified)
                {
                    $user->update(['email_verified'=>1]);
        
                    Mail::raw("Thank You For Verification", function ($message) use ($user) 
                    {
                        $message->to($user->email)->subject('Email Verified');
                        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    });
                    return redirect(env('USER_URL').'?email_verified=true');
                }
                return redirect(env('USER_URL').'?email_verified=true');
                break;

            case 'professional':
                $professional = Professional::find($id);
                if(!$professional->email_verified)
                {
                    $professional->update(['email_verified'=>1]);
        
                    Mail::raw("Thank You For Verification", function ($message) use ($professional) 
                    {
                        $message->to($professional->email)->subject('Email Verified');
                        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    });
                    return redirect(env('PROFESSIONAL_URL').'?email_verified=true');
                }
                return redirect(env('PROFESSIONAL_URL').'?email_verified=true');
                break;

            case 'business':
                $business = Business::find($id);
                if(!$business->email_verified)
                {
                    $business->update(['email_verified'=>1]);
        
                    Mail::raw("Thank You For Verification", function ($message) use ($business) 
                    {
                        $message->to($business->email)->subject('Email Verified');
                        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    });
                    return redirect(env('BUSINESS_URL').'?email_verified=true');
                }
                return redirect(env('BUSINESS_URL').'?email_verified=true');
                break;
        }
    }
}
