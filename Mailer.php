<?php

require_once 'vendor/autoload.php';

use Google\Service\Gmail;

class Mailer
{
    private static $client;

    private static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function getAccessToken($authCode)
    {
        $tokenPath = 'lib/token.json';
        $accessToken = self::$client->fetchAccessTokenWithAuthCode($authCode);
        self::$client->setAccessToken($accessToken);
        file_put_contents($tokenPath, json_encode($accessToken));
    }

    private static function getClient()
    {
        $clientId = 'YOUR_CLIENT_ID';
        $clientSecret = 'YOUR_CLIENT_SECRET';
        $redirectUri = 'YOUR_REDIRECT_URI';
        self::$client = new Google\Client();
        self::$client->setAuthConfig('lib/credentials.json');
        self::$client->addScope(Google\Service\Gmail::GMAIL_SEND);
        self::$client->setClientId($clientId);
        self::$client->setClientSecret($clientSecret);
        self::$client->setRedirectUri($redirectUri);


        $tokenPath = 'lib/token.json';

        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            self::$client->setAccessToken($accessToken);
        } else {
            $authUrl = self::$client->createAuthUrl();
            echo "Open the following link in your browser:\n";
            echo $authUrl;
            $authCode = readline("Enter the authorization code: ");
            self::getAccessToken($authCode);
        }

        if (self::$client->isAccessTokenExpired()) {
            self::$client->fetchAccessTokenWithRefreshToken(self::$client->getRefreshToken());
            file_put_contents($tokenPath, json_encode(self::$client->getAccessToken()));
        }

        return self::$client;
    }

    public static function sendEmail($to, $subject, $messageBody)
    {
        $client = self::getClient();
        $service = new Gmail($client);
        $message = new Google\Service\Gmail\Message();

        $messageBodyHtml = '<html><body>' .

            '
                <div style="text-align: center">
                    <h1>Mã xác nhận của bạn là: ' . $messageBody . '</h1>
                </div>

                '

            . '</body></html>';

        $message->setRaw(self::base64url_encode(
            "To: $to\r\n" .
            "Subject: $subject\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=utf-8\r\n\r\n" .
            $messageBodyHtml
        ));

        try {
            $service->users_messages->send('me', $message);
            echo "Email sent successfully.";
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage();
        }
    }
}

Mailer::sendEmail('hieumai1905it@gmail.com', 'Test Email', '12345');