<?php

namespace App\Providers;

use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;


class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // to ensure that the platform already installed
        if (!isInstalled())
            return null;

        $settings = getAllSettings();

        $config = array(
            'driver'     => "smtp",
            'host'       => $settings['SMTP_HOST'],
            'port'       => $settings['SMTP_PORT'],
            'from'       => [
                'address'   => $settings['SMTP_FROM'] ? $settings['SMTP_FROM'] : $settings['SMTP_USER'],
                'name'      => $settings['SITE_NAME']
            ],
            'encryption' => strtolower($settings['SMTP_MAIL_ENCRIPTION']),
            'username'   => $settings['SMTP_USER'],
            'password'   => $settings['SMTP_PASSWORD'],
            'sendmail'   => '/usr/sbin/sendmail -bs',
            'pretend'    => false,
            'auth_mode'  => null,
        );

        if ($settings['SMTP_ALLOW_INSECURE_MODE'])
        {
            $config += [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ];
        }

        #config(['mail.mailers.smtp' => $config]);
        Config::set('mail', $config);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
