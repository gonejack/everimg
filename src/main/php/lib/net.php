<?php
/**
 * Created by PhpStorm.
 * User: youi
 * Date: 2018/10/31
 * Time: 10:42 AM
 */

class Net {
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
}