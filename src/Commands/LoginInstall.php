<?php

namespace IM1\LoginInstaller\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class LoginInstall extends BaseCommand
{
    protected $group       = 'im1';
    protected $name        = 'im1:login';
    protected $description = 'Setup Login LDAP & InTeam, BaseController, Routes, DB config, and LoginController/Model.';

    public function run(array $params)
    {
        CLI::newLine();
        CLI::write('██╗      ██████╗  ██████╗ ██╗███╗   ██╗    ██╗███╗   ███╗', 'light_yellow');
        CLI::write('██║     ██╔═══██╗██╔════╝ ██║████╗  ██║    ██║████╗ ████║', 'light_yellow');
        CLI::write('██║     ██║   ██║██║  ███╗██║██╔██╗ ██║    ██║██╔████╔██║', 'light_yellow');
        CLI::write('██║     ██║   ██║██║   ██║██║██║╚██╗██║    ██║██║╚██╔╝██║', 'light_yellow');
        CLI::write('███████╗╚██████╔╝╚██████╔╝██║██║ ╚████║    ██║██║ ╚═╝ ██║', 'light_yellow');
        CLI::write('╚══════╝ ╚═════╝  ╚═════╝ ╚═╝╚═╝  ╚═══╝    ╚═╝╚═╝     ╚═╝', 'light_yellow');
        CLI::newLine();

        CLI::write(str_pad('Setup Login IM1 + InTeam', 85, ' ', STR_PAD_BOTH), 'yellow');
        CLI::newLine();

        $this->updateBaseController();
        $this->updateDatabaseConfig();
        $this->updateRoutesFile();
        $this->createLoginController();
        $this->createLoginModel();

        CLI::newLine();
        CLI::write("✅ Semua setup berjaya dilaksanakan.", 'green');
        CLI::newLine(2);
    }

    protected function updateBaseController()
    {
        $file = APPPATH . 'Controllers/BaseController.php';

        if (!file_exists($file)) {
            CLI::error("❌ BaseController.php tidak dijumpai.");
            return;
        }

        $content = file_get_contents($file);

        // Tambah $this->session
        if (!str_contains($content, '$this->session = \Config\Services::session();')) {
            $pattern = '/public function initController\([^\{]+\{/';
            $replacement = "$0\n        \$this->session = \\Config\\Services::session();";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        // Tambah function render()
        if (!str_contains($content, 'public function render(')) {
            $renderFunction = <<<PHP

    public function render(\$view, \$data)
    {
        \$role = session()->get('s_JenisPengguna');

        if (!\$role) {
            echo view('errors/html/403', ['message' => 'You do not have permission to access this page.']);
            exit;
        }

        \$allowedModules = [
            'PENTADBIR' => 'admin',
            'STAF'      => 'staf',
        ];

        \$uri = service('uri');
        \$modules = strtolower(\$uri->getSegment(1));

        if (!isset(\$allowedModules[\$role])) {
            echo view('errors/html/403', ['message' => 'Your role has no assigned module.']);
            exit;
        }

        if (\$modules !== \$allowedModules[\$role]) {
            echo view('errors/html/403', ['message' => 'You do not have permission to access this module.']);
            exit;
        }

        \$view_path = "Modules\\\\{\$modules}\\\\Views\\\\";
        \$sidebar_layout = "Modules\\\\{\$modules}\\\\Views\\\\sidebar_layout";

        echo view('layouts/main_layout', [
            'view' => \$view_path . \$view,
            'data' => \$data,
            'sidebar_layout' => \$sidebar_layout,
        ]);
    }
PHP;
            $content .= $renderFunction;
        }

        file_put_contents($file, $content);
        CLI::write("✅ BaseController dikemaskini.", 'green');
    }

    protected function updateDatabaseConfig()
    {
        $file = APPPATH . 'Config/Database.php';

        if (!file_exists($file)) {
            CLI::error("❌ Database.php tidak dijumpai.");
            return;
        }

        $content = file_get_contents($file);

        if (!str_contains($content, 'public array $project')) {
            $newDb = <<<PHP

    public array \$project = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'project',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => true,
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
        'numberNative' => false,
    ];

    public array \$inteam = [
        'DSN'      => '',
        'hostname' => '',
        'username' => '',
        'password' => '',
        'database' => '',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => true,
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
        'numberNative' => false,
    ];
PHP;
            $content = preg_replace('/(public array \$default.*?\];)/s', "$1\n$newDb", $content, 1);
            file_put_contents($file, $content);
            CLI::write("✅ Database.php dikemaskini.", 'green');
        } else {
            CLI::write("ℹ️  Database config sudah wujud, tidak ditambah.", 'yellow');
        }
    }

    protected function updateRoutesFile()
    {
        $file = APPPATH . 'Config/Routes.php';

        if (!file_exists($file)) {
            CLI::error("❌ Routes.php tidak dijumpai.");
            return;
        }

        $content = file_get_contents($file);

        $lines = [
            "\$routes->get('/', 'LoginController::index');",
            "\$routes->get('login', 'LoginController::index');",
            "\$routes->post('login/auth', 'LoginController::auth_login');",
            "\$routes->get('logout', 'LoginController::logout');",
            "foreach(glob(ROOTPATH.'Modules/*/Config/Routes.php') as \$file) { require \$file; }"
        ];

        $added = false;
        foreach ($lines as $line) {
            if (!str_contains($content, $line)) {
                $content .= "\n" . $line;
                $added = true;
            }
        }

        if ($added) {
            file_put_contents($file, $content);
            CLI::write("✅ Routes.php dikemaskini.", 'green');
        } else {
            CLI::write("ℹ️  Semua route telah wujud sebelum ini.", 'yellow');
        }
    }

    protected function createLoginController()
    {
        $source = realpath(__DIR__ . '/../../../stubs/LoginController.php');
        $target = APPPATH . 'Controllers/LoginController.php';

        if (!file_exists($source)) {
            CLI::error("❌ Fail LoginController.php tidak ditemui dalam stubs.");
            return;
        }

        copy($source, $target);
        CLI::write("✅ LoginController.php disalin ke app/Controllers/", 'green');
    }

    protected function createLoginModel()
    {
        $source = realpath(__DIR__ . '/../../../stubs/Login_m.php');
        $target = APPPATH . 'Models/Login_m.php';

        if (!file_exists($source)) {
            CLI::error("❌ Fail Login_m.php tidak ditemui dalam stubs.");
            return;
        }

        copy($source, $target);
        CLI::write("✅ Login_m.php disalin ke app/Models/", 'green');
    }

}