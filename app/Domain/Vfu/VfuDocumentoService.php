<?php

namespace App\Domain\Vfu;

use App\Enums\VfuAllegatoTipo;
use App\Models\User;
use App\Models\VfuDocumento;
use App\Models\VfuRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VfuDocumentoService
{
    /**
     * @return Collection<int, VfuDocumento>
     */
    public function listFor(VfuRegistration $registration): Collection
    {
        return $registration->documenti()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();
    }

    public function upload(
        VfuRegistration $registration,
        UploadedFile $file,
        VfuAllegatoTipo $tipo,
        User $user,
    ): VfuDocumento {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('File non valido.');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = sprintf(
            'vfu-allegati/%d/%s.%s',
            $registration->id,
            Str::uuid(),
            $ext,
        );

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return $registration->documenti()->create([
            'tipo'          => $tipo,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by'   => $user->id,
        ]);
    }

    public function download(VfuDocumento $documento): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($documento->path), 404);

        return Storage::disk('public')->download($documento->path, $documento->original_name);
    }

    public function delete(VfuDocumento $documento): void
    {
        if ($documento->path && Storage::disk('public')->exists($documento->path)) {
            Storage::disk('public')->delete($documento->path);
        }

        $documento->delete();
    }
}
