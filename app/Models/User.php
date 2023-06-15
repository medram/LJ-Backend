<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

use App\Models\Subscription;

use Mail;
use Str;
use DB;

$settings = getAllSettings();


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar',
        'is_active',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isAdmin()
    {
        return $this->role == 1;
    }

    public function sendVerificationEmail()
    {
        $settings = getAllSettings();

        $user_token = "";
        $verification_link = url("verify/{$user_token}");

        return Mail::send('mails.verification', [
            "SITE_NAME" => $settings['SITE_NAME'],

            "USERNAME"  => $this->username,
            "EMAIL"     => $email,
            "VERIFICATION_LINK" => $verification_link,
        ], function ($message) {

            $message->to($this->email, $this->username);
            //$message->replyTo($settings['SMTP_HOST'], $settings['SITE_NAME']);
            $message->subject("Account Verification.");

        });
    }


    public function sendResetPasswordEmail()
    {
        $settings = getAllSettings();

        $token = Str::random(60);

        // Delete any previous tokens for this particular email.
        DB::table('password_reset_tokens')
                ->where('email', $this->email)
                ->delete();

        //Create Password Reset Token
        DB::table('password_reset_tokens')->insert([
            'email' => $this->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        $reset_password_link = url("reset/{$token}");

        return Mail::send('mails.reset_password', [
            "SITE_NAME" => $settings['SITE_NAME'],

            "USERNAME"  => $this->username,
            "EMAIL"     => $this->email,
            "RESET_PASSWORD_LINK" => $reset_password_link,
        ], function ($message) {

            $message->to($this->email, $this->username);
            //$message->replyTo($settings['SMTP_HOST'], $settings['SITE_NAME']);
            $message->subject("Password Reset.");

        });
    }
}
