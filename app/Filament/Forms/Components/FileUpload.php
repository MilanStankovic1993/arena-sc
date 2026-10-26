<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload as BaseFileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUpload extends BaseFileUpload
{
    public function getValidationRules(): array
    {
        return [
            'bail',
            static function (string $attribute, mixed $value, Closure $fail): void {
                foreach ((array) $value as $file) {
                    if ($file instanceof TemporaryUploadedFile && ! $file->exists()) {
                        $fail('Privremena slika vise nije dostupna. Uklonite je i otpremite ponovo.');

                        return;
                    }
                }
            },
            ...parent::getValidationRules(),
        ];
    }
}
