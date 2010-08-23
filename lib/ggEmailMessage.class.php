<?php

/**
 * Description of ggEmailMessage
 *
 * @author georg
 */
class ggEmailMessage extends Swift_Message {

    public function setBodyFromTemplate(sfController $controller, $module, $name, array $params, $layout = null, $contentType = null, $charset = null) {
        $view = $this->getView($controller, $module, $name, $params, $layout);
        try {
            $this->setBody($view->render(), $contentType, $charset);
            return $this;
        } catch (Exception $e) {
//                echo $e->getMessage() . "\n";
        }

        // there was an error with the template (we haven't returned), let's try if we find html or text templates
        $bodies = array();
        $view = $this->getView($controller, $module, $name . '_text', $params, $layout);
        $view->setDecorator(false);
        try {
            $bodies['text'] = $view->render();
        } catch (Exception $e) {
//                echo $e->getMessage() . "\n";
        }
        $view = $this->getView($controller, $module, $name . '_html', $params, $layout);
        try {
            $bodies['html'] = $view->render();
        } catch (Exception $e) {
//                echo $e->getMessage() . "\n";
        }

        if (count($bodies) == 0) {
            throw new Exception("No usable template found for email '$module'/'$name'");
        }
        if (count($bodies) == 1) {
            $body = reset($bodies);
            $this->setBody($body, (key($bodies) == 'html' ? 'text/html' : 'text/plain'), $charset);
        } else {
            $this->addPart($bodies['html'], 'text/html', $charset);
            $this->addPart($bodies['text'], 'text/plain', $charset);
        }
        return $this;
    }

    /**
     *
     * @return sfView
     */
    protected function getView(sfController $controller, $module, $name, array $params, $layout) {
        $view = $controller->getView($module, $name, '');
        if (!$view->getDirectory()) {
            $view->setDirectory(sfConfig::get('app_ggEmail_templateDir', sfConfig::get('sf_lib_dir') . '/email/modules') . '/' . $module . '/templates');
        }
        $view->execute();
        if (isset($layout)) {
            $view->setDecoratorTemplate($layout);
            if (!is_readable($view->getDecoratorDirectory() . '/' . $view->getDecoratorTemplate())) {
                $view->setDecoratorTemplate(sfConfig::get('app_ggEmail_layoutDir', sfConfig::get('sf_lib_dir') . '/email/templates') . '/' . $layout);
            }
            if (!is_readable($view->getDecoratorDirectory() . '/' . $view->getDecoratorTemplate())) {
                throw new Exception("No usable layout found for email '$module'/'$name': layout '$layout'");
            }
        } elseif (!is_readable($view->getDecoratorDirectory() . '/' . $view->getDecoratorTemplate())) {
            $view->setDecoratorDirectory(sfConfig::get('app_ggEmail_layoutDir', sfConfig::get('sf_lib_dir') . '/email/templates'));
        }
        $view->getAttributeHolder()->add($params);
        return $view;
    }

}

