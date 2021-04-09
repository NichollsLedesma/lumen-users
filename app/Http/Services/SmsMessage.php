<?php

namespace App\Http\Services;

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Log;

class SmsMessage
{
    private $snsClient;

    public function __construct()
    {
        $this->snsClient = new SnsClient([
            'region' => "us-east-1",
            'version' => '2010-03-31'
        ]);
    }

    public function sendSms(string $phoneNumber,string $message): ?string
    {
        $dataSms = [
            'Message' => $message,
            'PhoneNumber' => $phoneNumber,
        ];

        try {
            $response = $this->snsClient->publish($dataSms);

            return $response["MessageId"];
        } catch (AwsException $e) {
            Log::error("Error sending sms: ".$e->getAwsErrorMessage(),$dataSms);

            return null;
        }
    }
}
