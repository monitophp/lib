<?php
namespace MonitoLib;

use \MonitoLib\App;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;

class Image
{
    const VERSION = '1.2.0';
    /**
    * 1.2.0 - 2021-05-17
    * new: convertToJpeg()
    *
    * 1.1.0 - 2021-05-10
    * new: getHeight(), getMimetype(), getSize(), getWidth
    *
    * 1.0.1 - 2021-05-04
    * new: using only GD to optimize images
    *
    * 1.0.0 - 2020-09-28
    * Initial release
    */

    private $base64encode;
    private $height;
    private $image;
    private $mimetype;
    private $quality;
    private $size;
    private $tmpFile;
    private $width;

    public function __construct($file)
    {
        $this->parse($file);
        $this->tmpFile = App::getTmpPath() . sha1(App::now() . rand(1, 999999));

    }
    public function __destruct()
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }
    public function adjust(int $width, int $height) : self
    {
        // Verifica se a imagem está maior que o determinado
        if ($this->width <= $width && $this->height <= $height) {
            return $this;
        }

        if ($this->width > $this->height) {
            $nw = $width;
            $nh = $this->height / $this->width * $height;
        } else {
            $nw = $this->width / $this->height * $width;
            $nh = $height;
        }

        // Cria uma imagem em branco
        $di = imagecreatetruecolor($nw, $nh);

        // Redimensiona a imagem
        if (imagecopyresampled($di, $this->image, 0, 0, 0, 0, $nw, $nh, $this->width, $this->height)) {
            $this->image  = $di;
            $this->width  = imagesx($this->image);
            $this->height = imagesy($this->image);
        }

        // Ajusta a tela da imagem
        if ($this->width !== $this->height) {
            $nil = $this->width > $this->height ? $this->width : $this->height;

            $di = imagecreatetruecolor($nil, $nil);
            $bg = imagecolorallocate($di, 255, 255, 255);
            imagefill($di, 0, 0, $bg);


            if ($this->width > $this->height) {
                $nx = 0;
                $ny = floor(($this->width - $this->height) / 2);
            } else {
                $nx = floor(($this->height - $this->width) / 2);
                $ny = 0;
            }

            if (imagecopyresampled($di, $this->image, $nx, $ny, 0, 0, $this->width, $this->height, $this->width, $this->height)) {
                $this->image = $di;
                $this->width  = imagesx($this->image);
                $this->height = imagesy($this->image);
            }
        }

        $this->updateDimensions();
        $this->base64encode = null;
        return $this;
    }
    public function autocrop(string $color = 'ffffff') : self
    {
        // Croppa a imagem
        $this->image  = imagecropauto($this->image, IMG_CROP_WHITE);
        $this->updateDimensions();
        $this->base64encode = null;
        return $this;
    }
    public function convertToJpeg()
    {
        $bg = imagecreatetruecolor($this->width, $this->height);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, true);
        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->width, $this->height);
        $this->image = $bg;
    }
    private function create(string $file) : void
    {
        switch ($this->mimetype) {
            case 'image/bmp':
                $this->image = imagecreatefrombmp($file);
                break;
            case 'image/gif':
                $this->image = imagecreatefromgif($file);
                break;
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($file);
                break;
            case 'image/png':
                $this->image = imagecreatefrompng($file);
                break;
            case 'image/webp':
                $this->image = imagecreatefromwebp($file);
                break;
            default:
                throw new BadRequest("Tipo de imagem inválido: {$this->mimetype}");
        }
    }
    public function getBase64Encode() : string
    {
        if (is_null($this->base64encode)) {
            $this->save($this->tmpFile);
            $this->base64encode = base64_encode(file_get_contents($this->tmpFile));
            unlink($this->tmpFile);
        }

        return $this->base64encode;
    }
    public function getHeight() : int
    {
        return $this->height;
    }
    public function getMimetype() : string
    {
        return $this->mimetype;
    }
    public function getQuality() : float
    {
        return $this->quality;
    }
    public function getSize() : int
    {
        return $this->size;
    }
    public function getWidth() : int
    {
        return $this->width;
    }
    public function optimize(float $quality, ?int $maxSize = 0) : self
    {
        $this->quality = $quality;

        if ($maxSize > 0) {
            imagejpeg($this->image, $this->tmpFile, $quality);
            $size = $this->parseSize($this->tmpFile);

            while ($size >= $maxSize) {
                imagejpeg($this->image, $this->tmpFile, $quality);
                $size = $this->parseSize($this->tmpFile);

                $this->quality = $quality;

                $quality -= 1;
            }

            $this->size = $size;
        }

        $this->base64encode = null;
        return $this;
    }
    private function parse(string $file) : void
    {
        if (!file_exists($file)) {
            throw new NotFound("O arquivo $file não foi encontrado");
        }

        // Identifica o tipo da imagem
        $imi = getimagesize($file);
        $this->width    = $imi[0];
        $this->height   = $imi[1];
        $this->mimetype = $imi['mime'];
        $this->size     = filesize($file);

        // Cria a image
        $this->create($file);
    }
    public function parseSize(string $file)
    {
        clearstatcache(true, $file);
        return filesize($file);
    }
    public function save(string $file) : self
    {
        imagejpeg($this->image, $file, $this->quality);
        return $this;
    }
    private function updateDimensions() : void
    {
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }
}