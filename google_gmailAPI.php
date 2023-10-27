<?php
require_once 'vendor/autoload.php';

use Google\Service\Gmail;

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function get_gmail_client()
{
    $token_path = 'lib/token.json';

    $client_id = 'YOUR_CLIENT_ID';
    $client_secret = 'YOUR_CLIENT_SECRET';
    $redirect_uri = 'YOUR_REDIRECT_URI';
    $client = new Google\Client();
    $client->setAuthConfig('lib/credentials.json');
    $client->addScope(Google\Service\Gmail::GMAIL_SEND);
    if (file_exists($token_path)) {
        $client->setAccessToken(file_get_contents($token_path));
    } else {
        $auth_url = $client->createAuthUrl();
        echo "Open the following link in your browser:\n";
        echo $auth_url;
        $auth_code = readline("Enter the authorization code: ");
        $access_token = $client->fetchAccessTokenWithAuthCode($auth_code);
        $client->setAccessToken($access_token);
        file_put_contents($token_path, json_encode($access_token));
    }
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($token_path, json_encode($client->getAccessToken()));
    }

    return $client;
}

function send_gmail_message($to, $subject, $message_body)
{
    $client = get_gmail_client();
    $service = new Gmail($client);
    $message = new Google\Service\Gmail\Message();
    $message->setRaw(base64url_encode(
        "To: $to\r\n" .
        "Subject: $subject\r\n\r\n" .
        "$message_body"
    ));
    try {
        $service->users_messages->send('me', $message);
        echo "Email sent successfully.";
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
    }
}

// Gọi hàm gửi email
send_gmail_message('hieumai1905it@gmail.com', 'Test Email', 'New');