<?php

namespace Modules\Subscription\Features\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Features\Payment\Contracts\PaymentGatewayInterface;
use Modules\Subscription\Models\SubscriptionInvoice;

class VNPayGateway implements PaymentGatewayInterface
{
    public function slug(): string      { return 'vnpay'; }
    public function type(): GatewayType { return GatewayType::Redirect; }

    public function isEnabled(): bool
    {
        return filled(config('subscription.gateways.vnpay.tmn_code'))
            && filled(config('subscription.gateways.vnpay.secret'));
    }

    public function buildCheckoutUrl(SubscriptionInvoice $invoice, string $returnUrl): ?string
    {
        $cfg = config('subscription.gateways.vnpay');

        $params = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $cfg['tmn_code'],
            'vnp_Amount'     => (int) ($invoice->amount * 100), // VNPay: VND × 100
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $invoice->invoice_number,
            'vnp_OrderInfo'  => 'Thanh toan ' . $invoice->invoice_number,
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => $returnUrl,
            'vnp_IpAddr'     => request()->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
        ];

        ksort($params);
        $query     = urldecode(http_build_query($params));
        $signature = hash_hmac('sha512', $query, $cfg['secret']);

        return $cfg['url'] . '?' . http_build_query($params) . '&vnp_SecureHash=' . $signature;
    }

    public function verifyWebhook(Request $request): bool
    {
        $inputHash = $request->input('vnp_SecureHash');
        if (!$inputHash) {
            return false;
        }

        $params = collect($request->all())
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_')
                && $k !== 'vnp_SecureHash'
                && $k !== 'vnp_SecureHashType')
            ->sortKeys()
            ->all();

        $query     = urldecode(http_build_query($params));
        $signature = hash_hmac('sha512', $query, config('subscription.gateways.vnpay.secret'));

        return hash_equals($signature, $inputHash);
    }

    public function verifyReturn(Request $request): ?bool
    {
        return $this->verifyWebhook($request)
            && $request->input('vnp_ResponseCode') === '00';
    }

    public function extractInvoiceNumber(Request $request): string
    {
        return $request->input('vnp_TxnRef', '');
    }

    public function extractPaymentRef(Request $request): string
    {
        return $request->input('vnp_TransactionNo', '');
    }

    public function extractAmount(Request $request): float
    {
        // VNPay sends amount × 100
        return (float) $request->input('vnp_Amount', 0) / 100;
    }

    public function webhookAck(bool $success): array
    {
        // VNPay IPN requires exactly this format
        return $success
            ? ['RspCode' => '00', 'Message' => 'Confirm Success']
            : ['RspCode' => '99', 'Message' => 'Unknown error'];
    }
}
