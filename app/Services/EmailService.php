<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public static function sendTemplate(string $templateName, $to, array $data = [], string $replyTo = null): bool
    {
        try {
            $template = EmailTemplate::where('name', $templateName)->firstOrFail();

            // رندر محتوا داینامیک ایمیل
            $content = Blade::render($template->body, $data);
            $subject = Blade::render($template->subject, $data);

            // رندر نهایی با layout ایمیل
            $fullBody = view('emails.layout', [
                'subject' => $subject,
                'content' => $content
            ])->render();

            // ارسال ایمیل با محتوای HTML
            Mail::html($fullBody, function ($message) use ($to, $subject, $replyTo) {
                $message->to($to)
                    ->subject($subject);

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('EmailService Error: ' . $e->getMessage());
            return false;
        }
    }
}
