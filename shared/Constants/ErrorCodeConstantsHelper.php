<?php

namespace Shared\Constants;

class ErrorCodeConstantsHelper
{
    const CODE_SUCCESS = 200;
    const CODE_UNAUTHORIZED = 401;
    const CODE_FORBIDDEN_ACCESS = 403;
    const CODE_NOT_FOUND = 404;
    const CODE_BAD_REQUEST = 400;
    const CODE_INVALID_VALIDATION = 422;
    const CODE_BUSINESS_RULE_VALIDATION = 409; // Conflict - untuk business rule validation
    const CODE_INTERNAL_SERVER_ERROR = 500;
    const CODE_BAD_GATEWAY = 502;
    const CODE_SERVICE_UNAVAILABLE = 503;
}
