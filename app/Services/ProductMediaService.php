<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores product photos and short videos.
 *
 * Photos: resized to max 1600px and saved as WebP, plus a 400px WebP thumbnail
 *         for lists/thumbnail strips. Big phone photos (3–8 MB) become
 *         ~150–300 KB, which is the single biggest win for app/web load speed.
 *         Falls back to storing the original if the server's GD/Imagick
 *         can't write WebP.
 *
 * Videos: stored as uploaded (no server-side transcoding, so this works on
 *         shared hosting). The admin's browser grabs a poster frame and the
 *         duration before upload; limits are enforced here.
 */
class ProductMediaService
{
    public const MAX_IMAGE_EDGE   = 1600;
    public const THUMB_EDGE       = 400;
    public const MAX_VIDEO_KB     = 30720;  // 30 MB
    public const MAX_VIDEO_SECONDS = 60;

    public function storePhoto(Product $product, UploadedFile $file, array $attrs = []): ProductImage
    {
        [$path, $thumb] = $this->optimise($file, "product/{$product->id}");

        return $product->images()->create(array_merge([
            'image_path' => $path,
            'thumb_path' => $thumb,
            'media_type' => ProductImage::TYPE_IMAGE,
            'sort_order' => $this->nextSortOrder($product),
        ], $attrs));
    }

    public function storeVideo(Product $product, UploadedFile $video, ?UploadedFile $poster, ?int $duration, array $attrs = []): ProductImage
    {
        $dir  = "product/{$product->id}/videos";
        $ext  = strtolower($video->getClientOriginalExtension() ?: $video->extension() ?: 'mp4');
        $path = $video->storeAs($dir, Str::uuid() . '.' . $ext, 'public');

        $posterPath = null;
        if ($poster) {
            [$posterPath] = $this->optimise($poster, $dir, false);
        }

        return $product->images()->create(array_merge([
            'image_path'       => $path,
            'poster_path'      => $posterPath,
            'thumb_path'       => $posterPath,
            'media_type'       => ProductImage::TYPE_VIDEO,
            'duration_seconds' => $duration ? min($duration, self::MAX_VIDEO_SECONDS) : null,
            // Videos go first in the gallery (Temu/Jumia style)
            'sort_order'       => $this->firstSortOrder($product),
        ], $attrs));
    }

    public function delete(ProductImage $media): void
    {
        foreach (array_unique(array_filter([$media->image_path, $media->thumb_path, $media->poster_path])) as $p) {
            Storage::disk('public')->delete(preg_replace('#^/?storage/#', '', $p));
        }
        $media->delete();
    }

    /**
     * Resize + WebP. Returns [mainPath, thumbPath|null].
     */
    public function optimise(UploadedFile $file, string $dir, bool $withThumb = true, int $maxEdge = self::MAX_IMAGE_EDGE): array
    {
        $name = (string) Str::uuid();

        try {
            if (!class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
                throw new \RuntimeException('Intervention Image not installed');
            }
            $image = \Intervention\Image\Laravel\Facades\Image::read($file->getRealPath());
            $image->scaleDown($maxEdge, $maxEdge);
            $main = "{$dir}/{$name}.webp";
            Storage::disk('public')->put($main, (string) $image->toWebp(82));

            $thumb = null;
            if ($withThumb) {
                $image->scaleDown(self::THUMB_EDGE, self::THUMB_EDGE);
                $thumb = "{$dir}/{$name}_thumb.webp";
                Storage::disk('public')->put($thumb, (string) $image->toWebp(75));
            }

            return [$main, $thumb];
        } catch (\Throwable $e) {
            Log::warning('Product image optimisation failed, storing original', ['error' => $e->getMessage()]);
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            return [$file->storeAs($dir, "{$name}.{$ext}", 'public'), null];
        }
    }

    /** Product card thumbnail: one 800px WebP (lists load these, so smaller is faster). */
    public function storeThumbnail(UploadedFile $file): string
    {
        [$path] = $this->optimise($file, 'product', false, 800);
        return $path;
    }

    protected function nextSortOrder(Product $product): int
    {
        return (int) $product->images()->max('sort_order') + 1;
    }

    protected function firstSortOrder(Product $product): int
    {
        return (int) $product->images()->min('sort_order') - 1;
    }
}
