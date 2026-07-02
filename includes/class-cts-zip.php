<?php

/**
 * ZIP Archive Helper
 */
class Picotse_Classic_Zip
{

    public function create_zip($source_dir, $zip_file)
    {
        if (! class_exists('ZipArchive')) {
            return new WP_Error('zip_archive_missing', __('ZipArchive class is missing on this server.', 'picot-theme-seeder'));
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return new WP_Error('zip_open_failed', __('Could not open ZIP file for writing.', 'picot-theme-seeder'));
        }

        $source = realpath($source_dir);
        if (is_dir($source)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $file = realpath($file);
                if (is_dir($file)) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file)) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        } else if (is_file($source)) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        $zip->close();
        return true;
    }
}
