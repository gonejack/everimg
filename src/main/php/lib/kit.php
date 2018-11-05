<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/31
 * Time: 10:42 AM
 */

class Kit {
    public static function parseHeaders(?array $headerStrings, $headerName = null) {
        $headers = [];

        if (!empty($headerStrings)) {
            // http code
            if (strpos($headerStrings[0], 'HTTP') !== false) {
                list(, $headers['status'], $headers['status_text']) = explode(' ', $headerStrings[0]);
                unset($headerStrings[0]);
            }

            // others
            foreach ($headerStrings as $value) {
                $header = preg_split('/:\s*/', $value);

                $headers[strtolower($header[0])] = $header[1];
            }

            // get interests
            if (!is_null($headerName)) {
                $headerName = strtolower($headerName);
                return isset($headers[$headerName]) ? $headers[$headerName] : null;
            }
        }

        return $headers;
    }
    public static function downloadImageBinary(string $src) {
        LogService::debug("Download image [%s]", $src);

        $better = self::getBetterSource($src);
        if ($better !== $src) {
            $src = $better;

            LogService::debug("Found better source [%s]", $better);
        }

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.1',
                ]
            ]
        ]);

        $tryTimes = 3;
        while ($tryTimes-- > 0) {
            $binary = @file_get_contents($src, false, $context);;
            $expectLen = intval(Kit::parseHeaders(@$http_response_header, 'content-length'));
            $realLen = strlen($binary);

            if ($realLen == $expectLen) {
                return $binary;
            }
            else {
                LogService::warn("Retry download [%s]", $src);

                sleep(10);
            }
        }

        LogService::error("Failed download [%s]", $src);

        return null;
    }
    public static function getBetterSource($src):string {
        // tumblr
        if (strpos($src, '.media.tumblr.com/') !== false && strpos($src, '500.') !== false) {
            $context = stream_context_create([
                "http" => [
                    "method" => "HEAD",
                    "header" => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.1',
                    ]
                ]
            ]);

            $better = str_replace('500.', '1280.', $src);
            $response = get_headers($better, 0, $context);

            if ($response && isset($response[0]) && strpos($response[0], '200') !== false) {
                return $better;
            }
        }

        // lofter
        if (strpos($src, '.126.net') !== false || strpos($src, '.127.net') !== false) {
            if (($idx = strpos($src, '?')) !== false) {
                return substr($src, 0, $idx + 1) . 'type=jpg';
            }
        }

        $src = str_replace(' ', '', $src);

        return $src;
    }
    public static function getEmojiHTML($macro, $src):string {
        $macro = str_replace('[', '', $macro);
        $macro = str_replace(']', '', $macro);

        return "<img src=\"$src\" alt=\"$macro\" />";
    }
}