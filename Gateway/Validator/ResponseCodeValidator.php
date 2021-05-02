<?php
/**
 * Copyright © 2019 Bananacode SA, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bananacode\GreenPay\Gateway\Validator;

use Bananacode\GreenPay\Gateway\Http\Client\ClientMock;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * Class ResponseCodeValidator
 * @package Bananacode\GreenPay\Gateway\Validator
 */
class ResponseCodeValidator extends AbstractValidator
{
    const RESULT_CODE = 'status';

    const ERROR_MESSAGES = [
        "01" => "Refer to Issuing Bank",
        "03" => "Invalid Commerce",
        "04" => "Remove Card / Take Out Card",
        "05" => "Denied",
        "12" => "Invalid Transaction",
        "13" => "Invalid Amount Try Again",
        "14" => "Invalid Card",
        "19" => "Re-enter Transaction",
        "31" => "Bank Not Supported",
        "41" => "Lost Card",
        "43" => "Retain and Call",
        "51" => "Non Sufficient Funds",
        "54" => "Expired Card Remove Renewal",
        "55" => "Incorrect Pin",
        "58" => "Function Not Allowed",
        "62" => "Failure to Authorize",
        "63" => "Failure to Authorize",
        "65" => "Failure to Authorize",
        "78" => "Failure to Authorize",
        "89" => "Invalid Terminal",
        "91" => "Non-answering Issuing Bank",
        "96" => "Not Supported"
    ];

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        $validations = $this->isSuccessfulTransaction($response);

        foreach ($validations as $validation) {
            if (!$validation) {
                $errors = $this->extractErrors($response);
                return $this->createResult(
                    false,
                    [
                        $errors[1]
                    ],
                    [
                        $errors[0]
                    ]
                );
            }
        }

        return $this->createResult(
            true,
            []
        );
    }

    /**
     * @param array $response
     * @return array
     */
    private function isSuccessfulTransaction(array $response)
    {
        $validations = [];
        foreach ($response as $r) {
            $validations[] = isset($r[self::RESULT_CODE]) && $r[self::RESULT_CODE] === ClientMock::SUCCESS;
        }
        return $validations;
    }

    /**
     * @param array $response
     * @return array
     */
    private function extractErrors(array $response)
    {
        foreach ($response as $r) {
            if (isset($r['result'])) {
                $r['result'] = (array)$r['result'];
                if (isset($r['result']['resp_code'])) {
                    if (isset(self::ERROR_MESSAGES[$r['result']['resp_code']])) {
                        return [
                            $r['result']['resp_code'],
                            self::ERROR_MESSAGES[$r['result']['resp_code']]
                        ];
                    }
                }
            }
        }

        return [
            01 ,
            __('Transaction has been declined. Please try again later.')
        ];
    }
}
