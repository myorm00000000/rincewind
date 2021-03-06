<?php

/**
 * The DefaultDispatcher
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dispatcher
 */
/**
 * Include the interface
 */
require_interface('Dispatcher');

/**
 * Including exceptions
 */
require_class('Model', 'Renderer');

/**
 * The DefaultDispatcher works with the controllerFactory.
 *
 * It tries to instantiate the appropriate controller, and to call the right action on it.
 * If it works, the dispatcher is very happy about it.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dispatcher
 */
class DefaultDispatcher implements Dispatcher {

  /**
   * @var type
   */
  private $controllerFactory;

  /**
   * @var Renderers
   */
  private $renderers;

  /**
   * @var Theme
   */
  private $theme;

  /**
   * @var string
   */
  private $defaultControllerName;

  /**
   * @var Sanitizer
   */
  private $controllerFromUrlSanitizer;
  /**
   * @var Sanitizer
   */
  private $actionFromUrlSanitizer;

  /**
   * @var NotificationCenter
   */
  private $notificationCenter;

  /**
   * @param ControllerFactory $controllerFactory
   * @param Renderer $renderers
   * @param Theme $theme
   * @param Sanitizer $controllerFromUrlSanitizer
   * @param Sanitizer $actionFromUrlSanitizer
   * @param NotificationCenter $notificationCenter
   * @param string $defaultControllerName
   */
  public function __construct(ControllerFactory $controllerFactory, Renderers $renderers, Theme $theme, Sanitizer $controllerFromUrlSanitizer, Sanitizer $actionFromUrlSanitizer, NotificationCenter $notificationCenter, $defaultControllerName = 'Home') {
    $this->controllerFactory = $controllerFactory;
    $this->renderers = $renderers;
    $this->theme = $theme;
    $this->defaultControllerName = $defaultControllerName;
    $this->controllerFromUrlSanitizer = $controllerFromUrlSanitizer;
    $this->actionFromUrlSanitizer = $actionFromUrlSanitizer;
    $this->notificationCenter = $notificationCenter;
  }

  /**
   * Returns the accepted content types from accept header.
   *
   * Tries to return an array sorted by this rules:
   *
   * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
   *
   * @param string $acceptHeader
   * @return array
   */
  public function getAcceptContentTypes($acceptHeader) {
    $groupedContentTypes = array();

    $headerContentTypes = explode(',', $acceptHeader);
    foreach ($headerContentTypes as $ct) {
      $ct = explode(';', $ct);

      $contentType = trim(array_shift($ct));

      $quality = 100;

      foreach ($ct as $qValue) {
        $qValue = trim($qValue);
        if (substr($qValue, 0, 2) === 'q=') {
          // Is really the qvalue not the extension
          $quality = (int) (substr($qValue, 2) * 100);
        }
      }

      if (!array_key_exists($quality, $groupedContentTypes)) {
        $groupedContentTypes[$quality] = array($contentType);
      }
      else {
        // TODO: a broader content type should not come before a more specific one.
        array_unshift($groupedContentTypes[$quality], $contentType);
      }
    }
    krsort($groupedContentTypes);

    $contentTypes = array();
    foreach ($groupedContentTypes as $contentTypesGroup) {
      foreach ($contentTypesGroup as $contentType) {
        $contentTypes[] = $contentType;
      }
    }
    return $contentTypes;
  }

  /**
   * Parses the url, and dispatches to the appropriate controller.
   * @param bool $skipControllerInitialization
   */
  public function dispatch($skipControllerInitialization = false) {
    Profile::start('Dispatcher', 'Dispatching');

    $contentTypes = array();

    try {

      $controllerName = isset($_GET['controller']) ? trim($_GET['controller']) : $this->defaultControllerName;

      $controllerName = $this->controllerFromUrlSanitizer->sanitize($controllerName);

      $invalidControllerName = false;
      try {
        $controller = $this->controllerFactory->get($controllerName);
      }
      catch (ControllerFactoryException $e) {
        // Not failing just yet, so the model gets initialized.
        $invalidControllerName = true;
        $controller = $this->controllerFactory->get($this->defaultControllerName);
      }

      $model = new Model();
      $controller->setModel($model);
      $controller->initModel();

      $contentTypes = $this->getAcceptContentTypes($_SERVER['HTTP_ACCEPT']);

      try {

        if ($invalidControllerName) {
          ErrorCode::notFound();
        }
        try {
          $errorDuringRender = null;
          $errorCode = null;

          // Try to dispatch to the actual action.
          $actionParameters = explode('/', isset($_GET['action']) ? $_GET['action'] : 'index');

          $action = $actionParameters[0];
          array_shift($actionParameters);

          if ($action{0} === '_') {
            throw new ErrorCode(ErrorCode::NOT_FOUND, 'Tried to access action with underscore.');
          }

          $action = $this->actionFromUrlSanitizer->sanitize($action);

          try {
            // Check if the action is valid
            $reflectionClass = new ReflectionClass($controller);

            $actionMethod = $reflectionClass->getMethod($action);

            if ($action !== 'index' && (method_exists('Controller', $action) || !$actionMethod->isPublic() || ($actionMethod->class !== get_class($controller)))) throw new DispatcherException();
          }
          catch (Exception $e) {
            throw new ErrorCode(ErrorCode::NOT_FOUND, 'Tried to access invalid action.');
          }

          $controller->setAction($action);

          $parameters = array();
          $stringParameters = array();

          $i = 0;
          foreach ($actionMethod->getParameters() as $parameter) {
            $actionParameter = isset($actionParameters[$i]) ? $actionParameters[$i] : null;

            if ($actionParameter === null) {
              if (!$parameter->isDefaultValueAvailable()) {
                throw new ErrorCode(ErrorCode::BAD_REQUEST, 'Not all parameters supplied.');
              }
              // Well: there is no more additional query, and apparently the rest of the parameters are optional, so continue.
              continue;
            }
            if (($parameterTypeClass = $parameter->getClass()) != false) {
              if (!$parameterTypeClass->isSubclassOf('RW_Type')) {
                throw new ErrorCode(ErrorCode::BAD_REQUEST, 'Invalid parameter type.');
              }
              $parameterTypeClassName = $parameterTypeClass->getName();
              $parameters[] = new $parameterTypeClassName($actionParameter);
            }
            else {
              $parameters[] = $actionParameter;
            }
            $stringParameters[] = $actionParameter;
            $i++;
          }
          $controller->setActionParameters($stringParameters);

          if (!$skipControllerInitialization) {
            $controller->initialize();
          }

          // This actually calls the apropriate action.
          call_user_func_array(array($controller, $action), $parameters);

          $controller->extendModel();

          try {
            $this->renderers->render($controller->getViewName(), $model, $this->notificationCenter, $this->theme->getTemplatesPath(), $contentTypes, $controller);
          }
          catch (Exception $e) {
            throw new ErrorCode(ErrorCode::INTERNAL_SERVER_ERROR, 'Error during render: ' . $e->getMessage());
          }
        }
        catch (ErrorMessageException $e) {
          $errorDuringRender = true;
          $this->notificationCenter->addError($e->getMessage());
        }
        catch (ErrorCode $e) {
          throw $e;
        }
        catch (Exception $e) {
          $additionalInfo = array();
          $additionalInfo['controllerName'] = $controllerName;
          if (isset($action)) $additionalInfo['action'] = $action;
          $additionalInfo['exceptionThrown'] = get_class($e);
          $additionalInfo['error'] = $e->getMessage();
          Log::warning($e->getMessage(), 'Dispatcher', $additionalInfo);
          throw new ErrorCode(ErrorCode::INTERNAL_SERVER_ERROR);
        }
      }
      catch (ErrorCode $e) {
        // All other exceptions have already been caught.
        $errorDuringRender = true;
        $errorCode = $e->getCode();
        $e->writeHttpHeader();

        if ($e->getMessage()) {
          Log::debug($e->getMessage(), 'Dispatcher');
        }
      }

      if ($errorDuringRender) {
        $this->renderers->renderError($errorCode, $model, $this->notificationCenter, $this->theme->getTemplatesPath(), $contentTypes);
      }
    }
    catch (Exception $e) {
      try {
        Log::fatal('There has been a fatal error dispatching.', 'Dispatcher', array('error' => $e->getMessage()));
        $this->renderers->renderFatalError($this->notificationCenter, $this->theme->getTemplatesPath(), $contentTypes);
      }
      catch (Exception $e) {
        die('<h1 class="error">Fatal error...</h1>');
      }
    }
    Profile::stop();
  }

}
