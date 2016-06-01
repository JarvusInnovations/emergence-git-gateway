<?php

namespace Emergence\Git;

use Gitonomy\Git\Repository;


class Tree
{
    protected $repository;
    protected $root;
    protected $dirty;
    protected $writtenHash;


    function __construct(Repository $repository = null, $hash = null)
    {
        $this->repository = $repository;
        $this->root = [];
        $this->dirty = !$repository || !$hash || !$this->read($hash);
        $this->writtenHash = $this->dirty ? null : $hash;
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


    public function getRoot()
    {
        return $this->root;
    }


    public function getDirty()
    {
        return $this->dirty;
    }


    public function getWrittenHash()
    {
        return $this->writtenHash;
    }


    public function getHash()
    {
        return $this->dirty ? null : $this->writtenHash;
    }


    public function setContent($path, $content)
    {
        $node =& $this->getNodeRef($path);
        $node = $content;
    }


    public function deleteContent($path)
    {
        $node =& $this->getNodeRef($path);
        $node = null;
    }


    public function hasContent($path)
    {
        return (boolean)$this->getNodeRef($path);
    }


    public function write()
    {
        $this->writtenHash = $this->writeTree($this->root);
        $this->dirty = false;
    }


    public function read($hash)
    {
        if (!$this->repository) {
            throw new \Exception('must set repository before reading');
        }
        // open git-mktree process
        $pipes = [];
        $process = proc_open(
            exec('which git') . ' ls-tree -r ' . $hash,
            [
        		1 => ['pipe', 'wb'], // STDOUT
        		2 => ['pipe', 'w']  // STDERR
            ],
            $pipes,
            $this->repository->getGitDir()
        );


        // check for error on STDERR and turn into exception
        stream_set_blocking($pipes[2], false);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if ($error) {
            $exitCode = proc_close($process);
            throw new \Exception("git exited with code $exitCode: $error");
        }


        // read tree hash from output
        while ($line = fgets($pipes[1])) {
            if (!preg_match('/^(?<mode>[^ ]+) (?<type>[^ ]+) (?<hash>[^\t]+)\t(?<path>.*)/', $line, $matches)) {
                throw new \Exception("invalid tree line: $line");
            }

            $this->setContent($matches['path'], $matches['hash']);
        }

        fclose($pipes[1]);


        // clean up
        $exitCode = proc_close($process);


        return $exitCode == 0;
    }


    public function dump($exit = true, $title = null)
    {
        \Debug::dumpVar([
            'repository' => ($repository = $this->getRepository()) ? $repository->getGitDir() : null,
            'hash' => $this->getHash(),
            'writtenHash' => $this->getWrittenHash(),
            'dirty' => $this->getDirty(),
            'root' => $this->getRoot()
        ], $exit, $title);
    }


    protected function &getNodeRef($path)
    {
        $path = explode('/', $path);
        $tree = &$this->root;

        while (($name = array_shift($path)) && count($path)) {
            $tree = &$tree[$name];
        }

        return $tree[$name];
    }


    protected function writeTree(&$tree)
    {
        if (!$this->repository) {
            throw new \Exception('must set repository before writing');
        }

        // build tree file content
        $treeContent = '';
        foreach ($tree AS $name => &$content) {
            if (is_string($content)) {
                if ($content[0] == '/') {
                    $content = trim($this->repository->run('hash-object', ['-w', $content]));
                }

                $treeContent .= '100644 blob '.$content."\t$name\n";
            } elseif(is_array($content)) {
                $treeContent .= '040000 tree '.$this->writeTree($content)."\t$name\n";
            }
        }


        // open git-mktree process
        $pipes = [];
        $process = proc_open(
            exec('which git') . ' mktree',
            [
        		0 => ['pipe', 'rb'], // STDIN
        		1 => ['pipe', 'wb'], // STDOUT
        		2 => ['pipe', 'w']  // STDERR
            ],
            $pipes,
            $this->repository->getGitDir()
        );


        // write tree content to mktree's STDIN
        fwrite($pipes[0], $treeContent);
        fclose($pipes[0]);


        // check for error on STDERR and turn into exception
        stream_set_blocking($pipes[2], false);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if ($error) {
            $exitCode = proc_close($process);
            throw new \Exception("git exited with code $exitCode: $error");
        }


        // read tree hash from output
        $hash = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);


        // clean up
        proc_close($process);


        return $hash;
    }
}