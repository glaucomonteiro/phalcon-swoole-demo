<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Lib\Swoole\Bridge;

use Phalcon\Http\ResponseInterface;
use OpenSwoole\Http\Response as SwooleResponse;

class Response extends \Phalcon\Http\Response implements ResponseInterface
{
    private SwooleResponse $swooleResponse;

    public function __construct(
        SwooleResponse $swooleResponse,
        ?string $content = null,
        ?int $code = null,
        ?string $status = null
    ) {
        $this->swooleResponse = $swooleResponse;

        parent::__construct($content, $code, $status);
    }

    public function setStatusCode(int $code, ?string $message = null): ResponseInterface
    {
        $this->swooleResponse->status($code, (string)$message);

        return $this;
    }

    public function setHeader(string $name, $value): ResponseInterface
    {
        $this->swooleResponse->header($name, $value);

        return $this;
    }

    public function setFileToSend(string $filePath, $attachmentName = null, $attachment = true): ResponseInterface
    {
        $this->swooleResponse->sendfile($filePath);
        return $this;
    }

    public function send(): ResponseInterface
    {
        return $this;
    }
}
