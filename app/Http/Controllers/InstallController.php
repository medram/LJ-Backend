<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

use App\Packages\LC\LCManager;

use App\Models\User;

use Str;
use Hash;


class InstallController extends Controller
{
    // Installer index page.
    public function index(Request $request)
    {
        return redirect()->route("install.requirements");
    }

    // installer Requirements step.
    public function requirements(Request $request)
    {
        $requirements = config("install.extensions");
        $results = [];
        $results["errors"] = false;

        // reset the session
        session()->forget("requirements");

        foreach($requirements as $type => $extensions)
        {
            if (strtolower($type) == "php")
            {
                foreach ($extensions as $ext)
                {
                    $results["extensions"]["php"][$ext] = true;

                    if (!extension_loaded($ext))
                    {
                        $results["extensions"]["php"][$ext] = false;
                        $results["errors"] = true;
                    }
                }
            }
            else if (strtolower($type) == "apache")
            {
                if (function_exists("apache_get_modules"))
                {
                    $available_modules = apache_get_modules();

                    foreach ($extensions as $ext)
                    {
                        $results["extensions"]["apache"][$ext] = true;

                        if (! in_array($ext, $available_modules))
                        {
                            $results["extensions"]["apache"][$ext] = false;
                            $results["errors"] = true;
                        }
                    }
                }
            }

            if (version_compare(PHP_VERSION, config("install.php_version")) == -1)
            {
                $results["php_version"] = false;
                $results["errors"] = true;
            }
            else
            {
                $results["php_version"] = true;
            }
        }

        if ($results["errors"] == false)
        {
            session(["requirements" => true]);
        }

        return view("install.requirements", [
            "php_version"   => config("install.php_version"),
            "requirements"  => $requirements,
            "results"       => $results
        ]);
    }

    // Installer Database step.
    public function database(Request $request)
    {
        $results = [];
        $results["errors"] = false;

        // reset the session
        session()->forget("database");

        if (!session("requirements"))
            return redirect()->route("install.requirements");

        if ($request->method() == "POST")
        {
            $fields = $request->validate([
                "db_host" => "string|required",
                "db_name" => "string|required",
                "db_user" => "string|required",
                "db_pass" => "string|required",
            ]);

            $db_host = $fields["db_host"];
            $db_name = $fields["db_name"];
            $db_user = $fields["db_user"];
            $db_pass = $fields["db_pass"];

            try {
                $databaseStatus = $this->checkDatabaseCredentials($db_host, $db_name, $db_user, $db_pass);
            } catch (\Exception $e){
                return redirect()->back()->with("error", "Invalid database credentials: " . $e->getMessage());
            }

            if ($databaseStatus === true)
            {
                // store DB config
                $this->storeConfiguration("DB_HOST", $db_host);
                $this->storeConfiguration("DB_DATABASE", $db_name);
                $this->storeConfiguration("DB_USERNAME", $db_user);
                $this->storeConfiguration("DB_PASSWORD", $db_pass);

                session(["database" => true]);

                sleep(1);
                // redirect to the next step
                return redirect()->route("install.verify");
            }
            else
            {
                return redirect()->back()->with("error", "Invalid database credentials.");
            }
        }

        return view("install.database", [
            "results" => $results
        ]);
    }

    // Installer perform database migrations, seeds and register default admin account.
    public function installDatabase(Request $request)
    {
        $requirements = session("requirements");
        $database = session("database");
        $verify = session("verify");

        if (!$verify OR !$requirements OR !$database)
            return redirect()->route("install.verify");

        // Perform the installation
        echo "<b>📥 Installing, Please wait...<br></b>";
        flush();

        # perform database migrations
        echo "👉 Installing database migrations...<br>";
        flush();
        $this->installDatabaseMigrations();

        // insert database seeds
        echo "👉 Inserting database seeds...<br>";
        flush();
        $this->installDatabaseSeeds();

        // register default admin account
        echo "👉 Creating default admin account...<br>";
        flush();
        $this->registerDefaultAdminAccount();

        echo "✅ Installed Successfully, redirecting...<br>";
        flush();

        sleep(2);

        session(["installed" => true]);
        return redirect()->route("install.completed");
    }

    // LC verification.
    public function verify(Request $request)
    {
        $requirements = session("requirements");
        $database = session("database");

        if (!$requirements OR !$database)
            return redirect()->route("install.database");

        // clear value
        session()->forget("verify");
        session()->forget("lc");

        $results = [];

        if ($request->method() == "POST")
        {
            $fields = $request->validate([
                "lc" => "string|required"
            ]);

            $lc = $fields["lc"];
            $lcManager = LCManager::getInstance();

            if ($lcManager->check($lc, true))
            {
                session(["verify" => true]);
                session(["lc" => $lc]);

                // redirect to the next step
                return redirect()->route("install.database.install");
            }
            else
            {
                return redirect()->back()->with("error", "Invalid Purchase Code 🫢!");
            }
        }

        return view("install.verify", [
            "results" => $results
        ]);
    }

    // Installer Complate page.
    public function completed(Request $request)
    {
        if (!session("installed"))
            return redirect()->route("install.database");
        // set that the platform is installed
        $this->storeConfiguration("INSTALLED", 1);

        $adminCredentials = session("admin");

        if (!$adminCredentials)
            return redirect("/");

        // register lc in db
        $lc = session("lc");
        if ($lc)
            setSetting("LICENSE_CODE", $lc);

        // clear all prvious sessions
        session()->forget("requirements");
        session()->forget("database");
        session()->forget("admin");
        session()->forget("verify");
        session()->forget("lc");

        return view("install.completed", [
            "admin" => $adminCredentials
        ]);
    }

    // edit .env file.
    private function storeConfiguration($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path))
        {
            try {
                $content = file_get_contents($path);
                $new_content = preg_replace("/{$key}=(.*)\\n/", "{$key}={$value}\n", $content);
                file_put_contents($path, $new_content);
            } catch (\Exception $e) {
                return back()->with('error', 'PHP file_put_contents() function is disabled in your hosting, enable it first');
            }
        }
    }

    // Perform database migrations.
    private function installDatabaseMigrations()
    {
        try {
            Artisan::call("migrate", ["--force" => true]);

            return true;
        } catch (\Exception $e) {
            return back()->with("error", $e->getMessage());
        }
    }

    // Inserting database seeds into DB.
    private function installDatabaseSeeds()
    {
        try {
            Artisan::call("db:seed", ["--force" => true]);

            return true;
        } catch (\Exception $e) {
            return back()->with("error", $e->getMessage());
        }
    }

    // Register default admin account after installation.
    private function registerDefaultAdminAccount()
    {
        $generated_password = Str::random(8);
        $default_email = "admin@test.com";

        $admin = User::where([
            "email" => "admin@test.com",
            "role" => User::ADMIN,
        ])->first();

        if (!$admin)
        {
            $admin = new User();
        }

        $admin->username = "admin";
        $admin->email = $default_email;
        $admin->password = Hash::make($generated_password);
        $admin->role = User::ADMIN;
        $admin->is_active = 1;
        $admin->email_verified_at = now();
        $admin->save();

        session(["admin" => [
            "email" => $default_email,
            "password" => $generated_password
        ]]);
    }

    // Check database credentials
    private function checkDatabaseCredentials(string $db_host, string $db_name, string $db_user, string $db_pass)
    {
        if ($db_host == "")
            $db_host = "localhost";

        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        if (!$conn)
            return new \Exception("Database Connection Error: " . mysqli_connect_error());

        return true;
    }
}
