<?php

/* This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Lib\Logger;

use JsonException;
use Phalcon\Logger\Item;

/**
 * Formats messages using JSON encoding
 */
class Formatter extends \Phalcon\Logger\Formatter\AbstractFormatter
{
    /**
     * Json constructor.
     *
     * @param string $dateFormat
     */
    public function __construct(string $dateFormat = 'c') {}

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param Item $item
     *
     * @return string
     * @throws JsonException
     */
    public function format(\Phalcon\Logger\Item $item): string
    {
        $message = json_decode($item->getMessage());
        if (json_last_error() !== JSON_ERROR_NONE)
            $message = $item->getMessage();
        return json_encode(
            [
                'message' => $message,
                'context' => $item->getContext(),
                'timestamp' => $item->getDateTime()->format('d-m-y H:i:s'),
                'level' => $item->getLevelName(),
            ]
        );
    }
}
