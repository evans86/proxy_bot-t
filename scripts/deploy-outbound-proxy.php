<?php

/**
 * Деплой HTTP-прокси на VPS (SSH + SFTP).
 *
 * Пример:
 * php scripts/deploy-outbound-proxy.php \
 *   --host=167.179.34.13 --user=vpn1 --password='...' \
 *   --allowed-ip=195.2.79.151
 */

declare(strict_types=1);

$vpnAutoload = dirname(__DIR__, 2) . '/vpn/vendor/autoload.php';
if (! is_readable($vpnAutoload)) {
    fwrite(STDERR, "Не найден {$vpnAutoload}. Запустите composer install в проекте vpn.\n");
    exit(1);
}

require $vpnAutoload;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

$options = getopt('', [
    'host:',
    'user:',
    'password:',
    'port::',
    'allowed-ip:',
    'proxy-user::',
    'proxy-pass::',
    'listen-port::',
]);

foreach (['host', 'user', 'password', 'allowed-ip'] as $required) {
    if (empty($options[$required])) {
        fwrite(STDERR, "Обязательный параметр --{$required}\n");
        exit(1);
    }
}

$sshHost = (string) $options['host'];
$sshUser = (string) $options['user'];
$sshPass = (string) $options['password'];
$sshPort = isset($options['port']) ? (int) $options['port'] : 22;
$allowedIp = (string) $options['allowed-ip'];
$proxyUser = (string) ($options['proxy-user'] ?? 'proxy6relay');
$proxyPass = (string) ($options['proxy-pass'] ?? bin2hex(random_bytes(8)));
$listenPort = isset($options['listen-port']) ? (int) $options['listen-port'] : 3128;

$installScript = dirname(__DIR__) . '/scripts/proxy6-outbound-proxy-install.sh';
if (! is_readable($installScript)) {
    fwrite(STDERR, "Не найден {$installScript}\n");
    exit(1);
}

echo "Подключение SSH {$sshUser}@{$sshHost}:{$sshPort}...\n";

$ssh = new SSH2($sshHost, $sshPort);
$ssh->setTimeout(900);
if (! $ssh->login($sshUser, $sshPass)) {
    fwrite(STDERR, "SSH: ошибка авторизации\n");
    exit(1);
}

$sftp = new SFTP($sshHost, $sshPort);
if (! $sftp->login($sshUser, $sshPass)) {
    fwrite(STDERR, "SFTP: ошибка авторизации\n");
    exit(1);
}

$remoteScript = '/tmp/proxy6-outbound-proxy-install.sh';
if (! $sftp->put($remoteScript, $installScript, SFTP::SOURCE_LOCAL_FILE)) {
    fwrite(STDERR, "Не удалось загрузить install-скрипт\n");
    exit(1);
}

$cmd = sprintf(
    'chmod 700 %s && sudo env ALLOWED_CLIENT_IP=%s PROXY_USER=%s PROXY_PASS=%s PORT=%d bash %s 2>&1',
    escapeshellarg($remoteScript),
    escapeshellarg($allowedIp),
    escapeshellarg($proxyUser),
    escapeshellarg($proxyPass),
    $listenPort,
    escapeshellarg($remoteScript)
);

echo "Установка 3proxy на VPS (может занять несколько минут)...\n";
$output = (string) $ssh->exec($cmd);
echo $output . "\n";

if ($ssh->getExitStatus() !== 0) {
    fwrite(STDERR, "Установка завершилась с ошибкой\n");
    exit(1);
}

$proxyUrl = sprintf(
    'http://%s:%s@%s:%d',
    rawurlencode($proxyUser),
    rawurlencode($proxyPass),
    $sshHost,
    $listenPort
);

echo "\nДобавьте в .env проекта proxy:\n";
echo "PROXY6_OUTBOUND_PROXY={$proxyUrl}\n";
echo "\nПроверка с сервера proxy6-back:\n";
echo sprintf(
    'curl -v --connect-timeout 5 --max-time 15 -x "%s" "https://proxy6.net/api/YOUR_KEY/getcountry?version=3"' . "\n",
    $proxyUrl
);
