<?php
namespace FileStorage;

use FileStorage\Exception\EmptyFileContentException;
use FileStorage\Exception\InvalidFileKeyException;
use FileStorage\Exception\FileNotFoundException;
use FileStorage\Exception\FileAlreadyExistsException;

class FileStorage
{
    protected $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Saves changes to file
     *
     * @throws InvalidFileKeyException if key is invalid
     * @throws EmptyFileContentException if file content is empty
     *
     * @param FileInterface $file
     *
     * @return boolean success
     */
    public function save(FileInterface $file)
    {
        $this->validateKey($file->getKey());

        if ($file->getContent() == null) {
            throw new EmptyFileContentException($file->getKey(), "Cannot save an empty file.");
        }

        return $this->adapter->save($file);
    }

    /**
     * Loads a file for reading and modifying
     *
     * @throws InvalidFileKeyException if key is invalid
     *
     * @param string $key
     *
     * @return FileInterface $file;
     */
    public function load($key)
    {
        $this->validateKey($key);

        return $this->adapter->load($key);
    }

    /**
     * Initializes a new file object for further modifying.
     * When touch is enabled, a new empty file is immediately saved to storage.
     * Use this feature if you wish to ensure key is absolutely reserved for you. Notice, however, it makes an extra request to storage backend.
     *
     * @throws InvalidFileKeyException if key is invalid
     *
     * @param string $key
     * @param boolean $touch If true, immediately touches file creating a timestamp and reserving the key
     *
     * @return FileInterface $file;
     */
    public function init($key, $touch = false)
    {
        $this->validateKey($key);

        try {
            $file = $this->load($key);
            if (isset($file)) {
                throw new FileAlreadyExistsException($key, "File already exists");
            }
        } catch(FileNotFoundException $e) {
            //It's good that file does not exists here
            $file = $this->adapter->init($key, $touch);
            //Immediately save an empty file (ie. reserve key) if 'touch' is enabled
            if ($touch) {
                $this->save($file);
            }

            return $file;
        }
    }

    /**
     * Deletes file from FileStorage
     *
     * @throws FileNotFoundException if key does not match any file in storage
     * @throws InvalidFileKeyException if key is invalid
     *
     * @param string key
     *
     * @return boolean success
     */
    public function delete($key)
    {
        $this->validateKey($key);

        return $this->adapter->delete($key);
    }


    /**
     * Helper for validating key
     *
     * @throws InvalidFileKeyException if key is invalid
     *
     * @param string $key
     *
     * @return boolean true if key is valid
     */
    protected function validateKey($key)
    {
        if (! isset($key) || strlen(trim($key)) == 0) {
            throw new InvalidFileKeyException($key, "File key cannot be empty");
        }

        //@todo: file key could be validated against a regular expression?
        return true;
    }

}
