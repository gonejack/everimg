<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/31
 * Time: 10:42 AM
 */

class Net {
    public static function parseHeaders(?array $originHeaders, string $headerName = null) {
        $parsedHeaders = [];

        if (!empty($originHeaders)) {
            // http code
            if (strpos($originHeaders[0], 'HTTP') !== false) {
                list(, $parsedHeaders['status'], $parsedHeaders['status_text']) = explode(' ', $originHeaders[0]);
                unset($originHeaders[0]);
            }

            // others
            foreach ($originHeaders as $value) {
                if ($header = preg_split('/:\s*/', $value)) {
                    $parsedHeaders[strtolower(@$header[0])] = @$header[1];
                }
            }
        }

        if (empty($headerName)) {
            return $parsedHeaders;
        }
        else {
            $headerName = strtolower($headerName);
            return isset($parsedHeaders[$headerName]) ? $parsedHeaders[$headerName] : null;
        }
    }
}