<?php

namespace Lib\Helpers;

class HttpStatus
{
    const SUCCESS = 200;
    const CREATED = 201;
    const NOT_FOUND = 404;
    const SERVER_ERROR = 500;
    const PAYMENT_REQUIRED = 402;
    const BAD_REQUEST = 400;
    const UNAUTHENTICATED = 401;
    const FORBIDDEN = 403;
    const CONFLICT = 409;
    const MAINTENANCE = 503;
    const NOT_IMPLEMENTED = 405;
}
