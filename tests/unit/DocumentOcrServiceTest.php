<?php

use App\Services\DocumentOcrService;
use CodeIgniter\Test\CIUnitTestCase;

final class DocumentOcrServiceTest extends CIUnitTestCase
{
    public function testPhilHealthNameMatchIgnoresCapitalizationAndWordOrder(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'nameMatches');
        $this->assertTrue($method->invoke(new DocumentOcrService(), 'Nazareno, Gerald Ace Vinluan', "PHILHEALTH\nNAZARENO, GERALD ACE VINLUAN"));
    }

    public function testDifferentOrMisspelledNameDoesNotMatch(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'nameMatches');
        $this->assertFalse($method->invoke(new DocumentOcrService(), 'Nazareno Gerald Ace Vinluan', 'NAZARENO GERALD ACE VINLUAM'));
    }

    public function testFirstAndLastNameMatchAllowsMiddleNameDifferences(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'nameMatches');

        // The OCR pass is preliminary. Philippine IDs may include or omit
        // middle names, while final identity confirmation remains with staff.
        $this->assertTrue($method->invoke(
            new DocumentOcrService(),
            'Gerald Nazareno',
            'PHILHEALTH NAZARENO GERALD ACE VINLUAN'
        ));
    }

    public function testPhilHealthIsRecognizedAsGovernmentId(): void { $method = new ReflectionMethod(DocumentOcrService::class, 'hasGovernmentIdMarker'); $this->assertTrue($method->invoke(new DocumentOcrService(), 'PHILHEALTH MEMBER DATA RECORD')); $this->assertFalse($method->invoke(new DocumentOcrService(), 'Random printed note')); }
}
