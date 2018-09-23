<?php

namespace Simff\Model\Fields;


use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Simff\Helpers\Paths;
use Simff\Main\Simff;
use Simff\Model\Model;

class ImageField extends CharField
{
    public $htmlType = 'file';

    public $accept = ['image/jpeg', 'image/png', 'image/jpg'];

    public $maxSizes = [];

    public $methodResize = 'contain';

    public $md5Name = true;

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $size;
    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string|int
     */
    public $error = UPLOAD_ERR_OK;
    /**
     * @var string
     */
    protected $ext;

    protected $_imagine;

    public function prepare($value)
    {
        if (is_array($value)) {
            $this->name = basename($value['name']);
            $this->path = $value['tmp_name'];
            $this->size = $value['size'];
            $this->type = $value['type'];
            $this->error = $value['error'];
        }
    }

    /**
     * @return string
     */
    public function getExt()
    {
        if ($this->ext === null) {
            $this->ext = pathinfo($this->name, PATHINFO_EXTENSION);

            if (strlen($this->ext) != 0 && strpos($this->ext, '.') === 0) {
                unset($this->ext[0]);
            }
        }
        return $this->ext;
    }

    public function getSizes()
    {
        $info = @getimagesize($this->path);
        $data = [];

        if (isset($info[0]) && isset($info[1])) {
            $data = [
                'width' => $info[0],
                'height' => $info[1]
            ];
        }

        return $data;
    }

    public function valid()
    {
        if ($this->type && (!in_array($this->type, $this->accept) || !$this->getSizes())) {
            $this->error = 'Недопустимый формат файла';
            return false;
        }

        if (!$this->path && $this->required) {
            $this->error = 'Обязательно для заполнения';
            return false;
        }

        if ($this->path) {
            $this->saveFile();
        }

        return true;
    }

    public function getValue()
    {
        if ($this->_value) {
            return '/media/' . $this->model::classNameShort() . '/' . $this->_value;
        }

        return $this->_value;
    }

    public function delete()
    {
        $uploadDir = $this->getUploadDir();

        $fileName = $this->getValue();

        unlink($uploadDir . DIRECTORY_SEPARATOR . $fileName);
    }

    public function saveFile()
    {
        $maxSizes = $this->maxSizes;
        $sizes = $this->getSizes();
        $source = null;

        if (isset($maxSizes[0]) && isset($maxSizes[1]) && $sizes) {
            if ($sizes['height'] > $maxSizes[1] || $sizes['width'] > $maxSizes[0]) {
                $source = $this->resize();
            }
        }

        if (!$source) {
            $source = file_get_contents($this->path);
        }

        $uploadDir = $this->getUploadDir();

        $fileName = $this->getFileName();
        file_put_contents($uploadDir.DIRECTORY_SEPARATOR.$fileName, $source);

        $this->setValue($fileName);
    }

    public function getUploadDir()
    {
        $basePath = Paths::get('public.media');
        $storagePath = $basePath . DIRECTORY_SEPARATOR . $this->model::classNameShort();

        if (!is_dir($storagePath)) {
            mkdir($storagePath);
        }

        return $storagePath;
    }

    public function getFileName()
    {
        $name = $this->name;

        if ($this->md5Name) {
            $name = md5($name . uniqid()) . '.' . $this->getExt();
        }

        return $name;
    }

    public function resize()
    {
        $imageInstance = $this->getImageInstance();

        $methodName = $this->methodResize;
        $methodName = 'size'. ucfirst($methodName);

        if ($imageInstance && method_exists($this, $methodName)) {

            $box = $this->getSizeBox($imageInstance, $this->maxSizes);

            $source = $this->{$methodName}($box, $imageInstance);

            return $source->get($this->getExt());
        }

        return false;
    }

    public function initImagine()
    {
        $imagine = null;

        if (class_exists('Gmagick', false)) {
            $imagine = new \Imagine\Gmagick\Imagine();
        }
        if (class_exists('Imagick', false)) {
            $imagine = new \Imagine\Imagick\Imagine();
        }
        if (function_exists('gd_info')) {
            $imagine = new \Imagine\Gd\Imagine();
        }

        if ($imagine) {
            return $imagine;
        }

        throw new \Exception('Libs: Gmagick, Imagick or Gd not found');
    }

    /**
     * @param ImageInterface $image
     * @param $sizeParams
     * @return Box|BoxInterface
     */
    protected function getSizeBox(ImageInterface $image, $sizeParams)
    {
        $width = isset($sizeParams[0]) ? $sizeParams[0] : 0;
        $height = isset($sizeParams[1]) ? $sizeParams[1] : 0;

        $box = new Box($width, $height);

        return $box;

    }

    public function getImagine()
    {
        if ($this->_imagine === null) {
            $this->_imagine = $this->initImagine();
        }
        return $this->_imagine;
    }

    public function getImageInstance()
    {
        $filePath = $this->path;
        $instance = null;
        if (is_readable($filePath)) {
            try {
                $instance = $this->getImagine()->open($filePath);
            } catch (Exception $e) {
            }
        }
        return $instance;
    }

    /**
     * @param BoxInterface $box
     * @param ImageInterface $imageInstance
     * @return ImageInterface|static
     */
    public function sizeCover(BoxInterface $box, $imageInstance)
    {
        return $imageInstance->thumbnail($box, ManipulatorInterface::THUMBNAIL_OUTBOUND);
    }

    /**
     * @param BoxInterface $box
     * @param ImageInterface $imageInstance
     * @return ImageInterface|static
     */
    public function sizeContain(BoxInterface $box, $imageInstance)
    {
        return $imageInstance->thumbnail($box, ManipulatorInterface::THUMBNAIL_INSET);
    }
}