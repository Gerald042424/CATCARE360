<?php

use App\Controllers\Adopter\ApplicationController;
use CodeIgniter\Test\CIUnitTestCase;

final class HomePhotoVerificationTest extends CIUnitTestCase
{
    /** @var list<string> */
    private array $temporaryImages = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryImages as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testHomeSceneMetadataAcceptsMatchingFreshCapture(): void
    {
        $capturedAt = time();
        $metadata = [
            'home_scene' => [
                'verified' => true,
                'capture_type' => 'indoor_living_area',
                'brightness' => 128,
                'contrast' => 32,
                'sharpness' => 18,
                'provider' => 'native_canvas_scene_quality',
                'verified_at' => gmdate('c', $capturedAt),
            ],
        ];

        $method = new ReflectionMethod(ApplicationController::class, 'validateHomeSceneMetadata');
        $this->assertNull($method->invoke(new ApplicationController(), $metadata, $capturedAt, 'home_photos'));
    }

    public function testHomeSceneMetadataRejectsWrongCaptureType(): void
    {
        $capturedAt = time();
        $metadata = [
            'home_scene' => [
                'verified' => true,
                'capture_type' => 'indoor_living_area',
                'brightness' => 128,
                'contrast' => 32,
                'sharpness' => 18,
                'provider' => 'native_canvas_scene_quality',
                'verified_at' => gmdate('c', $capturedAt),
            ],
        ];

        $method = new ReflectionMethod(ApplicationController::class, 'validateHomeSceneMetadata');
        $this->assertNotNull($method->invoke(new ApplicationController(), $metadata, $capturedAt, 'home_access'));
    }

    public function testPlainImageFailsWhileDetailedHomeScenePasses(): void
    {
        $plain = $this->createImage(false, 1);
        $detailed = $this->createImage(true, 2);
        $method = new ReflectionMethod(ApplicationController::class, 'hasSufficientSceneDetail');
        $controller = new ApplicationController();

        $this->assertFalse($method->invoke($controller, $plain));
        $this->assertTrue($method->invoke($controller, $detailed));
    }

    public function testDuplicateHomeScenesAreRejected(): void
    {
        $indoor = $this->createImage(true, 3);
        $duplicate = $this->copyImage($indoor);
        $method = new ReflectionMethod(ApplicationController::class, 'imagesAreNearDuplicates');

        $this->assertTrue($method->invoke(new ApplicationController(), $indoor, $duplicate));
    }

    public function testDifferentHomeScenesAreAccepted(): void
    {
        $indoor = $this->createImage(true, 4);
        $access = $this->createImage(true, 17);
        $method = new ReflectionMethod(ApplicationController::class, 'imagesAreNearDuplicates');

        $this->assertFalse($method->invoke(new ApplicationController(), $indoor, $access));
    }

    private function createImage(bool $detailed, int $seed): string
    {
        $path = tempnam(sys_get_temp_dir(), 'catcare-home-');
        $image = imagecreatetruecolor(640, 480);
        $background = imagecolorallocate($image, 140, 145, 150);
        imagefill($image, 0, 0, $background);

        if ($detailed) {
            mt_srand($seed);
            for ($index = 0; $index < 90; ++$index) {
                $color = imagecolorallocate($image, mt_rand(20, 235), mt_rand(20, 235), mt_rand(20, 235));
                $x1 = mt_rand(0, 600);
                $y1 = mt_rand(0, 300);
                imagefilledrectangle($image, $x1, $y1, $x1 + mt_rand(10, 100), $y1 + mt_rand(10, 80), $color);
            }
        }

        imagejpeg($image, $path, 90);
        imagedestroy($image);
        $this->temporaryImages[] = $path;

        return $path;
    }

    private function copyImage(string $source): string
    {
        $path = tempnam(sys_get_temp_dir(), 'catcare-home-copy-');
        copy($source, $path);
        $this->temporaryImages[] = $path;

        return $path;
    }
}
