<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;

class ImageManipulation
{
    /**
     * Process and resize a product image to a square format based on its smaller side,
     * with a maximum size of 480x480 pixels, and convert it to WebP format.
     *
     * @param  UploadedFile  $file  The uploaded product image file to be processed.
     * @return string The processed image as a WebP-encoded binary string.
     */
    public function processProductImage(UploadedFile $file): string
    {
        $image = Image::read($file->getPathname());

        $smallerSide = min($image->width(), $image->height());
        $size = min($smallerSide, 480);

        return $image
            ->cover($size, $size, 'center')
            ->toWebp(80)
            ->toString();
    }

    /**
     * Generate a filename for a product image.
     *
     * The filename is generated using a unique ID and returned as a WebP file name.
     * The `$options` array can be used to customize the filename prefix and the
     * directory path prepended to the generated name.
     *
     * @param array{
     *     prefix?: string,
     *     path?: string
     * } $options Optional configuration for the generated filename:
     *     - `prefix`: String prefix used when generating the unique ID. Defaults to `"product_"`.
     *     - `path`: Directory path prepended to the filename. Defaults to `"products/"`.
     * @return string The generated product image filename (including path), ending with the `.webp` extension.
     */
    public function getProductImageName($options = []): string
    {
        $prefix = $options['prefix'] ?? 'product_';
        $path = $options['path'] ?? 'products/';

        return $path.uniqid($prefix).'.webp';
    }
}
