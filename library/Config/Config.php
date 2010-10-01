<?php

/**
 * This file contains the Config class definition.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Config
 **/

/**
 * Loading all exceptions
 */
include dirname(__FILE__) . '/ConfigExceptions.php';

/**
 * This is the Config class. It implements basic functionality such as loading a configuration, and providing getters.
 * It also supports sections.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Config
 */
abstract class Config {

  
  /**
   * The cached config array
   * @var array
   */
  protected $config;


  /**
   * Whether sections should be used or not.
   *
   * @var bool
   */
  protected $useSections = true;

  /**
   * The default section. If this is set, this section will be used, if no section is specified when getting a variable.
   *
   * @var string
   */
  protected $defaultSection = 'general';



  /**
   * Get a configuration value.
   *
   * @param string $variable
   * @param string $section If null, $this->defaultSection will be used if set.
   */
  public function get($variable, $section = null) {
    $this->load();

    $variable = $this->sanitizeToken($variable);


    $section = $section ? $section : $this->defaultSection;
    $section = $this->sanitizeToken($section);

    if ($this->useSections) {
      if (!$section) throw new ConfigException('No section set.');

      if (!isset($this->config[$section])) throw new ConfigException("Section '$section' does not exist.");
      if (!array_key_exists($variable, $this->config[$section])) throw new ConfigException("Variable '$section.$variable' does not exist.");

      return $this->config[$section][$variable];
    }
    else {
      if (!array_key_exists($variable, $this->config)) throw new ConfigException("Variable '$variable' does not exist.");

      return $this->config[$variable];
    }
    
  }


  /**
   * Implement this to load your configuration.
   * It should only load the configuration once! If you want to reload it, use reload() instead.
   */
  abstract public function load();


  /**
   * Sets $config to null;
   * @uses $config
   */
  public function clear() {
    $this->config = null;
  }

  /**
   * Use this to reload your configuration
   */
  public function reload() {
    $this->clear();
    $this->load();
  }

  /**
   * @param bool $oneDimensional If set to true, and the config uses sections, the sections will be used to prefix the names.
   * @return array
   */
  public function getArray($oneDimensional = false) {
    $this->load();
    if ($this->useSections && $oneDimensional) {
      $return = array();
      foreach ($this->config as $section=>$variables) {
        foreach ($variables as $variable=>$content) {
          $return[$section . '.' . $variable] = $content;
        }
      }
      return $return;
    }
    else {
      return $this->config;
    }
  }


  /**
   * @param string $section
   * @see $defaultSection
   */
  public function setDefaultSection($section) {
    if (!$this->useSections) throw new ConfigException("Sections have been disabled for this config object.");
    $this->defaultSection = $section;
  }


  /**
   * Sanitizes a token by removing special chars, and coverting spaces to underscores
   *
   * @param $token
   */ 
  public function sanitizeToken($token) {
    return strtolower(str_replace(array('?', '!', '.'), '', str_replace(array(':', ' '), '_', $token)));
  }

}



