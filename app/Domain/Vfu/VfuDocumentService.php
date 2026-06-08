<?php

namespace App\Domain\Vfu;

use App\Enums\VfuTipoDocumento;
use App\Models\VfuDocument;
use App\Models\VfuRegistration;
use App\Services\CertificatoRottamazionePdfService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VfuDocumentService
{
    public function __construct(
        private readonly CertificatoRottamazionePdfService $pdfService,
    ) {}

    public function store(VfuRegistration $registration, UploadedFile $file, VfuTipoDocumento $tipo): VfuDocument
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('File non valido.');
        }

        $this->deleteExisting($registration, $tipo);

        $path = $file->store("vfu-documents/{$registration->id}", 'public');

        return $registration->documents()->create([
            'tipo' => $tipo,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * @return array{document: VfuDocument, extracted: array<string, string>}
     */
    public function storeCertificatoProvvisorio(VfuRegistration $registration, UploadedFile $file): array
    {
        $path = $file->store('vfu-documents/certificati', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $extracted = $this->pdfService->extractFromPath($fullPath);

        $this->deleteExisting($registration, VfuTipoDocumento::CertificatoRottamazioneProvvisorio);

        $document = $registration->documents()->create([
            'tipo' => VfuTipoDocumento::CertificatoRottamazioneProvvisorio,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        $registration->update(['certificato_provvisorio_caricato' => true]);

        return ['document' => $document, 'extracted' => $extracted];
    }

    public function deleteExisting(VfuRegistration $registration, VfuTipoDocumento $tipo): void
    {
        $existing = $registration->documents()->where('tipo', $tipo)->get();

        foreach ($existing as $doc) {
            $this->deleteFile($doc);
            $doc->delete();
        }
    }

    public function deleteFile(VfuDocument $document): void
    {
        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
    }

    public function hasRequiredDocuments(VfuRegistration $registration): bool
    {
        $registration->loadMissing('documents');

        foreach (VfuTipoDocumento::requiredForAccettazione() as $tipo) {
            if (! $registration->documents->contains(fn ($d) => $d->tipo === $tipo)) {
                return false;
            }
        }

        return true;
    }
}
