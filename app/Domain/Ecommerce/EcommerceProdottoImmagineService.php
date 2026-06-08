<?php

namespace App\Domain\Ecommerce;

use App\Models\EcommerceProdotto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EcommerceProdottoImmagineService
{
    public function upload(EcommerceProdotto $prodotto, UploadedFile $file): EcommerceProdotto
    {
        $this->deleteFile($prodotto);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = sprintf('ecommerce/prodotti/%d/%s.%s', $prodotto->id, Str::uuid(), $ext);

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        $prodotto->update(['immagine_path' => $path]);

        return $prodotto->fresh();
    }

    public function remove(EcommerceProdotto $prodotto): EcommerceProdotto
    {
        $this->deleteFile($prodotto);
        $prodotto->update(['immagine_path' => null]);

        return $prodotto->fresh();
    }

    public function publicUrl(?EcommerceProdotto $prodotto): ?string
    {
        if ($prodotto === null || $prodotto->immagine_path === null) {
            return null;
        }

        return Storage::disk('public')->url($prodotto->immagine_path);
    }

    private function deleteFile(EcommerceProdotto $prodotto): void
    {
        if ($prodotto->immagine_path === null) {
            return;
        }

        Storage::disk('public')->delete($prodotto->immagine_path);
    }
}
