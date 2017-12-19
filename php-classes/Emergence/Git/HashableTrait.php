<?php

namespace Emergence\Git;

use Gitonomy\Git\Repository;


trait HashableTrait
{
    abstract public function read($hash);
    abstract public function write();


    private $repository;

    protected $dirty;
    protected $writtenHash;


    // magic methods and property accessors
    function __construct(Repository $repository = null, $hash = null)
    {
        $this->repository = $repository;
        $this->dirty = !$repository || !$hash || !$this->read($hash);
        $this->writtenHash = $this->dirty ? null : $hash;
    }

    function __toString()
    {
        return sprintf(
            '%s(%s, %s%s)',
            static::class,
            $this->repository->getGitDir(),
            $this->writtenHash,
            $this->dirty ? '*' : ''
        );
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
        $this->dirty = true;
        $this->writtenHash = null;
    }

    public function getDirty()
    {
        return $this->dirty;
    }

    public function getReadHash()
    {
        return $this->writtenHash;
    }

    public function getWrittenHash()
    {
        return $this->dirty ? null : $this->writtenHash;
    }

    public function getHash()
    {
        return $this->write();
    }
}