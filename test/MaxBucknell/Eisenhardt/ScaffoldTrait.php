<?php declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

trait ScaffoldTrait {
    public function getTempDir(): string
    {
        return \sys_get_temp_dir() . '/eisenhardt/' . base64_encode((string)mt_rand());
    }

    public function getInitialisedProject(): string
    {
        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);
        \chdir($dir);

        \mkdir('.eisenhardt');

        return $dir;
    }
}