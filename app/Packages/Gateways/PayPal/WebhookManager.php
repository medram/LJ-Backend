<?php

namespace App\Packages\Gateways\PayPal;

use Illuminate\Http\Request;
use App\Packages\Gateways\PayPal\Webhook;

class WebhookManager
{
    private static $_instance = null;
    private $paypalGateway = null;

    private function __construct(PayPalClient $client)
    {
        $this->paypalGateway = $client;
    }

    public static function getInstance(PayPalClient $client = null)
    {
        if (self::$_instance === null or $client) {
            $paypalClient = $client ? $client : getPayPalGateway();
            self::$_instance = new WebhookManager($paypalClient);
        }

        return self::$_instance;
    }

    public static function refreshInstance()
    {
        self::$_instance = null;
        return self::getInstance();
    }

    public function register(Webhook $webhook)
    {
        return $webhook->register($this->paypalGateway);
    }

    public function update(Webhook $webhook)
    {
        return $webhook->update($this->paypalGateway);
    }

    public function delete(Webhook $webhook)
    {
        return $webhook->delete($this->paypalGateway);
    }

    public function webhookList(string $anchor_type = null)
    {
        $uri = "notifications/webhooks";

        if ($anchor_type !== null) {
            $uri = "notifications/webhooks?anchor_type={$anchor_type}";
        }

        $req = $this->paypalGateway->client->request("GET", $uri, [
            "http_errors" => false
        ]);


        $webhooks = [];
        if ($req->getStatusCode() === 200) {
            $result = json_decode((string)$req->getBody());
            # dd($result);
            foreach ($result["webhooks"] as $k => $webhooks_data) {
                $webhook = new Webhook();
                $webhook->setResult($webhooks_data);
                $webhooks[] = $webhook;
            }
        }

        return $webhooks;
    }

    public function getWebhookById(string $id)
    {
        if (!$id) {
            return null;
        }

        $req = $this->paypalGateway->client->request("GET", "notifications/webhooks/{$id}", [
            "http_errors" => false
        ]);

        if ($req->getStatusCode() === 200) {
            $data = json_decode($req->getBody());
            # Get PayPal events of this webhook
            $events = [];
            foreach ($data->event_types as $event) {
                $events[] = $event->name;
            }

            $webhook = new Webhook($data->url, $events, $id);
            $webhook->setResult((string)$req->getBody());
            return $webhook;
        }

        return null;
    }

    public function verifyWebhookSignature(Request $request)
    {
        $webhook_body = json_decode(file_get_contents('php://input'));

        $req = $this->paypalGateway->client->request("POST", "notifications/verify-webhook-signature", [
            "http_errors" => false,
            "json" => [
                'transmission_id' 	=> $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'transmission_sig' 	=> $request->header('PAYPAL-TRANSMISSION-SIG'),
                'cert_url' 			=> $request->header('PAYPAL-CERT-URL'),
                'auth_algo' 		=> $request->header('PAYPAL-AUTH-ALGO'),
                'webhook_id' 		=> getSetting('PM_PAYPAL_WEBHOOK_ID'),
                'webhook_event' 	=> $webhook_body
            ]
        ]);

        if ($req->getStatusCode() === 200) {
            $data = json_decode((string)$req->getBody(), true);
            if (isset($data["verification_status"]) && $data["verification_status"] === "SUCCESS") {
                return true;
            }
        }
        return false;
    }
}
