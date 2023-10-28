<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\UserImage;
use App\Models\UserQualification;

class UserController extends Controller
{
    use ImageUploadTrait;

    function index()
    {
        $users = User::with('qualifications', 'images')->orderBy('id', 'desc')->get();
        return response()->json(['status' => true, 'data' => $users]);
    }

    function store(StoreUserRequest $request)
    {
        $request = $request->validated();
        $qualificaitons = $request['qualifications']??[];
        $images = $request['images']??[];
        unset($request['qualifications']);
        unset($request['images']);

        try {
            !empty($request['password']) ? $request['password'] = bcrypt($request['password']) : '';
            $user = User::create($request);

            // STORE QUALIFICATION
            if (!empty($qualificaitons)) {
                // return $request;
                foreach ($qualificaitons as $qualification) {
                    $filePath = $this->uploadImage($qualification, 'uploads/users/qualifications/', Str::random(25));
                    $user->qualifications()->create(['qualification'=>$filePath]);
                }
            }

            // STORE IMAGE
            if (!empty($images)) {
                // return $request;
                foreach ($images as $image) {
                    $filePath = $this->uploadImage($image, 'uploads/users/images/', Str::random(25));
                    $user->images()->create(['image'=>$filePath]);
                }
            }

            // SEND WELCOME MAIL
            // Mail::to($user->email)->send(new JudgeWelcomeMail);

            return response()->json(['status' => true, 'response' => 'Record Created', 'data' => $user->load('qualifications', 'images')]);
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

    function show($user)
    {
        $user = User::with('qualifications', 'images')->find($user);
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

    function storeImages(User $user)
    {
        $validator = Validator::make(
            request()->all(),
            [
                'images' => 'required|array',
                'images.*' => 'required|mimes:jpeg,jpg,png,gif|max:30000',
            ]
        );

        $validatedData = $validator->validated();

        if ($validator->fails()) return response()->json(['status' => false, 'error' => $validator->errors()]);
        try {
            // UPLOADING
            foreach ($validatedData['images'] as $image) {
                $filePath = $this->uploadImage($image, 'uploads/users/images/');
                $user->images()->create(['image' => $filePath]);
            }
            return response()->json(['status' => true, 'response' => 'Images Added']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function imageDelete(UserImage $user_image)
    {
        if (!empty($user_image->image)) {
            // dd($user_image);
            $this->deleteImage($user_image->image);
            $user_image->delete();
        }
        return response()->json(['status' => true, 'response' => 'Image Deleted']);
    }

    function storeQualifications(User $user)
    {
        $validator = Validator::make(
            request()->all(),
            [
                'qualifications' => 'required|array',
                'qualifications.*' => 'required',
            ]
        );

        $validatedData = $validator->validated();

        if ($validator->fails()) return response()->json(['status' => false, 'error' => $validator->errors()]);
        try {
            // UPLOADING
            foreach ($validatedData['qualifications'] as $qualification) {
                $filePath = $this->uploadImage($qualification, 'uploads/users/qualifications/');
                $user->qualifications()->create(['qualification' => $filePath]);
            }
            return response()->json(['status' => true, 'response' => 'Qualifications Added']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
    }

    function deleteQualification(UserQualification $user_qualification)
    {
        if (!empty($user_qualification->qualification)) {
            $this->deleteImage($user_qualification->qualification);
            $user_qualification->delete();
        }
        return response()->json(['status' => true, 'response' => 'Qualification Deleted']);
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
}
