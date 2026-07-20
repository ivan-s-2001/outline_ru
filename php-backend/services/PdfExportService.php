<?php

declare(strict_types=1);

namespace app\services;

use app\models\Document;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfExportService
{
    public function render(Document $document): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', dirname(__DIR__));

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml((new DocumentExportService())->html($document), 'UTF-8');
        $dompdf->render();
        return $dompdf->output();
    }
}
