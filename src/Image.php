<?php
namespace MonitoLib;

use \MonitoLib\App;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\Conflict;
use \MonitoLib\Exception\NotFound;
use \Spatie\ImageOptimizer\OptimizerChain;
use \Spatie\ImageOptimizer\OptimizerChainFactory;
use \Spatie\ImageOptimizer\Optimizers\Jpegoptim;

class Image
{
    const VERSION = '1.0.0';

    public function __construct ($file)
    {
        $this->process($file);
    }

    private function create (string $file) : void
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

    public function adjust (int $width, int $height) : self
    {
        // Verifica se a imagem está dentro do tamanho limite
        if ($this->width > $width || $this->height > $height) {
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
                $this->image = $di;
                $this->width = imagesx($this->image);
                $this->height = imagesy($this->image);
            }
        }

        // Ajusta a tela da imagem
        if ($this->width !== $this->height) {
            $nil = $this->width > $this->height ? $this->width : $this->height;

            $di = imagecreatetruecolor($nil, $nil);
            $background = imagecolorallocate($di, 255, 255, 255);
            imagefill($di, 0, 0, $background);
            // imagejpeg($di, $dir . '_test1.jpg');

            // $bg = imagecolorat($di, 0, 0);

            // if ($background !== false) {
                // Set the backgrund to be blue
                // imagecolorset($di, $background, 0, 255, 0);
            // }

            if ($this->width > $this->height) {
                $nx = 0;
                $ny = floor(($this->width - $this->height) / 2);
            } else {
                $nx = floor(($this->height - $this->width) / 2);
                $ny = 0;
            }

            \MonitoLib\Dev::e($nx);
            \MonitoLib\Dev::e($ny);

            if (imagecopyresampled($di, $this->image, $nx, $ny, 0, 0, $this->width, $this->height, $this->width, $this->height)) {
                $this->image = $di;
            }
        }
        return $this;
    }
    public function autocrop (string $color = 'ffffff') : self
    {
        // Croppa a imagem
        $this->image = imagecropauto($this->image, IMG_CROP_WHITE);
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        return $this;
    }

    public function optimize (float $quality, ?int $maxSize) : self
    {
        // Cria a instância do otimizador
        // $optimizerChain = (new OptimizerChain)
        //     ->addOptimizer(new Jpegoptim([
        //         '--max=' . $quality,
        //         '--strip-all',
        //         '--all-progressive',
        //     ]));

        //     // Otimiza a imagem
        // $optimizerChain->optimize($this->file);
        while ($quality >= $minQuality && $size <= $maxSize) {
            // imagejpeg($this->image, $file, $quality);
            imagejpeg($this->image, 'php://temp/temp.jpg', $quality);
            $this->size = filesize('php://temp/temp.jpg');
        }

        return $this;
    }

    private function process($file)
    {
        if (!file_exists($file)) {
            throw new NotFound("O arquivo $file não foi encontrado");
        }

        $this->file = $file;

        // Identifica o tipo da imagem
        $imi = getimagesize($file);
        $this->width = $imi[0];
        $this->height = $imi[1];
        $this->mimetype = $imi['mime'];
        $this->size = filesize($file);

        // Cria a image
        $this->create($file);
    }

    public function save (string $file) : self
    {
        // if (!file_exists($file)) {
            // throw new NotFound("O arquivo de destino$file não foi encontrado");
        // }
        imagejpeg($this->image, $file, 20);
        return $this;
    }

    public function run()
    {
        try {
            $request = \MonitoLib\Mcl\Request::getInstance();
            $dir     = $request->getParam('dir')->getValue();

            $imageLimit = 1000;

            $logger = new \MonitoLib\Logger("media-upload.log");
            $logger->log("Processando diretório $dir", true);

            if (!is_dir($dir)) {
                throw new BadRequest("$dir não é um diretório válido");
            }

            if (substr($dir, -1) !== '/') {
                $dir .= '/';
            }

            $files = scandir($dir);
            $skus = [];

            // Cria a instância do otimizador
            $optimizerChain = (new OptimizerChain)
                ->addOptimizer(new Jpegoptim([
                    '--max=60',
                    '--strip-all',
                    '--all-progressive',
                ]));

            // Agrupa as imagens por sku
            foreach ($files as $f) {
                preg_match('/^([0-9]+)_?([0-9]+)?/', $f, $m);
                // \MonitoLib\Dev::pr($m);
                if (!empty($m)) {
                    $p = $m[1];
                    $s = $m[2] ?? '';
                    $skus[$p][$f] = $s;
                }
            }

            // \MonitoLib\Dev::pre($skus);
            // \MonitoLib\Dev::ee();

            $productDao = new \Magento\Dao\Product();
            $productList = $productDao->listBySku(array_keys($skus));

            $mediaDao = new \Magento\Dao\Media();

            $types = [
                'image',
                'small_image',
                'swatch_image',
                'thumbnail',
                'weltpixel_hover_image',
            ];

            foreach ($productList->items as $item) {
                \MonitoLib\Dev::pre($item);

                $medias = [];

                if (isset($item->media_gallery_entries)) {
                    foreach ($item->media_gallery_entries as $media) {
                        $medias[] = substr($media->file, strrpos($media->file, '/') + 1);
                    }
                }

                $sku  = $item->sku;
                $name = $item->name;
                $base = $this->parseName($item->name);

                $logger->log("$sku: iniciado", true);

                $i = 0;
                $images = [];

                foreach ($skus[$sku] as $image => $suf) {
                    try {
                        $move = false;

                        $suf = $suf === '' ? '' : "-$suf";
                        $inf = pathinfo($image);
                        $ext = $inf['extension'];
                        $pat = $dir . $image;
                        $fil = "{$base}{$suf}.{$ext}";
                        $len = strlen($fil);

                        $sha = hash_file('sha512', $pat);
                        \MonitoLib\Dev::e($sha);

                        if ($len > 90) {
                            $fil = substr($base, 0, ($suf === '' ? 86 : 84)) . "{$suf}.{$ext}";
                        }

                        // Identifica o tipo da imagem
                        $imi = getimagesize($pat);
                        $mime = $imi['mime'];

                        // Croppa a imagem
                        $imr = imagecreatefromjpeg($pat);
                        // file_put_contents($dir . '_test.jpg',
                        // $cropped_img_white = imagecropauto($imr , IMG_CROP_THRESHOLD, null, 16777215);
                        $cropped_img_white = imagecropauto($imr, IMG_CROP_WHITE);
                        // imagejpeg($cropped_img_white, $dir . '_test.jpg');

                        // Identifica o tamanho da imagem
                        // $imi = getimagesize($dir . '_test.jpg');
                        // $width = $imi[0];
                        // $height = $imi[1];
                        $width = imagesx($cropped_img_white);
                        $height = imagesy($cropped_img_white);

                        // Verifica se a imagem está dentro do tamanho limite
                        if ($width > $imageLimit || $height > $imageLimit) {
                            if ($width > $height) {
                                $nw = $imageLimit;
                                $nh = $height / $width * $imageLimit;
                            } else {
                                $nw = $width / $height * $imageLimit;
                                $nh = $imageLimit;
                            }

                            // Cria uma imagem em branco
                            $di = imagecreatetruecolor($nw, $nh);

                            // Redimensiona a imagem
                            if (imagecopyresampled($di, $cropped_img_white, 0, 0, 0, 0, $nw, $nh, $width, $height)) {
                                $cropped_img_white = $di;
                            }
                        }

                        $width  = imagesx($cropped_img_white);
                        $height = imagesy($cropped_img_white);

                        // Ajusta a tela da imagem
                        if ($width !== $height) {
                            $nil = $width > $height ? $width : $height;

                            $di = imagecreatetruecolor($nil, $nil);
                            $background = imagecolorallocate($di, 255, 255, 255);
                            imagefill($di, 0, 0, $background);
                            // imagejpeg($di, $dir . '_test1.jpg');

                            // $bg = imagecolorat($di, 0, 0);

                            // if ($background !== false) {
                                // Set the backgrund to be blue
                                // imagecolorset($di, $background, 0, 255, 0);
                            // }

                            if ($width > $height) {
                                $nx = 0;
                                $ny = floor(($width - $height) / 2);
                            } else {
                                $nx = floor(($height - $width) / 2);
                                $ny = 0;
                            }

                            \MonitoLib\Dev::e($nx);
                            \MonitoLib\Dev::e($ny);

                            // if (imagecopyresampled($di, $cropped_img_white, $nx, $ny, $nx, $ny, $imageLimit, $imageLimit, $width, $height)) {
                            if (imagecopyresampled($di, $cropped_img_white, $nx, $ny, 0, 0, $width, $height, $width, $height)) {
                                $cropped_img_white = $di;
                            }
                        }

                        imagejpeg($cropped_img_white, $dir . '_test.jpg');
                        \MonitoLib\Dev::ee();

                        // Se a imagem não for quadrada, ajusta
                            // throw new BadRequest("$sku: a proporção da imagem não é 1:1");

                        // \MonitoLib\Dev::ee($fil);
                        // jogo-de-discos-para-lixar-e-polir-4.1-2-pol-e-suporte-com-pluma-sistema-fixa-facil-vonder.jpg





                        if (in_array($fil, $medias)) {
                            throw new Conflict("$sku: a imagem $fil já existe no Magento");
                        }


                        $fsb = filesize($pat);

                        // Otimiza a imagem
                        $optimizerChain->optimize($pat);

                        $fsa = filesize($pat);

                        if ($fsb - $fsa > 0) {
                            $per = round($fsb / $fsa);
                            $logger->log("$sku: imagem otimizada {$per}% menor", true);
                        }

                        if ($i > 0) {
                            $types = [];
                        }

                        $img = [
                            'media_type' => 'image',
                            'label'      => $name,
                            'position'   => $i,
                            'disabled'   => 'false',
                            'types'      => $types,
                            'file'       => $fil,
                            'content'    => [
                                'base64_encoded_data' => base64_encode(file_get_contents($pat)),
                                'type' => 'image/jpeg',
                                'name' => $fil
                            ]
                        ];

                        // Envia a imagem para o Magento
                        $mediaDao->addMedia($sku, $img);
                        $logger->log("$sku: imagem $fil enviada pro Magento", true);
                        $move = true;
                        $i++;
                    } catch (Conflict $e) {
                        $logger->log($e->getMessage(), true);
                        $move = true;
                    } finally {
                        if ($move) {
                            // Move o arquivo para imagens enviadas
                            rename($dir . $image, $dir . 'uploaded/' . $image);
                            $logger->log("$sku: arquivo $image movido para ./uploaded/", true);
                        }
                    }
                }

                $product = [
                    'sku' => $sku,
                    'status' => 1,
                    'visibility' => 4,
                    'custom_attributes' => [
                        [
                            'attribute_code' => 'image_label',
                            'value' => $name
                        ],
                        [
                            'attribute_code' => 'small_image_label',
                            'value' => $name
                        ],
                        [
                            'attribute_code' => 'thumbnail_label',
                            'value' => $name
                        ],
                    ]
                ];

                // Atualiza o produto
                $productDao->update($product);
                $logger->log("$sku: atualizado no Magento", true);
            }
        } catch (\Throwable $e) {
            $logger->log($e->getMessage(), true);
        }

        $logger->log("Processamento finalizado $dir", true);
    }
}