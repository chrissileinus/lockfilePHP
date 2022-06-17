<?php
/*
 * Created on Wed Nov 03 2021
 *
 * Copyright (c) 2021 Christian Backus (Chrissileinus)
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Chrissileinus\LockFile;

class Handle
{
  private static $file;

  public static function acquire($name = null)
  {
    // Onetime init!
    if (is_string($name) && !self::$file) {
      self::$file = "/run/{$name}.pid";
    }

    if (file_exists(self::$file)) {
      $pid = file_get_contents(self::$file);
      $running = posix_kill($pid, 0);
      if ($running) {
        throw new LockException(
          "Could not acquire lock on '" . self::$file . "'. pid: {$pid} is running"
        );
      } else {
        unlink(self::$file);
      }
    }
    $handle = fopen(self::$file, 'x');
    if (!$handle) {
      throw new FileException(
        "Could not open or create lock file '" . self::$file . "'"
      );
    }
    $pid = getmypid();
    fwrite($handle, $pid);
    fclose($handle);
    register_shutdown_function(function () {
      self::release();
    });
    return true;
  }

  public static function release()
  {
    if (file_exists(self::$file)) {
      unlink(self::$file);
    }
  }
}
