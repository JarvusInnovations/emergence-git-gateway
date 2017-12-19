<?php

namespace Emergence\Git;

use Gitonomy\Git\Repository;


class TreeRef implements HashableInterface
{
    use HashableTrait;

    public function getObjectType()
    {
        return 'tree';
    }

    public function write()
    {
        $this->dirty = false;

        return $this->writtenHash;
    }

    public function read($hash)
    {
        $this->writtenHash = $hash;

        return true;
    }


    public static function fromRefPath(Repository $repository, $path)
    {
        return new static($repository, trim($repository->run('rev-parse', [$path])));
    }
}