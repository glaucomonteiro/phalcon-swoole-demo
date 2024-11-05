<?php

namespace Lib\Swoole\File;

use Lib\Logger\Logger;
use OpenSwoole\Http\Server;

class Watcher
{
    protected static bool $running = false;

    protected array $ignore_dirs =  [
        '/app/logs',
        '/app/features',
        '/app/docker',
        '/app/Test',
        '/app/TestData',
        '/app/composer-cache',
        '/app/config',
        '/app/vendor',
        '/app/Migrations'
    ];

    protected array $ignore_extensions = [
        'swp'
    ];

    protected string $mode = 'shutdown';

    protected array $original_load = [];

    public function watch_files(Server $server, $start_dir = '/main/app')
    {
        if (self::$running)
            return;
        self::$running = true;
        $logger = Logger::get_instance();
        $fd = inotify_init();
        $watchers = $this->inotify_add_watch_recursive($fd, $start_dir, IN_MODIFY | IN_MOVED_FROM | IN_CREATE | IN_DELETE | IN_ISDIR);
        $logger->info('Monitoring file changes');
        $restart = false;
        while (!$restart) {
            // Read events - restart?
            sleep(1);
            $events = inotify_read($fd);
            if (!$events)
                continue;
            $reload = true;
            foreach ($events as $file) {
                $dir = '';
                $extension = explode('.', $file['name']);
                if ($extension) {
                    $extension = array_pop($extension);
                    if (in_array($extension, $this->ignore_extensions))
                        continue;
                }
                foreach ($watchers as $path => $id) {
                    if ($id === $file['wd']) {
                        $dir = $path;
                        break;
                    }
                }
                $file_name = $dir . '/' . $file['name'];
                $logger->info($file_name . ' changed');
                if (in_array($file_name, $this->original_load)) {
                    $reload = false;
                    $restart = true;
                    break;
                }
            }
            if ($reload) {
                $server->reload();
            }
        }
        self::$running = false;
        if ($this->mode == 'kill')
            exec('kill -9 ' . $server->getMasterPid());
        else
            $server->shutdown();
    }

    public function mode($mode)
    {
        $this->mode = $mode;
    }

    public function ignore_dirs($list)
    {
        $this->ignore_dirs = $list;
    }

    public function ignore_extensions($list)
    {
        $this->ignore_extensions = $list;
    }

    public function loaded_files($list)
    {
        $this->original_load = $list;
    }

    protected function inotify_add_watch_recursive($inotify, $path, $mask)
    {
        $ids = [
            $path => inotify_add_watch($inotify, $path, $mask)
        ];

        if (is_dir($path)) {
            foreach (glob($path . '/*', GLOB_ONLYDIR) as $subdir) {
                if (!in_array($subdir, $this->ignore_dirs)) {
                    $recursive = $this->inotify_add_watch_recursive($inotify, $subdir, $mask);
                    $ids = array_merge($ids, $recursive);
                }
            }
        }
        return $ids;
    }
}
