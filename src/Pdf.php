<?php

declare(strict_types=1);

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders an approved/verified letter to a downloadable PDF with Dompdf.
 */
final class Pdf
{
    public static function letter(array $letter): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false); // no remote assets => safer
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::html($letter), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private static function html(array $letter): string
    {
        $org     = e(env('ORG_NAME', 'Dinas Contoh'));
        $address = e(env('ORG_ADDRESS', 'Jl. Contoh No. 1, Kota Contoh 00000'));
        $number  = e($letter['number'] ?? '(unnumbered draft)');
        $subject = e($letter['subject']);
        $recip   = e($letter['recipient']);
        $body    = nl2br(e($letter['body']));
        $date    = e(date('d F Y'));

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8">
        <style>
            @page { margin: 2cm; }
            body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #111; margin: 0; }
            .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 16px; }
            .header h1 { font-size: 18px; margin: 0 0 2px; }
            .meta td { padding: 1px 6px; vertical-align: top; }
            .subject { font-weight: bold; text-decoration: underline; text-align: center; margin: 14px 0; }
            .body { line-height: 1.6; text-align: justify; }
            .sign { margin-top: 36px; margin-left: 60%; text-align: center; }
            .sign .line { margin-top: 56px; }
            .watermark { color: #b00; font-size: 10px; text-align: center; margin-top: 20px; }
        </style>
        </head>
        <body>
            <div class="header">
                <h1>{$org}</h1>
                <div>{$address}</div>
            </div>
            <table class="meta">
                <tr><td>Number</td><td>: {$number}</td></tr>
                <tr><td>Date</td><td>: {$date}</td></tr>
                <tr><td>To</td><td>: {$recip}</td></tr>
            </table>
            <div class="subject">{$subject}</div>
            <div class="body">{$body}</div>
            <div class="sign">
                <div>{$org}</div>
                <div class="line">_____________________</div>
            </div>
            <div class="watermark">DEMO DOCUMENT - FICTITIOUS DATA ONLY</div>
        </body>
        </html>
        HTML;
    }
}
