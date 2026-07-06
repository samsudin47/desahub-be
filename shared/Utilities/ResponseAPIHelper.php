<?php

namespace Shared\Utilities;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ErrorCodeConstantsHelper;
use Shared\Constants\GlobalConstantsHelper;
use Shared\Constants\ResponseStandardConstantsHelper;
use Shared\Constants\ResponseTypeConstantsHelper;

class ResponseAPIHelper
{
    private $type = null;
    private $info = null;
    private $data = [];
    private $message = null;
    private $detail = null;
    private $error = null;
    private $validationFailed = null;
    private $pagination = [];

    public function type($type)
    {
        $this->type = $type;
        return $this;
    }

    public function info($info)
    {
        $this->info = $info;
        return $this;
    }

    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    public function message($message)
    {
        $this->message = $message;
        return $this;
    }

    public function detail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    public function error($error)
    {
        $this->error = $error;
        return $this;
    }

    public function validationFailed($validationFailed)
    {
        $this->validationFailed = ValidationFailedHelper::validationFailed($validationFailed);
        return $this;
    }

    public function pagination($pagination)
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function response()
    {
        $responseHttpCode = 0;
        $responseBody = [];
        switch($this->type) {
            case ResponseTypeConstantsHelper::TYPE_VALIDATION:
                $responseHttpCode = ErrorCodeConstantsHelper::CODE_INVALID_VALIDATION;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_FAILED,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_INVALID_VALIDATION,
                    ResponseStandardConstantsHelper::MESSAGE => $this->message ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->error ?? '-',
                    ResponseStandardConstantsHelper::ADDITIONAL_INFORMATION => $this->error ?? '-',
                    ResponseStandardConstantsHelper::VALIDATION_FAILED => $this->validationFailed,
                ];
                break;
            case ResponseTypeConstantsHelper::TYPE_ERROR:
                $responseHttpCode = ErrorCodeConstantsHelper::CODE_BAD_REQUEST;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_FAILED,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_BAD_REQUEST,
                    ResponseStandardConstantsHelper::MESSAGE => $this->info ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::ADDITIONAL_INFORMATION => $this->detail ?? '-',
                ];
                break;
            case ResponseTypeConstantsHelper::TYPE_SUCCESS:
                $responseHttpCode = ErrorCodeConstantsHelper::CODE_SUCCESS;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_SUCCESS,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_SUCCESS,
                    ResponseStandardConstantsHelper::MESSAGE => $this->info ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::ADDITIONAL_INFORMATION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::DATAS => $this->data,
                ];
                break;
            case ResponseTypeConstantsHelper::TYPE_PAGINATION:
                if(is_array($this->data)) {
                    $count = count($this->data);
                } else if(is_object($this->data)) {
                    $count = $this->data->count();
                } else {
                    $count = 0;
                }

                $responseHttpCode = ErrorCodeConstantsHelper::CODE_SUCCESS;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_SUCCESS,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_SUCCESS,
                    ResponseStandardConstantsHelper::MESSAGE => $this->info ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::PAGINATION => $this->pagination,
                    ResponseStandardConstantsHelper::DATAS => $this->data,
                ];
                break;
            case ResponseTypeConstantsHelper::TYPE_UNAUTHORIZED:
                $responseHttpCode = ErrorCodeConstantsHelper::CODE_UNAUTHORIZED;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_UNAUTHORIZED,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_UNAUTHORIZED,
                    ResponseStandardConstantsHelper::MESSAGE => $this->info ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::ADDITIONAL_INFORMATION => $this->detail ?? '-',
                ];
                break;
            case ResponseTypeConstantsHelper::TYPE_FORBIDDEN_ACCESS:
                $responseHttpCode = ErrorCodeConstantsHelper::CODE_FORBIDDEN_ACCESS;
                $responseBody = [
                    ResponseStandardConstantsHelper::RESULT => GlobalConstantsHelper::RESULT_FORBIDDEN_ACCESS,
                    ResponseStandardConstantsHelper::CODE => ErrorCodeConstantsHelper::CODE_FORBIDDEN_ACCESS,
                    ResponseStandardConstantsHelper::MESSAGE => $this->info ?? '-',
                    ResponseStandardConstantsHelper::DESCRIPTION => $this->detail ?? '-',
                    ResponseStandardConstantsHelper::ADDITIONAL_INFORMATION => $this->detail ?? '-',
                ];
                break;
        }

        return new JsonResponse($responseBody, $responseHttpCode);
    }
}
