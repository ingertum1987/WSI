<?php

namespace App\Service;

class File
{
    /**
     * @param string $dir
     * @return bool
     */
    public static function isEmptyDir(string $dir)
    {
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, ['.', '..'])) return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}