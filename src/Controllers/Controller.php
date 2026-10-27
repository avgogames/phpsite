<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200, string $layout = 'layouts/main'): Response
    {
        return Response::html(View::render($template, $data, $layout), $status);
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    /** Redirect back to a form with its input and validation errors flashed for one request. */
    protected function backWithErrors(string $to, array $input, array $errors): Response
    {
        Session::flashInput($input, $errors);
        Session::flash('error', 'Please fix the highlighted fields.');
        return Response::redirect($to);
    }

    protected function page(Request $request): int
    {
        return max(1, min(10_000, (int) $request->query('page', 1)));
    }

    protected function intParam(Request $request, string $key): int
    {
        $value = $request->param($key);
        return ctype_digit($value) ? (int) $value : 0;
    }
}
