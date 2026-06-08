<?php

namespace App\Domain\Ecommerce;

use App\Models\EcommerceProdotto;
use App\Models\EcommerceProdottoFotoOperatore;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OperatoreFotoCatalogoService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<EcommerceProdottoFotoOperatore>
     */
    public function linkBulk(EcommerceProdotto $prodotto, array $files, User $user): array
    {
        $linked = [];

        foreach ($files as $file) {
            $linked[] = $this->linkFoto($prodotto, $file, $user);
        }

        return $linked;
    }

    public function linkFoto(EcommerceProdotto $prodotto, UploadedFile $file, User $user): EcommerceProdottoFotoOperatore
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = sprintf('ecommerce/operatore/%d/%s.%s', $prodotto->id, Str::uuid(), $ext);

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return EcommerceProdottoFotoOperatore::create([
            'ecommerce_prodotto_id' => $prodotto->id,
            'uploaded_by'           => $user->id,
            'path'                  => $path,
            'is_demo'               => (bool) $prodotto->is_demo,
        ]);
    }

    /**
     * @return Collection<int, EcommerceProdottoFotoOperatore>
     */
    public function fotoForProdotto(EcommerceProdotto $prodotto): Collection
    {
        return EcommerceProdottoFotoOperatore::query()
            ->where('ecommerce_prodotto_id', $prodotto->id)
            ->latest()
            ->get();
    }

    public function publicUrl(EcommerceProdottoFotoOperatore $foto): string
    {
        return Storage::disk('public')->url($foto->path);
    }
}
