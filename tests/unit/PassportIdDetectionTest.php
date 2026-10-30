<?php

use App\Services\DocumentOcrService;
use CodeIgniter\Test\CIUnitTestCase;

final class PassportIdDetectionTest extends CIUnitTestCase
{
    public function testPassportMrzAllowsCommonLetterRecognitionError(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'detectGovernmentIdType');

        $this->assertSame('passport', $method->invoke(new DocumentOcrService(), 'P<PHI DELA<CRUZ<<JUAN'));
    }

    public function testPassportTitleAndCountryAreRecognizedWithoutMrz(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'detectGovernmentIdType');

        $this->assertSame('passport', $method->invoke(new DocumentOcrService(), 'PASSPOR7 REPUBLIC OF THE PHILIPPINES'));
    }

    public function testUnrelatedDocumentIsNotAcceptedAsPassport(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'detectGovernmentIdType');

        $this->assertNull($method->invoke(new DocumentOcrService(), 'ORDINARY PRINTED DOCUMENT JUAN DELA CRUZ'));
    }

    public function testApplicantNameMatchesPassportMrzSeparators(): void
    {
        $method = new ReflectionMethod(DocumentOcrService::class, 'nameMatches');

        $this->assertTrue($method->invoke(
            new DocumentOcrService(),
            'Juan Santos Dela Cruz',
            'P<PHLDELA<CRUZ<<JUAN<SANTOS<<<<<<<<<<<<<<<<',
        ));
    }
}
