<?php

namespace Bananacode\GreenPay\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class GreenPayHandler implements HandlerInterface
{
    const TIMEOUT_CODE = 504;

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $response = (Array)json_decode(json_encode($response), true);
        $timeout = [];
        $retrieval_ref_num = [];
        $authorization_id_resp = [];
        $payment = $paymentDO->getPayment();

        foreach ($response as $i => $r) {
            //Validate timeout
            if (isset($r['response'])) {
                if ($r['response'] == self::TIMEOUT_CODE) {
                    $timeout[$i] = true;
                }
            }

            //Validate success
            if (isset($r['result']['success'])) {
                if ((boolean)$r['result']['success']) {
                    $retrieval_ref_num[$i] = $r['result']['retrieval_ref_num'];
                    $authorization_id_resp[$i] = $r['result']['authorization_id_resp'];
                }
            }
        }

        if (count($timeout) > 0) {
            $payment->setAdditionalInformation('timeout', json_encode($timeout));
        }

        if (count($retrieval_ref_num) > 0) {
            $payment->setAdditionalInformation('retrieval_ref_num', json_encode($retrieval_ref_num));
        }

        if (count($authorization_id_resp) > 0) {
            $payment->setAdditionalInformation('authorization_id_resp', json_encode($authorization_id_resp));
        }
    }
}
