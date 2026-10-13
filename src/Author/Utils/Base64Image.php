<?php

namespace GisClient\Author\Utils;

/**
 * Immagine raster ricevuta o salvata come stringa base64.
 */
class Base64Image
{
    /**
     * @var string
     */
    private $binary;

    /**
     * @var string|null
     */
    private $mimeType;

    private function __construct(string $binary, ?string $mimeType)
    {
        $this->binary = $binary;
        $this->mimeType = $mimeType;
    }

    /**
     * Restituisce null se la stringa non e' base64 valido.
     */
    public static function fromBase64(string $base64): ?self
    {
        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        // Il tipo si ricava dal contenuto, non da quanto dichiara il client
        $info = @getimagesizefromstring($binary);
        $mimeType = is_array($info) && isset($info['mime']) ? (string) $info['mime'] : null;

        return new self($binary, $mimeType);
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getByteSize(): int
    {
        return strlen($this->binary);
    }

    public function toDataUri(): ?string
    {
        if ($this->mimeType === null) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $this->mimeType, base64_encode($this->binary));
    }
}
