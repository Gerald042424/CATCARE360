<?php

namespace App\Services;

use setasign\Fpdi\Tfpdf\Fpdi;

class AdoptionWaiverService
{
    public function render(array $application, string $email, array $cat): string
    {
        $pdf = new Fpdi('P', 'pt', 'Letter');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pageCount = $pdf->setSourceFile(APPPATH . 'Resources/adoption-waiver.pdf');

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf->AddPage();
            $pdf->useImportedPage($pdf->importPage($page));

            if ($page === 1) {
                $this->fillDetails($pdf, $application, $email, $cat);
            }
            if ($page === 3) {
                $this->writeField($pdf, 75, 285, 224, (string) $application['full_name'], 'C');
            }
        }

        return $pdf->Output('S');
    }

    private function fillDetails(Fpdi $pdf, array $application, string $email, array $cat): void
    {
        $metadata = json_decode((string) ($application['verification_metadata'] ?? ''), true);
        $idType = match ($metadata['gov_id']['id_verification']['selected_type'] ?? '') {
            'philhealth' => 'PhilHealth ID',
            'national_id' => 'National ID',
            'drivers_license' => "Driver's License",
            'passport' => 'Passport',
            'prc_id' => 'PRC ID',
            default => '',
        };
        // The application has no dedicated, staff-verified ID number field.
        $this->writeField($pdf, 129, 381, 309, (string) $application['full_name']);
        $this->writeField($pdf, 167, 395, 270, (string) ($application['address'] ?? ''));
        $this->writeField($pdf, 159, 410, 277, (string) ($application['phone'] ?? ''));
        $this->writeField($pdf, 150, 424, 281, $email);
        $this->writeField($pdf, 225, 439, 220, $idType);

        $details = [
            $cat['name'] ?? '',
            $cat['breed'] ?? '',
            $cat['age'] ?? '',
            $cat['gender'] ?? '',
            $cat['color'] ?? '',
            ! empty($cat['cnvr_neuter']) ? 'Yes' : 'Not recorded',
            ! empty($cat['cnvr_vaccinate']) ? 'Yes' : 'Not recorded',
            $cat['health'] ?? '',
        ];

        foreach ($details as $row => $value) {
            $this->writeField($pdf, 257, 509 + 24 * $row, 278, (string) $value);
        }
    }

    private function writeField(Fpdi $pdf, float $x, float $y, float $width, string $value, string $align = 'L'): void
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        for ($size = 9; $size >= 7; $size--) {
            $pdf->SetFont('DejaVu', '', $size);
            if ($pdf->GetStringWidth($value) <= $width) {
                break;
            }
        }
        if ($pdf->GetStringWidth($value) > $width) {
            $value = mb_substr($value, 0, 240);
            while ($pdf->GetStringWidth($value . '…') > $width) {
                $value = mb_substr($value, 0, -1);
            }
            $value .= '…';
        }

        if ($align === 'C') {
            $x += ($width - $pdf->GetStringWidth($value)) / 2;
        }
        $pdf->Text($x, $y + 7, $value);
    }
}
