<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //if ($this->shouldReport($exception)) {
                $this->sendEmail($e); // sends an email
            //}
        });
    }
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }
    public function sendEmail(Throwable $throwable)
    {
        $css = '';
        $content = '';
        if ($throwable instanceof \ErrorException) {
            $e = FlattenException::create($throwable);
            $handler = new HtmlErrorRenderer(true); // boolean, true raises debug flag...
            $css = $handler->getStylesheet();
            $content = $handler->getBody($e);
        }

        if ($throwable instanceof \ParseError)
            $content .= $throwable;

        if ($throwable instanceof \TypeError)
            $content .= $throwable;

        $subject = 'Exception Handler env='.\App::environment();
        $user = Auth::user();
        if ($user)
            $subject .= ' user_id='.$user->id.', mail='.$user->email;

        \Mail::send('mails.exception', compact('css','content'), function ($message) use (&$subject) {
            $message
                ->to(config('mail.to.support.address'))
                ->subject($subject)
            ;
        });
    }
}
