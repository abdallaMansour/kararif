<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parse multipart/form-data body for PUT/PATCH requests so that
 * $request->input() and $request->file() work. PHP only populates
 * $_POST and $_FILES for POST, so PUT with multipart is empty otherwise.
 */
class ParsePutMultipart
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $contentType = $request->header('Content-Type', '');
        if (strpos($contentType, 'multipart/form-data') === false) {
            return $next($request);
        }

        $raw = $request->getContent();
        if (empty($raw)) {
            return $next($request);
        }

        if (! preg_match('/boundary=(?:"([^"]+)"|([^\s;]+))/', $contentType, $m)) {
            return $next($request);
        }

        $boundary = '--' . trim($m[1] ?? $m[2]);
        $parts = array_slice(explode($boundary, $raw), 1);

        $params = [];
        $files = [];

        foreach ($parts as $part) {
            $part = ltrim($part, "\r\n");
            if ($part === "--\r\n" || $part === "--") {
                break;
            }
            if (! str_contains($part, "\r\n\r\n")) {
                continue;
            }
            [$rawHeaders, $body] = explode("\r\n\r\n", $part, 2);
            $body = rtrim($body, "\r\n");

            $name = null;
            $filename = null;
            $mimeType = 'application/octet-stream';
            foreach (explode("\r\n", $rawHeaders) as $header) {
                if (stripos($header, 'Content-Disposition:') === 0) {
                    if (preg_match('/name="([^"]+)"/', $header, $nm)) {
                        $name = $nm[1];
                    }
                    if (preg_match('/filename="([^"]*)"/', $header, $fn)) {
                        $filename = $fn[1];
                    }
                }
                if (stripos($header, 'Content-Type:') === 0) {
                    $mimeType = trim(substr($header, 13));
                }
            }

            if ($name === null) {
                continue;
            }

            if ($filename !== null && $filename !== '') {
                $tmp = tempnam(sys_get_temp_dir(), 'put_upload_');
                if ($tmp !== false && file_put_contents($tmp, $body) !== false) {
                    $files[$name] = new UploadedFile(
                        $tmp,
                        $filename,
                        $mimeType,
                        \UPLOAD_ERR_OK,
                        true
                    );
                }
            } else {
                $params[$name] = $body;
            }
        }

        if ($params !== []) {
            $request->request->add($params);
        }
        foreach ($files as $key => $file) {
            $request->files->set($key, $file);
        }

        return $next($request);
    }
}
