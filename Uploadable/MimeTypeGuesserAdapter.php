<?php

namespace Stof\DoctrineExtensionsBundle\Uploadable;

use Symfony\Component\Mime\MimeTypes;
use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;

class MimeTypeGuesserAdapter implements MimeTypeGuesserInterface
{
    public function guess($filePath)
    {
        $mimeTypes = new MimeTypes();
        return $mimeTypes->guessMimeType($filePath);
    }

}
