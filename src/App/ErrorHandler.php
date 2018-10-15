<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App;

use BearFramework\App;

/**
 * The default error handler.
 * @codeCoverageIgnore
 */
class ErrorHandler
{

    /**
     * 
     * @param \Throwable $exception
     * @return void No value is returned.
     */
    static function handleException(\Throwable $exception): void
    {
        $errors = [];
        while ($exception instanceof \Exception) {
            $message = 'Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
            $trace = $exception->getTrace();
            $errors[] = ['message' => $message, 'trace' => $trace];
            $exception = $exception->getPrevious();
        }
        $errors = array_reverse($errors);
        self::handleErrors($errors);
    }

    /**
     * 
     * @param array $errorData
     * @return void No value is returned.
     */
    static function handleFatalError(array $errorData): void
    {
        if (ob_get_length() > 0) {
            ob_end_clean();
        }
        $messageParts = explode(' in ' . $errorData['file'] . ':' . $errorData['line'], $errorData['message'], 2);
        $message = 'Fatal error: ' . trim($messageParts[0]) . ' in ' . $errorData['file'] . ':' . (int) $errorData['line'];
        $trace = isset($messageParts[1]) ? [trim(str_replace('Stack trace:', '', $messageParts[1]))] : [];
        self::handleErrors([['message' => $message, 'trace' => $trace]]);
    }

    /**
     * 
     * @param array $errors
     * @return void No value is returned.
     */
    static private function handleErrors(array $errors): void
    {
        $app = App::get();
        $logKey = uniqid();

        $simpleLog = '';
        $fullLog = '';
        $addContent = function(string $title, string $data, bool $addToSimpleLog, bool $addToFullLog) use (&$simpleLog, &$fullLog) {
            if ($addToSimpleLog) {
                if (isset($simpleLog[0])) {
                    $simpleLog .= "\n\n";
                }
                $simpleLog .= $title . ':' . (isset($data[0]) ? "\n" . trim($data) : '');
            }
            if ($addToFullLog) {
                if (isset($fullLog[0])) {
                    $fullLog .= "\n\n";
                }
                $fullLog .= $title . ':' . (isset($data[0]) ? "\n" . trim($data) : '');
            }
        };
        $addContent('Log key', $logKey, true, false);

        $errorsCount = sizeof($errors);
        foreach ($errors as $i => $error) {
            $message = isset($error['message']) ? $error['message'] : '';
            $trace = isset($error['trace']) ? $error['trace'] : [];

            $simpleTrace = [];
            foreach ($trace as $traceRow) {
                $simpleTrace[] = (isset($traceRow['file']) ? $traceRow['file'] : '') . ':' . (isset($traceRow['line']) ? $traceRow['line'] : '');
            }
            if ($errorsCount > 1) {
                $addContent('Error ' . ($i + 1) . ' of ' . $errorsCount, '', true, true);
            }
            $addContent('Message', $message, true, true);
            $addContent('Simple trace', implode("\n", $simpleTrace), true, true);
            $addContent('Full trace', print_r($trace, true), false, true);
        }
        $addContent('Request', $app->request->method . ' ' . $app->request->base . $app->request->path, true, true);
        $addContent('PHP variables', print_r([
            'GET' => $_GET,
            'POST' => $_POST,
            'SERVER' => $_SERVER
                        ], true), false, true);

        if ($app->config->logErrors) {
            try {
                $app->logger->log('error', $simpleLog);
                $app->logger->log('error-' . $logKey, $fullLog);
            } catch (\Exception $e) {
                
            }
        }
        if (ob_get_length() > 0) {
            ob_clean();
        }
        if ($app->config->displayErrors) {
            http_response_code(503);
            echo (($errorsCount > 1) ? $errorsCount . ' errors occurred:' : 'Error occurred:') . "\n\n";
            echo $fullLog;
        } else {
            $response = new App\Response\TemporaryUnavailable();
            try {
                $app->respond($response);
            } catch (\Exception $e) {
                http_response_code(503);
                echo 'Temporary Unavailable';
            }
        }
        exit;
    }

}
