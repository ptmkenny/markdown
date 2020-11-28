<?php

namespace Drupal\markdown\Traits;

/**
 * Trait to assist with accessing the file system.
 *
 */
trait FileSystemTrait
{

    /**
     * The file system.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * Retrieves the file system.
     *
     * @return \Drupal\Core\File\FileSystemInterface
     *   The file system.
     */
    public function fileSystem()
    {
        if (!$this->fileSystem) {
            $this->fileSystem = \Drupal::service('file_system');
        }
        return $this->fileSystem;
    }

}