<?php

namespace Oleksiil\ImageUploader;

class ImageUploader
{
    private static $allowedFileTypes = array(
        'image/jpeg',
        'image/gif',
        'image/png'
    );

    private static $maxFileSize = 1 * 1024 * 1024; // Bytes

    public static function upload($dir = '../upload', $keys = array())
    {
        $result = (object) array(
            'status'       => 'OK',
            'files'       => array()
        );
        foreach($_FILES as $name => $file) {
            if (count($keys) > 0 && !in_array($name, $keys)) {
                continue;
            }
            try {
                $result->files[] = self::handleFile($file, $dir);
            } catch (Exception $e) {
                $result->status = 'fail';
                $result->files[] = (object) array(
                    'status'      => 'fail',
                    'description' => $e->getMessage(),
                    'file'        => $file['name'],
                );
            }
        }
        return $result;
    }

    private static function handleFile($file, $dir)
    {
        $mime = self::getMIME($file['tmp_name']);
        if (!in_array($mime, self::$allowedFileTypes)) {
            throw new Exception("'{$mime}' isn't allowed MIME-type.");
        }
        $size = self::getSize($file['tmp_name']);
        if ($size > self::$maxFileSize) {
            throw new Exception("File is too big. Allowed max size: " . self::$maxFileSize . " Bytes");
        }
        $destination = __DIR__ . "/" .self::getDestination($file, $dir);
        self::moveUploaded($file['tmp_name'], $destination);
        return (object)array(
            'status'      => 'OK',
            'description' => '',
            'file'        => $destination,
            'mime'        => $mime,
            'size'        => $size
        );
    }

    private static function getMIME($file)
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($file);
        } else {
            throw new Exception('You must install extention php_fileinfo to your php');
        }
    }

    private static function getSize($file)
    {
        return filesize($file);
    }

    private static function getDestination($file, $dir)
    {
        $path = $dir . '/' . date('Y/m');
        self::createDirectories($path);
        list(,,$extention,$filename) = array_values(pathinfo($file['name']));
        if (file_exists("{$path}/{$filename}.{$extention}")) {
            $i = 1;
            while (true) {
                if (!file_exists("{$path}/{$filename}-{$i}.{$extention}")) {
                    $filename .= "-{$i}";
                    break;
                } else {
                    $i++;
                }
            }
        }
        return "{$path}/{$filename}.{$extention}";
    }

    private static function createDirectories($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }
    }

    private static function moveUploaded($file, $destination)
    {
        if (!move_uploaded_file($file, $destination)) {
            throw new Exception("Something went wrong. Try again.");
        }
    }
}
