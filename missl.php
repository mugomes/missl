#!/usr/bin/env php
<?php
# Copyright (C) 2025 Murilo Gomes Julio
# SPDX-License-Identifier: GPL-2.0-only

# Site: https://www.mugomes.com.br

function input($msg) {
    echo $msg;
    return trim(fgets(STDIN));
}

while (true) {

    echo "Script desenvolvido por Murilo Gomes\n";
    echo "Site: https://www.mugomes.com.br\n\n";
    echo "Apoie: https://www.mugomes.com.br/apoie.html\n\n";

    echo "-------------------------------- MiSSL --------------------------------\n";
    echo "Selecione uma opção:\n";
    echo "1. Iniciar o servidor Apache2\n";
    echo "2. Iniciar o servidor MariaDB\n\n";
    echo "3. Reiniciar o servidor Apache2\n";
    echo "4. Reiniciar o servidor MariaDB\n\n";
    echo "5. Parar o servidor Apache2\n";
    echo "6. Parar o servidor MariaDB\n\n";
    echo "7. Criar um subdomínio\n";
    echo "8. Remover subdomínio\n\n";
    echo "9. Listar Certificados do Chrome/Chromium\n";
    echo "10. Ativar SSL no Chrome/Chromium\n";
    echo "11. Remover SSL no Chrome/Chromium\n\n";
    echo "12. Sair\n";
    echo "-------------------------------- /MiSSL --------------------------------\n\n";

    $option = input("Opção: ");

    switch ($option) {

        case "1":
            echo "Iniciando o servidor Apache2...\n";
            system("sudo systemctl start apache2");
            echo "Servidor Apache2 iniciado com sucesso!\n";
            break;

        case "2":
            echo "Iniciando o servidor MariaDB...\n";
            system("sudo systemctl start mariadb");
            echo "Servidor MariaDB iniciado com sucesso!\n";
            break;

        case "3":
            echo "Reiniciando o servidor Apache2...\n";
            system("sudo systemctl restart apache2");
            echo "Servidor Apache2 reiniciado com sucesso!\n";
            break;

        case "4":
            echo "Reiniciando o servidor MariaDB...\n";
            system("sudo systemctl restart mariadb");
            echo "Servidor MariaDB reiniciado com sucesso!\n";
            break;

        case "5":
            echo "Parando o servidor Apache2...\n";
            system("sudo systemctl stop apache2");
            echo "Servidor Apache2 parado com sucesso!\n";
            break;

        case "6":
            echo "Parando o servidor MariaDB...\n";
            system("sudo systemctl stop mariadb");
            echo "Servidor MariaDB parado com sucesso!\n";
            break;

        case "7":
            $nomesub = input("Digite o nome do subdomínio: ");
            $pastaprojetos = input("Digite o nome da pasta principal dos projetos: ");
            $home = getenv("HOME");

            system("sudo rm -f /etc/apache2/ssl/{$nomesub}.localhost.crt");
            system("sudo rm -f /etc/apache2/ssl/{$nomesub}.localhost.key");

            system("rm -rf /tmp/missl/$nomesub");
            system("mkdir -p /tmp/missl/$nomesub");
            chdir("/tmp/missl/$nomesub");
				
            system("openssl req -x509 -nodes -new -sha256 -days 1024 -newkey rsa:2048 -keyout $nomesub.server.key -out $nomesub.server.pem -subj \"/C=BR/CN=$nomesub-Localhost-Root-CA\"");
            system("openssl x509 -outform pem -in {$nomesub}.server.pem -out {$nomesub}.server.crt");

            file_put_contents("{$nomesub}.domains.ext",
"authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names
[alt_names]
DNS.1 = {$nomesub}.localhost
DNS.2 = www.{$nomesub}.localhost");

            system("openssl req -new -nodes -newkey rsa:2048 -keyout {$nomesub}.localhost.key -out {$nomesub}.localhost.csr -subj \"/C=BR/ST=SP/L=Sao Paulo/O={$nomesub}-Localhost-Certificates/CN={$nomesub}.localhost.local\"");
            system("openssl x509 -req -sha256 -days 1024 -in {$nomesub}.localhost.csr -CA {$nomesub}.server.pem -CAkey {$nomesub}.server.key -CAcreateserial -extfile {$nomesub}.domains.ext -out {$nomesub}.localhost.crt");

            system("sudo mkdir -p /etc/apache2/ssl/");
            system("sudo cp {$nomesub}.localhost.crt /etc/apache2/ssl/");
            system("sudo cp {$nomesub}.localhost.key /etc/apache2/ssl/");

            echo "Excluindo subdomínios existentes...\n";
            system("sudo rm -f /etc/apache2/sites-available/{$nomesub}-localhost.conf");
            system("sudo rm -f /etc/apache2/sites-enabled/{$nomesub}-localhost.conf");

            $apacheConf = <<<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName {$nomesub}.localhost
    ServerAlias www.{$nomesub}.localhost
    DocumentRoot {$home}/{$pastaprojetos}/{$nomesub}
    <Directory {$home}/{$pastaprojetos}/{$nomesub}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog {$home}/{$pastaprojetos}/{$nomesub}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerAdmin webmaster@localhost
    ServerName {$nomesub}.localhost
    ServerAlias www.{$nomesub}.localhost
    DocumentRoot {$home}/{$pastaprojetos}/{$nomesub}
    <Directory {$home}/{$pastaprojetos}/{$nomesub}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/apache2/ssl/{$nomesub}.localhost.crt
    SSLCertificateKeyFile /etc/apache2/ssl/{$nomesub}.localhost.key

    ErrorLog {$home}/{$pastaprojetos}/{$nomesub}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF;

            file_put_contents("/tmp/missl/$nomesub/{$nomesub}-localhost.conf", $apacheConf);

            system("sudo cp /tmp/missl/$nomesub/{$nomesub}-localhost.conf /etc/apache2/sites-available/");
            system("sudo ln -s /etc/apache2/sites-available/{$nomesub}-localhost.conf /etc/apache2/sites-enabled/");

            system("sudo systemctl restart apache2");

            echo "Subdomínio criado com sucesso!\n";
            break;

        case "8":
            $nomesub = input("Digite o nome do subdomínio: ");
            echo "Removendo subdomínio...\n";
            system("sudo rm -f /etc/apache2/ssl/{$nomesub}.localhost.crt");
            system("sudo rm -f /etc/apache2/ssl/{$nomesub}.localhost.key");
            system("sudo rm -f /etc/apache2/sites-available/{$nomesub}-localhost.conf");
            system("sudo rm -f /etc/apache2/sites-enabled/{$nomesub}-localhost.conf");
            system("rm -rf /tmp/missl/{$nomesub}");
            echo "Subdomínio removido com sucesso!\n";
            break;

        case "9":
            $isSnap = shell_exec("snap list | grep -q '^chromium\\s' && echo 1");
            if (trim($isSnap) === "1") {
                system("certutil -d sql:\$HOME/snap/chromium/current/.pki/nssdb -L");
            } else {
                system("certutil -d sql:\$HOME/.pki/nssdb -L");
            }
            break;

        case "10":
            $nomesub = input("Digite o nome do subdomínio: ");
            $isSnap = shell_exec("snap list | grep -q '^chromium\\s' && echo 1");

            if (trim($isSnap) === "1") {
                system("certutil -d sql:\$HOME/snap/chromium/current/.pki/nssdb -A -t \"C,,\" -n \"$nomesub\" -i /tmp/missl/$nomesub/{$nomesub}.server.pem");
            } else {
                system("certutil -d sql:\$HOME/.pki/nssdb -A -t \"C,,\" -n \"$nomesub\" -i /tmp/missl/$nomesub/{$nomesub}.server.pem");
            }
            break;

        case "11":
            $nickname = input("Digite o nickname do certificado: ");
            $isSnap = shell_exec("snap list | grep -q '^chromium\\s' && echo 1");

            if (trim($isSnap) === "1") {
                system("certutil -d sql:\$HOME/snap/chromium/current/.pki/nssdb -D -n \"$nickname\"");
            } else {
                system("certutil -d sql:\$HOME/.pki/nssdb -D -n \"$nickname\"");
            }
            break;

        case "12":
            echo "Saindo...\n";
            exit(0);

        default:
            echo "Opção inválida. Tente novamente.\n";
    }

    echo "\n";
}
