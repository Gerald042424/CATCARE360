<?php

use App\Services\DocumentOcrService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdditionalGovernmentIdDetectionTest extends CIUnitTestCase
{
    private function detector(): ReflectionMethod
    {
        return new ReflectionMethod(DocumentOcrService::class, 'detectGovernmentIdType');
    }

    private function evidenceSourceDetector(): ReflectionMethod
    {
        return new ReflectionMethod(DocumentOcrService::class, 'detectDigitalEvidenceSource');
    }

    public function testPrcProfessionalIdentificationCardIsRecognized(): void
    {
        $this->assertSame(
            'prc_id',
            $this->detector()->invoke(new DocumentOcrService(), 'PROFESSIONAL REGULATION COMMISSION PROFESSIONAL IDENTIFICATION CARD REGISTRATION NO.'),
        );
    }

    public function testPrcEPicAndLerisMarkersAreRecognized(): void
    {
        $this->assertSame(
            'prc_id',
            $this->detector()->invoke(new DocumentOcrService(), 'PRC E-PIC LERIS PROFESSIONAL IDENTIFICATION CARD'),
        );
    }

    public function testRemovedUmidIsNotAccepted(): void
    {
        $this->assertNull(
            $this->detector()->invoke(new DocumentOcrService(), 'UNIFIED MULTI-PURPOSE ID COMMON REFERENCE NUMBER'),
        );
    }

    public function testRemovedPostalIdIsNotAccepted(): void
    {
        $this->assertNull(
            $this->detector()->invoke(new DocumentOcrService(), 'PHILIPPINE POSTAL CORPORATION PHLPOST POSTAL IDENTITY CARD'),
        );
    }

    public function testEgovNationalIdLabelIsRecognized(): void
    {
        $this->assertSame(
            'national_id',
            $this->detector()->invoke(new DocumentOcrService(), 'eGovPH DIGITAL NATIONAL ID PCN'),
        );
    }

    public function testOfficialDigitalEvidenceSourcesAreClassifiedByIdType(): void
    {
        $service = new DocumentOcrService();
        $detector = $this->evidenceSourceDetector();

        $this->assertSame('egovph', $detector->invoke($service, 'eGovPH Digital ID PHILHEALTH', 'philhealth'));
        $this->assertSame('lto_ltms', $detector->invoke($service, 'LTMS LAND TRANSPORTATION OFFICE DIGITAL DRIVER LICENSE', 'drivers_license'));
        $this->assertSame('prc_e_pic_or_leris', $detector->invoke($service, 'PRC LERIS PROFESSIONAL IDENTIFICATION CARD E-PIC', 'prc_id'));
        $this->assertNull($detector->invoke($service, 'ORDINARY GALLERY SCREENSHOT', 'national_id'));
    }
}
