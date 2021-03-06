<?php
App::uses('AppHelper', 'View/Helper');
App::uses('YakEmoji', 'Yak.Lib');
App::uses('YakLoader', 'Yak.Lib');
spl_autoload_register(array('YakLoader', 'loadClass'));

class YakHelper extends AppHelper {

    public $helpers = array('Html');
    public $mobileCss = false;

    /**
     * __construct
     *
     * @return
     */
    public function __construct(View $View, $settings = array()) {
        parent::__construct($View, $settings);
        YakLoader::setIncludePath();
        $this->emoji = YakEmoji::getStaticInstance();
        $this->emoji->setImageUrl($this->url('/') . 'yak/img/');
    }

    /**
     * __call
     *
     * @param $methodName, $args
     * @return
     */
    public function __call($methodName, $args){
        return call_user_func_array(array($this->emoji, $methodName), $args);
    }

    /**
     * charset
     *
     * @return
     */
    public function charset(){
        if ($this->emoji->isSjisCarrier()) {
            return $this->Html->charset('Shift_JIS');
        } else {
            return $this->Html->charset('UTF-8');
        }
    }

    /**
     * afterLayout
     *
     * @return
     */
    public function afterLayout($layoutFile){
        parent::afterLayout($layoutFile);
        if (isset($this->_View->output)) {
            // hankakuKana
            if (!empty($this->settings['hankakuKana']) && $this->emoji->isMobile()) {
                $this->_View->output = mb_convert_kana($this->_View->output, 'k', 'UTF-8');
            }
            if (empty($this->request->data) || $this->emoji->isMobile()) {
                $this->_View->output = $this->emoji->filter($this->_View->output, array('DecToUtf8', 'HexToUtf8', 'output'));
            } else {
                // for PC form
                $outputArray = preg_split('/(value ?= ?[\'"][^"]+[\'"])|(<textarea[^>]+>[^<]+<\/textarea>)/',  $this->_View->output, null, PREG_SPLIT_DELIM_CAPTURE);
                $output = '';
                foreach ($outputArray as $key => $value) {
                    if (!preg_match('/value ?= ?[\'"]([^"]+)[\'"]|<textarea[^>]+>([^<]+)<\/textarea>/',  $value) && strlen($value) > 0) {
                        $output .= $this->emoji->filter($value, array('DecToUtf8', 'HexToUtf8', 'output'));
                    } else {
                        $output .= $value;
                    }
                }
                $this->_View->output = $output;
            }
            // mobileCss
            if (!empty($this->settings['mobileCss']) && $this->emoji->isMobile()) {
                $mobileCssBaseDir = Configure::read('Yak.mobileCssBaseDir');
                $this->_View->output = HTML_CSS_Mobile::getInstance()->setBaseDir($mobileCssBaseDir)->apply($this->_View->output);
            }
        }
    }

}
