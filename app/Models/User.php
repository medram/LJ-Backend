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
use App\Models\AccessToken;
use Mail;
use Str;
use DB;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    public const USER = 0;
    public const ADMIN = 1;

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
        "email_verified_at" => "datetime",
        "role"              => "integer",
        "is_active"         => "boolean",
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function availableAccessTokens()
    {
        $tokens = $this->accessTokens();
        $validTokens = [];

        foreach ($tokens as $token) {
            if ($token->expires_at == null || Carbon::now()->lt($token->expires_at)) {
                $validTokens[] = $token;
            }
        }

        return $validTokens;
    }

    public function generateAccessToken(int $days = 30)
    {
        $token = Str::random(64);
        $hashedToken = hash("sha256", $token);

        $accessToken = new AccessToken();
        $accessToken->user_id = $this->id;
        $accessToken->token = $hashedToken;
        $accessToken->expires_at = Carbon::now()->addDays($days);
        $accessToken->save();

        return ["access_token" => $accessToken, "token" => $token];
    }

    public function getCurrentSubscription()
    {
        return Subscription::where("user_id", $this->id)->orderBy("created_at", "desc")->first();
    }

    public function isAdmin()
    {
        return $this->role == 1;
    }

    public function sendVerificationEmail()
    {
        $settings = getAllSettings();

        $personal_token = $this->createToken('reset_password', ["RESET_PASSWORD"])->accessToken;
        $personal_token->expires_at = now()->addMinutes(30); // the token will expire after 30 minutes.
        $personal_token->save();

        $token = $personal_token->token;

        $verification_link = url("verify/{$token}");

        return Mail::send('mails.verification', [
            "SITE_NAME" => $settings['SITE_NAME'],

            "USERNAME"  => $this->username,
            "EMAIL"     => $this->email,
            "VERIFICATION_LINK" => $verification_link,
        ], function ($message) {

            $message->to($this->email, $this->username);
            //$message->replyTo($settings['SMTP_USER'], $settings['SITE_NAME']);
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
            //$message->replyTo($settings['SMTP_USER'], $settings['SITE_NAME']);
            $message->subject("Password Reset.");

        });
    }
}
