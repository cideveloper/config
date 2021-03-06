<?php
/**
 * @author Jesus A. Domingo <jesus.domingo@gmail.com>
 * @license MIT <http://noodlehaus.mit-license.org>
 */
namespace Noodlehaus {

  class Config {

    private $data = null;

    /**
     * Alternative way of loading a config instance.
     *
     * @param string $path config file to load.
     *
     * @return Config config instance loaded
     */
    public static function load($path) {
      return new Config($path);
    }

    /**
     * Constructor. Loads a supported configuration file
     * format.
     *
     * @param string $path config file path to load
     *
     * @return Config config instance loaded
     */
    public function __construct($path) {

      $info = pathinfo($path);

      // php file
      if (preg_match('@^php$@i', $info['extension'])) {
        $temp = require $path;
        if (is_callable($temp))
          $temp = call_user_func($temp);
        if (!$temp || !is_array($temp))
          throw new \Exception('PHP file does not return an array');
        $this->data = $temp;
        return;
      }

      // ini file
      if (preg_match('@^ini$@i', $info['extension'])) {
        $this->data = @parse_ini_file($path, true);
        if (!$this->data)
          throw new \Exception('INI parse error');
        return;
      }

      // json file
      if (preg_match('@^json$@i', $info['extension'])) {
        $this->data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE)
          throw new \Exception('JSON parse error');
        return;
      }

      // unsupported type
      throw new \Exception('Unsupported configuration format');
    }

    /**
     * Gets a configuration setting using a simple or nested key.
     * Nested keys are similar to JSON paths that use the dot
     * dot notation.
     *
     * @param string $path config setting to fetch
     * @param mixed $default default value to use
     *
     * @return mixed config value, or null/default value if not found.
     */
    public function get($path, $default = null) {

      $segs = explode('.', $path);
      $root = $this->data;

      // nested case
      foreach ($segs as $part) {
        if (isset($root[$part])) {
          $root = $root[$part];
          continue;
        } else {
          $root = $default;
          break;
        }
      }

      // whatever we have is what we needed
      return $root;
    }

    /**
     * Function for setting configuration values, using
     * either simple or nested keys.
     *
     * @param string $path config key to set
     * @param mixed $value value to use for the key
     *
     * @return void
     */
    public function set($path, $value) {

      if ($value === null)
        return;

      $segs = explode('.', $path);
      $root = &$this->data;

      // we create the necessary nesting for configs
      while ($part = array_shift($segs)) {
        if (!isset($root[$part])) {
          if (count($segs))
            $root[$part] = array();
          else
            $root[$part] = $value;
        }
        $root = &$root[$part];
      }
    }
  }

}
?>
