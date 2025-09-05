<?php
if (isset($_SERVER['SCRIPT_NAME']) && str_ends_with($_SERVER['SCRIPT_NAME'], '/php/api/file_proxy.php')) {
  return; // skip global bootstrap for the proxy
}

// Idempotent helper: set only if missing
function vh_env_default(string $k, string $v): void {
    $cur = getenv($k);
    if ($cur === false || $cur === '' || $cur === 'change-me') {
        putenv($k.'='.$v);
    }
}

vh_env_default('APP_BASE_URL','https://vectrahub.online');
vh_env_default('APP_SECRET','bfeb38f3e01e450a55a7a638f0d2c68a39ff3ddd462d6cb545455c430291bf60');
vh_env_default('RUNNER_BASE_URL','https://runner.vectrahub.online');
vh_env_default('RUNNER_TOKEN','997f3011e1364e86ba238f3af129cd043b8e5e0440ae4f958c198753183f88b3');

