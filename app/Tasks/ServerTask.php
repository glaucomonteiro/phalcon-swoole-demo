<?php

use Config\Environment;
use Lib\Logger\Logger;
use Phalcon\Cli\Task;

class ServerTask extends Task
{
    public function mainAction()
    {
        echo 'This is the default action that does nothing at all' . PHP_EOL;
    }

    public function restartAction()
    {
        $logger = Logger::get_instance();
        $logger->info('Sending request to restart the server');
        $curl = curl_init();
        $service = new Auth;
        $token = $service->create_admin_token();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://' . Environment::load_env(Environment::DOCKER_IP) . ':' . Environment::load_env(Environment::PORT) . '/restart',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{}",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json, text/plain, */*",
                "Content-Type: application/json",
                'Authorization: Bearer ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $logger->info($err);
        } else {
            $logger->info($response);
        }
    }

    public function reloadAction()
    {
        $logger = Logger::get_instance();
        $logger->info('Sending request to reload the workers');
        $curl = curl_init();
        $service = new Auth;
        $token = $service->create_admin_token();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://' . Environment::load_env(Environment::DOCKER_IP) . ':' . Environment::load_env(Environment::PORT) . '/reload',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{}",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json, text/plain, */*",
                "Content-Type: application/json",
                'Authorization: Bearer ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $logger->info($err);
        } else {
            $logger->info($response);
        }
    }
}
