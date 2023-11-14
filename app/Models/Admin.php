<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Exception;
use Twilio\Rest\Client;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'access_token',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'access_token',
        'remember_token',
        'reset_token',
        'auth_token',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function sendSms($receiverNumber)
    {
        $message = "This is testing from hnh tech solutions";
  
        try {
  
            $account_sid = env("TWILIO_SID");
            $auth_token = env("TWILIO_TOKEN");
            $twilio_number = env("TWILIO_FROM");
  
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number, 
                'body' => $message]);
  
            return ['status'=>true];
  
        } catch (Exception $e) {
            return ['status'=>false, 'error'=>$e->getMessage()];
        }
    }

    private function adminByTokenExists($token)
    {
        $admin =  $this->where('reset_token', $token);
        if ($admin->exists()) return $admin->first()->id;
        return false;
    }

    public function login($email, $password, $type)
    {
        $attempt = auth($type)->attempt(['email' => $email, 'password' => $password]);
        return ['status' => true, 'role' => $type, 'attempt' => $attempt];
    }

    function emailVerified($type, $id): bool
    {
        switch ($type) {
            case 'user':
                $user = User::find($id);
                if (!$user->email_verified) return false;
                return true;
                break;
            case 'professional':
                $professional = Professional::find($id);
                if (!$professional->email_verified) return false;
                return true;
                break;
            case 'business':
                $business = Business::find($id);
                if (!$business->email_verified) return false;
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    public function checkSubadminApproveStatus($id): bool
    {
        $subadmin = Subadmin::find($id);
        if (!$subadmin->status) return false;
        return true;
    }

    public function checkUserApproveStatus($id): bool
    {
        $user = User::find($id);
        if (!$user->status) return false;
        return true;
    }

    public function checkCarehomeApproveStatus($id): bool
    {
        $carehome = CareHome::find($id);
        if (!$carehome->status) return false;
        return true;
    }

    public function checkProfessionalApproveStatus($id): bool
    {
        $professional = Professional::find($id);
        if (!$professional->status) return false;
        return true;
    }

    public function checkBusinessApproveStatus($id): bool
    {
        $business = Business::find($id);
        if (!$business->status) return false;
        return true;
    }

    public function setResetToken($email, $token, $type)
    {
        if ($this->adminExists($email, $type)) return $this->whereEmail($email)->update(['reset_token' => $token]);
    }

    public function resetPassword($token, $password)
    {
        $admin = $this->adminByTokenExists($token);
        if (!$admin) return false;

        $admin = $this->find($admin);
        $admin->reset_token = null;
        $admin->password = bcrypt($password);
        return $admin->update();
    }

    // POLYMORPHIC RELATION
    function addedUsers()
    {
        return $this->morphMany(User::class, 'added_by');
    }

    // RELATIONS

    // ACCESSOR
    protected function image(): Attribute
    {
        return Attribute::make(
            fn ($value) => !empty($value) ? asset($value) : asset('assets/profile_pics/admin.jpg'),
        );
    }
}
