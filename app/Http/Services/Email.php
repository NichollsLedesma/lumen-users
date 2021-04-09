<?php


namespace App\Http\Services;


use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class Email
{
    private $sesClient;

    public const TEMPLATE_REGISTER = "template_register_user";
    public const TEMPLATE_LOGIN = "template_login_code";

    /**
     * Email constructor.
     */
    public function __construct()
    {
        $this->sesClient = new SesClient([
            'region' => "us-east-1",
            'version' => '2010-12-01',
        ]);
    }

    public function checkIfVerified(string $email): bool
    {
        Log::info("Checking if is already register on aws");
        $response = $this->sesClient->listIdentities([
            'IdentityType' => 'EmailAddress',
        ]);

        return in_array($email, $response['Identities'], true);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function sendVerifyEmail(string $email): bool
    {
        try {
            $result = $this->sesClient->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            Log::info("Verify successfully sent");

            return true;
        } catch (AwsException $e) {
            Log::error("Error verifying: " . $e->getAwsErrorMessage());

            return false;
        }
    }


    public function sendEmail(string $templateName, string $email, array $templateData): ?string
    {
        $sender = Config::get("constants.SENDER");
        $data = [
            'Destination' => [
                'ToAddresses' => [$email],
            ],
            'ReplyToAddresses' => [$sender],
            'Source' => $sender,
            'Template' => $templateName,
            "TemplateData" => json_encode($templateData)
        ];

        try {
            $result = $this->sesClient->sendTemplatedEmail($data);

            Log::info("email successfully sent: $email ");

            return $result["MessageId"];
        } catch (AwsException $e) {
            Log::error("Error sending email: " . $e->getAwsErrorMessage());

            return null;
        }
    }

    public function sendLoginCode(string $email, string $code): ?string
    {
        $templateData = [
            "email" => $email,
            "code" => $code,
        ];

        return $this->sendEmail(self::TEMPLATE_LOGIN, $email, $templateData);
    }

    public function sendRegisterCode(string $email, string $code): ?string
    {
        $templateData = [
            "email" => $email,
            "code" => $code,
        ];

        return $this->sendEmail(self::TEMPLATE_REGISTER, $email, $templateData);
    }

    /**
     * @param string $templateName
     *
     * @return Result
     */
    public function createTemplate(string $templateName): Result
    {
        $templateSelected = [
            'HtmlPart' => '<p>Code to access: {{code}}</p>',
            'SubjectPart' => 'Code to login',
            'TemplateName' => $templateName,
        ];

        return $this->sesClient->createTemplate([
            'Template' => $templateSelected,
        ]);
    }

    public function getTemplates()
    {


        $result = $this->sesClient->listTemplates([]);
        dd($result);

        return $result;
    }

}
