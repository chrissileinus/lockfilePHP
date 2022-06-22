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
  private static $path;

  public static function acquire($name = null, $path = "/tmp")
  {
    if (is_string($path) && !self::$path) {
      self::$path = realpath($path);
    }

    // Onetime init!
    if (is_string($name) && !self::$file) {
      self::$file = $path . DIRECTORY_SEPARATOR . "{$name}.pid";
    }

    if (file_exists(self::$file)) {
      $pid = file_get_contents(self::$file);

      // Check if running with PID
      $filename = basename($_SERVER["SCRIPT_FILENAME"]);
      if (preg_match("/.+ (.+)/", `ps -p {$pid} -o comm,user | grep {$filename}`, $matches)) throw new LockException(
        "Could not acquire lock on '" . self::$file . "'. pid: {$pid} is running in user '{$matches[1]}'"
      );

      // Try to delete unused lock file
      if (unlink(self::$file) === false) throw new FileException(
        "Could not delete unused lock file '" . self::$file . "'"
      );
    }

    // Try to open or create lock file
    $handle = fopen(self::$file, 'x');
    if (!$handle) throw new FileException(
      "Could not open or create lock file '" . self::$file . "'"
    );

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
