<?php

namespace Lartrix\Modules\Manifest;

use InvalidArgumentException;
use JsonException;

/** Loads a validated Trix manifest from a Nwidart module.json file. */
final class ModuleManifestLoader
{
    public function loadFromPath(string $path): ?ModuleManifest
    {
        $manifestPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'module.json';

        if (!is_file($manifestPath)) {
            return null;
        }

        $module = $this->readJsonFile($manifestPath);

        if (!array_key_exists('trix', $module)) {
            return null;
        }

        $rootErrors = ModuleManifestValidator::validateNwidart($module);
        if ($rootErrors !== []) {
            throw $this->validationException($manifestPath, $rootErrors);
        }

        if (!is_array($module['trix'])) {
            throw $this->validationException($manifestPath, ['trix' => 'trix must be an object.']);
        }

        $trixErrors = ModuleManifestValidator::validate($module['trix']);
        if ($trixErrors !== []) {
            throw $this->validationException($manifestPath, $trixErrors, 'trix.');
        }

        return ModuleManifest::fromValidatedArray($module['trix']);
    }

    /** @return array<string, mixed> */
    private function readJsonFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read JSON manifest: $path");
        }

        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("Invalid JSON manifest: $path", previous: $e);
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException("Invalid JSON manifest: $path must contain an object.");
        }

        return $decoded;
    }

    /**
     * @param array<string, string> $errors
     */
    private function validationException(string $path, array $errors, string $prefix = ''): InvalidArgumentException
    {
        $messages = [];

        foreach ($errors as $field => $message) {
            $messages[] = $prefix . $field . ': ' . $message;
        }

        return new InvalidArgumentException(
            "Invalid module manifest $path: " . implode('; ', $messages)
        );
    }
}
