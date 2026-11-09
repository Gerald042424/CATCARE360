<?php

use App\Services\DocumentOcrService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdditionalGovernmentIdDetectionTest extends CIUnitTestCase
{
    private function detector(): ReflectionMethod
    {
        return new ReflectionMethod(DocumentOcrService::class, 'detectGovernmentIdType');
    }

    public function testUmidTitleIsRecognized(): void
    {
        $this->assertSame(
            'umid',
            $this->detector()->invoke(new DocumentOcrService(), 'UNIFIED MULTI-PURPOSE ID COMMON REFERENCE NUMBER'),
        );
    }

    public function testUmidIsNotMisclassifiedWhenPhilHealthIsAlsoVisible(): void
    {
        $this->assertSame(
            'umid',
            $this->detector()->invoke(new DocumentOcrService(), 'UMID COMMON REFERENCE NUMBER SSS GSIS PHILHEALTH'),
        );
    }

    public function testPostalIdTitleIsRecognized(): void
    {
        $this->assertSame(
            'postal_id',
            $this->detector()->invoke(new DocumentOcrService(), 'PHILIPPINE POSTAL CORPORATION PHLPOST POSTAL IDENTITY CARD'),
        );
    }

    public function testUnrelatedPostalTextIsNotAccepted(): void
    {
        $this->assertNull(
            $this->detector()->invoke(new DocumentOcrService(), 'POSTAL DELIVERY RECEIPT FOR A PARCEL'),
        );
    }
}
