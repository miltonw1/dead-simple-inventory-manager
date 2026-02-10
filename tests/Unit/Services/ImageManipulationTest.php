<?php

use App\Services\ImageManipulation;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

describe('ImageManipulation', function () {
    beforeEach(function () {
        $this->imageManipulation = new ImageManipulation;
    });

    describe('getProductImageName', function () {
        test('generates filename with default prefix and path', function () {
            $filename = $this->imageManipulation->getProductImageName();

            expect($filename)
                ->toStartWith('products/product_')
                ->toEndWith('.webp');
        });

        test('generates filename with custom prefix', function () {
            $filename = $this->imageManipulation->getProductImageName([
                'prefix' => 'custom_',
            ]);

            expect($filename)
                ->toStartWith('products/custom_')
                ->toEndWith('.webp');
        });

        test('generates filename with custom path', function () {
            $filename = $this->imageManipulation->getProductImageName([
                'path' => 'images/',
            ]);

            expect($filename)
                ->toStartWith('images/product_')
                ->toEndWith('.webp');
        });

        test('generates filename with custom prefix and path', function () {
            $filename = $this->imageManipulation->getProductImageName([
                'prefix' => 'img_',
                'path' => 'uploads/',
            ]);

            expect($filename)
                ->toStartWith('uploads/img_')
                ->toEndWith('.webp');
        });

        test('generates unique filenames', function () {
            $filename1 = $this->imageManipulation->getProductImageName();
            $filename2 = $this->imageManipulation->getProductImageName();

            expect($filename1)->not->toBe($filename2);
        });

        test('generates filename with empty path', function () {
            $filename = $this->imageManipulation->getProductImageName([
                'path' => '',
            ]);

            expect($filename)
                ->toStartWith('product_')
                ->toEndWith('.webp')
                ->not->toStartWith('/');
        });
    });

    describe('processProductImage', function () {
        test('processes image and returns webp string', function () {
            $mockEncodedImage = Mockery::mock(EncodedImageInterface::class);
            $mockEncodedImage->shouldReceive('toString')->andReturn('webp-binary-data');

            $mockImage = Mockery::mock(ImageInterface::class);
            $mockImage->shouldReceive('width')->andReturn(800);
            $mockImage->shouldReceive('height')->andReturn(600);
            $mockImage->shouldReceive('cover')->with(480, 480, 'center')->andReturnSelf();
            $mockImage->shouldReceive('scale')->with(480, 480)->andReturnSelf();
            $mockImage->shouldReceive('toWebp')->with(80)->andReturn($mockEncodedImage);

            Image::shouldReceive('read')
                ->once()
                ->andReturn($mockImage);

            $file = UploadedFile::fake()->image('test.jpg', 800, 600);
            $result = $this->imageManipulation->processProductImage($file);

            expect($result)->toBe('webp-binary-data');
        });

        test('resizes image to 480x480 when smaller side is larger', function () {
            $mockEncodedImage = Mockery::mock(EncodedImageInterface::class);
            $mockEncodedImage->shouldReceive('toString')->andReturn('webp-binary-data');

            $mockImage = Mockery::mock(ImageInterface::class);
            $mockImage->shouldReceive('width')->andReturn(1200);
            $mockImage->shouldReceive('height')->andReturn(1000);
            $mockImage->shouldReceive('cover')->with(480, 480, 'center')->andReturnSelf();
            $mockImage->shouldReceive('scale')->with(480, 480)->andReturnSelf();
            $mockImage->shouldReceive('toWebp')->with(80)->andReturn($mockEncodedImage);

            Image::shouldReceive('read')->andReturn($mockImage);

            $file = UploadedFile::fake()->image('test.jpg', 1200, 1000);
            $result = $this->imageManipulation->processProductImage($file);

            expect($result)->toBeString();
        });

        test('maintains aspect ratio for square images', function () {
            $mockEncodedImage = Mockery::mock(EncodedImageInterface::class);
            $mockEncodedImage->shouldReceive('toString')->andReturn('webp-binary-data');

            $mockImage = Mockery::mock(ImageInterface::class);
            $mockImage->shouldReceive('width')->andReturn(600);
            $mockImage->shouldReceive('height')->andReturn(600);
            $mockImage->shouldReceive('cover')->with(480, 480, 'center')->andReturnSelf();
            $mockImage->shouldReceive('scale')->with(480, 480)->andReturnSelf();
            $mockImage->shouldReceive('toWebp')->with(80)->andReturn($mockEncodedImage);

            Image::shouldReceive('read')->andReturn($mockImage);

            $file = UploadedFile::fake()->image('test.jpg', 600, 600);
            $result = $this->imageManipulation->processProductImage($file);

            expect($result)->toBe('webp-binary-data');
        });

        test('handles small images correctly', function () {
            $mockEncodedImage = Mockery::mock(EncodedImageInterface::class);
            $mockEncodedImage->shouldReceive('toString')->andReturn('webp-binary-data');

            $mockImage = Mockery::mock(ImageInterface::class);
            $mockImage->shouldReceive('width')->andReturn(200);
            $mockImage->shouldReceive('height')->andReturn(150);
            $mockImage->shouldReceive('cover')->with(150, 150, 'center')->andReturnSelf();
            $mockImage->shouldReceive('scale')->with(150, 150)->andReturnSelf();
            $mockImage->shouldReceive('toWebp')->with(80)->andReturn($mockEncodedImage);

            Image::shouldReceive('read')->andReturn($mockImage);

            $file = UploadedFile::fake()->image('test.jpg', 200, 150);
            $result = $this->imageManipulation->processProductImage($file);

            expect($result)->toBe('webp-binary-data');
        });

        test('applies webp compression quality of 80', function () {
            $mockEncodedImage = Mockery::mock(EncodedImageInterface::class);
            $mockEncodedImage->shouldReceive('toString')->andReturn('webp-binary-data');

            $mockImage = Mockery::mock(ImageInterface::class);
            $mockImage->shouldReceive('width')->andReturn(800);
            $mockImage->shouldReceive('height')->andReturn(600);
            $mockImage->shouldReceive('cover')->andReturnSelf();
            $mockImage->shouldReceive('scale')->andReturnSelf();
            $mockImage->shouldReceive('toWebp')->with(80)->andReturn($mockEncodedImage);

            Image::shouldReceive('read')->andReturn($mockImage);

            $file = UploadedFile::fake()->image('test.jpg', 800, 600);
            $this->imageManipulation->processProductImage($file);

            // Expectation is verified through Mockery's shouldReceive assertion
            expect(true)->toBeTrue();
        });
    });
});
