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
        $this->createAdminModule();
        $this->updateAutoloadPsr4();

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
        if (!str_contains($content, '$this->session = \\Config\\Services::session();')) {
            $pattern = '/public function initController\([^\{]+\{/';
            $replacement = "$0\n        \$this->session = \\Config\\Services::session();";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        // Tambah function render() dalam class sebelum penutup }
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
            // Masukkan sebelum penutup class
            $content = preg_replace('/}\s*$/', $renderFunction . "\n}", $content);
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

    // Check jika $project dan $inteam sudah wujud
    $hasProject = str_contains($content, 'public array $project');
    $hasInteam  = str_contains($content, 'public array $inteam');

    if ($hasProject && $hasInteam) {
        CLI::write("ℹ️  Database config sudah wujud, tidak ditambah.", 'yellow');
        return;
    }

    // Cuba cari $default untuk insert selepas itu
    $pattern = '/(public\s+array\s+\$default\s+=\s+\[[\s\S]+?\];)/m';

    if (preg_match($pattern, $content, $matches)) {
        $newArrays = '';

        if (!$hasProject) {
            $newArrays .= <<<PHP

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
PHP;
        }

        if (!$hasInteam) {
            $newArrays .= <<<PHP

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
        }

        // Replace original $default + tambah new arrays
        $updated = preg_replace($pattern, "$1\n$newArrays", $content, 1);
        file_put_contents($file, $updated);

        if (!$hasProject) CLI::write("✅ Array \$project ditambah.", 'green');
        if (!$hasInteam)  CLI::write("✅ Array \$inteam ditambah.", 'green');
    } else {
        CLI::error("❌ Gagal cari array \$default dalam Database.php.");
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
        $this->copyFileWithLog('LoginController.php', APPPATH . 'Controllers/LoginController.php', 'LoginController.php');
    }

    protected function createLoginModel()
    {
        $this->copyFileWithLog('Login_m.php', APPPATH . 'Models/Login_m.php', 'Login_m.php');
    }

    protected function createAdminModule()
    {
        $basePath = ROOTPATH . 'Modules/admin/';
        $folders = ['Config', 'Controllers', 'Models', 'Views'];
        $files = [
            'Modules/admin/Config/Routes.php'             => $basePath . 'Config/Routes.php',
            'Modules/admin/Controllers/Dashboard_controller.php' => $basePath . 'Controllers/Dashboard_controller.php',
            'Modules/admin/Views/dashboard.php'           => $basePath . 'Views/dashboard.php',
            'Modules/admin/Views/sidebar_layout.php'      => $basePath . 'Views/sidebar_layout.php',
        ];

        foreach ($folders as $folder) {
            $path = $basePath . $folder;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                CLI::write("📁 Folder dicipta: $path", 'blue');
            }
        }

        foreach ($files as $stubRelative => $targetPath) {
            $this->copyFileWithLog($stubRelative, $targetPath, basename($stubRelative));
        }
    }

    protected function copyFileWithLog(string $stubRelativePath, string $targetPath, string $label = '')
    {
        $stubBase = realpath(__DIR__ . '/../../stubs') ?: __DIR__ . '/../../stubs';
        $source = $stubBase . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stubRelativePath);

        CLI::write("🔎 Check source path: $source", 'cyan');

        if (file_exists($source)) {
            if (!is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0755, true);
            }
            copy($source, $targetPath);
            CLI::write("✅ $label disalin ke $targetPath", 'green');
        } else {
            CLI::write("❌ Fail $label tidak ditemui dalam stubs.", 'red');
        }
    }

    protected function updateAutoloadPsr4()
{
    $file = APPPATH . 'Config/Autoload.php';

    if (!file_exists($file)) {
        CLI::error("❌ Autoload.php tidak dijumpai.");
        return;
    }

    $content = file_get_contents($file);

    $hasModules = str_contains($content, "'Modules'");
    $hasConfig  = str_contains($content, "'Config'");

    if ($hasModules && $hasConfig) {
        CLI::write("ℹ️  PSR-4 Autoload telah dikemaskini sebelum ini.", 'yellow');
        return;
    }

    $pattern = '/public\s+\$psr4\s*=\s*\[\s*(.*?)\s*\];/s';

    if (preg_match($pattern, $content, $matches)) {
        $existing = $matches[1];

        if (!$hasConfig) {
            $existing .= "\n        'Config'      => APPPATH . 'Config',";
        }
        if (!$hasModules) {
            $existing .= "\n        'Modules'     => ROOTPATH . 'Modules',";
        }

        $replacement = "public \$psr4 = [\n        $existing\n    ];";
        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($file, $content);

        CLI::write("✅ PSR-4 Autoload dalam Autoload.php telah dikemaskini.", 'green');
    } else {
        CLI::error("❌ Tidak dapat cari definisi \$psr4 dalam Autoload.php.");
    }
}

}