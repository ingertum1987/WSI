<?php

namespace App\Model;

interface UploadInterface
{
    const UPLOAD_DIR = 'upload/';
    const DIR_CHMOD = 0755;
    const NOFOTO_SUBDIR = 'nofoto/';
    const IMAGE_SUBDIR = 'images/';
}