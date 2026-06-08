<?php

namespace App\Enums;

enum VfuTipoDocumento: string
{
    case CertificatoRottamazioneProvvisorio = 'certificato_rottamazione_provvisorio';
    case DocumentoIdentita = 'documento_identita';
    case CartaCircolazione = 'carta_circolazione';
    case DenunciaSmarrimento = 'denuncia_smarrimento';
    case CertificatoProprieta = 'certificato_proprieta';
    case Delega = 'delega';
    case CertificatoRottamazioneDefinitivo = 'certificato_rottamazione_definitivo';

    public function label(): string
    {
        return match ($this) {
            self::CertificatoRottamazioneProvvisorio => 'Certificato rottamazione (provvisorio)',
            self::DocumentoIdentita => 'Documento identità',
            self::CartaCircolazione => 'Carta di circolazione',
            self::DenunciaSmarrimento => 'Denuncia smarrimento',
            self::CertificatoProprieta => 'Certificato di proprietà',
            self::Delega => 'Delega',
            self::CertificatoRottamazioneDefinitivo => 'Certificato rottamazione (definitivo)',
        };
    }

    /** @return list<self> */
    public static function requiredForAccettazione(): array
    {
        return [
            self::DocumentoIdentita,
            self::CartaCircolazione,
        ];
    }
}
